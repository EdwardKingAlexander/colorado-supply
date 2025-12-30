<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SamOpportunityDocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sam_opportunity_id' => $this->sam_opportunity_id,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'storage_path' => $this->storage_path,
            'disk' => $this->disk,
            'uploaded_at' => $this->created_at?->toIso8601String(),
            'uploaded_by' => $this->uploadedBy?->only(['id', 'name']),
        ];
    }
}
