<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContractType;
use App\Enums\JobPostingStatus;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPosting extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'department_id',
        'created_by',
        'title',
        'description',
        'requirements',
        'contract_type',
        'location',
        'salary_range',
        'status',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'status'        => JobPostingStatus::class,
            'contract_type' => ContractType::class,
            'closed_at'     => 'datetime',
        ];
    }

    // ─── Relations ──────────────────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    public function isOpen(): bool   { return $this->status === JobPostingStatus::Open; }
    public function isDraft(): bool  { return $this->status === JobPostingStatus::Draft; }
    public function isClosed(): bool { return $this->status === JobPostingStatus::Closed; }
}
