<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

// ── Props ──────────────────────────────────────────────────────────────────────
const props = defineProps({
    document:   { type: Object,  required: true },
    signatures: { type: Array,   default: () => [] },
    can_manage: { type: Boolean, default: false },
})

// ── Flash ──────────────────────────────────────────────────────────────────────
const flash = computed(() => usePage().props.flash)

// ── Statuts signature ──────────────────────────────────────────────────────────
const sigStatusCfg = {
    none:      { label: 'Non requis',        icon: '—',  bg: 'bg-slate-100',   text: 'text-slate-500' },
    pending:   { label: 'En attente',        icon: '⏳', bg: 'bg-amber-50',    text: 'text-amber-700' },
    partial:   { label: 'Partiellement signé', icon: '✍️', bg: 'bg-blue-50',  text: 'text-blue-700'  },
    completed: { label: 'Signé et scellé',   icon: '✅', bg: 'bg-emerald-50', text: 'text-emerald-700' },
}

const statusConfig = computed(() => sigStatusCfg[props.document.signature_status] ?? sigStatusCfg.none)

// ── Actions ────────────────────────────────────────────────────────────────────
const requestingSignature = ref(false)
const resending           = ref(false)
const revoking            = ref(false)
const showRevokeConfirm   = ref(false)
const showSignatureImage  = ref(null) // signature id currently previewed

function sendSignatureRequest() {
    if (requestingSignature.value) return
    requestingSignature.value = true
    router.post(route('documents.request-signature', props.document.id), {}, {
        preserveScroll: true,
        onFinish: () => { requestingSignature.value = false },
    })
}

function resendSignature() {
    if (resending.value) return
    resending.value = true
    router.post(route('documents.resend-signature', props.document.id), {}, {
        onFinish: () => { resending.value = false },
    })
}

function revokeSignature() {
    if (revoking.value) return
    revoking.value = true
    router.delete(route('documents.revoke-signature', props.document.id), {
        onFinish:  () => { revoking.value = false },
        onSuccess: () => { showRevokeConfirm.value = false },
    })
}

// ── Computed ───────────────────────────────────────────────────────────────────
const hasPendingSignature = computed(() =>
    props.signatures.some(s => s.status === 'pending')
)

const isSealed = computed(() => props.document.signature_status === 'completed')

const sealedAt = computed(() => {
    if (!isSealed.value) return null
    return props.signatures.find(s => s.status === 'signed')?.signed_at ?? null
})

// ── Utilitaires ────────────────────────────────────────────────────────────────
const mimeTypeLabel = computed(() => {
    const m = props.document.mime_type ?? ''
    if (m.includes('pdf'))        return 'PDF'
    if (m.includes('word'))       return 'Word'
    if (m.includes('spreadsheet') || m.includes('excel')) return 'Excel'
    if (m.includes('image'))      return 'Image'
    if (m.includes('zip'))        return 'Archive ZIP'
    return 'Document'
})

const signerStatusColor = (status) => ({
    signed:   { dot: 'bg-emerald-500', ring: 'ring-emerald-200', text: 'text-emerald-700', bg: 'bg-emerald-50' },
    pending:  { dot: 'bg-amber-400',   ring: 'ring-amber-200',   text: 'text-amber-700',   bg: 'bg-amber-50'   },
    declined: { dot: 'bg-red-500',     ring: 'ring-red-200',     text: 'text-red-700',     bg: 'bg-red-50'     },
    expired:  { dot: 'bg-slate-400',   ring: 'ring-slate-200',   text: 'text-slate-500',   bg: 'bg-slate-50'   },
}[status] ?? { dot: 'bg-slate-300', ring: 'ring-slate-200', text: 'text-slate-500', bg: 'bg-slate-50' })
</script>

