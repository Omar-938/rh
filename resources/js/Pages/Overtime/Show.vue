<script setup>
import { ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    entry:       { type: Object,  required: true },
    is_reviewer: { type: Boolean, default: false },
    is_pending:  { type: Boolean, default: false },
})

const statusCfg = {
    pending:  { label: 'En attente',  bg: 'bg-amber-50',   text: 'text-amber-700',   border: 'border-amber-200',   dot: 'bg-amber-400'   },
    approved: { label: 'Approuvé',    bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', dot: 'bg-emerald-500' },
    rejected: { label: 'Refusé',      bg: 'bg-danger-50',  text: 'text-danger-700',  border: 'border-danger-200',  dot: 'bg-danger-400'  },
}

// ─── Modals validation ────────────────────────────────────────────────────────
const showApproveModal = ref(false)
const showRejectModal  = ref(false)
const comment = ref('')
const loading = ref(false)

function approve() {
    loading.value = true
    router.post(route('overtime.approve', props.entry.id), { comment: comment.value }, {
        onFinish: () => { loading.value = false; showApproveModal.value = false },
    })
}

function reject() {
    loading.value = true
    router.post(route('overtime.reject', props.entry.id), { comment: comment.value }, {
        onFinish: () => { loading.value = false; showRejectModal.value = false },
    })
}
</script>

<template>
    <Head :title="`HS — ${entry.date_label}`" />

    <AppLayout :title="`Heures sup. — ${entry.date_label}`">

        <div class="max-w-xl mx-auto space-y-5">

            <!-- ── Breadcrumb ──────────────────────────────────────────────── -->
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <Link :href="route('overtime.index')" class="hover:text-slate-700 transition-colors">
                    Heures supplémentaires
                </Link>
                <span class="text-slate-300">›</span>
                <span class="text-slate-700 font-medium">{{ entry.date_label }}</span>
            </div>

            <!-- ── Carte principale ────────────────────────────────────────── -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

                <!-- Header statut -->
                <div
                    class="px-6 py-4 flex items-center justify-between border-b"
                    :class="[statusCfg[entry.status]?.bg, statusCfg[entry.status]?.border]"
                >
                    <div class="flex items-center gap-3">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0"
                              :class="statusCfg[entry.status]?.dot" />
                        <span class="text-sm font-semibold"
                              :class="statusCfg[entry.status]?.text">
                            {{ statusCfg[entry.status]?.label }}
                        </span>
                    </div>
                    <span class="text-xs text-slate-500">{{ entry.source === 'auto' ? '⏱️ Détecté auto.' : '✏️ Manuel' }}</span>
                </div>

                <!-- Corps -->
                <div class="p-6 space-y-4">
                    <!-- Employé (si reviewer) -->
                    <div v-if="is_reviewer" class="flex items-center gap-3 pb-4 border-b border-slate-100">
                        <div class="w-10 h-10 rounded-full bg-primary-100 text-primary-700
                                    flex items-center justify-center text-sm font-bold overflow-hidden shrink-0">
                            <img v-if="entry.user_avatar_url" :src="entry.user_avatar_url"
                                 :alt="entry.user_name" class="w-full h-full object-cover" />
                            <span v-else>{{ entry.user_initials }}</span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">{{ entry.user_name }}</p>
                            <p class="text-xs text-slate-500">Déclarant</p>
                        </div>
                    </div>

                    <!-- Grille infos -->
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-xs text-slate-400 font-medium">Date</dt>
                            <dd class="text-sm font-semibold text-slate-800 mt-0.5 capitalize">{{ entry.date_label }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400 font-medium">Durée</dt>
                            <dd class="text-2xl font-display font-bold text-slate-900 mt-0.5">{{ entry.hours_label }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400 font-medium">Taux de majoration</dt>
                            <dd class="text-sm font-bold mt-0.5"
                                :class="entry.rate === '50' ? 'text-danger-600' : 'text-amber-600'">
                                {{ entry.rate_label }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400 font-medium">Compensation</dt>
                            <dd class="text-sm font-medium text-slate-700 mt-0.5">{{ entry.compensation_label }}</dd>
                        </div>
                    </dl>

                    <!-- Motif -->
                    <div v-if="entry.reason" class="pt-4 border-t border-slate-100">
                        <p class="text-xs text-slate-400 font-medium mb-1.5">Motif déclaré</p>
                        <p class="text-sm text-slate-700 leading-relaxed bg-slate-50 rounded-xl p-4">
                            {{ entry.reason }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- ── Décision du valideur ────────────────────────────────────── -->
            <div v-if="entry.reviewer_name" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <p class="text-xs text-slate-400 font-medium mb-1">
                    Décision de {{ entry.reviewer_name }} · {{ entry.reviewed_at }}
                </p>
                <p v-if="entry.reviewer_comment"
                   class="text-sm text-slate-700 leading-relaxed mt-2 bg-slate-50 rounded-xl p-4">
                    {{ entry.reviewer_comment }}
                </p>
                <p v-else class="text-sm text-slate-400 italic mt-1">Aucun commentaire.</p>
            </div>

            <!-- ── Actions reviewer ────────────────────────────────────────── -->
            <div v-if="is_reviewer && is_pending" class="flex gap-3">
                <button
                    @click="showRejectModal = true"
                    class="flex-1 py-3.5 rounded-xl border-2 border-danger-200 text-danger-700 text-sm
                           font-semibold hover:bg-danger-50 transition-colors"
                >
                    Refuser
                </button>
                <button
                    @click="showApproveModal = true"
                    class="flex-1 py-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm
                           font-semibold transition-all shadow-sm"
                >
                    ✓ Approuver
                </button>
            </div>

            <!-- Retour -->
            <div class="text-center pb-4">
                <Link :href="route('overtime.index')"
                      class="text-sm text-slate-500 hover:text-slate-700 transition-colors">
                    ← Retour à la liste
                </Link>
            </div>
        </div>

        <!-- ── Modal Approuver ─────────────────────────────────────────────── -->
        <Teleport to="body">
            <Transition name="fade">
                <div v-if="showApproveModal"
                     class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-end sm:items-center justify-center p-4"
                     @click.self="showApproveModal = false">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
                        <h3 class="text-lg font-display font-bold text-slate-900 mb-1">Approuver les heures</h3>
                        <p class="text-sm text-slate-500 mb-4">
                            Confirmez l'approbation de {{ entry.hours_label }} pour {{ entry.user_name ?? 'cet employé' }}.
                        </p>
                        <textarea
                            v-model="comment"
                            rows="3"
                            placeholder="Commentaire optionnel…"
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm resize-none
                                   focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-400 mb-4"
                        />
                        <div class="flex gap-3">
                            <button @click="showApproveModal = false"
                                    class="flex-1 py-3 rounded-xl border border-slate-200 text-slate-600 text-sm font-semibold hover:bg-slate-50">
                                Annuler
                            </button>
                            <button @click="approve" :disabled="loading"
                                    class="flex-1 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold disabled:opacity-60 flex items-center justify-center gap-2">
                                <span v-if="loading" class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" />
                                Confirmer l'approbation
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ── Modal Refuser ──────────────────────────────────────────────── -->
        <Teleport to="body">
            <Transition name="fade">
                <div v-if="showRejectModal"
                     class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-end sm:items-center justify-center p-4"
                     @click.self="showRejectModal = false">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
                        <h3 class="text-lg font-display font-bold text-slate-900 mb-1">Refuser les heures</h3>
                        <p class="text-sm text-slate-500 mb-4">
                            Expliquez le motif du refus à {{ entry.user_name ?? 'l\'employé' }}.
                        </p>
                        <textarea
                            v-model="comment"
                            rows="3"
                            placeholder="Motif du refus…"
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm resize-none
                                   focus:outline-none focus:ring-2 focus:ring-danger-500/30 focus:border-danger-400 mb-4"
                        />
                        <div class="flex gap-3">
                            <button @click="showRejectModal = false"
                                    class="flex-1 py-3 rounded-xl border border-slate-200 text-slate-600 text-sm font-semibold hover:bg-slate-50">
                                Annuler
                            </button>
                            <button @click="reject" :disabled="loading"
                                    class="flex-1 py-3 rounded-xl bg-danger-600 hover:bg-danger-700 text-white text-sm font-semibold disabled:opacity-60 flex items-center justify-center gap-2">
                                <span v-if="loading" class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" />
                                Confirmer le refus
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </AppLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
