<script setup>
import { computed, ref, watch } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    settings:       { type: Object, required: true },
    company_stats:  { type: Object, required: true },
    employee_stats: { type: Array,  default: () => [] },
    year:           { type: Number, required: true },
    current_year:   { type: Number, required: true },
})

// ─── Formulaire ───────────────────────────────────────────────────────────────
const form = useForm({
    overtime_auto_detect:       props.settings.overtime_auto_detect,
    overtime_threshold_minutes: props.settings.overtime_threshold_minutes,
    overtime_annual_quota:      props.settings.overtime_annual_quota,
    overtime_rate_50_threshold: props.settings.overtime_rate_50_threshold,
    overtime_threshold_alert:   props.settings.overtime_threshold_alert,
})

function save() {
    form.patch(route('settings.overtime-rules.update'), { preserveScroll: true })
}

// ─── Navigation année ─────────────────────────────────────────────────────────
function changeYear(delta) {
    router.get(
        route('settings.overtime-rules.index'),
        { year: props.year + delta },
        { preserveScroll: true }
    )
}

// ─── Stats compagnie ──────────────────────────────────────────────────────────
const companyQuotaPct = computed(() => {
    const quota = form.overtime_annual_quota
    if (!quota) return 0
    return Math.min(100, Math.round((props.company_stats.approved_hours / quota) * 100))
})

const companyQuotaBarClass = computed(() => {
    const pct = companyQuotaPct.value
    if (pct >= 90) return 'bg-danger-500'
    if (pct >= 70) return 'bg-amber-400'
    return 'bg-emerald-500'
})

// ─── Helpers employés ─────────────────────────────────────────────────────────
function empBarClass(pct) {
    if (pct >= 90) return 'bg-danger-500'
    if (pct >= 70) return 'bg-amber-400'
    return 'bg-primary-500'
}

function formatHours(h) {
    const int  = Math.floor(h)
    const mins = Math.round((h - int) * 60)
    return mins > 0 ? `${int}h${String(mins).padStart(2, '0')}` : `${int}h`
}

// ─── Info-bulles règles légales ───────────────────────────────────────────────
const showLegalInfo = ref(false)
</script>

