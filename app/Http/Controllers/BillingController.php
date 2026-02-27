<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CompanyPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    // ─── Page facturation ────────────────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $company = $request->user()->company;

        $invoices = $this->fetchInvoices($company);

        return Inertia::render('Settings/Billing', [
            'current_plan'     => $company->plan->value,
            'trial_ends_at'    => $company->trial_ends_at?->toDateString(),
            'trial_expired'    => $company->trial_expired,
            'pm_type'          => $company->pm_type,
            'pm_last_four'     => $company->pm_last_four,
            'invoices'         => $invoices,
            'plans'            => $this->plansData($company),
            'stripe_configured'=> ! empty(config('cashier.key')),
        ]);
    }

    // ─── Démarrer un abonnement (redirect → Stripe Checkout) ─────────────────────

    public function checkout(Request $request): RedirectResponse
    {
        $request->validate([
            'plan' => ['required', 'string', 'in:starter,business,enterprise'],
        ]);

        $company = $request->user()->company;
        $plan    = CompanyPlan::from($request->plan);
        $priceId = $plan->stripePriceId();

        if (empty($priceId)) {
            return back()->with('error', 'Ce plan n\'est pas encore disponible à l\'achat. Contactez-nous.');
        }

        $checkoutSession = $company
            ->newSubscription('default', $priceId)
            ->allowPromotionCodes()
            ->checkout([
                'success_url' => route('settings.billing.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('settings.billing.index'),
                'metadata'    => ['company_id' => $company->id, 'plan' => $plan->value],
            ]);

        return redirect($checkoutSession->url);
    }

    // ─── Succès paiement ─────────────────────────────────────────────────────────

    public function success(Request $request): RedirectResponse
    {
        return redirect()
            ->route('settings.billing.index')
            ->with('success', 'Abonnement activé avec succès ! Bienvenue sur SimpliRH.');
    }

    // ─── Portail client Stripe ────────────────────────────────────────────────────

    public function portal(Request $request): RedirectResponse
    {
        $company = $request->user()->company;

        if (! $company->stripe_id) {
            return back()->with('error', 'Aucun abonnement trouvé.');
        }

        $url = $company->billingPortalUrl(route('settings.billing.index'));

        return redirect($url);
    }

    // ─── Changer de plan (swap) ────────────────────────────────────────────────────

    public function swap(Request $request): RedirectResponse
    {
        $request->validate([
            'plan' => ['required', 'string', 'in:starter,business,enterprise'],
        ]);

        $company = $request->user()->company;
        $plan    = CompanyPlan::from($request->plan);
        $priceId = $plan->stripePriceId();

        if (empty($priceId)) {
            return back()->with('error', 'Ce plan n\'est pas encore disponible.');
        }

        $subscription = $company->subscription('default');

        if (! $subscription) {
            return $this->checkout($request);
        }

        $subscription->swap($priceId);

        $company->plan = $plan;
        $company->save();

        return back()->with('success', "Plan changé vers {$plan->label()} avec succès.");
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchInvoices(\App\Models\Company $company): array
    {
        if (! $company->stripe_id || empty(config('cashier.key'))) {
            return [];
        }

        try {
            return $company->invoices()
                ->take(12)
                ->map(fn ($inv) => [
                    'id'        => $inv->id,
                    'number'    => $inv->number ?? '—',
                    'date'      => $inv->date()->locale('fr')->isoFormat('D MMM YYYY'),
                    'total'     => number_format($inv->rawTotal() / 100, 2, ',', ' ') . ' €',
                    'status'    => $inv->paid ? 'payée' : 'en attente',
                    'pdf_url'   => $inv->invoice_pdf,
                ])
                ->toArray();
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * Données des plans pour l'affichage UI.
     *
     * @return array<int, array<string, mixed>>
     */
    private function plansData(\App\Models\Company $company): array
    {
        $plans = [CompanyPlan::Starter, CompanyPlan::Business, CompanyPlan::Enterprise];

        return array_map(fn (CompanyPlan $plan) => [
            'key'          => $plan->value,
            'label'        => $plan->label(),
            'price'        => $plan->priceFormatted(),
            'employees'    => $plan->maxEmployees() === PHP_INT_MAX ? 'Illimité' : $plan->maxEmployees(),
            'features'     => $plan->features(),
            'current'      => $company->plan === $plan,
            'popular'      => $plan === CompanyPlan::Business,
            'has_price_id' => ! empty($plan->stripePriceId()),
        ], $plans);
    }
}
