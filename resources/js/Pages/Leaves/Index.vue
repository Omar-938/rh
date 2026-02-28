<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    requests:       { type: Object,  required: true },
    counts:         { type: Object,  required: true },
    current_status: { type: String,  default: 'all' },
    can_create:     { type: Boolean, default: false },
    is_reviewer:    { type: Boolean, default: false },
})

const statusTabs = [
    { key: 'all',       label: 'Toutes' },
    { key: 'pending',   label: 'En attente' },
    { key: 'approved',  label: 'Approuvées' },
    { key: 'rejected',  label: 'Refusées' },
    { key: 'cancelled', label: 'Annulées' },
]

const statusColors = {
    pending:   { bg: '#FEF3C7', text: '#92400E', dot: '#F59E0B' },
    approved:  { bg: '#DCFCE7', text: '#166534', dot: '#22C55E' },
    rejected:  { bg: '#FEE2E2', text: '#991B1B', dot: '#EF4444' },
    cancelled: { bg: '#F1F5F9', text: '#475569', dot: '#94A3B8' },
}

function switchTab(key) {
    router.get(route('leaves.index'), { status: key }, { preserveState: true, replace: true })
}

function cancelLeave(id) {
    if (confirm('Annuler cette demande de congé ?')) {
        router.delete(route('leaves.cancel', id))
    }
}
</script>

