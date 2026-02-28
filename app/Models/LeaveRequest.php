<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeaveStatus;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'user_id',
        'company_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'start_half',
        'end_half',
        'days_count',
        'status',
        'employee_comment',
        'attachment_path',
        'attachment_original_name',
        'reviewer_comment',
        'reviewed_by',
        'reviewed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status'       => LeaveStatus::class,
            'start_date'   => 'date',
            'end_date'     => 'date',
            'days_count'   => 'decimal:1',
            'reviewed_at'  => 'datetime',
            'cancelled_at' => 'datetime',
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePending($query)
    {
        return $query->where('status', LeaveStatus::Pending->value);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', LeaveStatus::Approved->value);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForPeriod($query, string $start, string $end)
    {
        return $query->where('start_date', '<=', $end)
                     ->where('end_date', '>=', $start);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isPending(): bool   { return $this->status === LeaveStatus::Pending;   }
    public function isApproved(): bool  { return $this->status === LeaveStatus::Approved;  }
    public function isRejected(): bool  { return $this->status === LeaveStatus::Rejected;  }
    public function isCancelled(): bool { return $this->status === LeaveStatus::Cancelled; }

    public function canBeCancelled(): bool
    {
        return $this->isPending() || $this->isApproved();
    }

    /**
     * Libellé de la période (ex: "24 fév → 2 mars").
     */
    public function getPeriodLabelAttribute(): string
    {
        $fmt = fn ($d) => $d->translatedFormat('j M');

        if ($this->start_date->year !== now()->year) {
            $fmt = fn ($d) => $d->translatedFormat('j M Y');
        }

        $start = $fmt($this->start_date);
        $end   = $fmt($this->end_date);

        $startSuffix = $this->start_half === 'afternoon' ? ' (après-midi)' : '';
        $endSuffix   = $this->end_half   === 'morning'   ? ' (matin)'      : '';

        return $start . $startSuffix . ($start !== $end ? " → {$end}{$endSuffix}" : $endSuffix);
    }
}
