<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AcquisitionType;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LeaveType extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'color',
        'icon',
        'days_per_year',
        'requires_approval',
        'is_paid',
        'is_active',
        'acquisition_type',
        'max_consecutive_days',
        'notice_days',
        'requires_attachment',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_type'    => AcquisitionType::class,
            'days_per_year'       => 'decimal:2',
            'requires_approval'   => 'boolean',
            'is_paid'             => 'boolean',
            'is_active'           => 'boolean',
            'requires_attachment' => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Boot
    // -------------------------------------------------------------------------

    protected static function booted(): void
    {
        // Auto-génère le slug depuis le nom
        static::creating(function (self $leaveType): void {
            if (empty($leaveType->slug)) {
                $leaveType->slug = Str::slug($leaveType->name);
            }
        });
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Calcule les jours acquis par mois pour ce type de congé.
     */
    public function monthlyAccrual(): float
    {
        if ($this->acquisition_type !== AcquisitionType::Monthly) {
            return 0.0;
        }

        return round($this->days_per_year / 12, 4);
    }

    /**
     * Retourne la couleur pour les badges Tailwind.
     */
    public function colorClass(): string
    {
        return match(true) {
            str_starts_with($this->color, '#1B4F72'), str_starts_with($this->color, '#2E86C1') => 'primary',
            str_starts_with($this->color, '#27AE60')                                            => 'success',
            str_starts_with($this->color, '#F39C12')                                            => 'warning',
            str_starts_with($this->color, '#E74C3C')                                            => 'danger',
            default                                                                              => 'slate',
        };
    }
}
