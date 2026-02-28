<template>
  <AppLayout title="Facturation" :back-url="route('settings.index')">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-6 space-y-6">

      <!-- Header -->
      <div class="flex items-center gap-3">
        <Link :href="route('settings.index')" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
        </Link>
        <div>
          <h1 class="text-2xl font-bold text-slate-900" style="font-family: 'Plus Jakarta Sans', sans-serif;">
            Facturation & Abonnement
          </h1>
          <p class="text-sm text-slate-500 mt-0.5">Gérez votre plan SimpliRH et vos moyens de paiement</p>
        </div>
      </div>

      <!-- Flash -->
      <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
        <div v-if="$page.props.flash?.success" class="flex items-center gap-3 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm">
          <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
          {{ $page.props.flash.success }}
        </div>
      </Transition>
      <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
        <div v-if="$page.props.flash?.error" class="flex items-center gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
          <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          {{ $page.props.flash.error }}
        </div>
      </Transition>

      <!-- ── Stripe non configuré ──────────────────────────────────────────────── -->
      <div v-if="!props.stripe_configured" class="bg-amber-50 border border-amber-200 rounded-2xl p-6 flex items-start gap-4">
        <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
          <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <div>
          <h3 class="text-sm font-semibold text-amber-800">Stripe non configuré</h3>
          <p class="text-sm text-amber-700 mt-1">
            Configurez les variables d'environnement <code class="font-mono bg-amber-100 px-1 rounded">STRIPE_KEY</code>, <code class="font-mono bg-amber-100 px-1 rounded">STRIPE_SECRET</code> et <code class="font-mono bg-amber-100 px-1 rounded">STRIPE_PRICE_STARTER</code> / <code class="font-mono bg-amber-100 px-1 rounded">STRIPE_PRICE_BUSINESS</code> / <code class="font-mono bg-amber-100 px-1 rounded">STRIPE_PRICE_ENTERPRISE</code> dans votre fichier <code class="font-mono bg-amber-100 px-1 rounded">.env</code> pour activer les paiements.
          </p>
        </div>
      </div>

      <!-- ── Plan actuel ───────────────────────────────────────────────────────── -->
      <section>
        <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-3">Plan actuel</h2>

        <!-- Trial -->
        <div v-if="currentPlan.key === 'trial'" class="rounded-2xl border-2 p-6 flex flex-col sm:flex-row items-start sm:items-center gap-5"
          :class="trialExpiredCls">
          <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0" :class="trialExpiredIcon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1 flex-wrap">
              <h3 class="text-base font-bold text-slate-900">Essai gratuit</h3>
              <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                :class="props.trial_expired ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'">
                {{ props.trial_expired ? 'Expiré' : `${trialDaysLeft} jour(s) restant(s)` }}
              </span>
            </div>
            <p class="text-sm text-slate-500">Jusqu'à 5 collaborateurs — toutes les fonctionnalités incluses pendant l'essai.</p>
            <!-- Progress bar -->
            <div v-if="!props.trial_expired && trialDaysLeft !== null" class="mt-3">
              <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden w-48 max-w-full">
                <div class="h-full rounded-full transition-all" :class="trialBarCls" :style="{ width: `${trialProgress}%` }"></div>
              </div>
            </div>
          </div>
          <div v-if="props.stripe_configured" class="flex-shrink-0">
            <button
              @click="scrollToPlans"
              class="px-5 py-2.5 bg-primary-700 hover:bg-primary-800 text-white text-sm font-semibold rounded-xl transition-colors"
            >
              Choisir un plan →
            </button>
          </div>
        </div>

        <!-- Abonnement actif -->
        <div v-else class="rounded-2xl border-2 border-green-200 bg-green-50 p-6 flex flex-col sm:flex-row items-start sm:items-center gap-5">
          <div class="w-12 h-12 rounded-2xl bg-green-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1 flex-wrap">
              <h3 class="text-base font-bold text-slate-900">Plan {{ currentPlan.label }}</h3>
              <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Actif</span>
            </div>
            <p class="text-sm text-slate-600">
              {{ currentPlan.price }} / mois — jusqu'à {{ currentPlan.employees }} collaborateurs
            </p>
            <p v-if="props.pm_last_four" class="text-xs text-slate-400 mt-1">
              Carte {{ pmBrandLabel }} •••• {{ props.pm_last_four }}
            </p>
          </div>
          <div v-if="props.stripe_configured" class="flex-shrink-0">
            <button
              @click="openPortal"
              :disabled="portalForm.processing"
              class="flex items-center gap-2 px-5 py-2.5 border border-green-300 bg-white hover:bg-green-50 text-green-700 text-sm font-semibold rounded-xl transition-colors"
            >
              <svg v-if="portalForm.processing" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
              </svg>
              Gérer mon abonnement
            </button>
          </div>
        </div>
      </section>

      <!-- ── Choisir un plan ───────────────────────────────────────────────────── -->
      <section id="plans-section">
        <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">
          {{ props.current_plan === 'trial' ? 'Choisir un plan' : 'Changer de plan' }}
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div
            v-for="plan in props.plans"
            :key="plan.key"
            class="relative bg-white rounded-2xl border-2 p-5 flex flex-col transition-all"
            :class="{
              'border-primary-500 shadow-lg shadow-primary-100': plan.popular && !plan.current,
              'border-green-400 bg-green-50/30': plan.current,
              'border-slate-200 hover:border-slate-300': !plan.popular && !plan.current,
            }"
          >
            <!-- Popular badge -->
            <div v-if="plan.popular" class="absolute -top-3 left-1/2 -translate-x-1/2 z-10">
              <span class="px-3 py-1 bg-primary-700 text-white text-xs font-bold rounded-full shadow-sm">
                Populaire
              </span>
            </div>

            <!-- Current badge -->
            <div v-if="plan.current" class="absolute -top-3 right-4 z-10">
              <span class="px-3 py-1 bg-green-600 text-white text-xs font-bold rounded-full shadow-sm">
                Plan actuel
              </span>
            </div>

            <!-- Plan header -->
            <div class="mb-4">
              <h3 class="text-base font-bold text-slate-900">{{ plan.label }}</h3>
              <div class="flex items-baseline gap-1 mt-1">
                <span class="text-2xl font-extrabold text-slate-900">{{ plan.price }}</span>
                <span class="text-sm text-slate-400">/mois HT</span>
              </div>
              <p class="text-xs text-slate-500 mt-1">
                Jusqu'à <strong>{{ plan.employees }}</strong> collaborateurs
              </p>
            </div>

            <!-- Features list -->
            <ul class="space-y-2 flex-1 mb-5">
              <li
                v-for="(feature, i) in plan.features"
                :key="i"
                class="flex items-start gap-2 text-xs text-slate-600"
              >
                <svg class="w-3.5 h-3.5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                {{ feature }}
              </li>
            </ul>

            <!-- CTA -->
            <button
              v-if="!plan.current && props.stripe_configured"
              @click="choosePlan(plan.key)"
              :disabled="checkoutForm.processing"
              class="w-full py-2.5 text-sm font-semibold rounded-xl transition-colors"
              :class="plan.popular
                ? 'bg-primary-700 hover:bg-primary-800 text-white'
                : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
            >
              <span v-if="checkoutForm.processing && selectedPlanKey === plan.key" class="flex items-center justify-center gap-2">
                <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Redirection...
              </span>
              <span v-else>
                {{ props.current_plan === 'trial' ? 'Choisir ce plan' : 'Passer à ce plan' }}
              </span>
            </button>
            <div v-else-if="plan.current" class="w-full py-2.5 text-center text-sm font-medium text-green-600">
              Plan actuel ✓
            </div>
            <div v-else-if="!props.stripe_configured" class="w-full py-2 text-center text-xs text-slate-400 italic">
              Paiement non configuré
            </div>
          </div>
        </div>

        <p class="text-xs text-slate-400 mt-3 text-center">
          Tous les prix sont HT · TVA applicable selon la réglementation française · Résiliable à tout moment
        </p>
      </section>

      <!-- ── Historique des factures ───────────────────────────────────────────── -->
      <section v-if="props.invoices.length > 0">
        <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-3">Historique des factures</h2>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 bg-slate-50">
                <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Date</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide hidden sm:table-cell">Numéro</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Montant</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Statut</th>
                <th class="px-5 py-3"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="inv in props.invoices" :key="inv.id" class="hover:bg-slate-50 transition-colors">
                <td class="px-5 py-3.5 text-slate-700">{{ inv.date }}</td>
                <td class="px-5 py-3.5 text-slate-400 font-mono text-xs hidden sm:table-cell">{{ inv.number }}</td>
                <td class="px-5 py-3.5 font-semibold text-slate-800">{{ inv.total }}</td>
                <td class="px-5 py-3.5">
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"
                    :class="inv.status === 'payée' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'">
                    {{ inv.status }}
                  </span>
                </td>
                <td class="px-5 py-3.5 text-right">
                  <a v-if="inv.pdf_url" :href="inv.pdf_url" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-1.5 text-xs text-primary-600 hover:text-primary-700 font-medium transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    PDF
                  </a>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- ── Contact ───────────────────────────────────────────────────────────── -->
      <div class="flex items-center justify-center pt-2">
        <p class="text-sm text-slate-400 text-center">
          Des questions sur votre abonnement ?
          <a href="mailto:support@simpli-rh.com" class="text-primary-600 hover:text-primary-700 font-medium ml-1 transition-colors">
            Contactez-nous →
          </a>
        </p>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
  current_plan:      { type: String, required: true },
  trial_ends_at:     { type: String, default: null },
  trial_expired:     { type: Boolean, default: false },
  pm_type:           { type: String, default: null },
  pm_last_four:      { type: String, default: null },
  invoices:          { type: Array, default: () => [] },
  plans:             { type: Array, default: () => [] },
  stripe_configured: { type: Boolean, default: false },
})

