<script setup>
import { computed, ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    employees: { type: Array, default: () => [] },
})

// ─── Catégories ───────────────────────────────────────────────────────────────
const categories = [
    { value: 'contract',    label: 'Contrat',           icon: '📋', color: 'text-primary-700 bg-primary-50 border-primary-200' },
    { value: 'amendment',   label: 'Avenant',           icon: '✏️',  color: 'text-purple-700  bg-purple-50  border-purple-200'  },
    { value: 'certificate', label: 'Attestation',       icon: '🏅',  color: 'text-emerald-700 bg-emerald-50 border-emerald-200' },
    { value: 'rules',       label: 'Règlement',         icon: '📜',  color: 'text-slate-700   bg-slate-50   border-slate-200'   },
    { value: 'medical',     label: 'Médical',           icon: '🏥',  color: 'text-red-700     bg-red-50     border-red-200'     },
    { value: 'identity',    label: 'Pièce d\'identité', icon: '🪪',  color: 'text-amber-700   bg-amber-50   border-amber-200'   },
    { value: 'rib',         label: 'RIB',               icon: '🏦',  color: 'text-sky-700     bg-sky-50     border-sky-200'     },
    { value: 'review',      label: 'Entretien',         icon: '💬',  color: 'text-indigo-700  bg-indigo-50  border-indigo-200'  },
    { value: 'other',       label: 'Autre',             icon: '📄',  color: 'text-slate-600   bg-slate-50   border-slate-200'   },
]

// ─── Formulaire ───────────────────────────────────────────────────────────────
const form = useForm({
    file:               null,
    name:               '',
    category:           '',
    user_id:            '',
    requires_signature: false,
    expires_at:         '',
    notes:              '',
})

// ─── Drag & Drop ──────────────────────────────────────────────────────────────
const dropZone    = ref(null)
const isDragging  = ref(false)
const fileInput   = ref(null)
const selectedFile = ref(null)

function onDragOver(e) {
    e.preventDefault()
    isDragging.value = true
}
function onDragLeave() { isDragging.value = false }

function onDrop(e) {
    e.preventDefault()
    isDragging.value = false
    const file = e.dataTransfer?.files[0]
    if (file) handleFile(file)
}

function onFileChange(e) {
    const file = e.target.files[0]
    if (file) handleFile(file)
}

function handleFile(file) {
    selectedFile.value = file
    form.file = file
    // Pré-remplir le nom si vide
    if (!form.name) {
        form.name = file.name.replace(/\.[^.]+$/, '') // sans extension
    }
}

function clearFile() {
    selectedFile.value = null
    form.file = null
    if (fileInput.value) fileInput.value.value = ''
}

// ─── Infos fichier ────────────────────────────────────────────────────────────
const fileIcon = computed(() => {
    if (!selectedFile.value) return '📄'
    const mime = selectedFile.value.type
    if (mime.includes('pdf'))   return '📕'
    if (mime.includes('word') || mime.includes('document')) return '📝'
    if (mime.includes('sheet') || mime.includes('excel'))   return '📊'
    if (mime.includes('image')) return '🖼️'
    if (mime.includes('zip') || mime.includes('archive'))   return '📦'
    return '📄'
})

const fileSize = computed(() => {
    const bytes = selectedFile.value?.size ?? 0
    if (bytes < 1024)      return `${bytes} o`
    if (bytes < 1_048_576) return `${(bytes / 1024).toFixed(1)} Ko`
    return `${(bytes / 1_048_576).toFixed(1)} Mo`
})

const isTooLarge = computed(() => (selectedFile.value?.size ?? 0) > 20 * 1024 * 1024)

// ─── Submit ───────────────────────────────────────────────────────────────────
const isValid = computed(() =>
    form.file && form.name.trim() && form.category && !isTooLarge.value
)

function submit() {
    if (!isValid.value) return
    form.post(route('documents.store'), {
        forceFormData: true,
        preserveScroll: true,
    })
}

// Scope du document (entreprise vs. employé)
const targetScope = computed(() => {
    if (!props.employees.length) return 'self'
    return form.user_id ? 'employee' : 'company'
})
</script>

