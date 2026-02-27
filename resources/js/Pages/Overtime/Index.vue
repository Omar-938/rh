<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    entries:      { type: Object,  required: true },
    status_tab:   { type: String,  default: 'all' },
    counts:       { type: Object,  required: true },
    annual_stats: { type: Object,  required: true },
    is_reviewer:  { type: Boolean, default: false },
})

// ─── Onglets ──────────────────────────────────────────────────────────────────
const tabs = [
    { key: 'all',      label: 'Toutes',    count: computed(() => props.counts.all) },
    { key: 'pending',  label: 'En attente',count: computed(() => props.counts.pending) },
    { key: 'approved', label: 'Approuvées',count: computed(() => props.counts.approved) },
    { key: 'rejected', label: 'Refusées',  count: computed(() => props.counts.rejected) },
]

function changeTab(tab) {
    router.get(route('overtime.index'), { status: tab }, { preserveScroll: true, replace: true })
}

// ─── Status config ────────────────────────────────────────────────────────────
const statusCfg = {
    pending:  { label: 'En attente', bg: 'bg-amber-50',   text: 'text-amber-700',   dot: 'bg-amber-400'   },
    approved: { label: 'Approuvé',   bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    rejected: { label: 'Refusé',     bg: 'bg-danger-50',  text: 'text-danger-700',  dot: 'bg-danger-400'  },
}

const sourceCfg = {
    manual: { label: 'Manuel',    icon: '✏️' },
    auto:   { label: 'Pointeuse', icon: '⏱️' },
}

const compensationCfg = {
    payment: { label: 'Paiement majoré',    icon: '💰' },
    rest:    { label: 'Repos compensateur', icon: '😴' },
}

// ─── Quota annuel ─────────────────────────────────────────────────────────────
const quotaStyle = computed(() => ({ width: props.annual_stats.quota_pct + '%' }))

const quotaBarClass = computed(() => {
    const pct = props.annual_stats.quota_pct
    if (pct >= 90) return 'bg-danger-500'
    if (pct >= 70) return 'bg-amber-400'
    return 'bg-emerald-500'
})

// ─── Inline review (reviewer + pending) ───────────────────────────────────────
const expandedId    = ref(null)
const reviewComment = ref('')
const submitting    = ref(false)

function handleRowClick(entry) {
    if (props.is_reviewer && entry.status === 'pending') {
        expandedId.value  = expandedId.value === entry.id ? null : entry.id
        reviewComment.value = ''
    } else {
        router.get(route('overtime.show', entry.id))
    }
}

function submitReview(entry, action) {
    if (submitting.value) return
    submitting.value = true
    const url = action === 'approve'
        ? route('overtime.approve', entry.id)
        : route('overtime.reject',  entry.id)
    router.post(url, { comment: reviewComment.value }, {
        preserveScroll: true,
        onFinish:  () => { submitting.value = false },
        onSuccess: () => { expandedId.value = null; reviewComment.value = '' },
    })
}
</script>

<template>
    <Head title="Heures supplémentaires" />

    <AppLayout title="Heures supplémentaires">

        <!-- ── En-tête ────────────────────────────────────────────────────── -->
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-xl font-display font-bold text-slate-900">Heures supplémentaires</h2>
                <p class="text-sm text-slate-500 mt-0.5">
                    {{ is_reviewer ? 'Toutes les déclarations de votre équipe' : 'Vos déclarations d\'heures sup.' }}
                </p>
            </div>
            <Link
                :href="route('overtime.create')"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600 hover:bg-primary-700
                       text-white text-sm font-semibold rounded-xl shadow-sm transition-all active:scale-95"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Déclarer des heures
            </Link>
        </div>

        <!-- ── Compteur annuel ────────────────────────────────────────────── -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <!-- Stats chiffrées -->
                <div class="flex gap-6 flex-1">
                    <div>
                        <p class="text-2xl font-display font-bold text-slate-900 tabular-nums">
                            {{ annual_stats.approved_hours }}h
                        </p>
                        <p class="text-xs text-slate-500 mt-0.5">Approuvées {{ annual_stats.year }}</p>
                    </div>
                    <div>
                        <p class="text-2xl font-display font-bold text-amber-600 tabular-nums">
                            {{ annual_stats.pending_hours }}h
                        </p>
                        <p class="text-xs text-slate-500 mt-0.5">En attente</p>
                    </div>
                    <div>
                        <p class="text-2xl font-display font-bold tabular-nums"
                           :class="annual_stats.remaining_quota < 20 ? 'text-danger-600' : 'text-emerald-600'">
                            {{ annual_stats.remaining_quota }}h
                        </p>
                        <p class="text-xs text-slate-500 mt-0.5">Restant / {{ annual_stats.annual_quota }}h</p>
                    </div>
                </div>

                <!-- Barre quota -->
                <div class="flex-1 min-w-[200px]">
                    <div class="flex justify-between text-xs text-slate-500 mb-1.5">
                        <span>Contingent annuel légal</span>
                        <span class="font-semibold">{{ annual_stats.quota_pct }}%</span>
                    </div>
                    <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-700"
                            :class="quotaBarClass"
                            :style="quotaStyle"
                        />
                    </div>
                    <p v-if="annual_stats.quota_pct >= 80"
                       class="text-xs text-danger-600 font-medium mt-1">
                        ⚠ Approche du contingent de {{ annual_stats.annual_quota }}h
                    </p>
                </div>
            </div>
        </div>

        <!-- ── Onglets statuts ────────────────────────────────────────────── -->
        <div class="flex gap-1 p-1 bg-slate-100 rounded-2xl mb-5 w-fit">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                @click="changeTab(tab.key)"
                class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all"
                :class="status_tab === tab.key
                    ? 'bg-white text-slate-800 shadow-sm'
                    : 'text-slate-500 hover:text-slate-700'"
            >
                {{ tab.label }}
                <span
                    v-if="tab.count.value > 0"
                    class="text-xs px-1.5 py-0.5 rounded-full font-semibold min-w-[20px] text-center"
                    :class="status_tab === tab.key
                        ? (tab.key === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-slate-200 text-slate-600')
                        : 'bg-slate-200 text-slate-500'"
                >{{ tab.count.value }}</span>
            </button>
        </div>

        <!-- ── Liste des entrées ──────────────────────────────────────────── -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

            <!-- Vide -->
            <div v-if="entries.data.length === 0"
                 class="flex flex-col items-center justify-center py-16">
                <div class="text-5xl mb-3">⏱️</div>
                <p class="text-base font-medium text-slate-500">Aucune déclaration</p>
                <p class="text-sm text-slate-400 mt-1">
                    {{ status_tab === 'all'
                        ? 'Déclarez vos premières heures supplémentaires.'
                        : 'Aucune déclaration avec ce statut.' }}
                </p>
                <Link
                    :href="route('overtime.create')"
                    class="mt-4 px-4 py-2 bg-primary-600 text-white text-sm font-semibold rounded-xl
                           hover:bg-primary-700 transition-colors"
                >
                    Faire une déclaration
                </Link>
            </div>

            <!-- Items -->
            <div v-else class="divide-y divide-slate-50">
                <div
                    v-for="entry in entries.data"
                    :key="entry.id"
                    class="overflow-hidden"
                >
                    <!-- ── Ligne principale ─────────────────────────────────── -->
                    <div
                        @click="handleRowClick(entry)"
                        class="flex items-center gap-4 px-5 py-4 transition-colors group cursor-pointer"
                        :class="expandedId === entry.id
                            ? 'bg-amber-50/60'
                            : 'hover:bg-slate-50/60'"
                    >
                        <!-- Avatar (si reviewer voit d'autres employés) -->
                        <div v-if="is_reviewer"
                             class="w-9 h-9 rounded-full bg-primary-100 text-primary-700 flex items-center
                                    justify-center text-xs font-bold shrink-0 overflow-hidden">
                            <img v-if="entry.user_avatar_url" :src="entry.user_avatar_url"
                                 :alt="entry.user_name" class="w-full h-full object-cover" />
                            <span v-else>{{ entry.user_initials }}</span>
                        </div>

                        <!-- Icône source -->
                        <div v-else
                             class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center text-lg shrink-0">
                            {{ sourceCfg[entry.source]?.icon ?? '⏱️' }}
                        </div>

                        <!-- Info principale -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2 flex-wrap">
                                <div>
                                    <p class="text-sm font-semibold text-slate-800">
                                        <span v-if="is_reviewer" class="mr-1">{{ entry.user_name }} —</span>
                                        {{ entry.date_label }}
                                    </p>
                                    <p class="text-xs text-slate-500 mt-0.5 line-clamp-1">
                                        {{ entry.reason ?? 'Aucun motif renseigné' }}
                                    </p>
                                </div>

                                <!-- Badge statut -->
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold shrink-0"
                                    :class="[statusCfg[entry.status]?.bg, statusCfg[entry.status]?.text]"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full"
                                          :class="statusCfg[entry.status]?.dot" />
                                    {{ statusCfg[entry.status]?.label }}
                                </span>
                            </div>

                            <!-- Méta -->
                            <div class="flex items-center gap-3 mt-2 flex-wrap">
                                <span class="inline-flex items-center gap-1 text-xs text-slate-500">
                                    <span class="font-bold text-slate-700 text-sm">{{ entry.hours_label }}</span>
                                    <span class="text-slate-400">·</span>
                                    <span class="font-medium text-primary-600">{{ entry.rate_label }}</span>
                                </span>
                                <span class="text-xs text-slate-400">{{ compensationCfg[entry.compensation]?.icon }} {{ entry.compensation_label }}</span>
                                <span class="text-xs text-slate-400">{{ sourceCfg[entry.source]?.label }}</span>
                            </div>
                        </div>

                        <!-- Indicateur expand (reviewer + pending) -->
                        <div v-if="is_reviewer && entry.status === 'pending'"
                             class="shrink-0 transition-transform duration-200"
                             :class="expandedId === entry.id ? 'rotate-90' : ''">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor"
                                 stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </div>

                        <!-- Chevron navigation (non-reviewer ou non-pending) -->
                        <svg v-else
                             class="w-4 h-4 text-slate-300 group-hover:text-slate-400 shrink-0 transition-colors"
                             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </div>

                    <!-- ── Panneau de validation inline ─────────────────────── -->
                    <Transition
                        enter-active-class="transition-all duration-200 ease-out overflow-hidden"
                        enter-from-class="max-h-0 opacity-0"
                        enter-to-class="max-h-96 opacity-100"
                        leave-active-class="transition-all duration-150 ease-in overflow-hidden"
                        leave-from-class="max-h-96 opacity-100"
                        leave-to-class="max-h-0 opacity-0"
                    >
                        <div v-if="is_reviewer && expandedId === entry.id"
                             class="border-t border-amber-100 bg-amber-50/40 px-5 pb-5 pt-4">

                            <!-- Récap entrée -->
                            <div class="flex flex-wrap gap-4 mb-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="text-slate-500">Durée</span>
                                    <span class="font-bold text-slate-800">{{ entry.hours_label }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-slate-500">Taux</span>
                                    <span class="font-bold text-primary-700">{{ entry.rate_label }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-slate-500">Compensation</span>
                                    <span class="font-medium text-slate-700">{{ entry.compensation_label }}</span>
                                </div>
                                <div v-if="entry.reason" class="flex items-center gap-2">
                                    <span class="text-slate-500">Motif</span>
                                    <span class="text-slate-700 italic">{{ entry.reason }}</span>
                                </div>
                            </div>

                            <!-- Commentaire -->
                            <textarea
                                v-model="reviewComment"
                                placeholder="Commentaire optionnel (visible par l'employé)…"
                                rows="2"
                                class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2 mb-3
                                       focus:outline-none focus:ring-2 focus:ring-primary-300 focus:border-primary-300
                                       resize-none placeholder-slate-400 bg-white"
                            />

                            <!-- Actions -->
                            <div class="flex flex-col sm:flex-row gap-2">
                                <button
                                    @click.stop="submitReview(entry, 'approve')"
                                    :disabled="submitting"
                                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5
                                           bg-emerald-600 hover:bg-emerald-700 active:scale-95
                                           text-white text-sm font-semibold rounded-xl transition-all
                                           disabled:opacity-60 disabled:cursor-not-allowed"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    Approuver
                                </button>
                                <button
                                    @click.stop="submitReview(entry, 'reject')"
                                    :disabled="submitting"
                                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5
                                           bg-danger-600 hover:bg-danger-700 active:scale-95
                                           text-white text-sm font-semibold rounded-xl transition-all
                                           disabled:opacity-60 disabled:cursor-not-allowed"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Refuser
                                </button>
                                <button
                                    @click.stop="expandedId = null"
                                    class="px-4 py-2.5 border border-slate-200 text-slate-600 text-sm font-medium
                                           rounded-xl hover:bg-slate-100 transition-colors"
                                >
                                    Annuler
                                </button>
                                <Link
                                    :href="route('overtime.show', entry.id)"
                                    class="px-4 py-2.5 text-primary-600 text-sm font-medium rounded-xl
                                           hover:bg-primary-50 transition-colors text-center"
                                    @click.stop
                                >
                                    Voir détail →
                                </Link>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="entries.last_page > 1"
                 class="flex items-center justify-between px-5 py-3 border-t border-slate-100">
                <p class="text-sm text-slate-500">
                    {{ entries.from }}–{{ entries.to }} sur {{ entries.total }}
                </p>
                <div class="flex gap-1">
                    <Link v-if="entries.prev_page_url" :href="entries.prev_page_url"
                          class="px-3 py-1.5 text-sm border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-600">
                        ← Préc.
                    </Link>
                    <Link v-if="entries.next_page_url" :href="entries.next_page_url"
                          class="px-3 py-1.5 text-sm border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-600">
                        Suiv. →
                    </Link>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