<template>
    <Head title="Congés" />

    <AppLayout title="Congés" :back-url="route('dashboard')">

        <!-- ── Header ── -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-6">
            <div class="flex-1">
                <h2 class="text-xl font-display font-bold text-slate-900">Demandes de congé</h2>
                <p class="text-sm text-slate-500 mt-0.5">
                    {{ is_reviewer ? 'Toutes les demandes de l\'équipe' : 'Vos demandes de congé' }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <Link
                    v-if="can_create"
                    :href="route('leaves.balances')"
                    class="px-3 py-2 text-sm font-medium text-slate-600 border border-slate-200
                           rounded-xl hover:bg-slate-50 transition-colors"
                >
                    Mes soldes
                </Link>
                <Link
                    v-if="can_create"
                    :href="route('leaves.create')"
                    class="px-4 py-2 bg-primary-700 text-white text-sm font-semibold rounded-xl
                           hover:bg-primary-800 transition-colors shadow-sm"
                >
                    + Nouvelle demande
                </Link>
            </div>
        </div>

        <!-- ── Onglets statut ── -->
        <div class="flex gap-1 bg-slate-100 p-1 rounded-xl mb-5 overflow-x-auto">
            <button
                v-for="tab in statusTabs"
                :key="tab.key"
                @click="switchTab(tab.key)"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium
                       whitespace-nowrap transition-all"
                :class="current_status === tab.key
                    ? 'bg-white text-slate-900 shadow-sm'
                    : 'text-slate-500 hover:text-slate-700'"
            >
                {{ tab.label }}
                <span
                    v-if="counts[tab.key] > 0"
                    class="px-1.5 py-0.5 rounded-full text-[10px] font-bold leading-none"
                    :class="current_status === tab.key
                        ? 'bg-primary-100 text-primary-700'
                        : 'bg-slate-200 text-slate-500'"
                >
                    {{ counts[tab.key] }}
                </span>
            </button>
        </div>

        <!-- ── Liste ── -->
        <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">

            <!-- Vide -->
            <div v-if="requests.data.length === 0"
                 class="flex flex-col items-center justify-center py-16 text-slate-400">
                <div class="text-5xl mb-3">🌴</div>
                <p class="text-base font-medium text-slate-500">Aucune demande</p>
                <p class="text-sm mt-1">
                    {{ current_status === 'all'
                        ? 'Aucune demande de congé pour le moment.'
                        : `Aucune demande ${statusTabs.find(t => t.key === current_status)?.label?.toLowerCase()}.` }}
                </p>
                <Link
                    v-if="can_create && current_status === 'all'"
                    :href="route('leaves.create')"
                    class="mt-4 px-4 py-2 bg-primary-700 text-white text-sm font-semibold
                           rounded-xl hover:bg-primary-800 transition-colors"
                >
                    Faire une demande
                </Link>
            </div>

            <!-- Table desktop -->
            <table v-else class="w-full">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th v-if="is_reviewer"
                            class="text-left text-xs font-semibold text-slate-400 uppercase
                                   tracking-wider px-4 py-3">
                            Employé
                        </th>
                        <th class="text-left text-xs font-semibold text-slate-400 uppercase
                                   tracking-wider px-4 py-3">
                            Type
                        </th>
                        <th class="text-left text-xs font-semibold text-slate-400 uppercase
                                   tracking-wider px-4 py-3 hidden sm:table-cell">
                            Période
                        </th>
                        <th class="text-center text-xs font-semibold text-slate-400 uppercase
                                   tracking-wider px-4 py-3 hidden md:table-cell">
                            Jours
                        </th>
                        <th class="text-left text-xs font-semibold text-slate-400 uppercase
                                   tracking-wider px-4 py-3">
                            Statut
                        </th>
                        <th class="px-4 py-3 w-6" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <tr
                        v-for="req in requests.data"
                        :key="req.id"
                        class="hover:bg-slate-50/80 transition-colors cursor-pointer"
                        @click="router.visit(route('leaves.show', req.id))"
                    >
                        <!-- Employé (reviewer uniquement) -->
                        <td v-if="is_reviewer" class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-primary-100 text-primary-700
                                            flex items-center justify-center text-xs font-bold shrink-0">
                                    {{ req.user?.initials }}
                                </div>
                                <span class="text-sm font-medium text-slate-800 whitespace-nowrap">
                                    {{ req.user?.name }}
                                </span>
                            </div>
                        </td>

                        <!-- Type -->
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="text-base">{{ req.leave_type?.icon }}</span>
                                <span class="text-sm font-medium text-slate-700">
                                    {{ req.leave_type?.name }}
                                </span>
                            </div>
                        </td>

                        <!-- Période -->
                        <td class="px-4 py-3 hidden sm:table-cell">
                            <span class="text-sm text-slate-600">{{ req.period_label }}</span>
                        </td>

                        <!-- Jours -->
                        <td class="px-4 py-3 hidden md:table-cell text-center">
                            <span class="text-sm font-semibold text-slate-700">
                                {{ req.days_count }} j
                            </span>
                        </td>

                        <!-- Statut -->
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                                       text-xs font-semibold whitespace-nowrap"
                                :style="{
                                    backgroundColor: statusColors[req.status]?.bg,
                                    color: statusColors[req.status]?.text,
                                }"
                            >
                                <span
                                    class="w-1.5 h-1.5 rounded-full"
                                    :style="{ backgroundColor: statusColors[req.status]?.dot }"
                                />
                                {{ req.status_label }}
                            </span>
                        </td>

                        <!-- Chevron -->
                        <td class="pr-4 py-3 text-right">
                            <svg class="w-4 h-4 text-slate-300 inline" fill="none"
                                 stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="requests.last_page > 1"
                 class="flex items-center justify-between px-4 py-3 border-t border-slate-100">
                <p class="text-sm text-slate-500">
                    {{ requests.from }}–{{ requests.to }} sur {{ requests.total }} demandes
                </p>
                <div class="flex gap-1">
                    <Link
                        v-if="requests.prev_page_url"
                        :href="requests.prev_page_url"
                        class="px-3 py-1.5 text-sm border border-slate-200 rounded-lg
                               hover:bg-slate-50 text-slate-600 transition-colors"
                    >
                        ← Préc.
                    </Link>
                    <Link
                        v-if="requests.next_page_url"
                        :href="requests.next_page_url"
                        class="px-3 py-1.5 text-sm border border-slate-200 rounded-lg
                               hover:bg-slate-50 text-slate-600 transition-colors"
                    >
                        Suiv. →
                    </Link>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
