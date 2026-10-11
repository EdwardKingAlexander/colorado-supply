<?php

namespace App\Services\NsnSourcing;

use App\Models\MilSpecPart;
use App\Models\NsnCageVerification;
use App\Models\NsnCatalogItem;
use App\Models\NsnReference;
use App\Models\NsnSalesContact;
use App\Models\NsnSourcingCase;
use App\Models\NsnSourcingFile;
use App\Models\NsnSourcingInquiry;
use App\Models\NsnSourcingResponse;
use App\Models\PubLogImport;
use App\Models\Supplier;
use App\Models\VendorContact;
use App\Services\NsnLookupService;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Every write to a sourcing case goes through here so the Filament screens
 * and tests share one set of rules: ownership comes from SourcingContext,
 * evidence is append-only, and production state changes only from a
 * recorded response. Nothing in this class sends mail.
 */
class NsnSourcingService
{
    public const FILE_DISK = 'local';

    public const MAX_FILE_KB = 10240;

    /** Allowed extensions and the content types accepted for each. */
    public const FILE_TYPES = [
        'pdf' => ['application/pdf'],
        'png' => ['image/png'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'webp' => ['image/webp'],
        'eml' => ['message/rfc822', 'text/plain'],
        'msg' => ['application/vnd.ms-outlook', 'application/CDFV2', 'application/x-ole-storage', 'application/octet-stream'],
    ];

    /**
     * Open (or reuse) the company's case for one catalog reference row from
     * an activated release.
     */
    public function openFromReference(int $referenceId): NsnSourcingCase
    {
        Gate::authorize('create', NsnSourcingCase::class);
        $reference = NsnReference::find($referenceId) ?? abort(404);
        $import = PubLogImport::whereKey($reference->import_id)->where('status', 'ready')->whereNotNull('activated_at')->first() ?? abort(404);
        $item = NsnCatalogItem::where('import_id', $import->id)->where('niin', $reference->niin)->first() ?? abort(404);
        $companyId = SourcingContext::companyId();
        $key = NsnSourcingCase::openKey($reference->niin, $reference->part_search_key, $reference->cage_code);

        $existing = NsnSourcingCase::where('company_id', $companyId)->where('open_key', $key)->first();
        if ($existing) {
            return $existing;
        }

        [$actorType, $actorId] = SourcingContext::actorKey();
        $formatted = NsnLookupService::formatNsn($item->nsn);
        try {
            return NsnSourcingCase::create([
                'company_id' => $companyId,
                'pub_log_import_id' => $import->id,
                'nsn_reference_id' => $reference->id,
                'release_date' => $import->release_date,
                'nsn' => $item->nsn,
                'niin' => $reference->niin,
                'part_number' => $reference->part_number,
                'part_search_key' => $reference->part_search_key,
                'cage_code' => $reference->cage_code,
                'item_name' => $item->item_name,
                'mil_spec_part_id' => MilSpecPart::whereIn('nsn', [$item->nsn, $formatted])->value('id'),
                'status' => 'open',
                'production_state' => 'unknown',
                'opened_by_type' => $actorType,
                'opened_by_id' => $actorId,
                'open_key' => $key,
            ]);
        } catch (QueryException $e) {
            // Another request opened the same case first.
            return NsnSourcingCase::where('company_id', $companyId)->where('open_key', $key)->first() ?? throw $e;
        }
    }

    public function recordVerification(NsnSourcingCase $case, array $data): NsnCageVerification
    {
        $this->authorizeOpen($case);
        $data = Validator::make($data, [
            'outcome' => ['required', Rule::in(array_keys(NsnCageVerification::OUTCOMES))],
            'verified_name' => ['nullable', 'string', 'max:255', 'required_if:outcome,verified'],
            'verified_address' => ['nullable', 'string', 'max:2000'],
            'verified_status' => ['nullable', 'string', 'max:255'],
            'source_url' => ['required', 'url:http,https', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'verified_on' => ['required', 'date', 'before_or_equal:today'],
        ])->validate();
        [$actorType, $actorId] = SourcingContext::actorKey();

        return $case->verifications()->create([...$data, 'cage_code' => $case->cage_code, 'actor_type' => $actorType, 'actor_id' => $actorId]);
    }

    public function addContact(NsnSourcingCase $case, array $data): NsnSalesContact
    {
        $this->authorizeOpen($case);
        $data = Validator::make($data, [
            'company_name' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url:http,https', 'max:2048', 'required_without_all:email,phone'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'stated_role' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'source_url' => ['required', 'url:http,https', 'max:2048'],
            'source_on' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'vendor_contact_id' => ['nullable', 'integer', Rule::exists(VendorContact::class, 'id')],
            'supplier_id' => ['nullable', 'integer', Rule::exists(Supplier::class, 'id')],
        ], [
            'website.required_without_all' => 'Enter at least a website, email or phone number.',
        ])->validate();
        [$actorType, $actorId] = SourcingContext::actorKey();

        return $case->contacts()->create([...$data, 'actor_type' => $actorType, 'actor_id' => $actorId]);
    }

    public function draftInquiry(NsnSourcingCase $case, array $data): NsnSourcingInquiry
    {
        $this->authorizeOpen($case);
        $data = Validator::make($data, [
            'nsn_sales_contact_id' => ['nullable', 'integer', Rule::exists(NsnSalesContact::class, 'id')->where('nsn_sourcing_case_id', $case->id)],
            'channel' => ['required', Rule::in(array_keys(NsnSourcingInquiry::CHANNELS))],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
        ])->validate();
        [$actorType, $actorId] = SourcingContext::actorKey();

        return $case->inquiries()->create([...$data, 'actor_type' => $actorType, 'actor_id' => $actorId]);
    }

    /**
     * Records that the operator actually sent or made contact. Separate from
     * drafting so a draft is never mistaken for outreach.
     */
    public function markContacted(NsnSourcingInquiry $inquiry, array $data): NsnSourcingInquiry
    {
        $case = $inquiry->sourcingCase;
        $this->authorizeOpen($case);
        $data = Validator::make($data, [
            'contacted_on' => ['required', 'date', 'before_or_equal:today'],
            'follow_up_on' => ['nullable', 'date', 'after_or_equal:contacted_on'],
        ])->validate();
        abort_if($inquiry->isContacted(), 422, 'This inquiry is already marked as contacted.');
        [$actorType, $actorId] = SourcingContext::actorKey();

        DB::transaction(function () use ($inquiry, $case, $data, $actorType, $actorId) {
            $inquiry->update(['contacted_at' => Carbon::parse($data['contacted_on'])->startOfDay(), 'contacted_by_type' => $actorType, 'contacted_by_id' => $actorId]);
            $case->update([
                'status' => $case->status === 'open' ? 'awaiting_response' : $case->status,
                'follow_up_on' => $data['follow_up_on'] ?? $case->follow_up_on,
            ]);
        });

        return $inquiry->refresh();
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function recordResponse(NsnSourcingCase $case, array $data, array $files = []): NsnSourcingResponse
    {
        $this->authorizeOpen($case);
        $data = Validator::make([...$data, 'files' => $files], [
            'nsn_sourcing_inquiry_id' => ['nullable', 'integer', Rule::exists(NsnSourcingInquiry::class, 'id')->where('nsn_sourcing_case_id', $case->id)],
            'supersedes_response_id' => ['nullable', 'integer', Rule::exists(NsnSourcingResponse::class, 'id')->where('nsn_sourcing_case_id', $case->id)],
            'received_on' => ['required', 'date', 'before_or_equal:today'],
            'channel' => ['required', Rule::in(array_keys(NsnSourcingResponse::CHANNELS))],
            'responder' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:50000'],
            'source_url' => ['nullable', 'url:http,https', 'max:2048'],
            'production_state' => ['required', Rule::in(array_keys(NsnSourcingCase::REPORTED_STATES))],
            'replacement_nsn' => ['nullable', 'regex:/^\d{4}-?\d{2}-?\d{3}-?\d{4}$/'],
            'replacement_part_number' => ['nullable', 'string', 'max:255'],
            'minimum_order' => ['nullable', 'string', 'max:255'],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'sells_direct' => ['required', Rule::in(array_keys(NsnSourcingResponse::SELLS_DIRECT))],
            'distributors' => ['nullable', 'array', 'max:50'],
            'distributors.*.name' => ['required', 'string', 'max:255'],
            'distributors.*.cage_code' => ['nullable', 'regex:/^[A-Za-z0-9]{5}$/'],
            'distributors.*.contact' => ['nullable', 'string', 'max:255'],
            'distributors.*.authorization' => ['nullable', 'string', 'max:2000'],
            'files' => ['array', 'max:10'],
            'files.*' => ['file', 'max:'.self::MAX_FILE_KB, function (string $attribute, mixed $file, \Closure $fail) {
                if (! $file instanceof UploadedFile || ! $this->allowedFile($file)) {
                    $fail('Attach a PDF, PNG, JPG, WebP, .eml or .msg file.');
                }
            }],
        ])->validate();
        unset($data['files']);
        if (! empty($data['replacement_nsn'])) {
            $data['replacement_nsn'] = preg_replace('/\D/', '', $data['replacement_nsn']);
        }
        if (! empty($data['distributors'])) {
            $data['distributors'] = array_values(array_map(fn (array $d) => [
                'name' => $d['name'],
                'cage_code' => isset($d['cage_code']) ? strtoupper($d['cage_code']) : null,
                'contact' => $d['contact'] ?? null,
                'authorization' => $d['authorization'] ?? null,
            ], $data['distributors']));
        }
        [$actorType, $actorId] = SourcingContext::actorKey();

        $stored = [];
        try {
            return DB::transaction(function () use ($case, $data, $files, $actorType, $actorId, &$stored) {
                $response = $case->responses()->create([...$data, 'actor_type' => $actorType, 'actor_id' => $actorId]);
                foreach ($files as $file) {
                    $extension = strtolower($file->getClientOriginalExtension());
                    $path = $file->storeAs("nsn-sourcing/{$case->company_id}/{$case->id}", Str::uuid().'.'.$extension, self::FILE_DISK);
                    $stored[] = $path;
                    NsnSourcingFile::create([
                        'company_id' => $case->company_id,
                        'nsn_sourcing_response_id' => $response->id,
                        'path' => $path,
                        'original_name' => Str::limit(basename($file->getClientOriginalName()), 250, ''),
                        'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                        'size' => $file->getSize(),
                        'sha256' => hash_file('sha256', $file->getRealPath()),
                        'actor_type' => $actorType,
                        'actor_id' => $actorId,
                    ]);
                }
                $this->refreshCaseState($case);

                return $response;
            });
        } catch (Throwable $e) {
            foreach ($stored as $path) {
                Storage::disk(self::FILE_DISK)->delete($path);
            }
            throw $e;
        }
    }

    /**
     * Current answer = responses not superseded by a later one. One stated
     * state wins; differing stated states are "conflicting"; none is "unknown".
     */
    public function refreshCaseState(NsnSourcingCase $case): void
    {
        $responses = NsnSourcingResponse::withoutGlobalScopes()->where('nsn_sourcing_case_id', $case->id)->get(['id', 'supersedes_response_id', 'production_state']);
        $superseded = $responses->pluck('supersedes_response_id')->filter()->all();
        $states = $responses->reject(fn ($r) => in_array($r->id, $superseded, true))
            ->pluck('production_state')->reject(fn ($s) => $s === 'unknown')->unique()->values();

        $case->update([
            'production_state' => match ($states->count()) {
                0 => 'unknown',
                1 => $states->first(),
                default => 'conflicting',
            },
            'status' => $case->status === 'closed' || $responses->isEmpty() ? $case->status : 'answered',
        ]);
    }

    public function setFollowUp(NsnSourcingCase $case, ?string $date): void
    {
        $this->authorizeOpen($case);
        Validator::make(['follow_up_on' => $date], ['follow_up_on' => ['nullable', 'date']])->validate();
        $case->update(['follow_up_on' => $date]);
    }

    public function close(NsnSourcingCase $case, string $reason): void
    {
        $this->authorizeOpen($case);
        Validator::make(['close_reason' => $reason], ['close_reason' => ['required', 'string', 'max:255']])->validate();
        $case->update(['status' => 'closed', 'closed_at' => now(), 'close_reason' => $reason, 'open_key' => null, 'follow_up_on' => null]);
    }

    /**
     * Explicitly adds the case's company as a supplier of the curated part.
     * Only shared facts (name, CAGE, website) reach the Supplier record;
     * private contact details stay on the case.
     */
    public function linkSupplier(NsnSourcingCase $case, array $data): Supplier
    {
        Gate::authorize('update', $case);
        abort_unless($case->status === 'answered' || $case->latestResponse()->exists(), 422, 'Record a response before linking a supplier.');
        $data = Validator::make($data, [
            'supplier_id' => ['nullable', 'integer', Rule::exists(Supplier::class, 'id')],
            'name' => ['required_without:supplier_id', 'nullable', 'string', 'max:255'],
            'cage_code' => ['nullable', 'regex:/^[A-Za-z0-9]{5}$/'],
            'website' => ['nullable', 'url:http,https', 'max:2048'],
            'supplier_part_number' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $part = $case->milSpecPart ?? MilSpecPart::whereIn('nsn', [$case->nsn, $case->formattedNsn()])->first();
        if (! $part) {
            throw ValidationException::withMessages(['supplier_id' => 'Create the curated part for this NSN first (Review as new curated part in PUB LOG Lookup).']);
        }

        return DB::transaction(function () use ($case, $data, $part) {
            $supplier = isset($data['supplier_id'])
                ? Supplier::findOrFail($data['supplier_id'])
                : Supplier::create(['name' => $data['name'], 'cage_code' => isset($data['cage_code']) ? strtoupper($data['cage_code']) : null, 'website' => $data['website'] ?? null]);
            $part->suppliers()->syncWithoutDetaching([$supplier->id => ['supplier_part_number' => $data['supplier_part_number'] ?? $case->part_number]]);
            if (! $case->mil_spec_part_id) {
                $case->update(['mil_spec_part_id' => $part->id]);
            }
            activity('nsn-sourcing')->performedOn($case)->withProperties(['company_id' => $case->company_id, 'supplier_id' => $supplier->id, 'mil_spec_part_id' => $part->id])
                ->tap(fn ($activity) => $activity->company_id = $case->company_id)->log('linked supplier to curated part');

            return $supplier;
        });
    }

    public function allowedFile(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return isset(self::FILE_TYPES[$extension]) && in_array($file->getMimeType(), self::FILE_TYPES[$extension], true);
    }

    private function authorizeOpen(?NsnSourcingCase $case): void
    {
        abort_if($case === null, 404);
        Gate::authorize('update', $case);
        abort_unless($case->isOpen(), 422, 'This case is closed.');
    }
}
