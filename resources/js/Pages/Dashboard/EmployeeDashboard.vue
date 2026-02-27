<script setup>
import { computed, ref } from 'vue'
import { Head, Link, usePage, router } from '@inertiajs/vue3'

import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    leave_balances:  { type: Array,  default: () => [] },
    my_requests:     { type: Array,  default: () => [] },
    week_schedule:   { type: Array,  default: () => [] },
    time_entry_today:{ type: Object, default: null },
    upcoming_leaves: { type: Array,  default: () => [] },
})

const page = usePage()
const user = computed(() => page.props.auth?.user)

const greeting = computed(() => {
    const h = new Date().getHours()
    if (h < 12) return 'Bonjour'
    if (h < 18) return 'Bon après-midi'
    return 'Bonsoir'
})

const today = computed(() =>
    new Date().toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' })
)

// Horloge en temps réel
const clock = ref(new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }))
setInterval(() => {
    clock.value = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}, 1000)

function goToClock() {
    router.visit('/pointage')
}

// Jours de la semaine
const weekDays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']
const todayIndex = computed(() => {
    const d = new Date().getDay()
    return d === 0 ? 6 : d - 1 // Monday = 0
})

// Couleur de la barre de solde congés
function balanceColor(used, total) {
    if (total === 0) return 'bg-slate-300'
    const pct = used / total
    if (pct < 0.5)  return 'bg-success-500'
    if (pct < 0.8)  return 'bg-warning-500'
    return 'bg-danger-500'
}

const requestStatusConfig = {
    pending:  { label: 'En attente', bg: 'bg-warning-50',  text: 'text-warning-700' },
    approved: { label: 'Approuvée',  bg: 'bg-success-50',  text: 'text-success-700' },
    refused:  { label: 'Refusée',    bg: 'bg-danger-50',   text: 'text-danger-700'  },
    cancelled:{ label: 'Annulée',    bg: 'bg-slate-100',   text: 'text-slate-500'   },
}
</script>

