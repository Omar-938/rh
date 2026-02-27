<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    calendar:             { type: Array,   required: true },
    month_label:          { type: String,  required: true },
    year:                 { type: Number,  required: true },
    month:                { type: Number,  required: true },
    prev_month:           { type: String,  required: true },
    next_month:           { type: String,  required: true },
    stats:                { type: Object,  required: true },
    employees:            { type: Array,   default: () => [] },
    selected_user_id:     { type: Number,  default: null },
    selected_user_name:   { type: String,  default: null },
    can_select_employee:  { type: Boolean, default: false },
})

const TARGET_MINUTES = 7 * 60

// ─── Navigation mois ──────────────────────────────────────────────────────────
function buildUrl(ym, userId = null) {
    const [y, m] = ym.split('-')
    const params = new URLSearchParams({ year: y, month: m })
    const uid = userId ?? props.selected_user_id
    if (uid) params.set('user_id', uid)
    return route('time.history') + '?' + params.toString()
}

const prevUrl = computed(() => buildUrl(props.prev_month))
const nextUrl = computed(() => buildUrl(props.next_month))

// ─── Sélecteur employé ───────────────────────────────────────────────────────
function selectEmployee(userId) {
    router.get(buildUrl(`${props.year}-${String(props.month).padStart(2, '0')}`, userId || null))
}

// ─── Grille calendrier ────────────────────────────────────────────────────────
const calendarGrid = computed(() => {
    const days = props.calendar
    if (days.length === 0) return []
    const firstDate = new Date(days[0].date + 'T00:00:00')
    let startOffset = firstDate.getDay()
    startOffset = startOffset === 0 ? 6 : startOffset - 1
    const grid = []
    for (let i = 0; i < startOffset; i++) grid.push(null)
    for (const day of days) grid.push(day)
    return grid
})

function statusColor(status) {
    return {
        working:  'bg-primary-100 text-primary-700 border-primary-200',
        on_break: 'bg-amber-100  text-amber-700  border-amber-200',
        done:     'bg-emerald-100 text-emerald-700 border-emerald-200',
        idle:     '',
    }[status] ?? ''
}

function progressWidth(workedMinutes) {
    return Math.min(100, Math.round((workedMinutes / TARGET_MINUTES) * 100)) + '%'
}

// ─── Vue liste ────────────────────────────────────────────────────────────────
const viewMode = ref('calendar') // 'calendar' | 'list'

const listDays = computed(() =>
    props.calendar.filter(d => !d.is_weekend || d.worked_minutes > 0)
)
</script>

