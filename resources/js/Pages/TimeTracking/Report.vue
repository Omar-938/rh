<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    rows:             { type: Array,  required: true },
    month_label:      { type: String, required: true },
    year:             { type: Number, required: true },
    month:            { type: Number, required: true },
    prev_month:       { type: String, required: true },
    next_month:       { type: String, required: true },
    work_days_month:  { type: Number, required: true },
    totals:           { type: Object, required: true },
})

// ─── Navigation ───────────────────────────────────────────────────────────────
function monthUrl(ym) {
    const [y, m] = ym.split('-')
    return route('time.report') + `?year=${y}&month=${m}`
}

// ─── Recherche / filtre ───────────────────────────────────────────────────────
const search = ref('')
const sortKey = ref('full_name')
const sortAsc = ref(true)

const filteredRows = computed(() => {
    let rows = props.rows

    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        rows = rows.filter(r =>
            r.full_name.toLowerCase().includes(q) ||
            (r.department ?? '').toLowerCase().includes(q)
        )
    }

    return [...rows].sort((a, b) => {
        const av = a[sortKey.value] ?? ''
        const bv = b[sortKey.value] ?? ''
        const cmp = typeof av === 'number'
            ? av - bv
            : String(av).localeCompare(String(bv), 'fr')
        return sortAsc.value ? cmp : -cmp
    })
})

function toggleSort(key) {
    if (sortKey.value === key) {
        sortAsc.value = !sortAsc.value
    } else {
        sortKey.value = key
        sortAsc.value = false // Descendant par défaut pour les chiffres
    }
}

function sortIcon(key) {
    if (sortKey.value !== key) return '↕'
    return sortAsc.value ? '↑' : '↓'
}

// ─── Couleur taux de présence ─────────────────────────────────────────────────
function completionClass(pct) {
    if (pct >= 90) return 'bg-emerald-500'
    if (pct >= 60) return 'bg-amber-400'
    return 'bg-danger-400'
}

function completionTextClass(pct) {
    if (pct >= 90) return 'text-emerald-700'
    if (pct >= 60) return 'text-amber-700'
    return 'text-danger-700'
}

// ─── Impression ───────────────────────────────────────────────────────────────
function printReport() {
    window.print()
}
</script>

