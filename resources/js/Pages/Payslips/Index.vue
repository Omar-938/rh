<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

// ── Props ──────────────────────────────────────────────────────────────────────
const props = defineProps({
    payslips:        { type: Object,  required: true },
    employees:       { type: Array,   default: () => [] },
    counts:          { type: Object,  required: true },
    available_years: { type: Array,   default: () => [] },
    is_uploader:     { type: Boolean, default: false },
    filters:         { type: Object,  required: true },
})

// ── Constantes ─────────────────────────────────────────────────────────────────
const MONTHS = [
    { value: 0,  label: 'Tous les mois' },
    { value: 1,  label: 'Janvier'   }, { value: 2,  label: 'Février'    },
    { value: 3,  label: 'Mars'      }, { value: 4,  label: 'Avril'      },
    { value: 5,  label: 'Mai'       }, { value: 6,  label: 'Juin'       },
    { value: 7,  label: 'Juillet'   }, { value: 8,  label: 'Août'       },
    { value: 9,  label: 'Septembre' }, { value: 10, label: 'Octobre'    },
    { value: 11, label: 'Novembre'  }, { value: 12, label: 'Décembre'   },
]

const STATUS_TABS = [
    { key: 'all',         label: 'Tous',            countKey: 'all'         },
    { key: 'distributed', label: 'Distribués',      countKey: 'distributed' },
    { key: 'draft',       label: 'Non distribués',  countKey: 'draft'       },
]

// ── Navigation ─────────────────────────────────────────────────────────────────
function applyFilter(overrides) {
    router.get(
        route('payslips.index'),
        { ...props.filters, page: undefined, ...overrides },
        { preserveScroll: true, replace: true }
    )
}

// ── État suppression ────────────────────────────────────────────────────────────
const confirmId  = ref(null)
const deleting   = ref(false)

function askDelete(id)  { confirmId.value = id }
function cancelDelete() { confirmId.value = null }

function confirmDelete() {
    if (!confirmId.value || deleting.value) return
    deleting.value = true
    router.delete(route('payslips.destroy', confirmId.value), {
        preserveScroll: true,
        onFinish: () => { deleting.value = false; confirmId.value = null },
    })
}

// ── État distribution ───────────────────────────────────────────────────────────
const distributing    = ref([])   // IDs en cours de distribution unitaire
const distributingAll = ref(false)

function isDistributing(id) {
    return distributing.value.includes(id)
}

function distributeOne(payslipId) {
    if (isDistributing(payslipId)) return
    distributing.value.push(payslipId)
    router.post(route('payslips.distribute', payslipId), {}, {
        preserveScroll: true,
        onFinish: () => {
            distributing.value = distributing.value.filter(id => id !== payslipId)
        },
    })
}

// IDs des brouillons sur la page courante
const draftIds = computed(() =>
    props.payslips.data.filter(p => p.is_draft).map(p => p.id)
)

function distributeAll() {
    if (distributingAll.value || draftIds.value.length === 0) return
    distributingAll.value = true
    router.post(route('payslips.distribute-many'), { ids: draftIds.value }, {
        preserveScroll: true,
        onFinish: () => { distributingAll.value = false },
    })
}

// ── Groupement par année ────────────────────────────────────────────────────────
const groupedByYear = computed(() => {
    const groups = []
    let currentYear = null
    for (const p of props.payslips.data) {
        if (p.period_year !== currentYear) {
            currentYear = p.period_year
            groups.push({ year: p.period_year, items: [] })
        }
        groups[groups.length - 1].items.push(p)
    }
    return groups
})

// ── Récapitulatif mensuel (vue employé) ─────────────────────────────────────────
const monthCoverage = computed(() => {
    if (props.is_uploader) return null
    const year = parseInt(props.filters.year)
    const covered = new Set(
        props.payslips.data
            .filter(p => p.period_year === year && p.is_distributed)
            .map(p => p.period_month)
    )
    return Array.from({ length: 12 }, (_, i) => ({
        month: i + 1,
        label: new Date(year, i).toLocaleString('fr-FR', { month: 'short' }),
        covered: covered.has(i + 1),
    }))
})
</script>

