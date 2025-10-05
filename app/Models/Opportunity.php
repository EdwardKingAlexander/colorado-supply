<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opportunity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'pipeline_id',
        'stage_id',
        'title',
        'description',
        'amount',
        'currency',
        'probability_override',
        'expected_close_date',
        'status',
        'owner_id',
        'source',
        'score',
        'lost_reason_id',
        'closed_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'probability_override' => 'decimal:2',
        'expected_close_date' => 'date',
        'closed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function lostReason(): BelongsTo
    {
        return $this->belongsTo(LostReason::class, 'lost_reason_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * Get the effective probability for this opportunity.
     * Returns probability_override if set, otherwise returns stage's probability_default.
     */
    protected function probabilityEffective(): Attribute
    {
        return Attribute::make(
            get: fn (): ?float => $this->probability_override ?? $this->stage?->probability_default,
        );
    }

    /**
     * Get the forecast amount based on the effective probability.
     */
    protected function forecastAmount(): Attribute
    {
        return Attribute::make(
            get: fn (): ?float => $this->probability_effective !== null
                ? $this->amount * ($this->probability_effective / 100)
                : null,
        );
    }
}