<template>
    <Head :title="`Rapport ${month_label}`" />

    <AppLayout :title="`Rapport mensuel — ${month_label}`" :back-url="route('time.history')">

        <!-- ── Barre d'outils ─────────────────────────────────────────────── -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-6 print:hidden">

            <!-- Navigation mois -->
            <div class="flex items-center gap-2">
                <a
                    :href="monthUrl(prev_month)"
                    class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors text-slate-500"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </a>
                <h2 class="text-base font-display font-bold text-slate-900 capitalize min-w-[160px] text-center">
                    {{ month_label }}
                </h2>
                <a
                    :href="monthUrl(next_month)"
                    class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors text-slate-500"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            </div>

            <!-- Recherche -->
            <div class="relative flex-1 max-w-xs">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"
                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
                <input
                    v-model="search"
                    type="search"
                    placeholder="Rechercher un employé…"
                    class="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-xl
                           focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-400"
                />
            </div>

            <div class="flex gap-2 ml-auto">
                <!-- Lien historique individuel -->
                <Link
                    :href="route('time.history', { year, month })"
                    class="px-3 py-2 text-sm font-medium text-slate-600 border border-slate-200
                           rounded-xl hover:bg-slate-50 transition-colors"
                >
                    Mon historique
                </Link>
                <!-- Impression -->
                <button
                    @click="printReport"
                    class="px-3 py-2 text-sm font-medium text-primary-600 border border-primary-200
                           rounded-xl hover:bg-primary-50 transition-colors flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                    </svg>
                    Imprimer
                </button>
            </div>
        </div>

        <!-- ── Totaux équipe ──────────────────────────────────────────────── -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 text-center">
                <p class="text-2xl font-display font-bold text-slate-800">{{ totals.employees }}</p>
                <p class="text-xs text-slate-500 mt-0.5">Employés</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 text-center">
                <p class="text-2xl font-display font-bold text-primary-700">{{ totals.worked_label }}</p>
                <p class="text-xs text-slate-500 mt-0.5">Heures équipe</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 text-center">
                <p class="text-2xl font-display font-bold text-slate-700">{{ totals.avg_per_employee }}</p>
                <p class="text-xs text-slate-500 mt-0.5">Moy. / employé</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 text-center">
                <p class="text-2xl font-display font-bold text-slate-700">{{ totals.total_days }}</p>
                <p class="text-xs text-slate-500 mt-0.5">Jours travaillés</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 text-center">
                <p class="text-2xl font-display font-bold text-emerald-600">{{ totals.overtime_label }}</p>
                <p class="text-xs text-slate-500 mt-0.5">Heures sup.</p>
            </div>
        </div>

        <!-- ── Tableau ────────────────────────────────────────────────────── -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

            <!-- Vide -->
            <div v-if="filteredRows.length === 0"
                 class="py-16 text-center">
                <p class="text-4xl mb-3">🔍</p>
                <p class="text-sm font-medium text-slate-500">Aucun résultat</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/60">
                            <th class="text-left px-5 py-3.5 font-semibold text-slate-600">
                                <button @click="toggleSort('full_name')"
                                        class="flex items-center gap-1 hover:text-slate-900">
                                    Employé <span class="text-slate-400 text-xs">{{ sortIcon('full_name') }}</span>
                                </button>
                            </th>
                            <th class="text-center px-3 py-3.5 font-semibold text-slate-600">
                                <button @click="toggleSort('days_worked')"
                                        class="flex items-center justify-center gap-1 hover:text-slate-900 w-full">
                                    Présence <span class="text-slate-400 text-xs">{{ sortIcon('days_worked') }}</span>
                                </button>
                            </th>
                            <th class="text-center px-3 py-3.5 font-semibold text-slate-600">
                                <button @click="toggleSort('worked_minutes')"
                                        class="flex items-center justify-center gap-1 hover:text-slate-900 w-full">
                                    Total heures <span class="text-slate-400 text-xs">{{ sortIcon('worked_minutes') }}</span>
                                </button>
                            </th>
                            <th class="text-center px-3 py-3.5 font-semibold text-slate-600 hidden sm:table-cell">
                                <button @click="toggleSort('avg_minutes')"
                                        class="flex items-center justify-center gap-1 hover:text-slate-900 w-full">
                                    Moy./jour <span class="text-slate-400 text-xs">{{ sortIcon('avg_minutes') }}</span>
                                </button>
                            </th>
                            <th class="text-center px-3 py-3.5 font-semibold text-slate-600 hidden md:table-cell">
                                <button @click="toggleSort('break_minutes')"
                                        class="flex items-center justify-center gap-1 hover:text-slate-900 w-full">
                                    Pauses <span class="text-slate-400 text-xs">{{ sortIcon('break_minutes') }}</span>
                                </button>
                            </th>
                            <th class="text-center px-3 py-3.5 font-semibold text-slate-600 hidden md:table-cell">
                                <button @click="toggleSort('overtime_minutes')"
                                        class="flex items-center justify-center gap-1 hover:text-slate-900 w-full">
                                    Heures sup. <span class="text-slate-400 text-xs">{{ sortIcon('overtime_minutes') }}</span>
                                </button>
                            </th>
                            <th class="text-right px-5 py-3.5 font-semibold text-slate-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr
                            v-for="row in filteredRows"
                            :key="row.user_id"
                            class="hover:bg-slate-50/60 transition-colors"
                        >
                            <!-- Employé -->
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-xs font-bold overflow-hidden"
                                         :class="row.avatar_url ? '' : 'bg-primary-100 text-primary-700'">
                                        <img v-if="row.avatar_url" :src="row.avatar_url"
                                             :alt="row.full_name" class="w-full h-full object-cover" />
                                        <span v-else>{{ row.initials }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800 truncate">{{ row.full_name }}</p>
                                        <p v-if="row.department" class="text-xs text-slate-400 truncate">{{ row.department }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Présence avec barre -->
                            <td class="px-3 py-4 text-center">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="text-sm font-bold tabular-nums"
                                          :class="completionTextClass(row.completion_pct)">
                                        {{ row.days_worked }}<span class="text-xs font-medium text-slate-400">/{{ work_days_month }}j</span>
                                    </span>
                                    <div class="w-16 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all"
                                            :class="completionClass(row.completion_pct)"
                                            :style="{ width: row.completion_pct + '%' }"
                                        />
                                    </div>
                                    <span v-if="row.absence_days > 0"
                                          class="text-[10px] text-danger-500 font-medium">
                                        {{ row.absence_days }}j absent
                                    </span>
                                </div>
                            </td>

                            <!-- Total heures -->
                            <td class="px-3 py-4 text-center">
                                <span class="text-base font-bold text-slate-800 tabular-nums">
                                    {{ row.worked_label }}
                                </span>
                            </td>

                            <!-- Moy. par jour -->
                            <td class="px-3 py-4 text-center hidden sm:table-cell">
                                <span class="text-sm text-slate-600 tabular-nums">{{ row.avg_label }}</span>
                            </td>

                            <!-- Pauses -->
                            <td class="px-3 py-4 text-center hidden md:table-cell">
                                <span class="text-sm text-amber-600 tabular-nums">{{ row.break_label }}</span>
                            </td>

                            <!-- Heures sup -->
                            <td class="px-3 py-4 text-center hidden md:table-cell">
                                <span
                                    class="text-sm font-semibold tabular-nums"
                                    :class="row.overtime_minutes > 0 ? 'text-emerald-600' : 'text-slate-400'"
                                >
                                    {{ row.overtime_label }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-5 py-4 text-right">
                                <Link
                                    :href="route('time.history') + `?year=${year}&month=${month}&user_id=${row.user_id}`"
                                    class="text-xs font-medium text-primary-600 hover:text-primary-700
                                           hover:underline transition-colors whitespace-nowrap"
                                >
                                    Détail →
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Footer infos -->
            <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/40 flex items-center justify-between">
                <p class="text-xs text-slate-400">
                    {{ filteredRows.length }} employé{{ filteredRows.length > 1 ? 's' : '' }}
                    · Objectif {{ work_days_month }} jours ouvrés
                </p>
                <p class="text-xs text-slate-400">
                    Généré le {{ new Date().toLocaleDateString('fr-FR') }}
                </p>
            </div>
        </div>

    </AppLayout>
</template>

<style>
@media print {
    .print\:hidden { display: none !important; }
    body { background: white; }
}
</style>
