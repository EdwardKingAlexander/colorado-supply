<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SamOpportunity extends Model
{
    use HasFactory;

    protected $table = 'sam_opportunities';

    protected $guarded = [];

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sam_opportunity_favorites')->withTimestamps();
    }

    public function isFavoritedBy(User $user): bool
    {
        return $this->favoritedBy()->where('user_id', $user->id)->exists();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SamOpportunityDocument::class, 'sam_opportunity_id');
    }
}