<template>
    <Head title="Mon espace" />

    <AppLayout title="Mon espace">

        <!-- ── En-tête avec horloge ── -->
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-display font-bold text-slate-900">
                        {{ greeting }}, {{ user?.first_name }} 👋
                    </h2>
                    <p class="text-slate-500 text-sm mt-1 capitalize">{{ today }}</p>
                </div>

                <!-- Card pointage -->
                <button
                    @click="goToClock"
                    class="bg-white border rounded-2xl p-4 flex items-center gap-4 sm:min-w-[220px]
                           hover:border-primary-200 hover:bg-primary-50/30 transition-all text-left"
                    :class="time_entry_today?.clock_in ? 'border-success-200 bg-success-50/30' : 'border-slate-100'"
                >
                    <div class="text-center">
                        <p class="text-2xl font-display font-bold text-slate-900 tabular-nums">{{ clock }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            <template v-if="time_entry_today?.clock_in">
                                Pointé à {{ time_entry_today.clock_in }}
                            </template>
                            <template v-else>Non pointé</template>
                        </p>
                    </div>
                    <div
                        class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-center transition-all"
                        :class="time_entry_today?.clock_in
                            ? 'bg-success-50 text-success-700 border border-success-200'
                            : 'bg-primary-600 text-white'"
                    >
                        {{ time_entry_today?.clock_in ? '✓ En cours' : 'Pointer →' }}
                    </div>
                </button>
            </div>
        </div>

        <!-- ── Planning de la semaine ── -->
        <div class="bg-white rounded-2xl border border-slate-100 p-5 mb-6">
            <h3 class="font-semibold text-slate-900 mb-4">Ma semaine</h3>

            <div v-if="week_schedule.length === 0" class="flex flex-col items-center py-8 text-center">
                <div class="text-3xl mb-3 opacity-60">📆</div>
                <p class="text-sm font-medium text-slate-600">Aucun planning cette semaine</p>
                <p class="text-xs text-slate-400 mt-1">Votre planning apparaîtra ici une fois configuré</p>
            </div>

            <div v-else class="grid grid-cols-7 gap-1 sm:gap-2">
                <div v-for="(day, idx) in week_schedule" :key="idx"
                     :class="[
                         'flex flex-col items-center rounded-xl p-2 sm:p-3 transition-colors',
                         idx === todayIndex ? 'bg-primary-700 text-white' : 'bg-slate-50 text-slate-700',
                     ]">
                    <!-- Jour -->
                    <span :class="['text-xs font-semibold mb-2', idx === todayIndex ? 'text-primary-200' : 'text-slate-400']">
                        {{ weekDays[idx] }}
                    </span>

                    <!-- Indicateur travaillé / off -->
                    <div :class="[
                        'w-2 h-2 rounded-full mb-2',
                        day.is_off
                            ? (idx === todayIndex ? 'bg-primary-400' : 'bg-slate-300')
                            : (idx === todayIndex ? 'bg-white' : 'bg-success-500'),
                    ]" />

                    <!-- Horaires -->
                    <span v-if="!day.is_off" :class="['text-xs text-center leading-tight tabular-nums', idx === todayIndex ? 'text-primary-100' : 'text-slate-500']">
                        {{ day.start }}<br>{{ day.end }}
                    </span>
                    <span v-else :class="['text-xs', idx === todayIndex ? 'text-primary-300' : 'text-slate-400']">
                        Off
                    </span>
                </div>
            </div>
        </div>

        <!-- ── Colonnes principales ── -->
        <div class="grid lg:grid-cols-2 gap-6 mb-6">

            <!-- Soldes de congés -->
            <div class="bg-white rounded-2xl border border-slate-100 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-slate-900">Mes soldes de congés</h3>
                    <Link href="/conges/soldes"
                          class="text-xs text-primary-600 hover:text-primary-700 font-medium transition-colors">
                        Détails →
                    </Link>
                </div>

                <div v-if="leave_balances.length === 0"
                     class="flex flex-col items-center py-8 text-center">
                    <div class="text-3xl mb-3 opacity-60">🏖️</div>
                    <p class="text-sm font-medium text-slate-600">Aucun solde configuré</p>
                    <p class="text-xs text-slate-400 mt-1">Vos compteurs apparaîtront ici</p>
                </div>

                <div v-else class="space-y-4">
                    <div v-for="bal in leave_balances" :key="bal.id">
                        <div class="flex justify-between items-baseline mb-1.5">
                            <span class="text-sm font-medium text-slate-700">{{ bal.leave_type }}</span>
                            <div class="text-right">
                                <span class="text-sm font-bold text-slate-900">{{ bal.remaining }}</span>
                                <span class="text-xs text-slate-400"> / {{ bal.total }}j</span>
                            </div>
                        </div>
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-700 ease-out"
                                :class="balanceColor(bal.used, bal.total)"
                                :style="{ width: bal.total > 0 ? (bal.used / bal.total * 100) + '%' : '0%' }"
                            />
                        </div>
                        <p class="text-xs text-slate-400 mt-1">{{ bal.used }}j utilisés</p>
                    </div>
                </div>

                <!-- CTA demande de congé -->
                <Link href="/conges/demande"
                      class="mt-5 flex items-center justify-center gap-2 w-full py-2.5 border-2 border-dashed
                             border-primary-200 rounded-xl text-sm font-semibold text-primary-600
                             hover:bg-primary-50 hover:border-primary-300 transition-all group">
                    <svg class="w-4 h-4 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Poser un congé
                </Link>
            </div>

            <!-- Mes demandes récentes -->
            <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-900">Mes demandes récentes</h3>
                    <Link href="/conges"
                          class="text-xs text-primary-600 hover:text-primary-700 font-medium transition-colors">
                        Voir tout →
                    </Link>
                </div>

                <template v-if="my_requests.length === 0">
                    <div class="flex flex-col items-center justify-center py-10 px-4 text-center">
                        <div class="text-3xl mb-3 opacity-60">📋</div>
                        <p class="text-sm font-medium text-slate-600">Aucune demande pour le moment</p>
                        <p class="text-xs text-slate-400 mt-1">Vos demandes de congés apparaîtront ici</p>
                    </div>
                </template>

                <ul v-else class="divide-y divide-slate-50">
                    <li v-for="req in my_requests.slice(0, 5)" :key="req.id"
                        class="flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50 transition-colors">
                        <!-- Icône type -->
                        <div class="w-9 h-9 rounded-xl bg-primary-50 flex items-center justify-center shrink-0 text-lg">
                            {{ req.icon ?? '📅' }}
                        </div>
                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-900 truncate">{{ req.leave_type }}</p>
                            <p class="text-xs text-slate-500">{{ req.start_date }} → {{ req.end_date }}</p>
                        </div>
                        <!-- Statut -->
                        <span :class="[
                            'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold shrink-0',
                            requestStatusConfig[req.status]?.bg ?? 'bg-slate-100',
                            requestStatusConfig[req.status]?.text ?? 'text-slate-600',
                        ]">
                            {{ requestStatusConfig[req.status]?.label ?? req.status }}
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- ── Congés à venir ── -->
        <div v-if="upcoming_leaves.length > 0"
             class="bg-gradient-to-r from-success-50 to-success-100/50 rounded-2xl border border-success-200 p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-9 h-9 rounded-xl bg-success-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-success-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                </div>
                <h3 class="font-semibold text-slate-900">Prochains congés</h3>
            </div>
            <div class="space-y-2">
                <div v-for="leave in upcoming_leaves" :key="leave.id"
                     class="flex items-center justify-between bg-white/70 rounded-xl px-4 py-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ leave.leave_type }}</p>
                        <p class="text-xs text-slate-500">{{ leave.start_date }} → {{ leave.end_date }}</p>
                    </div>
                    <span class="text-sm font-bold text-success-700">{{ leave.days_count }}j</span>
                </div>
            </div>
        </div>

        <!-- ── Actions rapides ── -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6">
            <Link href="/conges/demande"
                  class="bg-white rounded-2xl border border-slate-100 p-4 hover:border-primary-200
                         hover:bg-primary-50 transition-all group text-center">
                <div class="text-2xl mb-2">🏖️</div>
                <p class="text-xs font-semibold text-slate-700 group-hover:text-primary-700 transition-colors leading-snug">
                    Demander un congé
                </p>
            </Link>
            <Link href="/heures-supplementaires/declarer"
                  class="bg-white rounded-2xl border border-slate-100 p-4 hover:border-warning-200
                         hover:bg-warning-50 transition-all group text-center">
                <div class="text-2xl mb-2">⏱️</div>
                <p class="text-xs font-semibold text-slate-700 group-hover:text-warning-700 transition-colors leading-snug">
                    Déclarer des heures sup.
                </p>
            </Link>
            <Link href="/bulletins"
                  class="bg-white rounded-2xl border border-slate-100 p-4 hover:border-primary-200
                         hover:bg-primary-50 transition-all group text-center">
                <div class="text-2xl mb-2">📄</div>
                <p class="text-xs font-semibold text-slate-700 group-hover:text-primary-700 transition-colors leading-snug">
                    Mes bulletins
                </p>
            </Link>
            <Link href="/pointage/historique"
                  class="bg-white rounded-2xl border border-slate-100 p-4 hover:border-success-200
                         hover:bg-success-50 transition-all group text-center">
                <div class="text-2xl mb-2">🕐</div>
                <p class="text-xs font-semibold text-slate-700 group-hover:text-success-700 transition-colors leading-snug">
                    Mon historique
                </p>
            </Link>
        </div>

    </AppLayout>
</template>
