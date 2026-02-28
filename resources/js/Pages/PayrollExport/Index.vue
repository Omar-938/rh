<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

// ── Props ──────────────────────────────────────────────────────────────────────
const props = defineProps({
    exports:         { type: Array,   default: () => [] },
    current_export:  { type: Object,  default: null },
    current_period:  { type: String,  required: true },
    current_year:    { type: Number,  required: true },
    available_years: { type: Array,   default: () => [] },
})

// ── Statuts ────────────────────────────────────────────────────────────────────
const STATUS = {
    draft:     { label: 'Brouillon',  bg: 'bg-slate-100',   text: 'text-slate-600',   dot: 'bg-slate-400',   ring: 'ring-slate-200'   },
    validated: { label: 'Validé',     bg: 'bg-blue-50',     text: 'text-blue-700',    dot: 'bg-blue-500',    ring: 'ring-blue-200'    },
    sent:      { label: 'Envoyé',     bg: 'bg-emerald-50',  text: 'text-emerald-700', dot: 'bg-emerald-500', ring: 'ring-emerald-200' },
    corrected: { label: 'Corrigé',    bg: 'bg-amber-50',    text: 'text-amber-700',   dot: 'bg-amber-400',   ring: 'ring-amber-200'   },
}

// ── Formats ────────────────────────────────────────────────────────────────────
const FORMAT_LABELS = { pdf: 'PDF', xlsx: 'Excel', csv: 'CSV' }

// ── Génération ─────────────────────────────────────────────────────────────────
const generating = ref(false)

function generateCurrent() {
    if (generating.value) return
    generating.value = true
    router.post(route('payroll-exports.generate'), { period: props.current_period }, {
        onFinish: () => { generating.value = false },
    })
}

// ── Filtres ────────────────────────────────────────────────────────────────────
function switchYear(year) {
    router.get(route('payroll-exports.index'), { year }, { preserveScroll: true, replace: true })
}

// ── Helpers ────────────────────────────────────────────────────────────────────
const currentPeriodLabel = computed(() => {
    const [y, m] = props.current_period.split('-').map(Number)
    return new Date(y, m - 1).toLocaleString('fr-FR', { month: 'long', year: 'numeric' })
})

// Mois sous forme de "Janvier 2026" depuis "2026-01"
function periodLabel(period) {
    const [y, m] = period.split('-').map(Number)
    return new Date(y, m - 1).toLocaleString('fr-FR', { month: 'long', year: 'numeric' })
}
function periodMonthShort(period) {
    const [y, m] = period.split('-').map(Number)
    return new Date(y, m - 1).toLocaleString('fr-FR', { month: 'short' }).replace('.', '')
}
function periodYear(period) {
    return parseInt(period.split('-')[0])
}
function periodMonthNum(period) {
    return parseInt(period.split('-')[1])
}

// Nombre total de bulletins envoyés cette année
const totalSentThisYear = computed(() =>
    props.exports.filter(e => e.status === 'sent').length
)
</script>

