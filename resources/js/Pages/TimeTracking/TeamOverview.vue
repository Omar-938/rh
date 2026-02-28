<script setup>
import { computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    employees:    { type: Array,  default: () => [] },
    stats:        { type: Object, default: () => ({}) },
    today_label:  { type: String, default: '' },
    refreshed_at: { type: String, default: '' },
})

// Tri : en cours → pause → parti → absent
const sortOrder = { working: 0, on_break: 1, done: 2, absent: 3 }
const sorted = computed(() =>
    [...props.employees].sort((a, b) =>
        (sortOrder[a.status] ?? 9) - (sortOrder[b.status] ?? 9)
    )
)

const statusConfig = {
    working:  { label: 'En cours',  dot: 'bg-success-500', badge: 'bg-success-50 text-success-700',  ring: 'ring-success-200'  },
    on_break: { label: 'En pause',  dot: 'bg-amber-400',   badge: 'bg-amber-50 text-amber-700',      ring: 'ring-amber-200'    },
    done:     { label: 'Parti',     dot: 'bg-slate-400',   badge: 'bg-slate-100 text-slate-600',     ring: 'ring-slate-200'    },
    absent:   { label: 'Absent',    dot: 'bg-danger-300',  badge: 'bg-danger-50 text-danger-600',    ring: 'ring-danger-100'   },
}

function refresh() {
    router.reload({ only: ['employees', 'stats', 'refreshed_at'] })
}
</script>

