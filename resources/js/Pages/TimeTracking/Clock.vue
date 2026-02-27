<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    entry:       { type: Object,  default: null },
    week:        { type: Array,   required: true },
    server_time: { type: String,  required: true },
})

// ─── Horloge live ─────────────────────────────────────────────────────────────
const now         = ref(new Date(props.server_time))
const serverDrift = Date.now() - new Date(props.server_time).getTime()
let   tickTimer   = null

onMounted(() => {
    tickTimer = setInterval(() => {
        now.value = new Date(Date.now() - serverDrift)
    }, 1000)
})
onBeforeUnmount(() => clearInterval(tickTimer))

const timeLabel = computed(() =>
    now.value.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
)
const dateLabel = computed(() =>
    now.value.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
)

// ─── Calculs live depuis l'entrée ─────────────────────────────────────────────
const TARGET_MINUTES = 7 * 60  // 7h — sera paramétrable (step 21)

const workedMinutes = computed(() => {
    if (!props.entry?.clock_in_ts) return 0
    const clockIn    = new Date(props.entry.clock_in_ts).getTime()
    const clockOut   = props.entry.clock_out_ts ? new Date(props.entry.clock_out_ts).getTime() : now.value.getTime()
    const totalMs    = clockOut - clockIn
    const breakMs    = (props.entry.total_break_minutes ?? 0) * 60_000
    const currentBreakMs = props.entry.break_start_ts && !props.entry.clock_out_ts
        ? now.value.getTime() - new Date(props.entry.break_start_ts).getTime()
        : 0
    return Math.max(0, Math.floor((totalMs - breakMs - currentBreakMs) / 60_000))
})

const breakMinutes = computed(() => {
    const done  = props.entry?.total_break_minutes ?? 0
    const live  = props.entry?.break_start_ts && !props.entry?.clock_out_ts
        ? Math.floor((now.value.getTime() - new Date(props.entry.break_start_ts).getTime()) / 60_000)
        : 0
    return done + live
})

// Minutes de dépassement live (quand en cours)
const overtimeMinutesLive = computed(() => {
    if (status.value === 'done') return props.entry?.overtime_minutes ?? 0
    return Math.max(0, workedMinutes.value - TARGET_MINUTES)
})

const progress = computed(() => Math.min(1, workedMinutes.value / TARGET_MINUTES))

const durationLabel     = computed(() => minutesToLabel(workedMinutes.value))
const breakLabel        = computed(() => minutesToLabel(breakMinutes.value))
const overtimeLiveLabel = computed(() => minutesToLabel(overtimeMinutesLive.value))

function minutesToLabel(min) {
    if (min <= 0) return '0h00'
    const h = Math.floor(min / 60)
    const m = min % 60
    return h > 0
        ? `${h}h${String(m).padStart(2, '0')}`
        : `${m} min`
}

// ─── Anneau SVG ───────────────────────────────────────────────────────────────
const RADIUS       = 88
const CIRCUMFERENCE = 2 * Math.PI * RADIUS

const dashOffset = computed(() => CIRCUMFERENCE * (1 - progress.value))

// Second arc pour les heures sup (orange, sur l'anneau extérieur)
const RADIUS_OT = 100
const CIRC_OT   = 2 * Math.PI * RADIUS_OT

const overtimeProgress = computed(() =>
    Math.min(1, overtimeMinutesLive.value / (TARGET_MINUTES * 0.5)) // 50% de l'objectif max en OT
)
const dashOffsetOT = computed(() => CIRC_OT * (1 - overtimeProgress.value))

// ─── Statut ───────────────────────────────────────────────────────────────────
const status = computed(() => props.entry?.status ?? 'idle')

const statusConfig = {
    idle:     { label: 'Non démarré',      color: '#94A3B8', textClass: 'text-slate-500' },
    working:  { label: 'En cours',         color: '#2E86C1', textClass: 'text-primary-700' },
    on_break: { label: 'En pause',         color: '#F59E0B', textClass: 'text-amber-700'  },
    done:     { label: 'Journée terminée', color: '#27AE60', textClass: 'text-emerald-700' },
}

