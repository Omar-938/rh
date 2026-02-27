<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentCategory;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'user_id',
        'uploaded_by',
        'name',
        'original_filename',
        'category',
        'mime_type',
        'file_size',
        'file_path',
        'is_encrypted',
        'requires_signature',
        'signature_status',
        'expires_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'category'           => DocumentCategory::class,
            'is_encrypted'       => 'boolean',
            'requires_signature' => 'boolean',
            'expires_at'         => 'date',
        ];
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Taille lisible (ex: "2,4 Mo").
     */
    public function getFileSizeLabelAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes < 1024)        return "{$bytes} o";
        if ($bytes < 1_048_576)   return round($bytes / 1024, 1)   . ' Ko';
        if ($bytes < 1_073_741_824) return round($bytes / 1_048_576, 1) . ' Mo';
        return round($bytes / 1_073_741_824, 2) . ' Go';
    }

    /**
     * Icône émoji selon le type MIME.
     */
    public function getMimeIconAttribute(): string
    {
        return match (true) {
            str_contains($this->mime_type, 'pdf')        => '📕',
            str_contains($this->mime_type, 'word')       => '📝',
            str_contains($this->mime_type, 'spreadsheet'),
            str_contains($this->mime_type, 'excel')      => '📊',
            str_contains($this->mime_type, 'image')      => '🖼️',
            str_contains($this->mime_type, 'zip'),
            str_contains($this->mime_type, 'archive')    => '📦',
            default                                       => '📄',
        };
    }

    /**
     * Le document est-il expiré ?
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Le document expire-t-il bientôt (dans les 30 jours) ?
     */
    public function getIsExpiringSoonAttribute(): bool
    {
        return $this->expires_at !== null
            && ! $this->is_expired
            && $this->expires_at->diffInDays(now()) <= 30;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isCompanyDocument(): bool
    {
        return $this->user_id === null;
    }

    public function needsSignature(): bool
    {
        return $this->requires_signature && $this->signature_status !== 'completed';
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForUser($query, int $userId): mixed
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForCategory($query, string $category): mixed
    {
        return $query->where('category', $category);
    }

    public function scopeCompanyWide($query): mixed
    {
        return $query->whereNull('user_id');
    }

    public function scopePendingSignature($query): mixed
    {
        return $query->where('requires_signature', true)
                     ->whereIn('signature_status', ['pending', 'partial']);
    }

    public function scopeExpiringSoon($query, int $days = 30): mixed
    {
        return $query->whereNotNull('expires_at')
                     ->whereBetween('expires_at', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }
}
