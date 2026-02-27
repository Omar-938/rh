<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PayrollExportStatus;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class PayrollExport extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'period',
        'status',
        'validated_by',
        'validated_at',
        'sent_at',
        'sent_to',
        'format',
        'file_path',
        'is_correction',
        'correction_of_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status'        => PayrollExportStatus::class,
            'sent_to'       => 'json',
            'is_correction' => 'boolean',
            'validated_at'  => 'datetime',
            'sent_at'       => 'datetime',
        ];
    }

    // ─── Relations ─────────────────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function correctionOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'correction_of_id');
    }

    public function correction(): HasOne
    {
        return $this->hasOne(self::class, 'correction_of_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PayrollExportLine::class);
    }

    // ─── Accessors ─────────────────────────────────────────────────────────────

    /**
     * Label long : "Janvier 2026".
     */
    public function getPeriodLabelAttribute(): string
    {
        return Carbon::createFromFormat('Y-m', $this->period)->translatedFormat('F Y');
    }

    /**
     * Label court : "Jan. 26".
     */
    public function getPeriodShortAttribute(): string
    {
        return Carbon::createFromFormat('Y-m', $this->period)->translatedFormat('M. y');
    }

    public function getPeriodYearAttribute(): int
    {
        return (int) substr($this->period, 0, 4);
    }

    public function getPeriodMonthAttribute(): int
    {
        return (int) substr($this->period, 5, 2);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    public function isDraft(): bool     { return $this->status === PayrollExportStatus::Draft; }
    public function isValidated(): bool { return $this->status === PayrollExportStatus::Validated; }
    public function isSent(): bool      { return $this->status === PayrollExportStatus::Sent; }
    public function isCorrected(): bool { return $this->status === PayrollExportStatus::Corrected; }

    /** Seul un brouillon peut être modifié / généré à nouveau. */
    public function canBeModified(): bool { return $this->isDraft(); }
}
