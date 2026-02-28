<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    calendar_days: { type: Array,  default: () => [] },
    month_label:   { type: String, required: true },
    month_start:   { type: String, required: true },
    prev_month:    { type: String, required: true },
    next_month:    { type: String, required: true },
    can_edit:      { type: Boolean, default: false },
})

const typeColors = {
    work:     { bg: '#DCFCE7', text: '#15803D', emoji: '💼' },
    remote:   { bg: '#DBEAFE', text: '#1D4ED8', emoji: '🏠' },
    leave:    { bg: '#FEF3C7', text: '#92400E', emoji: '🌴' },
    sick:     { bg: '#FEE2E2', text: '#991B1B', emoji: '🤒' },
    training: { bg: '#EDE9FE', text: '#5B21B6', emoji: '📚' },
    off:      { bg: '#F1F5F9', text: '#64748B', emoji: '😴' },
}

const weekDayHeaders = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']

// Calcul de la semaine à afficher quand on clique sur un jour
function weekForDay(dateStr) {
    const d = new Date(dateStr + 'T00:00:00')
    const day = d.getDay() // 0=Sun, 1=Mon...
    const diff = (day === 0 ? -6 : 1 - day) // offset vers lundi
    const monday = new Date(d)
    monday.setDate(d.getDate() + diff)
    return monday.toISOString().slice(0, 10)
}

// Stats globales du mois
const monthStats = computed(() => {
    const totals = { work: 0, remote: 0, leave: 0, sick: 0, off: 0, training: 0, total: 0 }
    props.calendar_days.forEach(day => {
        if (!day.in_month) return
        Object.keys(totals).forEach(k => {
            if (k !== 'total') totals[k] += day.stats[k] ?? 0
        })
        totals.total += day.stats.total ?? 0
    })
    return totals
})

// Semaines du calendrier (groupes de 7)
const weeks = computed(() => {
    const days = props.calendar_days
    const result = []
    for (let i = 0; i < days.length; i += 7) {
        result.push(days.slice(i, i + 7))
    }
    return result
})

// Dominante couleur pour un jour
function dayDominantStyle(day) {
    if (!day.in_month || day.stats.total === 0) return null
    const order = ['sick', 'leave', 'work', 'remote', 'training', 'off']
    for (const k of order) {
        if ((day.stats[k] ?? 0) > 0) return typeColors[k]
    }
    return null
}
</script>