<template>
    <Head :title="`Historique — ${month_label}`" />

    <AppLayout :title="selected_user_name ? `Historique de ${selected_user_name}` : 'Mon historique'">

        <!-- ── Sélecteur employé (admin/manager) ────────────────────────── -->
        <div v-if="can_select_employee && employees.length > 0"
             class="mb-5 bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-sm font-medium text-slate-600 shrink-0">Employé :</span>
                <div class="flex flex-wrap gap-2 flex-1">
                    <!-- "Moi-même" -->
                    <button
                        @click="selectEmployee(null)"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-xl text-sm font-medium transition-all border"
                        :class="!selected_user_id || selected_user_id === $page.props.auth.user.id
                            ? 'bg-primary-600 text-white border-primary-600 shadow-sm'
                            : 'bg-white text-slate-600 border-slate-200 hover:border-primary-300 hover:text-primary-700'"
                    >
                        Moi
                    </button>
                    <button
                        v-for="emp in employees.filter(e => e.id !== $page.props.auth.user.id)"
                        :key="emp.id"
                        @click="selectEmployee(emp.id)"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-xl text-sm font-medium transition-all border"
                        :class="selected_user_id === emp.id
                            ? 'bg-primary-600 text-white border-primary-600 shadow-sm'
                            : 'bg-white text-slate-600 border-slate-200 hover:border-primary-300 hover:text-primary-700'"
                    >
                        <span
                            v-if="!emp.avatar_url"
                            class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0"
                            :class="selected_user_id === emp.id ? 'bg-white/20 text-white' : 'bg-primary-100 text-primary-700'"
                        >{{ emp.initials }}</span>
                        <img v-else :src="emp.avatar_url" :alt="emp.full_name"
                             class="w-5 h-5 rounded-full object-cover shrink-0" />
                        {{ emp.full_name }}
                    </button>
                </div>

                <!-- Lien vers rapport équipe -->
                <Link
                    :href="route('time.report', { year, month })"
                    class="ml-auto text-xs text-primary-600 hover:text-primary-700 font-medium shrink-0 whitespace-nowrap"
                >
                    Rapport équipe →
                </Link>
            </div>
        </div>

        <!-- ── Navigation mois + toggle vue ──────────────────────────────── -->
        <div class="flex items-center justify-between mb-6">
            <a :href="prevUrl"
               class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors text-slate-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </a>

            <h2 class="text-lg font-display font-bold text-slate-900 capitalize">{{ month_label }}</h2>

            <div class="flex items-center gap-2">
                <!-- Toggle vue -->
                <div class="flex bg-slate-100 rounded-xl p-0.5 text-xs">
                    <button
                        @click="viewMode = 'calendar'"
                        class="px-3 py-1.5 rounded-lg font-medium transition-all"
                        :class="viewMode === 'calendar' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    >📅</button>
                    <button
                        @click="viewMode = 'list'"
                        class="px-3 py-1.5 rounded-lg font-medium transition-all"
                        :class="viewMode === 'list' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    >≡</button>
                </div>

                <a :href="nextUrl"
                   class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors text-slate-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            </div>
        </div>

        <!-- ── Stats du mois ──────────────────────────────────────────────── -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 text-center">
                <p class="text-2xl font-display font-bold text-primary-700">{{ stats.total_worked_label }}</p>
                <p class="text-xs text-slate-500 mt-0.5">Travaillées</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 text-center">
                <p class="text-2xl font-display font-bold text-slate-700">{{ stats.days_worked }}</p>
                <p class="text-xs text-slate-500 mt-0.5">Jours travaillés</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 text-center">
                <p class="text-2xl font-display font-bold text-amber-600">{{ stats.total_break_label }}</p>
                <p class="text-xs text-slate-500 mt-0.5">En pause</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 text-center">
                <p class="text-2xl font-display font-bold"
                   :class="stats.overtime_minutes > 0 ? 'text-emerald-600' : 'text-slate-400'">
                    {{ stats.overtime_label }}
                </p>
                <p class="text-xs text-slate-500 mt-0.5">Heures sup.</p>
            </div>
        </div>

        <!-- ── Vue calendrier ─────────────────────────────────────────────── -->
        <div v-if="viewMode === 'calendar'"
             class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <!-- En-têtes jours -->
            <div class="grid grid-cols-7 border-b border-slate-100">
                <div
                    v-for="label in ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']"
                    :key="label"
                    class="py-3 text-center text-xs font-medium text-slate-400"
                >{{ label }}</div>
            </div>

            <!-- Grille -->
            <div class="grid grid-cols-7">
                <div
                    v-for="(day, idx) in calendarGrid"
                    :key="idx"
                    class="border-b border-r border-slate-50 min-h-[76px] p-2"
                    :class="{
                        'bg-transparent':    !day,
                        'bg-primary-50/40':  day?.is_today,
                        'bg-slate-50/50':    day?.is_weekend && !day?.is_today,
                    }"
                >
                    <template v-if="day">
                        <div class="flex items-center justify-between mb-1.5">
                            <span
                                class="text-xs font-semibold w-6 h-6 flex items-center justify-center rounded-full"
                                :class="{
                                    'bg-primary-600 text-white':   day.is_today,
                                    'text-slate-400':               day.is_weekend && !day.is_today,
                                    'text-slate-700':               !day.is_weekend && !day.is_today,
                                }"
                            >{{ day.day_num }}</span>

                            <span
                                v-if="day.status !== 'idle'"
                                class="text-[9px] font-semibold px-1.5 py-0.5 rounded-full border"
                                :class="statusColor(day.status)"
                            >
                                {{ day.status === 'done' ? '✓' : day.status === 'working' ? '⏱' : '☕' }}
                            </span>
                        </div>

                        <template v-if="day.worked_minutes > 0">
                            <p class="text-xs font-semibold text-slate-700 tabular-nums">{{ day.duration_label }}</p>
                            <div class="mt-1 h-1 rounded-full bg-slate-100 overflow-hidden">
                                <div
                                    class="h-full rounded-full"
                                    :class="day.status === 'done' ? 'bg-emerald-400' : 'bg-primary-400'"
                                    :style="{ width: progressWidth(day.worked_minutes) }"
                                />
                            </div>
                            <p v-if="day.clock_in" class="text-[10px] text-slate-400 mt-1 tabular-nums">
                                {{ day.clock_in }}{{ day.clock_out ? ' → ' + day.clock_out : ' →…' }}
                            </p>
                        </template>
                        <template v-else-if="!day.is_weekend">
                            <p class="text-[10px] text-slate-300 mt-1">—</p>
                        </template>
                    </template>
                </div>
            </div>
        </div>

        <!-- ── Vue liste ──────────────────────────────────────────────────── -->
        <div v-else class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div v-if="listDays.length === 0"
                 class="py-12 text-center text-sm text-slate-400">
                Aucune entrée pour ce mois.
            </div>
            <div v-else class="divide-y divide-slate-50">
                <div
                    v-for="day in listDays"
                    :key="day.date"
                    class="flex items-center gap-4 px-5 py-3.5"
                    :class="{ 'bg-primary-50/30': day.is_today }"
                >
                    <!-- Date -->
                    <div class="w-14 text-center shrink-0">
                        <p class="text-xs font-medium text-slate-400 uppercase">{{ day.day_label }}</p>
                        <p
                            class="text-lg font-display font-bold w-9 h-9 mx-auto flex items-center justify-center rounded-full"
                            :class="day.is_today ? 'bg-primary-600 text-white' : 'text-slate-800'"
                        >{{ day.day_num }}</p>
                    </div>

                    <!-- Statut si pas travaillé -->
                    <div v-if="day.worked_minutes === 0" class="flex-1 text-sm text-slate-400">
                        {{ day.is_weekend ? 'Week-end' : 'Absent / Non pointé' }}
                    </div>

                    <!-- Détail si travaillé -->
                    <template v-else>
                        <div class="flex-1 min-w-0">
                            <!-- Horaires -->
                            <div class="flex items-center gap-2 text-sm font-medium text-slate-700">
                                <span>{{ day.clock_in }}</span>
                                <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                                <span v-if="day.clock_out">{{ day.clock_out }}</span>
                                <span v-else class="text-slate-400 italic text-xs">en cours…</span>
                            </div>
                            <!-- Barre -->
                            <div class="mt-1.5 h-1.5 rounded-full bg-slate-100 overflow-hidden w-48">
                                <div
                                    class="h-full rounded-full"
                                    :class="day.status === 'done' ? 'bg-emerald-400' : 'bg-primary-400'"
                                    :style="{ width: progressWidth(day.worked_minutes) }"
                                />
                            </div>
                        </div>

                        <!-- Durée + pause -->
                        <div class="text-right shrink-0">
                            <p class="text-sm font-bold text-slate-800 tabular-nums">{{ day.duration_label }}</p>
                            <p v-if="day.total_break > 0" class="text-xs text-amber-500 tabular-nums">
                                ☕ {{ Math.floor(day.total_break / 60) > 0
                                    ? Math.floor(day.total_break / 60) + 'h' + String(day.total_break % 60).padStart(2,'0')
                                    : day.total_break + ' min' }}
                            </p>
                        </div>

                        <!-- Badge statut -->
                        <span
                            class="text-xs font-semibold px-2.5 py-1 rounded-full border shrink-0"
                            :class="statusColor(day.status)"
                        >
                            {{ day.status === 'done' ? 'Terminé' : day.status === 'working' ? 'En cours' : 'En pause' }}
                        </span>
                    </template>
                </div>
            </div>
        </div>

        <!-- ── Liens bas de page ──────────────────────────────────────────── -->
        <div class="mt-5 flex items-center justify-between text-sm">
            <a
                :href="route('time.clock')"
                class="text-slate-500 hover:text-slate-700 font-medium transition-colors"
            >
                ← Retour au pointage
            </a>
            <Link
                v-if="can_select_employee"
                :href="route('time.report', { year, month })"
                class="text-primary-600 hover:text-primary-700 font-medium transition-colors"
            >
                Rapport équipe →
            </Link>
        </div>

    </AppLayout>
</template>