<template>
    <Head :title="`${document.name} — Documents`" />

    <AppLayout :title="document.name" :back-url="route('documents.index')">

        <!-- ── Fil d'Ariane ──────────────────────────────────────────────── -->
        <nav class="flex items-center gap-2 text-sm text-slate-500 mb-5">
            <Link :href="route('documents.index')"
                  class="hover:text-primary-600 transition-colors font-medium">
                Documents
            </Link>
            <svg class="w-3.5 h-3.5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-slate-700 font-medium truncate max-w-xs">{{ document.name }}</span>
        </nav>

        <!-- ── Flash ─────────────────────────────────────────────────────── -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            leave-active-class="transition-all duration-200 ease-in"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div v-if="flash?.message" class="mb-5 flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium"
                 :class="flash.type === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200'
                        : flash.type === 'info'    ? 'bg-blue-50 text-blue-800 border border-blue-200'
                        : 'bg-red-50 text-red-800 border border-red-200'">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          :d="flash.type === 'success' ? 'M5 13l4 4L19 7' : 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'"/>
                </svg>
                {{ flash.message }}
            </div>
        </Transition>

        <!-- ── Bannière "Scellé" ──────────────────────────────────────────── -->
        <div v-if="isSealed"
             class="relative overflow-hidden mb-5 rounded-2xl px-6 py-5
                    bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-lg">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center shrink-0">
                    <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="font-bold text-lg leading-tight">Document scellé</p>
                    <p class="text-emerald-100 text-sm mt-0.5">
                        Toutes les signatures ont été collectées et le document est maintenant scellé.
                        <span v-if="sealedAt">Dernière signature le {{ sealedAt }}.</span>
                    </p>
                </div>
                <a v-if="document.certificate_url"
                   :href="document.certificate_url"
                   target="_blank"
                   class="shrink-0 flex items-center gap-2 px-4 py-2 rounded-xl bg-white/20
                          hover:bg-white/30 text-white text-sm font-semibold transition-colors
                          border border-white/30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    <span class="hidden sm:inline">Certificat PDF</span>
                </a>
            </div>
            <!-- Motif décoratif -->
            <div class="absolute -right-6 -top-6 w-32 h-32 rounded-full bg-white/10"/>
            <div class="absolute -right-2 -bottom-8 w-20 h-20 rounded-full bg-white/10"/>
        </div>

        <!-- ── Grille principale ──────────────────────────────────────────── -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

            <!-- ─── Colonne gauche : info document ─────────────────────────── -->
            <div class="lg:col-span-2 space-y-4">

                <!-- Card document -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <!-- En-tête coloré -->
                    <div class="px-6 pt-6 pb-5 bg-gradient-to-br from-slate-50 to-slate-100/50">
                        <div class="flex items-start gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-white shadow-sm flex items-center justify-center text-3xl shrink-0">
                                {{ document.mime_icon }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <h1 class="font-display font-bold text-slate-900 text-base leading-snug">
                                    {{ document.name }}
                                </h1>
                                <p class="text-sm text-slate-500 mt-0.5">{{ document.original_filename }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Métadonnées -->
                    <div class="px-6 py-5 space-y-3.5">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500">Catégorie</span>
                            <span class="font-medium text-slate-700 px-2.5 py-0.5 bg-slate-100 rounded-full text-xs">
                                {{ document.category_label }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500">Type</span>
                            <span class="font-medium text-slate-700">{{ mimeTypeLabel }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500">Taille</span>
                            <span class="font-medium text-slate-700">{{ document.file_size_label }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500">Ajouté le</span>
                            <span class="font-medium text-slate-700">{{ document.created_at }}</span>
                        </div>
                        <div v-if="document.uploaded_by_name" class="flex items-center justify-between text-sm">
                            <span class="text-slate-500">Importé par</span>
                            <span class="font-medium text-slate-700">{{ document.uploaded_by_name }}</span>
                        </div>
                        <template v-if="document.user_name">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-500">Destinataire</span>
                                <span class="font-medium text-primary-700">{{ document.user_name }}</span>
                            </div>
                        </template>
                        <template v-else-if="document.is_company_doc">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-500">Visibilité</span>
                                <span class="font-medium text-slate-700">🏢 Toute l'entreprise</span>
                            </div>
                        </template>
                        <template v-if="document.expires_at">
                            <div class="w-full h-px bg-slate-100"/>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-500">Expiration</span>
                                <span class="font-medium"
                                      :class="document.is_expired ? 'text-red-600' : document.is_expiring_soon ? 'text-amber-600' : 'text-slate-700'">
                                    {{ document.expires_at_label }}
                                    <span v-if="document.is_expired" class="ml-1 text-xs">(expiré)</span>
                                    <span v-else-if="document.is_expiring_soon" class="ml-1 text-xs">(bientôt)</span>
                                </span>
                            </div>
                        </template>
                        <template v-if="document.notes">
                            <div class="w-full h-px bg-slate-100"/>
                            <div class="text-sm">
                                <p class="text-slate-500 mb-1">Notes</p>
                                <p class="text-slate-700 leading-relaxed whitespace-pre-wrap text-sm">{{ document.notes }}</p>
                            </div>
                        </template>
                    </div>

                    <!-- Chiffrement badge -->
                    <div class="px-6 py-3 bg-emerald-50 border-t border-emerald-100 flex items-center gap-2 text-xs text-emerald-700">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Stocké chiffré AES-256
                    </div>

                    <!-- Boutons téléchargement -->
                    <div class="px-6 py-4 border-t border-slate-100 space-y-2">
                        <!-- Télécharger le document -->
                        <a :href="document.download_url"
                           :download="document.original_filename"
                           class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl
                                  bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold
                                  transition-colors active:scale-95 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                            </svg>
                            Télécharger le document
                        </a>
                        <!-- Télécharger le certificat PDF (si signature requise) -->
                        <a v-if="document.certificate_url && signatures.length > 0"
                           :href="document.certificate_url"
                           target="_blank"
                           class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl border
                                  text-sm font-semibold transition-colors active:scale-95"
                           :class="isSealed
                               ? 'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                               : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            {{ isSealed ? 'Certificat de signature' : 'Certificat (en cours)' }}
                        </a>
                    </div>
                </div>

            </div>

            <!-- ─── Colonne droite : signatures ─────────────────────────────── -->
            <div class="lg:col-span-3 space-y-4">

                <!-- Card signatures -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

                    <!-- En-tête avec statut -->
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <h2 class="font-semibold text-slate-800">Signatures</h2>
                            <span v-if="document.requires_signature"
                                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                                  :class="[statusConfig.bg, statusConfig.text]">
                                {{ statusConfig.icon }} {{ statusConfig.label }}
                            </span>
                        </div>

                        <!-- Actions admin/manager -->
                        <div v-if="can_manage && document.requires_signature && document.signature_status !== 'completed'"
                             class="flex items-center gap-2">

                            <!-- Première demande (aucune signature) -->
                            <button v-if="signatures.length === 0 || (!hasPendingSignature && document.signature_status !== 'completed')"
                                    @click="sendSignatureRequest"
                                    :disabled="requestingSignature"
                                    class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-sm font-semibold
                                           bg-primary-600 hover:bg-primary-700 text-white transition-colors
                                           disabled:opacity-60 shadow-sm">
                                <svg v-if="requestingSignature" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                </svg>
                                <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                                Envoyer le lien
                            </button>

                            <!-- Renvoyer (si pending ou refusé) -->
                            <template v-else>
                                <button @click="resendSignature"
                                        :disabled="resending"
                                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-sm font-medium
                                               border border-slate-200 hover:border-slate-300 hover:bg-slate-50
                                               text-slate-600 transition-colors disabled:opacity-60">
                                    <svg v-if="resending" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                    </svg>
                                    <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    Renvoyer
                                </button>
                                <button @click="showRevokeConfirm = true"
                                        class="flex items-center gap-1 px-3 py-1.5 rounded-xl text-sm font-medium
                                               text-slate-400 hover:text-red-600 hover:bg-red-50 border border-transparent
                                               hover:border-red-200 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Annuler
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Aucune signature requise -->
                    <div v-if="!document.requires_signature"
                         class="flex flex-col items-center justify-center py-14 px-6 text-center">
                        <div class="w-14 h-14 rounded-2xl bg-slate-50 flex items-center justify-center mb-3 text-2xl">
                            📄
                        </div>
                        <p class="text-slate-600 font-medium text-sm">Ce document ne requiert pas de signature</p>
                        <p class="text-slate-400 text-xs mt-1">Il a été importé en mode lecture seule.</p>
                    </div>

                    <!-- Aucune signature envoyée -->
                    <div v-else-if="signatures.length === 0"
                         class="flex flex-col items-center justify-center py-14 px-6 text-center">
                        <div class="w-14 h-14 rounded-2xl bg-amber-50 flex items-center justify-center mb-3 text-2xl">
                            ✍️
                        </div>
                        <p class="text-slate-700 font-semibold text-sm">Aucune demande envoyée</p>
                        <p class="text-slate-400 text-xs mt-1 mb-4 max-w-xs">
                            Envoyez un lien de signature à
                            <strong>{{ document.user_name }}</strong>
                            pour commencer le processus.
                        </p>
                        <button v-if="can_manage"
                                @click="sendSignatureRequest"
                                :disabled="requestingSignature"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary-600
                                       hover:bg-primary-700 text-white text-sm font-semibold transition-colors
                                       disabled:opacity-60">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            Envoyer le lien de signature
                        </button>
                    </div>

                    <!-- Timeline des signatures ──────────────────────────── -->
                    <div v-else class="divide-y divide-slate-50">
                        <div
                            v-for="(sig, idx) in signatures"
                            :key="sig.id"
                            class="px-6 py-5"
                        >
                            <div class="flex items-start gap-4">
                                <!-- Indicateur statut -->
                                <div class="relative shrink-0 mt-0.5">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center
                                                text-sm font-bold text-white ring-4"
                                         :class="[signerStatusColor(sig.status).bg.replace('bg-', 'bg-'),
                                                  signerStatusColor(sig.status).ring]"
                                         :style="sig.status === 'signed' ? 'background: #27AE60'
                                               : sig.status === 'pending' ? 'background: #F39C12'
                                               : sig.status === 'declined' ? 'background: #E74C3C'
                                               : 'background: #94a3b8'">
                                        {{ sig.user_initials ?? '?' }}
                                    </div>
                                    <!-- Connecteur vertical -->
                                    <div v-if="idx < signatures.length - 1"
                                         class="absolute left-5 top-10 w-0.5 h-8 bg-slate-200 -translate-x-0.5"/>
                                </div>

                                <!-- Contenu -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2 flex-wrap">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-800">
                                                {{ sig.user_name ?? 'Inconnu' }}
                                            </p>
                                            <p class="text-xs text-slate-500">{{ sig.user_email }}</p>
                                        </div>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold shrink-0"
                                              :class="sig.status === 'signed'   ? 'bg-emerald-50 text-emerald-700'
                                                    : sig.status === 'pending'  ? 'bg-amber-50 text-amber-700'
                                                    : sig.status === 'declined' ? 'bg-red-50 text-red-700'
                                                    : 'bg-slate-100 text-slate-500'">
                                            {{ sig.status_label }}
                                        </span>
                                    </div>

                                    <!-- Signé -->
                                    <template v-if="sig.status === 'signed'">
                                        <p class="text-xs text-slate-500 mt-2 flex items-center gap-1.5">
                                            <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Signé le {{ sig.signed_at }}
                                        </p>
                                        <!-- Signature dessinée -->
                                        <div v-if="sig.signature_image"
                                             class="mt-3 border border-slate-200 rounded-xl overflow-hidden bg-slate-50">
                                            <div class="flex items-center justify-between px-3 py-2 border-b border-slate-200 bg-white">
                                                <p class="text-xs font-medium text-slate-500">Signature manuscrite</p>
                                                <button @click="showSignatureImage = showSignatureImage === sig.id ? null : sig.id"
                                                        class="text-xs text-primary-600 hover:text-primary-700 font-medium transition-colors">
                                                    {{ showSignatureImage === sig.id ? 'Masquer' : 'Afficher' }}
                                                </button>
                                            </div>
                                            <Transition
                                                enter-active-class="transition-all duration-300 ease-out"
                                                enter-from-class="opacity-0 max-h-0"
                                                enter-to-class="opacity-100 max-h-48"
                                                leave-active-class="transition-all duration-200 ease-in"
                                                leave-from-class="opacity-100 max-h-48"
                                                leave-to-class="opacity-0 max-h-0"
                                            >
                                                <div v-if="showSignatureImage === sig.id" class="overflow-hidden">
                                                    <img :src="sig.signature_image" alt="Signature"
                                                         class="max-h-32 w-auto mx-auto p-3 object-contain"/>
                                                </div>
                                            </Transition>
                                        </div>
                                        <!-- Signature tapée -->
                                        <div v-else-if="sig.typed_name"
                                             class="mt-3 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl">
                                            <p class="text-xs text-slate-500 mb-1">Signature dactylographiée</p>
                                            <p class="text-[#1B4F72] text-xl"
                                               style="font-family: 'Dancing Script', 'Brush Script MT', cursive">
                                                {{ sig.typed_name }}
                                            </p>
                                        </div>
                                        <!-- Preuve légale -->
                                        <div class="mt-3 grid grid-cols-2 gap-2">
                                            <div v-if="sig.ip_address"
                                                 class="flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-50
                                                        rounded-lg border border-slate-200 text-xs text-slate-500">
                                                <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                                </svg>
                                                <span class="truncate font-mono">{{ sig.ip_address }}</span>
                                            </div>
                                            <div v-if="sig.document_hash_short"
                                                 class="flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-50
                                                        rounded-lg border border-slate-200 text-xs text-slate-500">
                                                <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                                </svg>
                                                <span class="truncate font-mono">SHA256: {{ sig.document_hash_short }}</span>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- En attente -->
                                    <template v-else-if="sig.status === 'pending'">
                                        <p class="text-xs text-amber-600 mt-2 flex items-center gap-1.5">
                                            <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Lien envoyé · Expire le {{ sig.expires_at }}
                                        </p>
                                        <p class="text-xs text-slate-400 mt-1 flex items-center gap-1.5">
                                            <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                            </svg>
                                            Email envoyé à {{ sig.user_email }}
                                        </p>
                                    </template>

                                    <!-- Refusé -->
                                    <template v-else-if="sig.status === 'declined'">
                                        <p class="text-xs text-red-600 mt-2 flex items-center gap-1.5">
                                            <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            Refusé le {{ sig.declined_at }}
                                        </p>
                                        <div v-if="sig.declined_reason"
                                             class="mt-2 px-3 py-2 bg-red-50 border border-red-200 rounded-lg">
                                            <p class="text-xs text-red-700 italic">« {{ sig.declined_reason }} »</p>
                                        </div>
                                    </template>

                                    <!-- Expiré -->
                                    <template v-else-if="sig.status === 'expired'">
                                        <p class="text-xs text-slate-400 mt-2">Lien expiré le {{ sig.expires_at }}</p>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Info légale en bas de la card -->
                    <div v-if="signatures.length > 0 && document.signature_status === 'completed'"
                         class="px-6 py-4 bg-emerald-50 border-t border-emerald-100">
                        <p class="text-xs text-emerald-700 flex items-start gap-2">
                            <svg class="w-3.5 h-3.5 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            Ce document est scellé et horodaté. L'empreinte SHA-256 garantit qu'il n'a pas été modifié après signature.
                        </p>
                    </div>
                </div>

            </div>
        </div>

        <!-- ── Modal confirmation révocation ─────────────────────────────── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showRevokeConfirm"
                     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center
                            bg-slate-900/60 backdrop-blur-sm p-4"
                     @click.self="showRevokeConfirm = false">
                    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 border border-slate-100">
                        <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 text-center mb-1.5">
                            Annuler la demande de signature ?
                        </h3>
                        <p class="text-sm text-slate-500 text-center mb-6">
                            Le lien de signature envoyé à {{ document.user_name }} sera révoqué.
                            Vous pourrez en renvoyer un nouveau à tout moment.
                        </p>
                        <div class="flex gap-3">
                            <button @click="showRevokeConfirm = false"
                                    class="flex-1 py-2.5 border border-slate-200 text-slate-700 text-sm
                                           font-semibold rounded-xl hover:bg-slate-50 transition-colors">
                                Annuler
                            </button>
                            <button @click="revokeSignature"
                                    :disabled="revoking"
                                    class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white
                                           text-sm font-semibold rounded-xl transition-colors
                                           disabled:opacity-60 flex items-center justify-center gap-2">
                                <svg v-if="revoking" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                {{ revoking ? 'Révocation…' : 'Révoquer le lien' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </AppLayout>
</template>
