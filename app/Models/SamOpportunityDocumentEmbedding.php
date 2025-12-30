<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SamOpportunityDocumentEmbedding extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'vector' => 'array',
    ];

    public function chunk(): BelongsTo
    {
        return $this->belongsTo(SamOpportunityDocumentChunk::class, 'sam_opportunity_document_chunk_id');
    }
}
