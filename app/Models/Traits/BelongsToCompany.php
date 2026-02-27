<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Isole automatiquement chaque modèle par company_id via un Global Scope.
 * À utiliser sur TOUS les modèles multi-tenant.
 */
trait BelongsToCompany
{
    // -------------------------------------------------------------------------
    // Boot — Global Scope automatique
    // -------------------------------------------------------------------------

    public static function bootBelongsToCompany(): void
    {
        // Applique le scope company_id si un utilisateur est authentifié
        static::addGlobalScope('company', function (Builder $builder): void {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where(
                    $builder->getModel()->getTable() . '.company_id',
                    auth()->user()->company_id
                );
            }
        });

        // Injecte automatiquement company_id à la création
        static::creating(function ($model): void {
            if (empty($model->company_id) && auth()->check()) {
                $model->company_id = auth()->user()->company_id;
            }
        });
    }

    // -------------------------------------------------------------------------
    // Relation
    // -------------------------------------------------------------------------

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Désactive le scope company pour une requête (ex: seeders, console).
     */
    public static function withoutCompanyScope(): Builder
    {
        return static::withoutGlobalScope('company');
    }

    /**
     * Scope explicite par company_id (utile sans authentification).
     */
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where($this->getTable() . '.company_id', $companyId);
    }
}
