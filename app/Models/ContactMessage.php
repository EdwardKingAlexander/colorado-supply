<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'message', 'ip', 'user_agent', 'handled_at', 'handled_by',
    ];

    protected $casts = [
        'handled_at' => 'datetime',
    ];

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'handled_by');
    }

    public function getIsHandledAttribute(): bool
    {
        return ! is_null($this->handled_at);
    }
}
