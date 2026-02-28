<script setup>
import { computed } from 'vue'
import { Head, Link, usePage, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import StatsCard from '@/Components/Charts/StatsCard.vue'

const props = defineProps({
    stats:            { type: Object, default: () => ({}) },
    pending_leaves:   { type: Array,  default: () => [] },
    team_today:       { type: Array,  default: () => [] },
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

const presenceRate = computed(() => {
    const total = props.stats.team_size ?? 0
    if (total === 0) return 0
    return Math.round(((props.stats.present_today ?? 0) / total) * 100)
})

const statIcons = {
    present:  `<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
    absent:   `<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>`,
    overtime: `<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
    leaves:   `<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>`,
}

const statusColors = {
    present:  { dot: 'bg-success-500', text: 'text-success-700', bg: 'bg-success-50' },
    absent:   { dot: 'bg-danger-500',  text: 'text-danger-700',  bg: 'bg-danger-50'  },
    leave:    { dot: 'bg-warning-500', text: 'text-warning-700', bg: 'bg-warning-50'  },
    remote:   { dot: 'bg-primary-500', text: 'text-primary-700', bg: 'bg-primary-50'  },
}

const statusLabel = {
    present: 'Présent',
    absent:  'Absent',
    leave:   'En congé',
    remote:  'Télétravail',
}
</script>

<template>
    <Head title="Tableau de bord" />

    <AppLayout title="Tableau de bord">

        <!-- ── En-tête ── -->
        <div class="mb-8">
            <h2 class="text-2xl font-display font-bold text-slate-900">
                {{ greeting }}, {{ user?.first_name }} 👋
            </h2>
            <p class="text-slate-500 text-sm mt-1 capitalize">{{ today }}</p>
        </div>

        <!-- ── Stats ── -->
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
            <StatsCard
                label="Présents aujourd'hui"
                :value="stats.present_today ?? 0"
                :icon="statIcons.present"
                color="success"
            />
            <StatsCard
                label="Absents aujourd'hui"
                :value="stats.absent_today ?? 0"
                :icon="statIcons.absent"
                :color="(stats.absent_today ?? 0) > 0 ? 'danger' : 'slate'"
            />
            <StatsCard
                label="Congés en attente"
                :value="stats.leaves_pending ?? 0"
                :icon="statIcons.leaves"
                :color="(stats.leaves_pending ?? 0) > 0 ? 'warning' : 'slate'"
                href="/conges"
            />
        </div>

        <!-- ── Congés en attente ── -->
        <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden mb-6">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <h3 class="font-semibold text-slate-900">Congés à valider</h3>
                <Link :href="route('leaves.index')"
                      class="text-xs text-primary-600 hover:text-primary-700 font-medium transition-colors">
                    Voir tout →
                </Link>
            </div>

            <div v-if="pending_leaves.length === 0"
                 class="flex flex-col items-center justify-center py-10 px-4 text-center">
                <div class="w-12 h-12 rounded-full bg-success-50 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor"
                         stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-sm font-semibold text-slate-700">Tout est à jour !</p>
                <p class="text-xs text-slate-400 mt-1">Aucune demande de congé en attente</p>
            </div>

            <ul v-else class="divide-y divide-slate-50">
                <li
                    v-for="req in pending_leaves"
                    :key="req.id"
                    class="flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50 transition-colors
                           cursor-pointer"
                    @click="router.visit(route('leaves.show', req.id))"
                >
                    <!-- Avatar -->
                    <div class="w-9 h-9 rounded-full bg-primary-100 flex items-center
                                justify-center shrink-0">
                        <span class="text-primary-700 text-xs font-bold">{{ req.initials }}</span>
                    </div>
                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-900 truncate">
                            {{ req.employee_name }}
                        </p>
                        <p class="text-xs text-slate-500">
                            <span v-if="req.icon">{{ req.icon }} </span>{{ req.type }}
                            · {{ req.days_count }}j · {{ req.start_date }}
                        </p>
                    </div>
                    <!-- Chevron -->
                    <svg class="w-4 h-4 text-slate-300 shrink-0" fill="none" stroke="currentColor"
                         stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </li>
            </ul>
        </div>

        <!-- ── Actions rapides ── -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <Link href="/planning"
                  class="bg-white rounded-2xl border border-slate-100 p-4 hover:border-primary-200
                         hover:bg-primary-50 transition-all group text-center">
                <div class="text-2xl mb-2">📆</div>
                <p class="text-xs font-semibold text-slate-700 group-hover:text-primary-700 transition-colors">
                    Planning équipe
                </p>
            </Link>
            <Link href="/heures-supplementaires"
                  class="bg-white rounded-2xl border border-slate-100 p-4 hover:border-warning-200
                         hover:bg-warning-50 transition-all group text-center">
                <div class="text-2xl mb-2">⏱️</div>
                <p class="text-xs font-semibold text-slate-700 group-hover:text-warning-700 transition-colors">
                    Heures sup.
                </p>
            </Link>
            <Link href="/documents"
                  class="bg-white rounded-2xl border border-slate-100 p-4 hover:border-primary-200
                         hover:bg-primary-50 transition-all group text-center">
                <div class="text-2xl mb-2">📄</div>
                <p class="text-xs font-semibold text-slate-700 group-hover:text-primary-700 transition-colors">
                    Documents
                </p>
            </Link>
            <Link :href="route('time.team-overview')"
                  class="bg-white rounded-2xl border border-slate-100 p-4 hover:border-success-200
                         hover:bg-success-50 transition-all group text-center">
                <div class="text-2xl mb-2">🕐</div>
                <p class="text-xs font-semibold text-slate-700 group-hover:text-success-700 transition-colors">
                    Suivi pointage
                </p>
            </Link>
        </div>

    </AppLayout>
</template>
