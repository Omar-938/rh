<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PayslipStatus;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Payslip extends Model
{
    use HasFactory, BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'user_id',
        'uploaded_by',
        'period_year',
        'period_month',
        'original_filename',
        'file_path',
        'file_size',
        'is_encrypted',
        'status',
        'distributed_at',
        'is_viewed',
        'viewed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status'         => PayslipStatus::class,
            'is_encrypted'   => 'boolean',
            'is_viewed'      => 'boolean',
            'distributed_at' => 'datetime',
            'viewed_at'      => 'datetime',
        ];
    }

    // ─── Relations ─────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ─── Accessors ─────────────────────────────────────────────────────────────

    /**
     * Label de la période : "Janvier 2025".
     */
    public function getPeriodLabelAttribute(): string
    {
        return Carbon::createFromDate($this->period_year, $this->period_month, 1)
            ->translatedFormat('F Y');
    }

    /**
     * Label court : "Jan. 25".
     */
    public function getPeriodShortAttribute(): string
    {
        return Carbon::createFromDate($this->period_year, $this->period_month, 1)
            ->translatedFormat('M. y');
    }

    /**
     * Taille lisible.
     */
    public function getFileSizeLabelAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes < 1024)      return "{$bytes} o";
        if ($bytes < 1_048_576) return round($bytes / 1024, 1) . ' Ko';
        return round($bytes / 1_048_576, 1) . ' Mo';
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->status === PayslipStatus::Draft;
    }

    public function isDistributed(): bool
    {
        return $this->status === PayslipStatus::Distributed;
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForPeriod($query, int $year, int $month): mixed
    {
        return $query->where('period_year', $year)->where('period_month', $month);
    }

    public function scopeDistributed($query): mixed
    {
        return $query->where('status', PayslipStatus::Distributed->value);
    }

    public function scopeDraft($query): mixed
    {
        return $query->where('status', PayslipStatus::Draft->value);
    }

    public function scopeUnread($query): mixed
    {
        return $query->where('is_viewed', false)->distributed();
    }
}
