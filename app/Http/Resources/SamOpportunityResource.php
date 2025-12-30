<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SamOpportunityResource extends JsonResource
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
            'title' => $this->title,
            'agency' => $this->agency ?? null,
            'naics_code' => $this->naics_code ?? null,
            'psc_code' => $this->psc_code ?? null,
            'posted_date' => $this->posted_date ?? null,
            'response_deadline' => $this->response_deadline ?? null,
            'sam_url' => $this->sam_url ?? null,
            'is_favorite' => true,
        ];
    }
}