// ── Plan actuel enrichi ────────────────────────────────────────────────────
const PLAN_META = {
  trial:      { key: 'trial',      label: 'Essai gratuit',  price: 'Gratuit', employees: 5    },
  starter:    { key: 'starter',    label: 'Starter',        price: '29 €',    employees: 20   },
  business:   { key: 'business',   label: 'Business',       price: '69 €',    employees: 100  },
  enterprise: { key: 'enterprise', label: 'Enterprise',     price: '149 €',   employees: '∞'  },
}
const currentPlan = computed(() => PLAN_META[props.current_plan] ?? PLAN_META.trial)

// ── Trial countdown ────────────────────────────────────────────────────────
const TRIAL_DAYS = 14
const trialDaysLeft = computed(() => {
  if (!props.trial_ends_at) return null
  const ms   = new Date(props.trial_ends_at) - Date.now()
  return Math.max(0, Math.ceil(ms / 86400000))
})
const trialProgress = computed(() =>
  trialDaysLeft.value === null ? 100 : Math.round((trialDaysLeft.value / TRIAL_DAYS) * 100)
)
const trialExpiredCls = computed(() =>
  props.trial_expired
    ? 'border-red-200 bg-red-50/30'
    : 'border-amber-200 bg-amber-50/30'
)
const trialExpiredIcon = computed(() =>
  props.trial_expired ? 'bg-red-100 text-red-500' : 'bg-amber-100 text-amber-500'
)
const trialBarCls = computed(() =>
  trialDaysLeft.value !== null && trialDaysLeft.value <= 3 ? 'bg-red-400' : 'bg-amber-400'
)

// ── Payment method display ─────────────────────────────────────────────────
const pmBrandLabel = computed(() => {
  const brands = { visa: 'Visa', mastercard: 'Mastercard', amex: 'Amex', cartes_bancaires: 'CB' }
  return brands[props.pm_type] ?? props.pm_type ?? 'Carte'
})

// ── Forms ──────────────────────────────────────────────────────────────────
const selectedPlanKey = ref(null)

const checkoutForm = useForm({ plan: '' })
const portalForm   = useForm({})

function choosePlan(planKey) {
  selectedPlanKey.value = planKey
  checkoutForm.plan     = planKey

  const isUpgradeFromPaid = props.current_plan !== 'trial'
  const routeName = isUpgradeFromPaid ? 'settings.billing.swap' : 'settings.billing.checkout'

  checkoutForm.post(route(routeName))
}

function openPortal() {
  portalForm.post(route('settings.billing.portal'))
}

function scrollToPlans() {
  document.getElementById('plans-section')?.scrollIntoView({ behavior: 'smooth' })
}
</script>
