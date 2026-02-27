<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'company_id',
        'year',
        'allocated',
        'used',
        'pending',
        'carried_over',
        'adjustment',
    ];

    protected function casts(): array
    {
        return [
            'year'         => 'integer',
            'allocated'    => 'decimal:2',
            'used'         => 'decimal:2',
            'pending'      => 'decimal:2',
            'carried_over' => 'decimal:2',
            'adjustment'   => 'decimal:2',
        ];
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    // -------------------------------------------------------------------------
    // Accesseurs calculés
    // -------------------------------------------------------------------------

    /**
     * Total alloué = allocation + report + ajustement.
     */
    public function getTotalAllocatedAttribute(): float
    {
        return (float) ($this->allocated + $this->carried_over + $this->adjustment);
    }

    /**
     * Jours restants (hors pendants).
     */
    public function getRemainingAttribute(): float
    {
        return max(0.0, $this->total_allocated - (float) $this->used);
    }

    /**
     * Jours restants en tenant compte des demandes en attente.
     */
    public function getEffectiveRemainingAttribute(): float
    {
        return max(0.0, $this->remaining - (float) $this->pending);
    }

    /**
     * Pourcentage utilisé (pour les barres de progression).
     */
    public function getUsedPercentageAttribute(): float
    {
        $total = $this->total_allocated;
        if ($total <= 0) {
            return 0.0;
        }

        return min(100.0, round(($this->used / $total) * 100, 1));
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeCurrentYear($query)
    {
        return $query->where('year', now()->year);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Incrémente les jours utilisés et décrémente les pendants si applicable.
     */
    public function approve(float $days): void
    {
        $this->increment('used', $days);
        $this->decrement('pending', min($days, (float) $this->pending));
    }

    /**
     * Remet les jours en attente dans les disponibles (refus ou annulation).
     */
    public function release(float $days, bool $wasApproved = false): void
    {
        if ($wasApproved) {
            $this->decrement('used', min($days, (float) $this->used));
        } else {
            $this->decrement('pending', min($days, (float) $this->pending));
        }
    }

    /**
     * Ajoute des jours acquis mensuellement.
     */
    public function accrue(float $days): void
    {
        $this->increment('allocated', $days);
    }
}
