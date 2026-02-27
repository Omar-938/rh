<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import StatsCard from '@/Components/Charts/StatsCard.vue'
import { formatDate } from '@/Utils/dates'

const props = defineProps({
    stats:            { type: Object, default: () => ({}) },
    pending_leaves:   { type: Array,  default: () => [] },
    pending_overtime: { type: Array,  default: () => [] },
    recent_activity:  { type: Array,  default: () => [] },
    is_new_account:   { type: Boolean,default: true },
    team_today:       { type: Object, default: () => ({ present: 0, remote: 0, absent: 0, members: [] }) },
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

const quickActions = [
    { label: 'Ajouter un employé',      href: '/employees/create',                      icon: '👤', color: 'primary' },
    { label: 'Gérer les congés',         href: '/conges',                                icon: '📅', color: 'success' },
    { label: 'Valider les heures sup.',  href: '/heures-supplementaires',                icon: '⏱️', color: 'warning' },
    { label: 'Préparer l\'export paie',  href: '/export-paie',                           icon: '📊', color: 'slate'   },
    { label: 'Règles heures sup.',       href: '/parametres/heures-supplementaires',     icon: '⚖️', color: 'warning' },
    { label: 'Types de congés',          href: '/parametres/types-conges',               icon: '⚙️', color: 'slate'   },
    { label: 'Jours fériés',             href: '/parametres/jours-feries',               icon: '🗓️', color: 'primary'  },
]

const statIcons = {
    employees: `<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>`,
    leaves:    `<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>`,
    overtime:  `<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
    docs:      `<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>`,
}
</script>

<template>
    <Head title="Tableau de bord" />

    <AppLayout title="Tableau de bord">
        <!-- ── En-tête de bienvenue ── -->
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-2xl font-display font-bold text-slate-900">
                        {{ greeting }}, {{ user?.first_name }} 👋
                    </h2>
                    <p class="text-slate-500 text-sm mt-1 capitalize">{{ today }}</p>
                </div>
                <Link
                    href="/employees/create"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-700 hover:bg-primary-800
                           text-white text-sm font-semibold rounded-xl transition-all active:scale-95
                           shadow-sm shadow-primary-200 hover:shadow-md self-start sm:self-auto"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Ajouter un employé
                </Link>
            </div>
        </div>

        <!-- ── Onboarding (nouveau compte) ── -->
        <Transition
            enter-active-class="transition duration-500 ease-out"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
        >
            <div v-if="is_new_account" class="mb-8">
                <div class="relative bg-gradient-to-br from-primary-700 to-primary-500 rounded-2xl p-6 sm:p-8 overflow-hidden">
                    <!-- Décorations -->
                    <div class="absolute -top-8 -right-8 w-40 h-40 rounded-full bg-white/10 pointer-events-none" />
                    <div class="absolute bottom-0 right-16 w-24 h-24 rounded-full bg-white/5 pointer-events-none" />

                    <div class="relative z-10 max-w-xl">
                        <div class="inline-flex items-center gap-2 bg-white/15 text-white text-xs font-semibold
                                    px-3 py-1.5 rounded-full mb-4">
                            🚀 Bienvenue dans SimpliRH
                        </div>
                        <h3 class="text-white text-xl sm:text-2xl font-display font-bold mb-2">
                            Configurez votre espace en 3 étapes
                        </h3>
                        <p class="text-primary-100 text-sm mb-6 leading-relaxed">
                            Votre compte est prêt. Commencez par ajouter vos collaborateurs pour
                            profiter de toutes les fonctionnalités.
                        </p>

                        <!-- Étapes d'onboarding -->
                        <div class="grid sm:grid-cols-3 gap-3">
                            <Link href="/employees/create"
                                  class="bg-white/15 hover:bg-white/25 backdrop-blur rounded-xl p-4 transition-all group">
                                <div class="text-2xl mb-2">👥</div>
                                <p class="text-white font-semibold text-sm">Ajouter des employés</p>
                                <p class="text-primary-200 text-xs mt-1">Importez ou créez manuellement</p>
                                <div class="flex items-center gap-1 mt-3 text-primary-200 text-xs group-hover:text-white transition-colors">
                                    Commencer
                                    <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                    </svg>
                                </div>
                            </Link>
                            <Link href="/parametres/conges"
                                  class="bg-white/15 hover:bg-white/25 backdrop-blur rounded-xl p-4 transition-all group">
                                <div class="text-2xl mb-2">📅</div>
                                <p class="text-white font-semibold text-sm">Types de congés</p>
                                <p class="text-primary-200 text-xs mt-1">Congés payés, RTT, sans solde…</p>
                                <div class="flex items-center gap-1 mt-3 text-primary-200 text-xs group-hover:text-white transition-colors">
                                    Configurer
                                    <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                    </svg>
                                </div>
                            </Link>
                            <Link href="/parametres/entreprise"
                                  class="bg-white/15 hover:bg-white/25 backdrop-blur rounded-xl p-4 transition-all group">
                                <div class="text-2xl mb-2">🏢</div>
                                <p class="text-white font-semibold text-sm">Profil entreprise</p>
                                <p class="text-primary-200 text-xs mt-1">Logo, SIRET, coordonnées</p>
                                <div class="flex items-center gap-1 mt-3 text-primary-200 text-xs group-hover:text-white transition-colors">
                                    Compléter
                                    <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                    </svg>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ── Stats cards ── -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <StatsCard
                label="Collaborateurs"
                :value="stats.employees_total ?? 0"
                :icon="statIcons.employees"
                color="primary"
                href="/employees"
            />
            <StatsCard
                label="Congés en attente"
                :value="stats.leaves_pending ?? 0"
                :icon="statIcons.leaves"
                :color="(stats.leaves_pending ?? 0) > 0 ? 'warning' : 'slate'"
                href="/conges"
            />
            <StatsCard
                label="Heures sup. à valider"
                :value="stats.overtime_pending ?? 0"
                :icon="statIcons.overtime"
                :color="(stats.overtime_pending ?? 0) > 0 ? 'warning' : 'slate'"
                href="/heures-supplementaires"
            />
            <StatsCard
                label="Documents à signer"
                :value="stats.documents_unsigned ?? 0"
                :icon="statIcons.docs"
                :color="(stats.documents_unsigned ?? 0) > 0 ? 'danger' : 'slate'"
                href="/documents"
            />
        </div>

        <!-- ── Colonnes : demandes en attente ── -->
        <div class="grid lg:grid-cols-2 gap-6 mb-6" v-if="!is_new_account">
            <!-- Congés en attente -->
            <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-900">Congés en attente</h3>
                    <Link href="/conges" class="text-xs text-primary-600 hover:text-primary-700 font-medium transition-colors">
                        Voir tout →
                    </Link>
                </div>
                <template v-if="pending_leaves.length === 0">
                    <EmptySection
                        icon="📅"
                        message="Aucune demande de congé en attente"
                        hint="Les nouvelles demandes apparaîtront ici"
                    />
                </template>
                <ul v-else class="divide-y divide-slate-50">
                    <li v-for="leave in pending_leaves" :key="leave.id"
                        class="flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50 transition-colors">
                        <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center shrink-0">
                            <span class="text-primary-700 text-xs font-bold">{{ leave.initials }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 truncate">{{ leave.employee_name }}</p>
                            <p class="text-xs text-slate-500">{{ leave.type }} · {{ leave.days_count }}j</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-xs text-slate-600">{{ leave.start_date }}</p>
                            <Link :href="`/conges/${leave.id}`"
                                  class="text-xs text-primary-600 font-medium hover:underline">
                                Traiter
                            </Link>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Heures sup en attente -->
            <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-900">Heures sup. à valider</h3>
                    <Link href="/heures-supplementaires" class="text-xs text-primary-600 hover:text-primary-700 font-medium transition-colors">
                        Voir tout →
                    </Link>
                </div>
                <template v-if="pending_overtime.length === 0">
                    <EmptySection
                        icon="⏱️"
                        message="Aucune heure supplémentaire en attente"
                        hint="Les déclarations soumises apparaîtront ici"
                    />
                </template>
                <ul v-else class="divide-y divide-slate-50">
                    <li v-for="ot in pending_overtime" :key="ot.id"
                        class="flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50 transition-colors">
                        <!-- Avatar -->
                        <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
                            <span class="text-amber-700 text-xs font-bold">{{ ot.initials }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 truncate">{{ ot.employee_name }}</p>
                            <p class="text-xs text-slate-500">{{ ot.hours }} · {{ ot.date }}</p>
                        </div>
                        <Link :href="`/heures-supplementaires/${ot.id}`"
                              class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700
                                     text-xs font-semibold rounded-lg transition-colors shrink-0">
                            Valider
                        </Link>
                    </li>
                </ul>
            </div>
        </div>

        <!-- ── Planning équipe aujourd'hui ── -->
        <div v-if="!is_new_account" class="bg-white rounded-2xl border border-slate-100 p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-slate-900">Équipe aujourd'hui</h3>
                <Link href="/planning" class="text-xs text-primary-600 hover:text-primary-700 font-medium transition-colors">
                    Planning →
                </Link>
            </div>

            <!-- Compteurs -->
            <div class="grid grid-cols-3 gap-3 mb-5">
                <div class="flex flex-col items-center p-3 rounded-xl bg-success-50 border border-success-100">
                    <span class="text-2xl font-bold text-success-700">{{ team_today.present }}</span>
                    <span class="text-xs text-success-600 mt-0.5 font-medium">Présents</span>
                </div>
                <div class="flex flex-col items-center p-3 rounded-xl bg-primary-50 border border-primary-100">
                    <span class="text-2xl font-bold text-primary-700">{{ team_today.remote }}</span>
                    <span class="text-xs text-primary-600 mt-0.5 font-medium">Télétravail</span>
                </div>
                <div class="flex flex-col items-center p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-2xl font-bold text-slate-600">{{ team_today.absent }}</span>
                    <span class="text-xs text-slate-500 mt-0.5 font-medium">Absents</span>
                </div>
            </div>

            <!-- Avatars membres -->
            <div v-if="team_today.members.length > 0" class="flex flex-wrap gap-2">
                <div v-for="member in team_today.members" :key="member.id"
                     class="flex items-center gap-2 px-3 py-1.5 rounded-full border text-xs font-medium transition-colors"
                     :class="{
                         'bg-success-50 border-success-200 text-success-700': member.status === 'present',
                         'bg-primary-50 border-primary-200 text-primary-700': member.status === 'remote',
                         'bg-slate-50 border-slate-200 text-slate-500':       member.status === 'absent',
                     }">
                    <span>{{ member.emoji }}</span>
                    <span>{{ member.name }}</span>
                </div>
            </div>
        </div>

        <!-- ── Actions rapides ── -->
        <div class="bg-white rounded-2xl border border-slate-100 p-5">
            <h3 class="font-semibold text-slate-900 mb-4">Actions rapides</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <Link
                    v-for="action in quickActions"
                    :key="action.label"
                    :href="action.href"
                    class="flex flex-col items-center gap-2 p-4 rounded-xl border border-slate-100
                           hover:border-primary-200 hover:bg-primary-50 transition-all group text-center"
                >
                    <span class="text-2xl">{{ action.icon }}</span>
                    <span class="text-xs font-medium text-slate-600 group-hover:text-primary-700 transition-colors leading-snug">
                        {{ action.label }}
                    </span>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<!-- Sous-composant empty state -->
<script>
const EmptySection = {
    name: 'EmptySection',
    props: { icon: String, message: String, hint: String },
    template: `
        <div class="flex flex-col items-center justify-center py-10 px-4 text-center">
            <div class="text-3xl mb-3 opacity-60">{{ icon }}</div>
            <p class="text-sm font-medium text-slate-600">{{ message }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ hint }}</p>
        </div>
    `,
}
export { EmptySection }
</script>
