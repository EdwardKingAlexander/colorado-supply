<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SamOpportunityDocumentChunk extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function document(): BelongsTo
    {
        return $this->belongsTo(SamOpportunityDocument::class, 'sam_opportunity_document_id');
    }

    public function embeddings(): HasMany
    {
        return $this->hasMany(SamOpportunityDocumentEmbedding::class, 'sam_opportunity_document_chunk_id');
    }
}
