<?php

namespace App\Filament\Resources\MilSpecParts\Pages;

use App\Filament\Resources\MilSpecParts\MilSpecPartResource;
use App\Models\MilSpecPart;
use App\Models\NsnCatalogItem;
use App\Models\NsnReference;
use App\Models\PubLogImport;
use App\Services\NsnLookupService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CreateMilSpecPart extends CreateRecord
{
    protected static string $resource = MilSpecPartResource::class;

    protected string $view = 'filament.resources.mil-spec-parts.pages.create-mil-spec-part';

    protected function fillForm(): void
    {
        parent::fillForm();
        $referenceId = request()->query('catalog_reference');
        if ($referenceId === null) {
            return;
        }
        abort_unless(is_string($referenceId) && ctype_digit($referenceId), 404);
        $reference = NsnReference::find($referenceId) ?? abort(404);
        abort_unless(PubLogImport::whereKey($reference->import_id)->where('status', 'ready')->whereNotNull('activated_at')->exists(), 404);
        $item = NsnCatalogItem::where('import_id', $reference->import_id)->where('niin', $reference->niin)->first() ?? abort(404);
        $this->form->fill([
            'nsn' => NsnLookupService::formatNsn($item->nsn),
            'description' => $item->item_name,
            'manufacturer_part_number' => $reference->part_number,
            'manufacturer_id' => null,
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            $nsn = app(NsnLookupService::class)->normalize('nsn', $data['nsn']);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['data.nsn' => $e->getMessage()]);
        }
        $formatted = NsnLookupService::formatNsn($nsn);
        if (MilSpecPart::whereIn('nsn', [$nsn, $formatted])->exists()) {
            throw ValidationException::withMessages(['data.nsn' => 'This NSN already has a curated part. Open that record to review it; the existing selections have not been changed.']);
        }
        $data['nsn'] = $formatted;

        return $data;
    }
}
