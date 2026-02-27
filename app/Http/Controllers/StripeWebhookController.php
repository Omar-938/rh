<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CompanyPlan;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    /**
     * Point d'entrée pour tous les webhooks Stripe.
     * Vérifie la signature, dispatche vers les handlers.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature', '');
        $secret    = config('cashier.webhook.secret', '');

        if (empty($secret)) {
            return response()->json(['error' => 'Webhook secret not configured'], 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\UnexpectedValueException) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $this->dispatch($event);

        return response()->json(['status' => 'ok']);
    }

    // ─── Dispatch ────────────────────────────────────────────────────────────────

    private function dispatch(Event $event): void
    {
        match ($event->type) {
            'checkout.session.completed'        => $this->handleCheckoutCompleted($event),
            'customer.subscription.created',
            'customer.subscription.updated'     => $this->handleSubscriptionUpdated($event),
            'customer.subscription.deleted'     => $this->handleSubscriptionDeleted($event),
            'invoice.payment_failed'            => $this->handlePaymentFailed($event),
            default                             => null,
        };
    }

    // ─── Handlers ────────────────────────────────────────────────────────────────

    /**
     * Après un checkout Stripe réussi : sync le plan.
     */
    private function handleCheckoutCompleted(Event $event): void
    {
        $session    = $event->data->object;
        $customerId = $session->customer;

        if (! $customerId) {
            return;
        }

        $company = $this->findByStripeId($customerId);
        if (! $company) {
            return;
        }

        // Sync stripe_id si c'est la première fois
        if (! $company->stripe_id) {
            $company->stripe_id = $customerId;
            $company->save();
        }

        // Le plan sera synchronisé via l'événement subscription.created qui suit
    }

    /**
     * Abonnement créé ou mis à jour : sync le plan.
     */
    private function handleSubscriptionUpdated(Event $event): void
    {
        $subscription = $event->data->object;
        $customerId   = $subscription->customer;

        $company = $this->findByStripeId($customerId);
        if (! $company) {
            return;
        }

        $priceId = $subscription->items->data[0]->price->id ?? null;
        if (! $priceId) {
            return;
        }

        $plan = CompanyPlan::fromStripePriceId($priceId);
        if ($plan) {
            $company->plan           = $plan;
            $company->trial_ends_at  = null; // subscription active = not on trial
            $company->save();
        }

        // Sync payment method info
        $this->syncPaymentMethod($company);
    }

    /**
     * Abonnement annulé : retour en mode Trial expiré.
     */
    private function handleSubscriptionDeleted(Event $event): void
    {
        $subscription = $event->data->object;

        $company = $this->findByStripeId($subscription->customer);
        if (! $company) {
            return;
        }

        $company->plan          = CompanyPlan::Trial;
        $company->trial_ends_at = null; // trial expired
        $company->save();
    }

    /**
     * Paiement échoué : logue pour investigation future.
     */
    private function handlePaymentFailed(Event $event): void
    {
        // TODO: Notifier l'admin de l'entreprise
        \Illuminate\Support\Facades\Log::warning('Stripe payment failed', [
            'invoice_id'  => $event->data->object->id ?? null,
            'customer_id' => $event->data->object->customer ?? null,
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    private function findByStripeId(string $stripeId): ?Company
    {
        return Company::withoutGlobalScopes()
            ->where('stripe_id', $stripeId)
            ->first();
    }

    private function syncPaymentMethod(Company $company): void
    {
        try {
            $stripeCustomer = $company->asStripeCustomer();
            $pmId = $stripeCustomer->invoice_settings->default_payment_method;

            if ($pmId) {
                $pm = \Stripe\PaymentMethod::retrieve($pmId);
                $company->pm_type      = $pm->card?->brand ?? $pm->type;
                $company->pm_last_four = $pm->card?->last4 ?? null;
                $company->save();
            }
        } catch (\Exception) {
            // Non-bloquant
        }
    }
}
