<?php

declare(strict_types=1);

namespace App\Enums;

enum CompanyPlan: string
{
    case Trial      = 'trial';
    case Starter    = 'starter';
    case Business   = 'business';
    case Enterprise = 'enterprise';

    public function label(): string
    {
        return match($this) {
            self::Trial      => 'Essai gratuit',
            self::Starter    => 'Starter',
            self::Business   => 'Business',
            self::Enterprise => 'Enterprise',
        };
    }

    public function maxEmployees(): int
    {
        return match($this) {
            self::Trial      => 5,
            self::Starter    => 20,
            self::Business   => 100,
            self::Enterprise => PHP_INT_MAX,
        };
    }

    /**
     * Prix mensuel en centimes (EUR).
     */
    public function priceInCents(): int
    {
        return match($this) {
            self::Trial      => 0,
            self::Starter    => 2900,
            self::Business   => 6900,
            self::Enterprise => 14900,
        };
    }

    /**
     * Prix mensuel affiché (EUR).
     */
    public function priceFormatted(): string
    {
        return match($this) {
            self::Trial      => 'Gratuit',
            self::Starter    => '29 €',
            self::Business   => '69 €',
            self::Enterprise => '149 €',
        };
    }

    /**
     * Stripe Price ID (configuré dans .env).
     */
    public function stripePriceId(): string
    {
        return match($this) {
            self::Trial      => '',
            self::Starter    => (string) env('STRIPE_PRICE_STARTER', ''),
            self::Business   => (string) env('STRIPE_PRICE_BUSINESS', ''),
            self::Enterprise => (string) env('STRIPE_PRICE_ENTERPRISE', ''),
        };
    }

    /**
     * Retrouve un plan depuis un Stripe Price ID.
     */
    public static function fromStripePriceId(string $priceId): ?self
    {
        foreach ([self::Starter, self::Business, self::Enterprise] as $plan) {
            if ($plan->stripePriceId() === $priceId && $priceId !== '') {
                return $plan;
            }
        }
        return null;
    }

    /**
     * Liste des fonctionnalités incluses pour l'affichage UI.
     *
     * @return array<string>
     */
    public function features(): array
    {
        $core = [
            'Gestion des congés',
            'Pointage & heures sup.',
            'Bulletins de paie',
            'Documents & signatures',
        ];

        return match($this) {
            self::Trial => [
                ...$core,
                'Jusqu\'à 5 collaborateurs',
                'Support par email',
            ],
            self::Starter => [
                ...$core,
                'Jusqu\'à 20 collaborateurs',
                'Export paie CSV/Excel',
                'Planning mensuel',
                'Support par email',
            ],
            self::Business => [
                ...$core,
                'Jusqu\'à 100 collaborateurs',
                'Export paie CSV/Excel/PDF',
                'Planning mensuel',
                'Recrutement & pipeline',
                'Personnalisation (logo, couleurs)',
                'Support prioritaire',
            ],
            self::Enterprise => [
                ...$core,
                'Collaborateurs illimités',
                'Export paie CSV/Excel/PDF',
                'Planning mensuel',
                'Recrutement & pipeline',
                'Personnalisation complète',
                'Import CSV en masse',
                'Manager dédié',
                'SLA 99,9 %',
            ],
        };
    }
}
