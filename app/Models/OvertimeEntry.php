<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OvertimeStatus;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeEntry extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'user_id',
        'company_id',
        'date',
        'hours',
        'rate',
        'source',
        'time_entry_id',
        'status',
        'reason',
        'reviewer_comment',
        'reviewed_by',
        'reviewed_at',
        'compensation',
        'included_in_export_id',
    ];

    protected function casts(): array
    {
        return [
            'date'        => 'date',
            'hours'       => 'decimal:2',
            'status'      => OvertimeStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function timeEntry(): BelongsTo
    {
        return $this->belongsTo(TimeEntry::class);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Durée formatée (ex: "2h30").
     */
    public function getHoursLabelAttribute(): string
    {
        $total = (float) $this->hours;
        $h = (int) $total;
        $m = (int) round(($total - $h) * 60);

        return $h > 0
            ? sprintf('%dh%02d', $h, $m)
            : sprintf('%d min', $m);
    }

    /**
     * Minutes équivalentes.
     */
    public function getMinutesAttribute(): int
    {
        return (int) round((float) $this->hours * 60);
    }

    /**
     * Libellé taux de majoration.
     */
    public function getRateLabelAttribute(): string
    {
        return $this->rate === '50' ? '+50%' : '+25%';
    }

    /**
     * Libellé mode de compensation.
     */
    public function getCompensationLabelAttribute(): string
    {
        return $this->compensation === 'rest' ? 'Repos compensateur' : 'Paiement majoré';
    }

    // -------------------------------------------------------------------------
    // Helpers état
    // -------------------------------------------------------------------------

    public function isPending(): bool  { return $this->status === OvertimeStatus::Pending;  }
    public function isApproved(): bool { return $this->status === OvertimeStatus::Approved; }
    public function isRejected(): bool { return $this->status === OvertimeStatus::Rejected; }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePending($query)
    {
        return $query->where('status', OvertimeStatus::Pending->value);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', OvertimeStatus::Approved->value);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('date', $year);
    }

    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    public function scopeManual($query)
    {
        return $query->where('source', 'manual');
    }
}
