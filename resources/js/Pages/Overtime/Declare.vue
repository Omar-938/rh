<script setup>
import { computed, ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    default_date: { type: String, required: true },
})

const form = useForm({
    date:         props.default_date,
    hours:        '',
    rate:         '25',
    reason:       '',
    compensation: 'payment',
})

// ─── Conversion durée ─────────────────────────────────────────────────────────
// Permet de saisir "2h30" ou "2.5" ou "150" (minutes)
const hoursInput = ref('')

function parseHoursInput(val) {
    const s = val.trim()
    // Format "2h30" ou "2H30"
    const hm = s.match(/^(\d+)[hH](\d{0,2})$/)
    if (hm) {
        const h = parseInt(hm[1], 10)
        const m = parseInt(hm[2] || '0', 10)
        return Math.round((h + m / 60) * 100) / 100
    }
    // Format décimal "2.5"
    const dec = parseFloat(s.replace(',', '.'))
    if (!isNaN(dec)) return Math.round(dec * 100) / 100
    return null
}

const parsedHours = computed(() => parseHoursInput(hoursInput.value))
const hoursError  = computed(() => {
    if (!hoursInput.value) return null
    if (parsedHours.value === null) return 'Format invalide (ex: 2h30, 1.5 ou 2)'
    if (parsedHours.value < 0.25)  return 'Minimum 15 minutes (0.25h)'
    if (parsedHours.value > 12)    return 'Maximum 12 heures par déclaration'
    return null
})

const hoursLabel = computed(() => {
    if (!parsedHours.value) return null
    const h = Math.floor(parsedHours.value)
    const m = Math.round((parsedHours.value - h) * 60)
    return h > 0 ? `${h}h${String(m).padStart(2, '0')}` : `${m} min`
})

function syncHours() {
    form.hours = parsedHours.value !== null ? parsedHours.value : ''
}

// ─── Prévisualisation du montant ──────────────────────────────────────────────
// (indicatif uniquement — le vrai calcul est fait côté RH)
const rateOptions = [
    { value: '25', label: '+25%', desc: 'Heures 36 à 43 / sem.', color: 'text-amber-700', bg: 'bg-amber-50 border-amber-200' },
    { value: '50', label: '+50%', desc: 'Heures 44+ / sem.',      color: 'text-danger-700', bg: 'bg-danger-50 border-danger-200' },
]

const compensationOptions = [
    {
        value: 'payment',
        icon:  '💰',
        label: 'Paiement majoré',
        desc:  'Les heures seront intégrées à votre prochain bulletin de paie avec la majoration applicable.',
    },
    {
        value: 'rest',
        icon:  '😴',
        label: 'Repos compensateur',
        desc:  'Vous récupérerez ces heures sous forme de repos, selon accord avec votre responsable.',
    },
]

// ─── Soumission ───────────────────────────────────────────────────────────────
const isValid = computed(() =>
    form.date &&
    parsedHours.value !== null &&
    !hoursError.value &&
    form.reason.length >= 5 &&
    form.rate &&
    form.compensation
)

function submit() {
    if (!isValid.value) return
    form.hours = parsedHours.value
    form.post(route('overtime.store'))
}
</script>