<template>
    <Head title="Importer un document" />

    <AppLayout title="Documents" :back-url="route('documents.index')">

        <!-- ── En-tête ────────────────────────────────────────────────────── -->
        <div class="flex items-start justify-between mb-6">
            <div>
                <h2 class="text-xl font-display font-bold text-slate-900">Importer un document</h2>
                <p class="text-sm text-slate-500 mt-0.5">Le fichier sera chiffré et stocké de manière sécurisée.</p>
            </div>
            <Link :href="route('documents.index')"
                  class="text-sm text-slate-500 hover:text-slate-700 transition-colors font-medium">
                ← Retour
            </Link>
        </div>

        <div class="max-w-2xl">
            <form @submit.prevent="submit" class="space-y-6">

                <!-- ── Zone de dépôt ──────────────────────────────────────── -->
                <div
                    ref="dropZone"
                    @dragover="onDragOver"
                    @dragleave="onDragLeave"
                    @drop="onDrop"
                    class="relative rounded-2xl border-2 border-dashed transition-all duration-200 overflow-hidden"
                    :class="isDragging
                        ? 'border-primary-400 bg-primary-50 scale-[1.01]'
                        : selectedFile
                            ? 'border-emerald-300 bg-emerald-50/40'
                            : 'border-slate-200 bg-slate-50/60 hover:border-primary-300 hover:bg-primary-50/40'"
                >
                    <!-- Sans fichier -->
                    <div v-if="!selectedFile"
                         @click="fileInput.click()"
                         class="flex flex-col items-center justify-center py-14 px-6 cursor-pointer">
                        <div class="w-16 h-16 rounded-2xl bg-white shadow-sm flex items-center justify-center
                                    text-4xl mb-4 transition-transform duration-200"
                             :class="isDragging ? 'scale-110' : ''">
                            📤
                        </div>
                        <p class="font-semibold text-slate-700 text-base mb-1">
                            {{ isDragging ? 'Relâchez pour déposer' : 'Glissez votre fichier ici' }}
                        </p>
                        <p class="text-sm text-slate-500 mb-4">
                            ou cliquez pour sélectionner
                        </p>
                        <span class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white
                                     text-sm font-semibold rounded-xl hover:bg-primary-700 transition-colors
                                     active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                            </svg>
                            Parcourir les fichiers
                        </span>
                        <p class="text-xs text-slate-400 mt-4">
                            PDF, Word, Excel, OpenDocument, Images, ZIP · 20 Mo max
                        </p>
                    </div>

                    <!-- Fichier sélectionné -->
                    <div v-else class="flex items-center gap-4 p-4">
                        <!-- Icône -->
                        <div class="w-14 h-14 rounded-xl bg-white shadow-sm flex items-center
                                    justify-center text-3xl shrink-0">
                            {{ fileIcon }}
                        </div>
                        <!-- Infos -->
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-slate-800 truncate">
                                {{ selectedFile.name }}
                            </p>
                            <p class="text-sm mt-0.5 font-medium"
                               :class="isTooLarge ? 'text-danger-600' : 'text-emerald-600'">
                                {{ fileSize }}
                                <span v-if="isTooLarge" class="ml-1">— fichier trop lourd (max 20 Mo)</span>
                                <span v-else class="ml-1 text-slate-400">· chiffré AES-256 🔒</span>
                            </p>
                        </div>
                        <!-- Changer -->
                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button"
                                    @click="fileInput.click()"
                                    class="text-xs text-primary-600 hover:text-primary-700
                                           font-medium transition-colors">
                                Changer
                            </button>
                            <button type="button"
                                    @click="clearFile"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg
                                           hover:bg-slate-100 text-slate-400 hover:text-slate-600
                                           transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Indicateur chargement si trop lourd -->
                    <div v-if="isTooLarge"
                         class="bg-danger-50 border-t border-danger-100 px-4 py-2.5 flex items-center gap-2">
                        <svg class="w-4 h-4 text-danger-500 shrink-0" fill="none" stroke="currentColor"
                             stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                        <p class="text-sm text-danger-700 font-medium">
                            Ce fichier dépasse la limite de 20 Mo. Veuillez en sélectionner un autre.
                        </p>
                    </div>
                </div>

                <!-- Input file caché -->
                <input
                    ref="fileInput"
                    type="file"
                    class="sr-only"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.png,.jpg,.jpeg,.webp,.zip"
                    @change="onFileChange"
                />

                <!-- Erreur fichier -->
                <p v-if="form.errors.file" class="text-sm text-danger-600 font-medium -mt-4">
                    {{ form.errors.file }}
                </p>

                <!-- ── Catégorie ───────────────────────────────────────────── -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-3">
                        Catégorie <span class="text-danger-500">*</span>
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="cat in categories"
                            :key="cat.value"
                            type="button"
                            @click="form.category = cat.value"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border
                                   text-sm font-medium transition-all active:scale-95"
                            :class="form.category === cat.value
                                ? cat.color + ' shadow-sm ring-2 ring-primary-400 ring-offset-1'
                                : 'text-slate-600 bg-white border-slate-200 hover:border-slate-300'"
                        >
                            <span>{{ cat.icon }}</span>
                            {{ cat.label }}
                        </button>
                    </div>
                    <p v-if="form.errors.category" class="text-sm text-danger-600 mt-2">
                        {{ form.errors.category }}
                    </p>
                </div>

                <!-- ── Nom du document ─────────────────────────────────────── -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Nom du document <span class="text-danger-500">*</span>
                    </label>
                    <input
                        v-model="form.name"
                        type="text"
                        placeholder="Ex : Contrat de travail — Alice Martin"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm
                               focus:outline-none focus:ring-2 focus:ring-primary-300 focus:border-primary-300
                               placeholder-slate-400"
                        :class="{ 'border-danger-300 focus:ring-danger-300': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="text-sm text-danger-600 mt-1">
                        {{ form.errors.name }}
                    </p>
                </div>

                <!-- ── Concerne (employé ou entreprise) ───────────────────── -->
                <div v-if="employees.length > 0">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Concerne
                    </label>
                    <div class="relative">
                        <select
                            v-model="form.user_id"
                            class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm
                                   focus:outline-none focus:ring-2 focus:ring-primary-300 focus:border-primary-300
                                   appearance-none bg-white pr-10"
                        >
                            <option value="">🏢 Document d'entreprise (tous les collaborateurs)</option>
                            <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                👤 {{ emp.name }}
                            </option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                 stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </div>
                    <!-- Indicateur visuel du scope -->
                    <p class="text-xs mt-1.5"
                       :class="targetScope === 'company' ? 'text-primary-600' : 'text-amber-600'">
                        <span v-if="targetScope === 'company'">
                            📢 Visible par tous les collaborateurs de l'entreprise
                        </span>
                        <span v-else>
                            🔒 Visible uniquement par cet employé et les admins
                        </span>
                    </p>
                </div>

                <!-- ── Options avancées ───────────────────────────────────── -->
                <div class="bg-slate-50 rounded-2xl border border-slate-100 p-5 space-y-4">
                    <h3 class="text-sm font-semibold text-slate-700">Options</h3>

                    <!-- Signature requise -->
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-700">Signature requise</p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Envoie une demande de signature électronique à l'employé concerné
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="form.requires_signature = !form.requires_signature"
                            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full
                                   border-2 border-transparent transition-colors duration-200"
                            :class="form.requires_signature ? 'bg-primary-600' : 'bg-slate-200'"
                        >
                            <span
                                class="inline-block h-4 w-4 rounded-full bg-white shadow-sm transition-transform duration-200"
                                :class="form.requires_signature ? 'translate-x-5' : 'translate-x-0.5'"
                            />
                        </button>
                    </div>

                    <!-- Date d'expiration -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">
                            Date d'expiration
                            <span class="font-normal text-slate-400">(optionnel)</span>
                        </label>
                        <input
                            v-model="form.expires_at"
                            type="date"
                            :min="new Date().toISOString().split('T')[0]"
                            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm
                                   focus:outline-none focus:ring-2 focus:ring-primary-300 focus:border-primary-300
                                   bg-white"
                        />
                        <p v-if="form.errors.expires_at" class="text-sm text-danger-600 mt-1">
                            {{ form.errors.expires_at }}
                        </p>
                    </div>

                    <!-- Notes internes -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">
                            Notes internes
                            <span class="font-normal text-slate-400">(optionnel, non visible par l'employé)</span>
                        </label>
                        <textarea
                            v-model="form.notes"
                            rows="2"
                            placeholder="Contexte, numéro de dossier, rappels…"
                            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm
                                   focus:outline-none focus:ring-2 focus:ring-primary-300 focus:border-primary-300
                                   resize-none placeholder-slate-400 bg-white"
                        />
                    </div>
                </div>

                <!-- ── Actions ────────────────────────────────────────────── -->
                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <Link
                        :href="route('documents.index')"
                        class="flex-1 sm:flex-none px-5 py-3 border border-slate-200 text-slate-600
                               text-sm font-semibold rounded-xl hover:bg-slate-50 transition-colors text-center"
                    >
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="!isValid || form.processing"
                        class="flex-1 flex items-center justify-center gap-2 px-6 py-3
                               bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold
                               rounded-xl shadow-sm transition-all active:scale-95
                               disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <!-- Spinner -->
                        <svg v-if="form.processing"
                             class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <!-- Icône upload -->
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor"
                             stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                        {{ form.processing ? 'Chiffrement et import…' : 'Importer le document' }}
                    </button>
                </div>

                <!-- Info sécurité -->
                <p class="text-xs text-slate-400 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor"
                         stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                    Chiffré AES-256-CBC à l'upload · Stocké hors de l'espace public · Accès contrôlé par rôle
                </p>
            </form>
        </div>

    </AppLayout>
</template>