const cfg = computed(() => statusConfig[status.value] ?? statusConfig.idle)

// ─── Actions ──────────────────────────────────────────────────────────────────
const loading = ref(false)

function post(routeName) {
    if (loading.value) return
    loading.value = true
    router.post(route(routeName), {}, {
        preserveScroll: true,
        onFinish: () => { loading.value = false },
    })
}

// ─── Semaine ──────────────────────────────────────────────────────────────────
const weekTotal    = computed(() => props.week.reduce((s, d) => s + (d.worked_minutes ?? 0), 0))
const weekLabel    = computed(() => minutesToLabel(weekTotal.value))
const weekOT       = computed(() => props.week.reduce((s, d) => s + (d.overtime_minutes ?? 0), 0))
const weekOTLabel  = computed(() => minutesToLabel(weekOT.value))

// ─── Heures sup auto-détectées ────────────────────────────────────────────────
const autoOvertime = computed(() => props.entry?.auto_overtime ?? null)
</script>

<template>
    <Head title="Pointage" />

    <AppLayout title="Pointage">

        <div class="max-w-lg mx-auto space-y-4">

            <!-- ── Carte horloge principale ──────────────────────────────── -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

                <!-- En-tête date/heure -->
                <div class="px-6 pt-6 pb-3 text-center">
                    <p class="text-sm font-medium text-slate-500 capitalize">{{ dateLabel }}</p>
                    <p class="text-4xl font-display font-bold text-slate-900 tabular-nums tracking-tight mt-1">
                        {{ timeLabel }}
                    </p>
                </div>

                <!-- Anneau de progression SVG -->
                <div class="flex justify-center pb-1">
                    <div class="relative">
                        <svg width="240" height="240" viewBox="0 0 240 240" class="-rotate-90">
                            <!-- Piste de fond principale -->
                            <circle cx="120" cy="120" :r="RADIUS"
                                    fill="none" stroke="#F1F5F9" stroke-width="14"/>
                            <!-- Arc principal (heures normales) -->
                            <circle cx="120" cy="120" :r="RADIUS"
                                    fill="none"
                                    :stroke="cfg.color"
                                    stroke-width="14"
                                    stroke-linecap="round"
                                    :stroke-dasharray="CIRCUMFERENCE"
                                    :stroke-dashoffset="dashOffset"
                                    style="transition: stroke-dashoffset 0.8s ease, stroke 0.4s ease"/>

                            <!-- Arc heures sup (extérieur, orange) — visible si dépassement -->
                            <template v-if="overtimeMinutesLive > 0">
                                <circle cx="120" cy="120" :r="RADIUS_OT"
                                        fill="none" stroke="#FEF3C7" stroke-width="6"/>
                                <circle cx="120" cy="120" :r="RADIUS_OT"
                                        fill="none"
                                        stroke="#F59E0B"
                                        stroke-width="6"
                                        stroke-linecap="round"
                                        :stroke-dasharray="CIRC_OT"
                                        :stroke-dashoffset="dashOffsetOT"
                                        style="transition: stroke-dashoffset 0.8s ease"/>
                            </template>
                        </svg>

                        <!-- Contenu central -->
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-3xl font-display font-bold text-slate-900 tabular-nums">
                                {{ durationLabel }}
                            </span>
                            <span class="text-xs font-medium mt-0.5" :class="cfg.textClass">
                                {{ cfg.label }}
                            </span>
                            <!-- Heures sup en cours -->
                            <span v-if="overtimeMinutesLive >= 15 && status !== 'idle'"
                                  class="text-xs text-amber-600 font-semibold mt-1 bg-amber-50 px-2 py-0.5 rounded-full">
                                +{{ overtimeLiveLabel }} sup.
                            </span>
                            <span v-else-if="status === 'on_break'" class="text-xs text-amber-500 mt-0.5">
                                ☕ {{ breakLabel }}
                            </span>
                            <span v-else-if="status === 'working' && breakMinutes > 0"
                                  class="text-xs text-slate-400 mt-0.5">
                                Pause : {{ breakLabel }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Détails clock_in / clock_out -->
                <div v-if="entry" class="flex justify-center gap-8 pb-3">
                    <div class="text-center">
                        <p class="text-xs text-slate-400">Arrivée</p>
                        <p class="text-base font-semibold text-slate-700">{{ entry.clock_in ?? '—' }}</p>
                    </div>
                    <div v-if="entry.clock_out" class="text-center">
                        <p class="text-xs text-slate-400">Départ</p>
                        <p class="text-base font-semibold text-slate-700">{{ entry.clock_out }}</p>
                    </div>
                    <div v-if="status === 'working'" class="text-center">
                        <p class="text-xs text-slate-400">Objectif</p>
                        <p class="text-base font-semibold text-slate-700">{{ minutesToLabel(TARGET_MINUTES) }}</p>
                    </div>
                </div>

                <!-- Barre d'actions -->
                <div class="px-6 pb-6">
                    <!-- IDLE → Pointer arrivée -->
                    <button
                        v-if="status === 'idle'"
                        @click="post('time.clock-in')"
                        :disabled="loading"
                        class="w-full py-4 rounded-xl font-semibold text-white text-base
                               bg-primary-600 hover:bg-primary-700 active:scale-[.98]
                               disabled:opacity-60 transition-all shadow-sm"
                    >
                        <span v-if="loading" class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin mr-2" />
                        Pointer l'arrivée
                    </button>

                    <!-- WORKING → Pause + Départ -->
                    <div v-else-if="status === 'working'" class="flex gap-3">
                        <button
                            @click="post('time.break-start')"
                            :disabled="loading"
                            class="flex-1 py-3.5 rounded-xl font-semibold text-amber-700
                                   bg-amber-50 hover:bg-amber-100 border border-amber-200
                                   active:scale-[.98] disabled:opacity-60 transition-all text-sm"
                        >
                            ☕ Pause
                        </button>
                        <button
                            @click="post('time.clock-out')"
                            :disabled="loading"
                            class="flex-1 py-3.5 rounded-xl font-semibold text-white
                                   bg-emerald-600 hover:bg-emerald-700
                                   active:scale-[.98] disabled:opacity-60 transition-all text-sm"
                        >
                            <span v-if="loading" class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin mr-1.5" />
                            Pointer le départ
                        </button>
                    </div>

                    <!-- ON_BREAK → Reprendre -->
                    <button
                        v-else-if="status === 'on_break'"
                        @click="post('time.break-end')"
                        :disabled="loading"
                        class="w-full py-4 rounded-xl font-semibold text-amber-800 text-base
                               bg-amber-50 hover:bg-amber-100 border border-amber-200
                               active:scale-[.98] disabled:opacity-60 transition-all"
                    >
                        <span v-if="loading" class="inline-block w-4 h-4 border-2 border-amber-400/40 border-t-amber-600 rounded-full animate-spin mr-2" />
                        ▶ Reprendre le travail
                    </button>

                    <!-- DONE → Résumé + éventuelles heures sup -->
                    <div v-else-if="status === 'done'" class="space-y-2.5">
                        <div class="py-4 rounded-xl bg-emerald-50 border border-emerald-100 text-center">
                            <p class="text-emerald-700 font-semibold text-sm">
                                ✓ Journée terminée — {{ durationLabel }} travaillées
                            </p>
                            <p class="text-emerald-500 text-xs mt-0.5">
                                {{ entry.clock_in }} → {{ entry.clock_out }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Bandeau heures sup auto-détectées ─────────────────────── -->
            <div v-if="autoOvertime"
                 class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-start gap-3">
                <span class="text-2xl shrink-0">⏱️</span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-amber-800">
                        {{ autoOvertime.hours_label }} d'heures supplémentaires détectées
                    </p>
                    <p class="text-xs text-amber-600 mt-0.5">
                        Taux {{ autoOvertime.rate_label }} · En attente de validation par votre responsable
                    </p>
                </div>
                <Link
                    :href="route('overtime.show', autoOvertime.id)"
                    class="text-xs font-semibold text-amber-700 hover:text-amber-900 shrink-0
                           border border-amber-300 rounded-lg px-2.5 py-1.5 hover:bg-amber-100 transition-colors"
                >
                    Voir →
                </Link>
            </div>

            <!-- ── Semaine en cours ────────────────────────────────────────── -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-slate-700">Cette semaine</h3>
                    <div class="text-right">
                        <span class="text-sm font-bold text-primary-600">{{ weekLabel }}</span>
                        <span v-if="weekOT > 0" class="text-xs text-amber-600 font-medium ml-2">
                            +{{ weekOTLabel }} sup.
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-7 gap-1.5">
                    <div
                        v-for="day in week"
                        :key="day.date"
                        class="flex flex-col items-center gap-1"
                    >
                        <span class="text-[10px] font-medium text-slate-400 uppercase">
                            {{ day.day_label }}
                        </span>

                        <!-- Barre empilée : normal + overtime -->
                        <div class="relative w-full flex justify-center">
                            <div class="w-7 h-16 rounded-lg overflow-hidden flex flex-col-reverse"
                                 :class="day.is_weekend ? 'bg-slate-50' : 'bg-slate-100'">
                                <!-- Section normale -->
                                <div
                                    class="w-full transition-all duration-500"
                                    :class="{
                                        'bg-primary-500': day.status === 'working' && !day.overtime_minutes,
                                        'bg-emerald-400': day.status === 'done'    && !day.overtime_minutes,
                                        'bg-amber-300':   day.status === 'on_break',
                                        'bg-slate-200':   day.status === 'idle'    && !day.is_weekend,
                                        'bg-primary-400': day.overtime_minutes > 0 && day.status !== 'done',
                                        'bg-emerald-300': day.overtime_minutes > 0 && day.status === 'done',
                                    }"
                                    :style="{ height: Math.min(100, ((day.worked_minutes - (day.overtime_minutes ?? 0)) / TARGET_MINUTES) * 100) + '%' }"
                                />
                                <!-- Section heures sup (orange par-dessus) -->
                                <div
                                    v-if="day.overtime_minutes > 0"
                                    class="w-full bg-amber-400 transition-all duration-500"
                                    :style="{ height: Math.min(30, (day.overtime_minutes / TARGET_MINUTES) * 100) + '%' }"
                                />
                            </div>
                            <span v-if="day.is_today"
                                  class="absolute -bottom-1 w-1.5 h-1.5 rounded-full bg-primary-500" />
                        </div>

                        <span class="text-[10px] text-slate-400 tabular-nums">
                            {{ day.worked_minutes > 0 ? minutesToLabel(day.worked_minutes) : '—' }}
                        </span>
                    </div>
                </div>

                <!-- Légende si des heures sup existent cette semaine -->
                <div v-if="weekOT > 0" class="mt-3 pt-3 border-t border-slate-100 flex items-center gap-4 text-xs text-slate-500">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-sm bg-emerald-400 shrink-0"></span>Temps normal
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-sm bg-amber-400 shrink-0"></span>Heures sup.
                    </span>
                </div>
            </div>

            <!-- ── Liens bas de page ───────────────────────────────────────── -->
            <div class="flex justify-between text-sm pb-2">
                <a :href="route('time.history')"
                   class="text-slate-500 hover:text-slate-700 font-medium transition-colors">
                    Historique →
                </a>
                <a :href="route('overtime.index')"
                   class="text-amber-600 hover:text-amber-700 font-medium transition-colors">
                    Mes heures sup. →
                </a>
            </div>

        </div>

    </AppLayout>
</template>