<template>
    <Head title="Déclarer des heures supplémentaires" />

    <AppLayout title="Déclarer des heures supplémentaires" :back-url="route('overtime.index')">

        <div class="max-w-xl mx-auto">

            <!-- ── Breadcrumb ──────────────────────────────────────────────── -->
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-6">
                <a :href="route('overtime.index')" class="hover:text-slate-700 transition-colors">
                    Heures supplémentaires
                </a>
                <span class="text-slate-300">›</span>
                <span class="text-slate-700 font-medium">Nouvelle déclaration</span>
            </div>

            <form @submit.prevent="submit" class="space-y-5">

                <!-- ── Carte principale ───────────────────────────────────── -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-5">

                    <!-- Date -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Date des heures supplémentaires
                        </label>
                        <input
                            v-model="form.date"
                            type="date"
                            :max="new Date().toISOString().split('T')[0]"
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm
                                   focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-400
                                   transition-colors"
                            :class="{ 'border-danger-400 bg-danger-50/30': form.errors.date }"
                        />
                        <p v-if="form.errors.date" class="text-xs text-danger-600 mt-1.5">{{ form.errors.date }}</p>
                    </div>

                    <!-- Durée -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Durée des heures supplémentaires
                        </label>
                        <div class="relative">
                            <input
                                v-model="hoursInput"
                                @input="syncHours"
                                type="text"
                                placeholder="ex : 2h30  ou  1.5  ou  90"
                                class="w-full px-4 py-3 border rounded-xl text-sm pr-20
                                       focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-400
                                       transition-colors"
                                :class="{
                                    'border-slate-200': !hoursError,
                                    'border-danger-400 bg-danger-50/30': hoursError,
                                    'border-emerald-400': !hoursError && hoursLabel,
                                }"
                            />
                            <!-- Badge durée parsée -->
                            <div v-if="hoursLabel && !hoursError"
                                 class="absolute right-3 top-1/2 -translate-y-1/2 px-2.5 py-1 bg-emerald-100
                                        text-emerald-700 rounded-lg text-xs font-bold">
                                = {{ hoursLabel }}
                            </div>
                        </div>
                        <p class="text-xs text-slate-400 mt-1.5">Formats acceptés : 2h30 · 1.5 · 90 (minutes)</p>
                        <p v-if="hoursError" class="text-xs text-danger-600 mt-1">{{ hoursError }}</p>
                        <p v-if="form.errors.hours" class="text-xs text-danger-600 mt-1">{{ form.errors.hours }}</p>
                    </div>

                    <!-- Taux de majoration -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Taux de majoration
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <button
                                v-for="opt in rateOptions"
                                :key="opt.value"
                                type="button"
                                @click="form.rate = opt.value"
                                class="flex flex-col items-start p-4 rounded-xl border-2 text-left transition-all"
                                :class="form.rate === opt.value
                                    ? opt.bg + ' ' + opt.color + ' border-current shadow-sm'
                                    : 'border-slate-100 text-slate-600 hover:border-slate-200 bg-white'"
                            >
                                <span class="text-xl font-display font-bold">{{ opt.label }}</span>
                                <span class="text-xs mt-0.5 opacity-80">{{ opt.desc }}</span>
                            </button>
                        </div>
                        <p v-if="form.errors.rate" class="text-xs text-danger-600 mt-1">{{ form.errors.rate }}</p>
                    </div>

                    <!-- Motif -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Motif
                            <span class="text-xs font-normal text-slate-400 ml-1">(obligatoire)</span>
                        </label>
                        <textarea
                            v-model="form.reason"
                            rows="3"
                            placeholder="Ex : Réunion client urgente, livraison de projet, permanence…"
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm resize-none
                                   focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-400
                                   transition-colors"
                            :class="{ 'border-danger-400 bg-danger-50/30': form.errors.reason }"
                        />
                        <div class="flex justify-between mt-1">
                            <p v-if="form.errors.reason" class="text-xs text-danger-600">{{ form.errors.reason }}</p>
                            <p class="text-xs text-slate-400 ml-auto">{{ form.reason.length }} / 1000</p>
                        </div>
                    </div>
                </div>

                <!-- ── Mode de compensation ───────────────────────────────── -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                    <p class="text-sm font-semibold text-slate-700 mb-3">Mode de compensation souhaité</p>
                    <div class="space-y-3">
                        <button
                            v-for="opt in compensationOptions"
                            :key="opt.value"
                            type="button"
                            @click="form.compensation = opt.value"
                            class="w-full flex items-start gap-4 p-4 rounded-xl border-2 text-left transition-all"
                            :class="form.compensation === opt.value
                                ? 'border-primary-400 bg-primary-50/40'
                                : 'border-slate-100 hover:border-slate-200'"
                        >
                            <span class="text-2xl shrink-0 mt-0.5">{{ opt.icon }}</span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-semibold text-slate-800">{{ opt.label }}</p>
                                    <!-- Radio visuel -->
                                    <span
                                        class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 ml-auto"
                                        :class="form.compensation === opt.value
                                            ? 'border-primary-500'
                                            : 'border-slate-300'"
                                    >
                                        <span v-if="form.compensation === opt.value"
                                              class="w-2 h-2 rounded-full bg-primary-500" />
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">{{ opt.desc }}</p>
                            </div>
                        </button>
                    </div>
                    <p v-if="form.errors.compensation" class="text-xs text-danger-600 mt-2">{{ form.errors.compensation }}</p>
                    <p class="text-xs text-slate-400 mt-3">
                        ℹ Le choix final est soumis à validation par votre responsable.
                    </p>
                </div>

                <!-- ── Boutons ─────────────────────────────────────────────── -->
                <div class="flex gap-3 pb-6">
                    <a
                        :href="route('overtime.index')"
                        class="flex-1 py-3.5 rounded-xl border border-slate-200 text-slate-600 text-sm
                               font-semibold text-center hover:bg-slate-50 transition-colors"
                    >
                        Annuler
                    </a>
                    <button
                        type="submit"
                        :disabled="!isValid || form.processing"
                        class="flex-1 py-3.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-sm
                               font-semibold transition-all active:scale-[.98] disabled:opacity-50 shadow-sm
                               flex items-center justify-center gap-2"
                    >
                        <span v-if="form.processing"
                              class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" />
                        Envoyer la déclaration
                    </button>
                </div>

            </form>
        </div>

    </AppLayout>
</template>
