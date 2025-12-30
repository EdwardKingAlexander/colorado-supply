<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SamOpportunityDocument extends Model
{
    use HasFactory;

    protected $guarded = [];

    public const DEFAULT_DISK = 'sam_documents';

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(SamOpportunity::class, 'sam_opportunity_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function parse(): HasOne
    {
        return $this->hasOne(SamOpportunityDocumentParse::class, 'sam_opportunity_document_id');
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(SamOpportunityDocumentChunk::class, 'sam_opportunity_document_id');
    }
}
