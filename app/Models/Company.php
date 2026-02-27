<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CompanyPlan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;

class Company extends Model
{
    use HasFactory, SoftDeletes, Billable;

    protected $fillable = [
        'name',
        'slug',
        'siret',
        'address',
        'city',
        'postal_code',
        'phone',
        'logo_path',
        'primary_color',
        'plan',
        'trial_ends_at',
        'settings',
        'stripe_id',
        'pm_type',
        'pm_last_four',
    ];

    protected $hidden = [
        'stripe_id',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'plan'          => CompanyPlan::class,
            'settings'      => 'array',
        ];
    }

    // -------------------------------------------------------------------------
    // Boot
    // -------------------------------------------------------------------------

    protected static function booted(): void
    {
        // Génère le slug automatiquement à la création
        static::creating(function (Company $company): void {
            if (empty($company->slug)) {
                $company->slug = static::generateUniqueSlug($company->name);
            }
        });
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Génère un slug unique à partir du nom.
     */
    public static function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 2;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Retourne les settings avec valeurs par défaut fusionnées.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $settings = $this->settings ?? [];
        return $settings[$key] ?? $default;
    }

    /**
     * Retourne le nombre d'heures de travail par jour configuré.
     */
    public function getWorkHoursPerDayAttribute(): float
    {
        return (float) $this->getSetting('work_hours_per_day', 7);
    }

    /**
     * Retourne le nombre de jours de travail par semaine.
     */
    public function getWorkDaysPerWeekAttribute(): int
    {
        return (int) $this->getSetting('work_days_per_week', 5);
    }

    /**
     * Retourne le fuseau horaire de l'entreprise.
     */
    public function getTimezoneAttribute(): string
    {
        return $this->getSetting('timezone', 'Europe/Paris');
    }

    /**
     * Indique si l'essai gratuit est expiré.
     */
    public function getTrialExpiredAttribute(): bool
    {
        if ($this->plan !== CompanyPlan::Trial) {
            return false;
        }
        return $this->trial_ends_at?->isPast() ?? true;
    }

    /**
     * Indique si l'abonnement est actif (essai non expiré ou plan payant).
     */
    public function getSubscriptionActiveAttribute(): bool
    {
        return match ($this->plan) {
            CompanyPlan::Trial      => !$this->trial_expired,
            CompanyPlan::Starter,
            CompanyPlan::Business,
            CompanyPlan::Enterprise => true,
        };
    }

    /**
     * URL du logo ou null.
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path
            ? asset('storage/' . $this->logo_path)
            : null;
    }

    // -------------------------------------------------------------------------
    // Settings helpers
    // -------------------------------------------------------------------------

    /**
     * Met à jour un ou plusieurs paramètres JSON sans écraser les autres.
     */
    public function updateSettings(array $data): void
    {
        $this->settings = array_merge($this->settings ?? [], $data);
        $this->save();
    }

    /**
     * Retourne les emails du comptable configurés.
     *
     * @return array<string>
     */
    public function getAccountantEmails(): array
    {
        return $this->getSetting('accountant_emails', []);
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // Les autres relations (departments, leave_types, etc.) seront ajoutées
    // au fur et à mesure des étapes suivantes.
}