<template>
    <Head title="Export variables de paie" />
    <AppLayout title="Export variables de paie" :back-url="route('dashboard')">

        <!-- ── En-tête ────────────────────────────────────────────────────── -->
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-xl font-display font-bold text-slate-900">Export variables de paie</h2>
                <p class="text-sm text-slate-500 mt-0.5">
                    Variables mensuelles à transmettre à votre comptable
                </p>
            </div>
            <Link :href="route('payroll-exports.index')"
                  class="inline-flex items-center gap-2 px-3.5 py-2 border border-slate-200
                         text-slate-600 text-sm font-medium rounded-xl hover:bg-slate-50 transition-colors self-start">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Historique
            </Link>
        </div>

        <!-- ── Bannière mois courant ──────────────────────────────────────── -->
        <div class="mb-6 rounded-2xl border overflow-hidden"
             :class="current_export
                 ? current_export.status === 'sent'
                     ? 'border-emerald-200 bg-emerald-50'
                     : current_export.status === 'validated'
                         ? 'border-blue-200 bg-blue-50'
                         : 'border-amber-200 bg-amber-50'
                 : 'border-primary-200 bg-primary-50'">

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5">
                <div class="flex items-center gap-4">
                    <!-- Icône mois -->
                    <div class="w-14 h-14 rounded-xl shrink-0 flex flex-col items-center justify-center border-2"
                         :class="current_export
                             ? current_export.status === 'sent'
                                 ? 'bg-emerald-100 border-emerald-300'
                                 : current_export.status === 'validated'
                                     ? 'bg-blue-100 border-blue-300'
                                     : 'bg-amber-100 border-amber-300'
                             : 'bg-primary-100 border-primary-300'">
                        <p class="text-xs font-bold leading-none"
                           :class="current_export
                               ? current_export.status === 'sent' ? 'text-emerald-800'
                                   : current_export.status === 'validated' ? 'text-blue-800'
                                   : 'text-amber-800'
                               : 'text-primary-800'">
                            {{ new Date().toLocaleString('fr-FR', { month: 'short' }).replace('.', '') }}
                        </p>
                        <p class="text-xs font-semibold mt-0.5"
                           :class="current_export
                               ? current_export.status === 'sent' ? 'text-emerald-700'
                                   : current_export.status === 'validated' ? 'text-blue-700'
                                   : 'text-amber-700'
                               : 'text-primary-700'">
                            {{ new Date().getFullYear() }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-bold text-slate-900">
                            {{ currentPeriodLabel.charAt(0).toUpperCase() + currentPeriodLabel.slice(1) }}
                        </p>
                        <p v-if="!current_export" class="text-sm text-primary-700 font-medium mt-0.5">
                            Export non généré — Compilez les variables maintenant
                        </p>
                        <template v-else>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-semibold"
                                      :class="[STATUS[current_export.status]?.bg, STATUS[current_export.status]?.text]">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                          :class="STATUS[current_export.status]?.dot" />
                                    {{ current_export.status_label }}
                                </span>
                                <span class="text-xs text-slate-500">
                                    {{ current_export.lines_count }} employé{{ current_export.lines_count !== 1 ? 's' : '' }}
                                </span>
                                <template v-if="current_export.status === 'sent' && current_export.sent_at">
                                    <span class="text-slate-300">·</span>
                                    <span class="text-xs text-emerald-700">Envoyé le {{ current_export.sent_at }}</span>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Actions mois courant -->
                <div class="flex items-center gap-2 shrink-0">
                    <template v-if="current_export">
                        <Link :href="current_export.show_url"
                              class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200
                                     hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-xl
                                     shadow-sm transition-all active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Voir l'export
                        </Link>
                    </template>
                    <template v-else>
                        <button @click="generateCurrent"
                                :disabled="generating"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600
                                       hover:bg-primary-700 text-white text-sm font-semibold rounded-xl
                                       shadow-sm transition-all active:scale-95 disabled:opacity-60">
                            <svg v-if="generating" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                            </svg>
                            {{ generating ? 'Génération…' : 'Générer l\'export' }}
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- ── Stats rapides ──────────────────────────────────────────────── -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-4 py-3.5">
                <p class="text-xs font-medium text-slate-400 mb-0.5">Exports {{ current_year }}</p>
                <p class="text-2xl font-bold text-slate-900">{{ exports.length }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-4 py-3.5">
                <p class="text-xs font-medium text-slate-400 mb-0.5">Envoyés</p>
                <p class="text-2xl font-bold text-emerald-600">{{ totalSentThisYear }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-4 py-3.5">
                <p class="text-xs font-medium text-slate-400 mb-0.5">Brouillons</p>
                <p class="text-2xl font-bold text-slate-500">
                    {{ exports.filter(e => e.status === 'draft').length }}
                </p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-4 py-3.5">
                <p class="text-xs font-medium text-slate-400 mb-0.5">Validés / En attente</p>
                <p class="text-2xl font-bold text-blue-600">
                    {{ exports.filter(e => e.status === 'validated').length }}
                </p>
            </div>
        </div>

        <!-- ── Filtre année ────────────────────────────────────────────────── -->
        <div v-if="available_years.length > 1" class="flex gap-2 mb-5">
            <button
                v-for="y in available_years" :key="y"
                @click="switchYear(y)"
                class="px-3.5 py-1.5 rounded-xl text-sm font-semibold transition-colors"
                :class="y === current_year
                    ? 'bg-primary-600 text-white'
                    : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'"
            >
                {{ y }}
            </button>
        </div>

        <!-- ── Liste des exports ───────────────────────────────────────────── -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

            <!-- Vide -->
            <div v-if="exports.length === 0"
                 class="flex flex-col items-center justify-center py-20 text-center px-6">
                <div class="w-20 h-20 rounded-2xl bg-slate-50 border border-slate-100 flex items-center
                            justify-center text-4xl mb-4">
                    📊
                </div>
                <p class="text-base font-semibold text-slate-700">Aucun export pour {{ current_year }}</p>
                <p class="text-sm text-slate-400 mt-1 max-w-sm">
                    Générez l'export du mois courant pour commencer à compiler les variables de paie.
                </p>
            </div>

            <!-- Lignes -->
            <ul v-else class="divide-y divide-slate-50">
                <li v-for="exp in exports" :key="exp.id"
                    class="group flex items-center gap-4 px-5 py-4 hover:bg-slate-50/60 transition-colors">

                    <!-- Icône période -->
                    <div class="w-12 h-12 rounded-xl shrink-0 flex flex-col items-center justify-center border"
                         :class="{
                             'bg-emerald-50 border-emerald-200': exp.status === 'sent',
                             'bg-blue-50 border-blue-200':       exp.status === 'validated',
                             'bg-amber-50 border-amber-200':     exp.status === 'corrected',
                             'bg-slate-50 border-slate-200':     exp.status === 'draft',
                         }">
                        <p class="text-xs font-bold leading-none"
                           :class="{
                               'text-emerald-700': exp.status === 'sent',
                               'text-blue-700':    exp.status === 'validated',
                               'text-amber-700':   exp.status === 'corrected',
                               'text-slate-500':   exp.status === 'draft',
                           }">
                            {{ periodMonthShort(exp.period) }}
                        </p>
                        <p class="text-xs font-semibold mt-0.5"
                           :class="{
                               'text-emerald-600': exp.status === 'sent',
                               'text-blue-600':    exp.status === 'validated',
                               'text-amber-600':   exp.status === 'corrected',
                               'text-slate-400':   exp.status === 'draft',
                           }">
                            {{ periodYear(exp.period) }}
                        </p>
                    </div>

                    <!-- Infos -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-sm font-semibold text-slate-800 capitalize">
                                {{ exp.period_label }}
                            </p>

                            <!-- Badge statut -->
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-semibold"
                                  :class="[STATUS[exp.status]?.bg, STATUS[exp.status]?.text]">
                                <span class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                                      :class="STATUS[exp.status]?.dot" />
                                {{ exp.status_label }}
                            </span>

                            <!-- Badge correction -->
                            <span v-if="exp.is_correction"
                                  class="px-2 py-0.5 bg-orange-50 text-orange-700 text-xs font-semibold rounded-full">
                                Correction
                            </span>

                            <!-- Badge format -->
                            <span class="px-2 py-0.5 bg-slate-100 text-slate-500 text-xs font-mono rounded-full">
                                {{ FORMAT_LABELS[exp.format] ?? exp.format }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2 mt-0.5 text-xs text-slate-400 flex-wrap">
                            <span>{{ exp.lines_count }} employé{{ exp.lines_count !== 1 ? 's' : '' }}</span>

                            <template v-if="exp.validated_at">
                                <span class="text-slate-200">·</span>
                                <span class="text-blue-600">Validé le {{ exp.validated_at }}</span>
                            </template>

                            <template v-if="exp.sent_at">
                                <span class="text-slate-200">·</span>
                                <span class="text-emerald-600">Envoyé le {{ exp.sent_at }}</span>
                            </template>

                            <template v-if="!exp.validated_at && !exp.sent_at">
                                <span class="text-slate-200">·</span>
                                <span>Créé le {{ exp.created_at }}</span>
                            </template>

                            <template v-if="exp.sent_to?.length">
                                <span class="text-slate-200">·</span>
                                <span>→ {{ exp.sent_to.join(', ') }}</span>
                            </template>
                        </div>
                    </div>

                    <!-- Action -->
                    <div class="flex items-center gap-1.5 shrink-0 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                        <Link :href="exp.show_url"
                              class="flex items-center gap-1.5 px-3 py-1.5 bg-primary-50 hover:bg-primary-100
                                     text-primary-700 text-xs font-semibold rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="hidden sm:inline">Voir</span>
                        </Link>
                    </div>
                </li>
            </ul>
        </div>

        <!-- ── Aide / workflow ────────────────────────────────────────────── -->
        <div class="mt-6 bg-slate-50 rounded-2xl border border-slate-100 p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Workflow export paie</p>
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div v-for="(step, i) in [
                    { n: 1, title: 'Générer',  desc: 'Compile automatiquement les données du mois : congés, HS, présences',  icon: '⚡' },
                    { n: 2, title: 'Vérifier', desc: 'Contrôlez les données, ajoutez les variables et primes manuelles', icon: '✏️' },
                    { n: 3, title: 'Valider',  desc: 'Verrouillez l\'export une fois toutes les données confirmées', icon: '✅' },
                    { n: 4, title: 'Envoyer',  desc: 'Envoyez par email au comptable avec le fichier PDF / Excel en pièce jointe', icon: '📬' },
                ]" :key="step.n"
                     class="flex gap-3 p-3.5 bg-white rounded-xl border border-slate-100">
                    <div class="w-8 h-8 rounded-lg bg-primary-50 text-primary-700 text-sm font-bold
                                flex items-center justify-center shrink-0">
                        {{ step.n }}
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-800">{{ step.title }}</p>
                        <p class="text-xs text-slate-400 mt-0.5 leading-relaxed">{{ step.desc }}</p>
                    </div>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
