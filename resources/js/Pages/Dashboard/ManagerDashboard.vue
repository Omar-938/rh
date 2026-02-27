<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import StatsCard from '@/Components/Charts/StatsCard.vue'

const props = defineProps({
    stats:            { type: Object, default: () => ({}) },
    pending_approvals:{ type: Array,  default: () => [] },
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
    team:    `<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>`,
    present: `<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
    absent:  `<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>`,
    overtime:`<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
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
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-2xl font-display font-bold text-slate-900">
                        {{ greeting }}, {{ user?.first_name }} 👋
                    </h2>
                    <p class="text-slate-500 text-sm mt-1 capitalize">{{ today }}</p>
                </div>
                <Link
                    href="/heures-supplementaires?status=pending"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-700 hover:bg-primary-800
                           text-white text-sm font-semibold rounded-xl transition-all active:scale-95
                           shadow-sm shadow-primary-200 hover:shadow-md self-start sm:self-auto"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Valider les heures sup.
                    <span v-if="(stats.overtime_pending ?? 0) > 0"
                          class="bg-white/25 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">
                        {{ stats.overtime_pending }}
                    </span>
                </Link>
            </div>
        </div>

        <!-- ── Stats ── -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <StatsCard
                label="Mon équipe"
                :value="stats.team_size ?? 0"
                :icon="statIcons.team"
                color="primary"
                suffix=" pers."
            />
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
                label="Heures sup. à valider"
                :value="stats.overtime_pending ?? 0"
                :icon="statIcons.overtime"
                :color="(stats.overtime_pending ?? 0) > 0 ? 'warning' : 'slate'"
                href="/heures-supplementaires"
            />
        </div>

        <!-- ── Colonnes ── -->
        <div class="grid lg:grid-cols-2 gap-6 mb-6">

            <!-- Taux de présence -->
            <div class="bg-white rounded-2xl border border-slate-100 p-5">
                <h3 class="font-semibold text-slate-900 mb-4">Présence de l'équipe</h3>

                <!-- Barre de progression -->
                <div class="mb-6">
                    <div class="flex justify-between items-end mb-2">
                        <span class="text-4xl font-display font-bold text-slate-900">{{ presenceRate }}%</span>
                        <span class="text-sm text-slate-500">
                            {{ stats.present_today ?? 0 }} / {{ stats.team_size ?? 0 }} présents
                        </span>
                    </div>
                    <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-1000 ease-out"
                            :class="presenceRate >= 80 ? 'bg-success-500' : presenceRate >= 50 ? 'bg-warning-500' : 'bg-danger-500'"
                            :style="{ width: presenceRate + '%' }"
                        />
                    </div>
                </div>

                <!-- Liste équipe -->
                <template v-if="team_today.length === 0">
                    <div class="flex flex-col items-center justify-center py-8 text-center">
                        <div class="text-3xl mb-3 opacity-60">👥</div>
                        <p class="text-sm font-medium text-slate-600">Aucun membre dans l'équipe</p>
                        <p class="text-xs text-slate-400 mt-1">Les membres assignés apparaîtront ici</p>
                    </div>
                </template>
                <ul v-else class="space-y-2">
                    <li v-for="member in team_today.slice(0, 6)" :key="member.id"
                        class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-slate-50 transition-colors">
                        <!-- Avatar -->
                        <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center shrink-0">
                            <span class="text-primary-700 text-xs font-bold">{{ member.initials }}</span>
                        </div>
                        <!-- Nom -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 truncate">{{ member.full_name }}</p>
                            <p class="text-xs text-slate-400 truncate">{{ member.job_title }}</p>
                        </div>
                        <!-- Statut -->
                        <span :class="[
                            'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold shrink-0',
                            statusColors[member.status]?.bg,
                            statusColors[member.status]?.text,
                        ]">
                            <span :class="['w-1.5 h-1.5 rounded-full', statusColors[member.status]?.dot]" />
                            {{ statusLabel[member.status] ?? member.status }}
                        </span>
                    </li>
                    <li v-if="team_today.length > 6" class="text-center py-2">
                        <Link href="/planning" class="text-xs text-primary-600 font-medium hover:underline">
                            Voir les {{ team_today.length - 6 }} autres →
                        </Link>
                    </li>
                </ul>
            </div>

            <!-- Demandes en attente -->
            <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-900">Heures sup. à valider</h3>
                    <Link href="/heures-supplementaires?status=pending"
                          class="text-xs text-primary-600 hover:text-primary-700 font-medium transition-colors">
                        Voir tout →
                    </Link>
                </div>

                <template v-if="pending_approvals.length === 0">
                    <div class="flex flex-col items-center justify-center py-10 px-4 text-center">
                        <div class="w-12 h-12 rounded-full bg-success-50 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-700">Tout est à jour !</p>
                        <p class="text-xs text-slate-400 mt-1">Aucune demande en attente de validation</p>
                    </div>
                </template>

                <ul v-else class="divide-y divide-slate-50">
                    <li v-for="req in pending_approvals" :key="req.id"
                        class="group flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50 transition-colors">
                        <!-- Avatar -->
                        <div class="w-9 h-9 rounded-full bg-primary-100 flex items-center justify-center shrink-0">
                            <span class="text-primary-700 text-xs font-bold">{{ req.initials }}</span>
                        </div>
                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-900 truncate">{{ req.employee_name }}</p>
                            <p class="text-xs text-slate-500">
                                {{ req.type }}
                                <span v-if="req.days_count"> · {{ req.days_count }}j</span>
                                <span v-if="req.hours"> · {{ req.hours }}h</span>
                            </p>
                        </div>
                        <!-- Actions -->
                        <div class="flex items-center gap-2 shrink-0">
                            <Link :href="`/heures-supplementaires/${req.id}`"
                                  class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700
                                         text-xs font-semibold rounded-lg transition-colors">
                                Valider
                            </Link>
                        </div>
                    </li>
                </ul>
            </div>
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
            <Link href="/pointage"
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
