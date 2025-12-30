<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SamOpportunityDocumentParse extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function document(): BelongsTo
    {
        return $this->belongsTo(SamOpportunityDocument::class, 'sam_opportunity_document_id');
    }
}