<template>
    <Head :title="`Planning — ${month_label}`" />

    <AppLayout title="Planning" :back-url="route('dashboard')">

        <!-- ── Toolbar ── -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-6">
            <!-- Navigation mois -->
            <div class="flex items-center gap-2">
                <Link
                    :href="route('planning.month', { date: prev_month })"
                    class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 transition-all"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </Link>
                <Link
                    :href="route('planning.month', { date: new Date().toISOString().slice(0,10) })"
                    class="px-3 py-2 text-xs font-semibold border border-slate-200 rounded-xl
                           hover:bg-slate-50 text-slate-600 transition-all"
                >
                    Ce mois
                </Link>
                <Link
                    :href="route('planning.month', { date: next_month })"
                    class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 transition-all"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </Link>
            </div>

            <!-- Titre -->
            <h2 class="flex-1 text-xl font-display font-bold text-slate-900">{{ month_label }}</h2>

            <!-- Switch Semaine / Mois -->
            <div class="flex items-center rounded-xl border border-slate-200 p-1 bg-slate-50 self-start sm:self-auto">
                <Link
                    :href="route('planning.week', { date: month_start })"
                    class="px-3 py-1.5 text-sm font-medium text-slate-500 hover:text-slate-700 transition-colors"
                >
                    Semaine
                </Link>
                <span class="px-3 py-1.5 rounded-lg bg-white shadow-sm text-sm font-semibold text-primary-700">
                    Mois
                </span>
            </div>
        </div>

        <!-- ── Stats du mois ── -->
        <div v-if="monthStats.total > 0"
             class="grid grid-cols-3 sm:grid-cols-6 gap-3 mb-6">
            <div v-for="(cfg, key) in typeColors" :key="key"
                 v-show="(monthStats[key] ?? 0) > 0"
                 class="flex items-center gap-2 bg-white rounded-xl border border-slate-100 px-3 py-2.5">
                <span class="text-lg">{{ cfg.emoji }}</span>
                <div>
                    <p class="text-lg font-display font-bold leading-none" :style="{ color: cfg.text }">
                        {{ monthStats[key] }}
                    </p>
                    <p class="text-[10px] text-slate-400 capitalize">
                        {{ key === 'work' ? 'Travail' : key === 'remote' ? 'Télétravail' : key === 'leave' ? 'Congés' : key === 'sick' ? 'Maladie' : key === 'training' ? 'Formation' : 'Repos' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- ── Grille calendrier ── -->
        <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">

            <!-- En-tête jours de semaine -->
            <div class="grid grid-cols-7 border-b border-slate-100">
                <div
                    v-for="header in weekDayHeaders"
                    :key="header"
                    class="py-3 text-center text-xs font-semibold text-slate-400 uppercase tracking-wider"
                    :class="['Sam', 'Dim'].includes(header) ? 'bg-slate-50/70' : ''"
                >
                    {{ header }}
                </div>
            </div>

            <!-- Semaines -->
            <div class="divide-y divide-slate-100">
                <div
                    v-for="(week, wi) in weeks"
                    :key="wi"
                    class="grid grid-cols-7"
                >
                    <Link
                        v-for="day in week"
                        :key="day.date"
                        :href="route('planning.week', { date: weekForDay(day.date) })"
                        class="relative min-h-[90px] sm:min-h-[110px] p-2 border-r border-slate-50 last:border-r-0
                               transition-colors group"
                        :class="[
                            !day.in_month ? 'bg-slate-50/40' : 'hover:bg-slate-50/70',
                            day.is_weekend ? 'bg-slate-50/60' : '',
                        ]"
                    >
                        <!-- Numéro du jour -->
                        <div class="flex justify-end mb-2">
                            <span
                                :class="[
                                    'w-7 h-7 flex items-center justify-center rounded-full text-sm font-semibold',
                                    day.is_today
                                        ? 'bg-primary-700 text-white'
                                        : day.in_month
                                            ? day.is_weekend ? 'text-slate-400' : 'text-slate-700'
                                            : 'text-slate-300',
                                ]"
                            >
                                {{ day.day }}
                            </span>
                        </div>

                        <!-- Indicateurs de types -->
                        <div v-if="day.in_month && day.stats.total > 0" class="space-y-1">
                            <div
                                v-for="(cfg, key) in typeColors"
                                :key="key"
                                v-if="(day.stats[key] ?? 0) > 0"
                                class="flex items-center gap-1.5 px-1.5 py-0.5 rounded-md text-[10px] font-semibold"
                                :style="{ backgroundColor: cfg.bg, color: cfg.text }"
                            >
                                <span>{{ cfg.emoji }}</span>
                                <span class="hidden sm:inline">{{ day.stats[key] }} pers.</span>
                                <span class="sm:hidden">{{ day.stats[key] }}</span>
                            </div>
                        </div>

                        <!-- Hover : voir la semaine -->
                        <div class="absolute inset-0 flex items-center justify-center opacity-0
                                    group-hover:opacity-100 transition-opacity pointer-events-none">
                            <div class="bg-primary-700/90 text-white text-[10px] font-semibold
                                        px-2 py-1 rounded-lg backdrop-blur-sm">
                                Voir semaine →
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>

        <!-- ── Légende ── -->
        <div class="flex flex-wrap gap-3 mt-4">
            <div v-for="(cfg, key) in typeColors" :key="key"
                 class="flex items-center gap-1.5 text-xs text-slate-500">
                <span class="w-3 h-3 rounded-sm" :style="{ backgroundColor: cfg.bg, outline: `1px solid ${cfg.text}40` }" />
                {{ cfg.emoji }} {{ key === 'work' ? 'Travail' : key === 'remote' ? 'Télétravail' : key === 'leave' ? 'Congé' : key === 'sick' ? 'Maladie' : key === 'training' ? 'Formation' : 'Repos' }}
            </div>
        </div>

    </AppLayout>
</template>