<template>
    <Head title="Bulletins de paie" />
    <AppLayout title="Bulletins de paie">

        <!-- ── En-tête ────────────────────────────────────────────────────── -->
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-xl font-display font-bold text-slate-900">Bulletins de paie</h2>
                <p class="text-sm text-slate-500 mt-0.5">
                    {{ counts.all }} bulletin{{ counts.all !== 1 ? 's' : '' }}
                    <span v-if="counts.unread > 0" class="ml-1.5 inline-flex items-center gap-1 text-primary-600 font-medium">
                        · {{ counts.unread }} non lu{{ counts.unread > 1 ? 's' : '' }}
                    </span>
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
                <!-- Distribuer tout (admin/manager, brouillons sur la page) -->
                <button v-if="is_uploader && draftIds.length > 0"
                        @click="distributeAll"
                        :disabled="distributingAll"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700
                               text-white text-sm font-semibold rounded-xl shadow-sm transition-all
                               active:scale-95 disabled:opacity-60">
                    <svg v-if="distributingAll" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                    </svg>
                    {{ distributingAll ? 'Distribution…' : `Distribuer (${draftIds.length})` }}
                </button>
                <!-- Importer -->
                <Link v-if="is_uploader"
                      :href="route('payslips.create')"
                      class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600 hover:bg-primary-700
                             text-white text-sm font-semibold rounded-xl shadow-sm transition-all
                             active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                    </svg>
                    Importer
                </Link>
            </div>
        </div>

        <!-- ── Récapitulatif mensuel (employé) ───────────────────────────── -->
        <div v-if="monthCoverage && counts.all > 0"
             class="mb-5 bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">
                Récapitulatif {{ filters.year }}
            </p>
            <div class="grid grid-cols-6 sm:grid-cols-12 gap-1.5">
                <div v-for="m in monthCoverage" :key="m.month"
                     class="flex flex-col items-center gap-1 py-2 rounded-xl transition-colors"
                     :class="m.covered
                         ? 'bg-emerald-50 border border-emerald-200'
                         : 'bg-slate-50 border border-slate-100'">
                    <div class="w-2 h-2 rounded-full"
                         :class="m.covered ? 'bg-emerald-500' : 'bg-slate-300'" />
                    <span class="text-xs font-medium"
                          :class="m.covered ? 'text-emerald-700' : 'text-slate-400'">
                        {{ m.label }}
                    </span>
                </div>
            </div>
            <p class="text-xs text-slate-400 mt-2.5">
                {{ monthCoverage.filter(m => m.covered).length }} mois reçu{{ monthCoverage.filter(m => m.covered).length > 1 ? 's' : '' }}
                sur 12 pour {{ filters.year }}
            </p>
        </div>

        <!-- ── Filtres ────────────────────────────────────────────────────── -->
        <div class="flex flex-wrap items-center gap-3 mb-5">

            <!-- Année -->
            <div class="relative">
                <select
                    :value="filters.year"
                    @change="applyFilter({ year: $event.target.value })"
                    class="appearance-none pl-3 pr-8 py-2 border border-slate-200 rounded-xl text-sm font-medium
                           text-slate-700 bg-white hover:border-slate-300 focus:outline-none
                           focus:ring-2 focus:ring-primary-300 cursor-pointer transition-colors"
                >
                    <option v-for="y in available_years" :key="y" :value="y">{{ y }}</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                    </svg>
                </div>
            </div>

            <!-- Mois -->
            <div class="relative">
                <select
                    :value="filters.month"
                    @change="applyFilter({ month: $event.target.value })"
                    class="appearance-none pl-3 pr-8 py-2 border border-slate-200 rounded-xl text-sm font-medium
                           text-slate-700 bg-white hover:border-slate-300 focus:outline-none
                           focus:ring-2 focus:ring-primary-300 cursor-pointer transition-colors"
                    :class="parseInt(filters.month) > 0 ? 'border-primary-300 bg-primary-50 text-primary-700' : ''"
                >
                    <option v-for="m in MONTHS" :key="m.value" :value="m.value">{{ m.label }}</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                    </svg>
                </div>
            </div>

            <!-- Employé (admin/manager) -->
            <div v-if="is_uploader && employees.length > 0" class="relative">
                <select
                    :value="filters.user_id"
                    @change="applyFilter({ user_id: $event.target.value })"
                    class="appearance-none pl-3 pr-8 py-2 border border-slate-200 rounded-xl text-sm font-medium
                           text-slate-700 bg-white hover:border-slate-300 focus:outline-none
                           focus:ring-2 focus:ring-primary-300 cursor-pointer transition-colors"
                    :class="filters.user_id ? 'border-primary-300 bg-primary-50 text-primary-700' : ''"
                >
                    <option value="">Tous les employés</option>
                    <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                    </svg>
                </div>
            </div>

            <!-- Onglets statut -->
            <div class="flex rounded-xl border border-slate-200 overflow-hidden bg-white ml-auto">
                <button
                    v-for="tab in STATUS_TABS"
                    :key="tab.key"
                    @click="applyFilter({ status: tab.key })"
                    class="px-3.5 py-2 text-xs font-semibold transition-colors flex items-center gap-1.5"
                    :class="filters.status === tab.key
                        ? 'bg-primary-600 text-white'
                        : 'text-slate-600 hover:bg-slate-50'"
                >
                    {{ tab.label }}
                    <span v-if="counts[tab.countKey] > 0"
                          class="px-1.5 py-0.5 rounded-full text-xs"
                          :class="filters.status === tab.key ? 'bg-white/25 text-white' : 'bg-slate-100'">
                        {{ counts[tab.countKey] }}
                    </span>
                </button>
            </div>
        </div>

        <!-- ── Liste des bulletins ─────────────────────────────────────────── -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

            <!-- Vide -->
            <div v-if="payslips.data.length === 0"
                 class="flex flex-col items-center justify-center py-20 text-center px-6">
                <div class="w-20 h-20 rounded-2xl bg-slate-50 border border-slate-100 flex items-center
                            justify-center text-4xl mb-4">
                    💰
                </div>
                <p class="text-base font-semibold text-slate-700">Aucun bulletin</p>
                <p class="text-sm text-slate-400 mt-1 mb-5">
                    {{ is_uploader
                        ? 'Importez vos premiers bulletins de paie.'
                        : 'Aucun bulletin disponible pour cette période.' }}
                </p>
                <Link v-if="is_uploader"
                      :href="route('payslips.create')"
                      class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white
                             text-sm font-semibold rounded-xl hover:bg-primary-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                    </svg>
                    Importer des bulletins
                </Link>
            </div>

            <!-- Groupes par année -->
            <template v-else>
                <template v-for="group in groupedByYear" :key="group.year">

                    <!-- Séparateur d'année -->
                    <div v-if="groupedByYear.length > 1"
                         class="px-5 py-2 bg-slate-50/70 border-b border-slate-100 flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                            {{ group.year }}
                        </span>
                        <div class="flex-1 h-px bg-slate-200/80" />
                        <span class="text-xs text-slate-400">
                            {{ group.items.length }} bulletin{{ group.items.length > 1 ? 's' : '' }}
                        </span>
                    </div>

                    <!-- Lignes -->
                    <ul class="divide-y divide-slate-50">
                        <li v-for="p in group.items"
                            :key="p.id"
                            class="group flex items-center gap-4 px-5 py-4 hover:bg-slate-50/60 transition-colors">

                            <!-- Icône période -->
                            <div class="w-12 h-12 rounded-xl shrink-0 flex flex-col items-center justify-center border"
                                 :class="p.is_distributed
                                     ? 'bg-emerald-50 border-emerald-200'
                                     : 'bg-slate-50 border-slate-200'">
                                <p class="text-xs font-bold leading-none"
                                   :class="p.is_distributed ? 'text-emerald-700' : 'text-slate-500'">
                                    {{ new Date(p.period_year, p.period_month - 1).toLocaleString('fr-FR', { month: 'short' }).replace('.', '') }}
                                </p>
                                <p class="text-xs font-semibold mt-0.5"
                                   :class="p.is_distributed ? 'text-emerald-600' : 'text-slate-400'">
                                    {{ p.period_year }}
                                </p>
                            </div>

                            <!-- Infos -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ p.period_label }}
                                    </p>
                                    <!-- Badge "Nouveau" si non lu (employé) -->
                                    <span v-if="!p.is_viewed && p.is_distributed && !is_uploader"
                                          class="px-2 py-0.5 bg-primary-100 text-primary-700 text-xs font-bold rounded-full">
                                        Nouveau
                                    </span>
                                    <!-- Statut (admin/manager) -->
                                    <span v-if="is_uploader"
                                          class="px-2 py-0.5 rounded-full text-xs font-semibold"
                                          :class="p.is_distributed
                                              ? 'bg-emerald-50 text-emerald-700'
                                              : 'bg-amber-50 text-amber-700'">
                                        {{ p.status_label }}
                                    </span>
                                    <!-- Vu par l'employé (admin) -->
                                    <span v-if="is_uploader && p.is_distributed && p.is_viewed"
                                          class="px-2 py-0.5 bg-slate-100 text-slate-500 text-xs rounded-full flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        Lu
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 mt-0.5 text-xs text-slate-400 flex-wrap">
                                    <span v-if="p.user_name" class="text-primary-600 font-medium">{{ p.user_name }}</span>
                                    <span v-else class="text-slate-400 italic">Non associé</span>
                                    <span class="text-slate-200">·</span>
                                    <span>{{ p.file_size_label }}</span>
                                    <span class="text-slate-200">·</span>
                                    <span>Importé le {{ p.created_at }}</span>
                                    <template v-if="p.is_distributed && p.distributed_at">
                                        <span class="text-slate-200">·</span>
                                        <span class="text-emerald-600">Distribué le {{ p.distributed_at }}</span>
                                    </template>
                                    <template v-if="p.is_viewed && p.viewed_at">
                                        <span class="text-slate-200">·</span>
                                        <span>Lu le {{ p.viewed_at }}</span>
                                    </template>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-1.5 shrink-0 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                                <!-- Distribuer (admin/manager, brouillon seulement) -->
                                <button v-if="is_uploader && p.is_draft"
                                        @click="distributeOne(p.id)"
                                        :disabled="isDistributing(p.id)"
                                        class="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100
                                               text-emerald-700 text-xs font-semibold rounded-lg transition-colors
                                               disabled:opacity-60"
                                        title="Distribuer ce bulletin">
                                    <svg v-if="isDistributing(p.id)"
                                         class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                                    </svg>
                                    <span class="hidden sm:inline">Distribuer</span>
                                </button>
                                <!-- Télécharger (employé si distribué, admin toujours) -->
                                <a v-if="p.is_distributed || is_uploader"
                                   :href="p.download_url"
                                   :download="p.original_filename"
                                   class="flex items-center gap-1.5 px-3 py-1.5 bg-primary-50 hover:bg-primary-100
                                          text-primary-700 text-xs font-semibold rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                    </svg>
                                    <span class="hidden sm:inline">Télécharger</span>
                                </a>
                                <!-- Supprimer (admin/manager) -->
                                <button v-if="is_uploader"
                                        @click="askDelete(p.id)"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg
                                               hover:bg-red-50 text-slate-300 hover:text-red-500 transition-colors"
                                        title="Supprimer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                    </svg>
                                </button>
                            </div>
                        </li>
                    </ul>
                </template>
            </template>

            <!-- Pagination -->
            <div v-if="payslips.last_page > 1"
                 class="flex items-center justify-between px-5 py-3.5 border-t border-slate-100">
                <p class="text-sm text-slate-500">
                    {{ payslips.from }}–{{ payslips.to }} sur {{ payslips.total }}
                </p>
                <div class="flex gap-1.5">
                    <Link v-if="payslips.prev_page_url" :href="payslips.prev_page_url"
                          class="px-3 py-1.5 text-sm border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors text-slate-600">
                        ← Préc.
                    </Link>
                    <template v-for="page in payslips.links.slice(1, -1)" :key="page.label">
                        <Link v-if="page.url" :href="page.url"
                              class="px-3 py-1.5 text-sm border rounded-lg transition-colors"
                              :class="page.active
                                  ? 'bg-primary-600 border-primary-600 text-white font-semibold'
                                  : 'border-slate-200 hover:bg-slate-50 text-slate-600'">
                            {{ page.label }}
                        </Link>
                        <span v-else class="px-2 py-1.5 text-sm text-slate-400">…</span>
                    </template>
                    <Link v-if="payslips.next_page_url" :href="payslips.next_page_url"
                          class="px-3 py-1.5 text-sm border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors text-slate-600">
                        Suiv. →
                    </Link>
                </div>
            </div>
        </div>

        <!-- ── Modal suppression ───────────────────────────────────────────── -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0" enter-to-class="opacity-100"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="confirmId !== null"
                     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center
                            bg-slate-900/60 backdrop-blur-sm p-4"
                     @click.self="cancelDelete">
                    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 border border-slate-100">
                        <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                </svg>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 text-center mb-1.5">Supprimer ce bulletin ?</h3>
                        <p class="text-sm text-slate-500 text-center mb-6">
                            Le fichier PDF chiffré sera supprimé définitivement.
                        </p>
                        <div class="flex gap-3">
                            <button @click="cancelDelete"
                                    class="flex-1 py-2.5 border border-slate-200 text-slate-700 text-sm
                                           font-semibold rounded-xl hover:bg-slate-50 transition-colors">
                                Annuler
                            </button>
                            <button @click="confirmDelete" :disabled="deleting"
                                    class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm
                                           font-semibold rounded-xl transition-colors disabled:opacity-60
                                           flex items-center justify-center gap-2">
                                <svg v-if="deleting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                {{ deleting ? 'Suppression…' : 'Supprimer' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </AppLayout>
</template>