<template>
    <Head title="Suivi pointage équipe" />

    <AppLayout title="Suivi pointage" :back-url="route('time.clock')">

        <!-- ── En-tête ── -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
            <div>
                <h2 class="text-xl font-display font-bold text-slate-900 capitalize">
                    {{ today_label }}
                </h2>
                <p class="text-sm text-slate-400 mt-0.5">Actualisé à {{ refreshed_at }}</p>
            </div>
            <button
                @click="refresh"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-600
                       border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors self-start"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                Actualiser
            </button>
        </div>

        <!-- ── Chips de stats ── -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            <div class="bg-white rounded-2xl border border-slate-100 px-4 py-3 flex items-center gap-3">
                <span class="w-2.5 h-2.5 rounded-full bg-success-500 shrink-0" />
                <div>
                    <p class="text-2xl font-display font-bold text-slate-900 leading-none">
                        {{ stats.working ?? 0 }}
                    </p>
                    <p class="text-xs text-slate-500 mt-0.5">En cours</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 px-4 py-3 flex items-center gap-3">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-400 shrink-0" />
                <div>
                    <p class="text-2xl font-display font-bold text-slate-900 leading-none">
                        {{ stats.on_break ?? 0 }}
                    </p>
                    <p class="text-xs text-slate-500 mt-0.5">En pause</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 px-4 py-3 flex items-center gap-3">
                <span class="w-2.5 h-2.5 rounded-full bg-slate-400 shrink-0" />
                <div>
                    <p class="text-2xl font-display font-bold text-slate-900 leading-none">
                        {{ stats.done ?? 0 }}
                    </p>
                    <p class="text-xs text-slate-500 mt-0.5">Partis</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 px-4 py-3 flex items-center gap-3">
                <span class="w-2.5 h-2.5 rounded-full bg-danger-300 shrink-0" />
                <div>
                    <p class="text-2xl font-display font-bold text-slate-900 leading-none">
                        {{ stats.absent ?? 0 }}
                    </p>
                    <p class="text-xs text-slate-500 mt-0.5">Absents</p>
                </div>
            </div>
        </div>

        <!-- ── Liste employés ── -->
        <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">

            <!-- Vide -->
            <div v-if="employees.length === 0"
                 class="flex flex-col items-center justify-center py-16 text-slate-400">
                <div class="text-4xl mb-3">👥</div>
                <p class="text-sm font-medium text-slate-500">Aucun collaborateur</p>
            </div>

            <template v-else>
                <!-- En-têtes desktop -->
                <div class="hidden sm:grid grid-cols-[1fr_auto_auto_auto] gap-4
                            px-5 py-3 bg-slate-50 border-b border-slate-100
                            text-xs font-semibold text-slate-400 uppercase tracking-wider">
                    <span>Collaborateur</span>
                    <span class="text-center w-28">Statut</span>
                    <span class="text-center w-32">Arrivée → Départ</span>
                    <span class="text-right w-24">Temps</span>
                </div>

                <ul class="divide-y divide-slate-50">
                    <li
                        v-for="emp in sorted"
                        :key="emp.id"
                        class="px-5 py-3.5 flex items-center gap-4 hover:bg-slate-50/60 transition-colors"
                    >
                        <!-- Avatar -->
                        <div
                            class="w-9 h-9 rounded-full flex items-center justify-center
                                   text-xs font-bold shrink-0 ring-2"
                            :class="[
                                statusConfig[emp.status]?.ring ?? 'ring-slate-200',
                                emp.status === 'working'  ? 'bg-success-100 text-success-700' :
                                emp.status === 'on_break' ? 'bg-amber-100 text-amber-700' :
                                emp.status === 'done'     ? 'bg-slate-100 text-slate-600' :
                                                            'bg-slate-50 text-slate-400',
                            ]"
                        >
                            <img v-if="emp.avatar_url" :src="emp.avatar_url"
                                 class="w-full h-full object-cover rounded-full" :alt="emp.full_name" />
                            <span v-else>{{ emp.initials }}</span>
                        </div>

                        <!-- Nom + département -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-900 truncate">
                                {{ emp.full_name }}
                            </p>
                            <p class="text-xs text-slate-400 truncate">
                                {{ emp.department ?? '—' }}
                            </p>
                        </div>

                        <!-- Statut badge -->
                        <span
                            class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                                   text-xs font-semibold whitespace-nowrap w-28 justify-center"
                            :class="statusConfig[emp.status]?.badge"
                        >
                            <span class="w-1.5 h-1.5 rounded-full"
                                  :class="statusConfig[emp.status]?.dot" />
                            {{ statusConfig[emp.status]?.label ?? emp.status }}
                        </span>

                        <!-- Horaires -->
                        <div class="hidden sm:flex items-center gap-1 w-32 justify-center">
                            <span v-if="emp.clock_in"
                                  class="text-sm font-mono text-slate-700">
                                {{ emp.clock_in }}
                            </span>
                            <span v-if="emp.clock_in && emp.clock_out"
                                  class="text-xs text-slate-300">→</span>
                            <span v-if="emp.clock_out"
                                  class="text-sm font-mono text-slate-500">
                                {{ emp.clock_out }}
                            </span>
                            <span v-if="!emp.clock_in" class="text-xs text-slate-300">—</span>
                        </div>

                        <!-- Temps travaillé + barre -->
                        <div class="flex flex-col items-end gap-1 w-24 shrink-0">
                            <span
                                v-if="emp.worked_label"
                                class="text-sm font-semibold font-mono"
                                :class="emp.status === 'working'  ? 'text-success-700' :
                                        emp.status === 'on_break' ? 'text-amber-600' :
                                        emp.status === 'done'     ? 'text-slate-600' :
                                                                    'text-slate-300'"
                            >
                                {{ emp.worked_label }}
                            </span>
                            <span v-else class="text-xs text-slate-300">—</span>

                            <!-- Barre de progression vers 7h -->
                            <div v-if="emp.progress > 0"
                                 class="w-full h-1 bg-slate-100 rounded-full overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all"
                                    :class="emp.progress >= 100 ? 'bg-success-500' :
                                            emp.status === 'working' ? 'bg-success-400' :
                                            emp.status === 'on_break' ? 'bg-amber-400' :
                                            'bg-slate-300'"
                                    :style="{ width: Math.min(emp.progress, 100) + '%' }"
                                />
                            </div>
                        </div>

                        <!-- Statut mobile (petit dot) -->
                        <span
                            class="sm:hidden w-2 h-2 rounded-full shrink-0"
                            :class="statusConfig[emp.status]?.dot"
                        />
                    </li>
                </ul>
            </template>
        </div>

        <!-- Légende mobile -->
        <div class="sm:hidden mt-4 flex flex-wrap gap-3 text-xs text-slate-500">
            <span v-for="(cfg, key) in statusConfig" :key="key"
                  class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full" :class="cfg.dot" />
                {{ cfg.label }}
            </span>
        </div>

    </AppLayout>
</template>
