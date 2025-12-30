<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SamOpportunityDocumentResource;
use App\Models\SamOpportunity;
use App\Models\SamOpportunityDocument;
use App\Jobs\ParseSamOpportunityDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SamOpportunityDocumentsController extends Controller
{
    public function index(SamOpportunity $samOpportunity): AnonymousResourceCollection
    {
        $documents = $samOpportunity->documents()->latest()->get();

        return SamOpportunityDocumentResource::collection($documents);
    }

    public function store(Request $request, SamOpportunity $samOpportunity): JsonResponse
    {
        $this->authorizeUser($request);

        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'max:20480', // 20 MB
                'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        ]);

        $file = $validated['file'];
        $disk = $this->resolveDisk();
        $relativePath = sprintf(
            'sam_documents/%d/%s_%s',
            $samOpportunity->id,
            Str::uuid(),
            $file->getClientOriginalName()
        );

        $storedPath = Storage::disk($disk)->putFileAs(
            dirname($relativePath),
            $file,
            basename($relativePath)
        );

        $document = SamOpportunityDocument::create([
            'sam_opportunity_id' => $samOpportunity->id,
            'uploaded_by_user_id' => $request->user()->id,
            'storage_path' => $storedPath,
            'disk' => $disk,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
        ]);

        ParseSamOpportunityDocument::dispatch($document->id);

        return response()->json(
            new SamOpportunityDocumentResource($document),
            Response::HTTP_CREATED
        );
    }

    public function destroy(Request $request, SamOpportunity $samOpportunity, SamOpportunityDocument $document): JsonResponse
    {
        $this->authorizeUser($request);

        if ($document->sam_opportunity_id !== $samOpportunity->id) {
            return response()->json(['message' => 'Document not found for this opportunity'], Response::HTTP_NOT_FOUND);
        }

        Storage::disk($document->disk)->delete($document->storage_path);
        $document->delete();

        return response()->json([
            'id' => $document->id,
            'deleted' => true,
        ]);
    }

    protected function resolveDisk(): string
    {
        return config('filesystems.disks.sam_documents') ? 'sam_documents' : config('filesystems.default', 'local');
    }

    protected function authorizeUser(Request $request): void
    {
        // Placeholder for future role-based checks; currently just ensure authenticated.
        if (! $request->user()) {
            abort(Response::HTTP_UNAUTHORIZED, 'Unauthenticated');
        }
    }
}
