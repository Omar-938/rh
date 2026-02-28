<script setup>
import { ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    leave_request: { type: Object, required: true },
    balance:       { type: Object, default: null },
    can_review:    { type: Boolean, default: false },
    can_cancel:    { type: Boolean, default: false },
})

const showRejectModal = ref(false)
const rejectForm = useForm({ comment: '' })
const approveForm = useForm({ comment: '' })

const statusColors = {
    pending:   { bg: '#FEF3C7', text: '#92400E', dot: '#F59E0B' },
    approved:  { bg: '#DCFCE7', text: '#166534', dot: '#22C55E' },
    rejected:  { bg: '#FEE2E2', text: '#991B1B', dot: '#EF4444' },
    cancelled: { bg: '#F1F5F9', text: '#475569', dot: '#94A3B8' },
}

function approve() {
    approveForm.post(route('leaves.approve', props.leave_request.id))
}

function submitReject() {
    rejectForm.post(route('leaves.reject', props.leave_request.id), {
        onSuccess: () => { showRejectModal.value = false },
    })
}

function cancel() {
    if (confirm('Annuler cette demande de congé ?')) {
        router.delete(route('leaves.cancel', props.leave_request.id))
    }
}
</script>

<template>
    <Head :title="`Demande de ${leave_request.employee.name}`" />

    <AppLayout title="Congés" :back-url="route('leaves.index')">

        <!-- ── Breadcrumb ── -->
        <div class="flex items-center gap-2 text-sm text-slate-500 mb-6">
            <Link :href="route('leaves.index')" class="hover:text-slate-700 transition-colors">
                Congés
            </Link>
            <span>/</span>
            <span class="text-slate-800 font-medium">Détail de la demande</span>
        </div>

        <div class="max-w-2xl mx-auto space-y-4">

            <!-- ── Carte principale ── -->
            <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">

                <!-- En-tête -->
                <div class="px-6 py-5 border-b border-slate-100 flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary-100 text-primary-700
                                flex items-center justify-center text-lg font-bold shrink-0">
                        {{ leave_request.employee.initials }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h2 class="text-lg font-display font-bold text-slate-900">
                            {{ leave_request.employee.name }}
                        </h2>
                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                            <span class="text-base">{{ leave_request.leave_type.icon }}</span>
                            <span class="text-sm font-medium text-slate-600">
                                {{ leave_request.leave_type.name }}
                            </span>
                            <span
                                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full
                                       text-xs font-semibold"
                                :style="{
                                    backgroundColor: statusColors[leave_request.status]?.bg,
                                    color: statusColors[leave_request.status]?.text,
                                }"
                            >
                                <span
                                    class="w-1.5 h-1.5 rounded-full"
                                    :style="{ backgroundColor: statusColors[leave_request.status]?.dot }"
                                />
                                {{ leave_request.status_label }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Détails -->
                <div class="px-6 py-5 space-y-4">

                    <!-- Période & durée -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-50 rounded-xl p-4">
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">
                                Période
                            </p>
                            <p class="text-base font-semibold text-slate-800">
                                {{ leave_request.period_label }}
                            </p>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4">
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">
                                Durée
                            </p>
                            <p class="text-2xl font-display font-bold text-primary-700">
                                {{ leave_request.days_count }} j
                            </p>
                        </div>
                    </div>

                    <!-- Pièce jointe -->
                    <div v-if="leave_request.attachment_url">
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">
                            Justificatif joint
                        </p>
                        <a
                            :href="leave_request.attachment_url"
                            target="_blank"
                            class="inline-flex items-center gap-2.5 px-4 py-2.5 rounded-xl border
                                   border-primary-200 bg-primary-50 text-primary-700 text-sm font-semibold
                                   hover:bg-primary-100 transition-colors"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                            <span class="truncate max-w-xs">{{ leave_request.attachment_original_name ?? 'Télécharger le justificatif' }}</span>
                            <svg class="w-3.5 h-3.5 shrink-0 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                        </a>
                    </div>

                    <!-- Commentaire employé -->
                    <div v-if="leave_request.employee_comment">
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">
                            Commentaire de l'employé
                        </p>
                        <div class="bg-slate-50 rounded-xl p-3 text-sm text-slate-700 italic">
                            "{{ leave_request.employee_comment }}"
                        </div>
                    </div>

                    <!-- Réponse du reviewer -->
                    <div v-if="leave_request.reviewer_comment">
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">
                            Réponse du responsable
                        </p>
                        <div class="rounded-xl p-3 text-sm italic"
                             :class="leave_request.status === 'rejected'
                                 ? 'bg-danger-50 text-danger-700'
                                 : 'bg-success-50 text-success-700'"
                        >
                            "{{ leave_request.reviewer_comment }}"
                            <p v-if="leave_request.reviewer"
                               class="mt-1 text-xs not-italic font-medium opacity-70">
                                — {{ leave_request.reviewer.name }}
                                <template v-if="leave_request.reviewed_at">
                                    · {{ leave_request.reviewed_at }}
                                </template>
                            </p>
                        </div>
                    </div>

                    <!-- Date de demande -->
                    <p class="text-xs text-slate-400">
                        Demande soumise le {{ leave_request.created_at }}
                    </p>
                </div>
            </div>

            <!-- ── Solde de congé ── -->
            <div v-if="balance" class="bg-white rounded-2xl border border-slate-100 p-5">
                <p class="text-sm font-semibold text-slate-700 mb-3">
                    Solde {{ leave_request.leave_type.name }}
                    <span class="text-slate-400 font-normal">({{ new Date().getFullYear() }})</span>
                </p>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="bg-slate-50 rounded-xl p-3">
                        <p class="text-xl font-display font-bold text-slate-800">
                            {{ balance.allocated }}
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">Alloués</p>
                    </div>
                    <div class="bg-amber-50 rounded-xl p-3">
                        <p class="text-xl font-display font-bold text-amber-700">
                            {{ balance.pending }}
                        </p>
                        <p class="text-xs text-amber-500 mt-0.5">En attente</p>
                    </div>
                    <div class="bg-success-50 rounded-xl p-3">
                        <p class="text-xl font-display font-bold text-success-700">
                            {{ balance.effective_remaining }}
                        </p>
                        <p class="text-xs text-success-500 mt-0.5">Disponibles</p>
                    </div>
                </div>
            </div>

            <!-- ── Actions ── -->
            <div v-if="can_review && leave_request.status === 'pending'"
                 class="bg-white rounded-2xl border border-slate-100 p-5">
                <p class="text-sm font-semibold text-slate-700 mb-3">Décision</p>
                <div class="flex gap-3">
                    <button
                        @click="approve"
                        :disabled="approveForm.processing"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5
                               bg-success-600 text-white rounded-xl text-sm font-semibold
                               hover:bg-success-700 transition-colors shadow-sm
                               disabled:opacity-50"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                             stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M5 13l4 4L19 7" />
                        </svg>
                        Approuver
                    </button>
                    <button
                        @click="showRejectModal = true"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5
                               bg-danger-500 text-white rounded-xl text-sm font-semibold
                               hover:bg-danger-600 transition-colors shadow-sm"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                             stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Refuser
                    </button>
                </div>
            </div>

            <div v-if="can_cancel && leave_request.status !== 'cancelled'"
                 class="flex justify-end">
                <button
                    @click="cancel"
                    class="px-4 py-2 text-sm font-medium text-danger-600 border border-danger-200
                           rounded-xl hover:bg-danger-50 transition-colors"
                >
                    Annuler cette demande
                </button>
            </div>

            <!-- Retour -->
            <div class="flex justify-start">
                <Link
                    :href="route('leaves.index')"
                    class="text-sm text-slate-500 hover:text-slate-700 transition-colors"
                >
                    ← Retour à la liste
                </Link>
            </div>
        </div>

        <!-- ── Modal Refus ── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showRejectModal"
                     class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50
                            flex items-center justify-center p-4"
                     @click.self="showRejectModal = false">
                    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6"
                         @click.stop>
                        <h3 class="text-lg font-display font-bold text-slate-900 mb-1">
                            Refuser la demande
                        </h3>
                        <p class="text-sm text-slate-500 mb-4">
                            Indiquez un motif de refus (optionnel mais recommandé).
                        </p>
                        <textarea
                            v-model="rejectForm.comment"
                            rows="3"
                            placeholder="Motif du refus…"
                            class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm
                                   text-slate-800 resize-none focus:outline-none
                                   focus:ring-2 focus:ring-danger-300 transition-shadow mb-4"
                        />
                        <p v-if="rejectForm.errors.comment"
                           class="mb-3 text-xs text-danger-600">
                            {{ rejectForm.errors.comment }}
                        </p>
                        <div class="flex gap-3">
                            <button
                                @click="showRejectModal = false"
                                class="flex-1 px-4 py-2 text-sm font-medium text-slate-600
                                       border border-slate-200 rounded-xl hover:bg-slate-50
                                       transition-colors"
                            >
                                Annuler
                            </button>
                            <button
                                @click="submitReject"
                                :disabled="rejectForm.processing"
                                class="flex-1 px-4 py-2 bg-danger-500 text-white text-sm
                                       font-semibold rounded-xl hover:bg-danger-600
                                       transition-colors disabled:opacity-50"
                            >
                                Confirmer le refus
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </AppLayout>
</template>
