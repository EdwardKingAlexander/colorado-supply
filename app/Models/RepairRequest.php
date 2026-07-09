<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'company',
        'equipment_type', 'manufacturer', 'model_number', 'serial_number',
        'issue_description', 'urgency',
        'ip', 'user_agent', 'handled_at', 'handled_by',
    ];

    protected $casts = [
        'handled_at' => 'datetime',
    ];

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function getIsHandledAttribute(): bool
    {
        return ! is_null($this->handled_at);
    }
}