<template>
    <Head title="Règles heures supplémentaires" />

    <AppLayout title="Paramètres — Heures supplémentaires">

        <!-- ── En-tête ──────────────────────────────────────────────────────── -->
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-xl font-display font-bold text-slate-900">
                    Règles heures supplémentaires
                </h2>
                <p class="text-sm text-slate-500 mt-0.5">
                    Contingent légal, taux de majoration et détection automatique
                </p>
            </div>
            <button
                @click="save"
                :disabled="form.processing || !form.isDirty"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600 hover:bg-primary-700
                       text-white text-sm font-semibold rounded-xl shadow-sm transition-all active:scale-95
                       disabled:opacity-50 disabled:cursor-not-allowed self-start sm:self-auto"
            >
                <svg v-if="form.processing"
                     class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor"
                     stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
            </button>
        </div>

        <!-- ── Compteur global entreprise ───────────────────────────────────── -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-700 rounded-2xl p-5 sm:p-6 mb-6 text-white">
            <!-- Navigation année -->
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-display font-bold text-lg">Bilan {{ year }}</h3>
                <div class="flex items-center gap-1">
                    <button
                        @click="changeYear(-1)"
                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-white/10
                               hover:bg-white/20 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </button>
                    <button
                        @click="changeYear(1)"
                        :disabled="year >= current_year"
                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-white/10
                               hover:bg-white/20 transition-colors disabled:opacity-30"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
                <div>
                    <p class="text-3xl font-display font-bold tabular-nums">
                        {{ formatHours(company_stats.approved_hours) }}
                    </p>
                    <p class="text-slate-300 text-xs mt-1">Heures approuvées</p>
                </div>
                <div>
                    <p class="text-3xl font-display font-bold text-amber-300 tabular-nums">
                        {{ formatHours(company_stats.pending_hours) }}
                    </p>
                    <p class="text-slate-300 text-xs mt-1">En attente</p>
                </div>
                <div>
                    <p class="text-3xl font-display font-bold tabular-nums">
                        {{ company_stats.employees_count }}
                    </p>
                    <p class="text-slate-300 text-xs mt-1">Employés concernés</p>
                </div>
                <div>
                    <p class="text-3xl font-display font-bold tabular-nums">
                        {{ company_stats.total_entries }}
                    </p>
                    <p class="text-slate-300 text-xs mt-1">Déclarations</p>
                </div>
            </div>

            <!-- Barre contingent global -->
            <div>
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-slate-300">Contingent annuel utilisé</span>
                    <span class="font-semibold">{{ companyQuotaPct }}%</span>
                </div>
                <div class="h-3 bg-white/10 rounded-full overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-700"
                        :class="companyQuotaBarClass"
                        :style="{ width: companyQuotaPct + '%' }"
                    />
                </div>
                <p class="text-slate-400 text-xs mt-2">
                    Contingent légal : {{ form.overtime_annual_quota }}h / employé / an
                </p>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-6 mb-6">

            <!-- ── Paramètres de règles ─────────────────────────────────────── -->
            <div class="space-y-5">

                <!-- Détection automatique -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <h3 class="font-semibold text-slate-900 mb-0.5">
                                Détection automatique
                            </h3>
                            <p class="text-sm text-slate-500 leading-relaxed">
                                Crée automatiquement une déclaration d'heures sup. après
                                chaque pointage dépassant la durée quotidienne prévue.
                            </p>
                        </div>

                        <!-- Toggle switch -->
                        <button
                            @click="form.overtime_auto_detect = !form.overtime_auto_detect"
                            class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full
                                   border-2 border-transparent transition-colors duration-200 focus:outline-none
                                   focus-visible:ring-2 focus-visible:ring-primary-500"
                            :class="form.overtime_auto_detect ? 'bg-emerald-500' : 'bg-slate-200'"
                            role="switch"
                            :aria-checked="form.overtime_auto_detect"
                        >
                            <span
                                class="inline-block h-5 w-5 rounded-full bg-white shadow-sm
                                       transition-transform duration-200"
                                :class="form.overtime_auto_detect ? 'translate-x-5' : 'translate-x-0'"
                            />
                        </button>
                    </div>

                    <!-- Seuil de grâce (visible si auto activé) -->
                    <Transition
                        enter-active-class="transition-all duration-200 ease-out"
                        enter-from-class="opacity-0 -translate-y-2"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition-all duration-150 ease-in"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 -translate-y-2"
                    >
                        <div v-if="form.overtime_auto_detect" class="mt-5 pt-5 border-t border-slate-100">
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Seuil de déclenchement
                                <span class="font-normal text-slate-400">(minutes de dépassement minimum)</span>
                            </label>
                            <div class="flex items-center gap-3">
                                <input
                                    v-model.number="form.overtime_threshold_minutes"
                                    type="range"
                                    min="5" max="60" step="5"
                                    class="flex-1 accent-primary-600 h-1.5 cursor-pointer"
                                />
                                <span class="w-16 text-center font-bold text-slate-800 bg-slate-100
                                             rounded-lg py-1 text-sm tabular-nums">
                                    {{ form.overtime_threshold_minutes }} min
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-1.5">
                                Un dépassement inférieur à {{ form.overtime_threshold_minutes }} minutes
                                sera ignoré (arrondi de pointage).
                            </p>
                        </div>
                    </Transition>
                </div>

                <!-- Taux de majoration -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-slate-900">Taux de majoration légaux</h3>
                        <button
                            @click="showLegalInfo = !showLegalInfo"
                            class="text-xs text-primary-600 hover:text-primary-700 font-medium transition-colors"
                        >
                            {{ showLegalInfo ? 'Masquer' : 'Voir le cadre légal' }}
                        </button>
                    </div>

                    <!-- Info légale -->
                    <Transition
                        enter-active-class="transition-all duration-200 ease-out overflow-hidden"
                        enter-from-class="max-h-0 opacity-0"
                        enter-to-class="max-h-40 opacity-100"
                        leave-active-class="transition-all duration-150 ease-in overflow-hidden"
                        leave-from-class="max-h-40 opacity-100"
                        leave-to-class="max-h-0 opacity-0"
                    >
                        <div v-if="showLegalInfo"
                             class="bg-primary-50 border border-primary-100 rounded-xl p-3.5 mb-4 text-xs
                                    text-primary-800 space-y-1 leading-relaxed">
                            <p><strong>Article L. 3121-36 du Code du travail :</strong></p>
                            <p>· 36e à 43e heure hebdomadaire → <strong>majoration de 25 %</strong></p>
                            <p>· Au-delà de la 43e heure → <strong>majoration de 50 %</strong></p>
                            <p class="text-primary-600">
                                Ces taux peuvent être modifiés par accord de branche (minimum 10 %).
                            </p>
                        </div>
                    </Transition>

                    <!-- Visualisation des tranches -->
                    <div class="flex rounded-xl overflow-hidden text-xs font-semibold text-center mb-4 h-9">
                        <div class="flex items-center justify-center bg-slate-100 text-slate-500"
                             style="width:35%">
                            35h — normal
                        </div>
                        <div class="flex items-center justify-center bg-amber-100 text-amber-700 relative"
                             :style="{ width: (form.overtime_rate_50_threshold - 35) / 25 * 65 + '%' }">
                            +25%
                        </div>
                        <div class="flex items-center justify-center bg-orange-200 text-orange-700 flex-1">
                            +50%
                        </div>
                    </div>

                    <!-- Seuil 50% -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Seuil de basculement à +50%
                            <span class="font-normal text-slate-400">(heures hebdomadaires)</span>
                        </label>
                        <div class="flex items-center gap-3">
                            <input
                                v-model.number="form.overtime_rate_50_threshold"
                                type="range"
                                min="36" max="60" step="1"
                                class="flex-1 accent-orange-500 h-1.5 cursor-pointer"
                            />
                            <span class="w-16 text-center font-bold text-slate-800 bg-slate-100
                                         rounded-lg py-1 text-sm tabular-nums">
                                {{ form.overtime_rate_50_threshold }}h
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1.5">
                            Légal : 43h. Modifiable par accord d'entreprise (min. 36h).
                        </p>
                    </div>
                </div>

                <!-- Contingent annuel -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <h3 class="font-semibold text-slate-900 mb-4">Contingent annuel</h3>

                    <div class="space-y-4">
                        <!-- Quota heures -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">
                                Contingent maximum par employé / an
                            </label>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model.number="form.overtime_annual_quota"
                                    type="number"
                                    min="0" max="1000" step="10"
                                    class="w-28 text-center text-lg font-bold text-slate-800 border border-slate-200
                                           rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2
                                           focus:ring-primary-300 focus:border-primary-300"
                                />
                                <span class="text-slate-500 font-medium">heures / an</span>
                            </div>
                            <p class="text-xs text-slate-400 mt-1.5">
                                Légal : 220h (art. D. 3121-24). Modifiable par accord collectif.
                            </p>
                        </div>

                        <!-- Alerte préventive -->
                        <div class="pt-4 border-t border-slate-100">
                            <label class="block text-sm font-medium text-slate-700 mb-2">
                                Alerte préventive
                                <span class="font-normal text-slate-400">
                                    (déclencher une alerte quand il reste X heures)
                                </span>
                            </label>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model.number="form.overtime_threshold_alert"
                                    type="number"
                                    min="0" max="200" step="5"
                                    class="w-28 text-center text-lg font-bold text-slate-800 border border-slate-200
                                           rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2
                                           focus:ring-primary-300 focus:border-primary-300"
                                />
                                <span class="text-slate-500 font-medium">heures restantes</span>
                            </div>
                            <p class="text-xs text-slate-400 mt-1.5">
                                Un bandeau d'alerte apparaîtra sur le profil de l'employé concerné.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Bouton sauvegarde mobile -->
                <div v-if="form.isDirty"
                     class="lg:hidden sticky bottom-4 z-10">
                    <button
                        @click="save"
                        :disabled="form.processing"
                        class="w-full flex items-center justify-center gap-2 px-4 py-3
                               bg-primary-600 hover:bg-primary-700 text-white font-semibold
                               rounded-2xl shadow-lg transition-all active:scale-98
                               disabled:opacity-60"
                    >
                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        {{ form.processing ? 'Enregistrement…' : 'Enregistrer les modifications' }}
                    </button>
                </div>
            </div>

            <!-- ── Compteurs par employé ─────────────────────────────────────── -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden h-fit">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-semibold text-slate-900">
                        Compteurs individuels
                        <span class="text-slate-400 font-normal text-sm ml-1">{{ year }}</span>
                    </h3>
                    <span class="text-xs text-slate-400">
                        {{ employee_stats.length }} employé{{ employee_stats.length > 1 ? 's' : '' }}
                    </span>
                </div>

                <!-- Vide -->
                <div v-if="employee_stats.length === 0"
                     class="flex flex-col items-center justify-center py-16 text-center px-6">
                    <div class="text-5xl mb-3 opacity-50">⏱️</div>
                    <p class="text-base font-medium text-slate-500">Aucune heure sup. en {{ year }}</p>
                    <p class="text-sm text-slate-400 mt-1">
                        Les compteurs apparaîtront dès qu'une déclaration sera approuvée.
                    </p>
                </div>

                <!-- Liste -->
                <ul v-else class="divide-y divide-slate-50">
                    <li
                        v-for="emp in employee_stats"
                        :key="emp.id"
                        class="px-5 py-4"
                    >
                        <div class="flex items-center gap-3 mb-2.5">
                            <!-- Avatar -->
                            <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center
                                        justify-center shrink-0 overflow-hidden">
                                <img v-if="emp.avatar_url" :src="emp.avatar_url"
                                     :alt="emp.full_name" class="w-full h-full object-cover" />
                                <span v-else class="text-primary-700 text-xs font-bold">
                                    {{ emp.initials }}
                                </span>
                            </div>

                            <!-- Nom + badge alerte -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-slate-800 truncate">
                                    {{ emp.full_name }}
                                </p>
                            </div>

                            <!-- Heures / quota -->
                            <div class="text-right shrink-0">
                                <p class="text-sm font-bold text-slate-800 tabular-nums">
                                    {{ formatHours(emp.approved_hours) }}
                                    <span class="text-slate-400 font-normal">/ {{ form.overtime_annual_quota }}h</span>
                                </p>
                                <p v-if="emp.pending_hours > 0"
                                   class="text-xs text-amber-600 font-medium tabular-nums">
                                    + {{ formatHours(emp.pending_hours) }} en attente
                                </p>
                            </div>
                        </div>

                        <!-- Barre de progression -->
                        <div class="relative">
                            <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                <!-- Heures approuvées -->
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="empBarClass(emp.quota_pct)"
                                    :style="{ width: emp.quota_pct + '%' }"
                                />
                            </div>

                            <!-- Seuil d'alerte (trait vertical) -->
                            <div
                                v-if="form.overtime_threshold_alert > 0 && form.overtime_annual_quota > 0"
                                class="absolute top-0 h-2 w-0.5 bg-amber-400 rounded-full"
                                :style="{
                                    left: Math.min(100, Math.round(
                                        ((form.overtime_annual_quota - form.overtime_threshold_alert)
                                        / form.overtime_annual_quota) * 100
                                    )) + '%'
                                }"
                                title="Seuil d'alerte"
                            />
                        </div>

                        <!-- Labels -->
                        <div class="flex justify-between mt-1.5">
                            <span class="text-xs font-semibold"
                                  :class="emp.quota_pct >= 90 ? 'text-danger-600' : emp.quota_pct >= 70 ? 'text-amber-600' : 'text-slate-500'">
                                {{ emp.quota_pct }}%
                            </span>
                            <span class="text-xs text-slate-400 tabular-nums">
                                {{ formatHours(emp.remaining_quota) }} restantes
                            </span>
                        </div>

                        <!-- Badge dépassement -->
                        <div v-if="emp.quota_pct >= 100"
                             class="mt-2 inline-flex items-center gap-1.5 px-2.5 py-1 bg-danger-50
                                    text-danger-700 text-xs font-semibold rounded-full">
                            <span class="w-1.5 h-1.5 bg-danger-500 rounded-full" />
                            Contingent atteint
                        </div>
                        <div v-else-if="emp.quota_pct >= (form.overtime_annual_quota > 0
                            ? Math.round(((form.overtime_annual_quota - form.overtime_threshold_alert)
                                / form.overtime_annual_quota) * 100) : 91)"
                             class="mt-2 inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-50
                                    text-amber-700 text-xs font-semibold rounded-full">
                            <span class="w-1.5 h-1.5 bg-amber-400 rounded-full" />
                            Proche du contingent
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- ── Barre de sauvegarde flottante (modif. non sauvegardées) ─────── -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-4"
        >
            <div v-if="form.isDirty"
                 class="hidden lg:flex fixed bottom-6 left-1/2 -translate-x-1/2 z-50
                        items-center gap-4 bg-slate-900 text-white px-5 py-3 rounded-2xl
                        shadow-xl border border-slate-700">
                <p class="text-sm font-medium text-slate-300">Modifications non enregistrées</p>
                <div class="flex gap-2">
                    <button
                        @click="form.reset()"
                        class="px-3 py-1.5 text-sm text-slate-400 hover:text-white
                               border border-slate-600 rounded-xl transition-colors"
                    >
                        Annuler
                    </button>
                    <button
                        @click="save"
                        :disabled="form.processing"
                        class="px-4 py-1.5 bg-primary-500 hover:bg-primary-400 text-white text-sm
                               font-semibold rounded-xl transition-colors disabled:opacity-60"
                    >
                        {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                    </button>
                </div>
            </div>
        </Transition>

    </AppLayout>
</template>
