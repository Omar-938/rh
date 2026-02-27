<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    documents:      { type: Object, required: true },
    category_counts:{ type: Object, default: () => ({}) },
    filter_counts:  { type: Object, default: () => ({}) },
    employees:      { type: Array,  default: () => [] },
    is_uploader:    { type: Boolean, default: false },
    filters:        { type: Object,  required: true },
})

// ─── Définitions statiques ────────────────────────────────────────────────────
const categories = [
    { value: 'contract',    label: 'Contrats',           icon: '📋' },
    { value: 'amendment',   label: 'Avenants',           icon: '✏️' },
    { value: 'certificate', label: 'Attestations',       icon: '🏅' },
    { value: 'rules',       label: 'Règlements',         icon: '📜' },
    { value: 'medical',     label: 'Médicaux',           icon: '🏥' },
    { value: 'identity',    label: 'Pièces d\'identité', icon: '🪪' },
    { value: 'rib',         label: 'RIB',                icon: '🏦' },
    { value: 'review',      label: 'Entretiens',         icon: '💬' },
    { value: 'other',       label: 'Autres',             icon: '📄' },
]

const quickFilters = [
    { key: 'all',       label: 'Tous',              icon: '🗂️' },
    { key: 'signature', label: 'Signature requise', icon: '✍️' },
    { key: 'expiring',  label: 'Expire bientôt',    icon: '⏰' },
    { key: 'expired',   label: 'Expiré',            icon: '⚠️' },
    { key: 'company',   label: 'Entreprise',         icon: '🏢' },
]

const sortOptions = [
    { value: 'created_at', label: 'Date d\'ajout' },
    { value: 'name',       label: 'Nom' },
    { value: 'file_size',  label: 'Taille' },
    { value: 'category',   label: 'Catégorie' },
    { value: 'expires_at', label: 'Expiration' },
]

const sigStatusCfg = {
    pending:   { label: 'Signature requise',   bg: 'bg-amber-50',   text: 'text-amber-700'   },
    partial:   { label: 'Part. signé',         bg: 'bg-amber-50',   text: 'text-amber-700'   },
    completed: { label: 'Signé',               bg: 'bg-emerald-50', text: 'text-emerald-700' },
}

// ─── État local ───────────────────────────────────────────────────────────────
const search    = ref(props.filters.search ?? '')
const showSort  = ref(false)
let searchTimer = null

// ─── Navigation avec filtres ──────────────────────────────────────────────────
function applyFilter(overrides) {
    router.get(
        route('documents.index'),
        { ...props.filters, page: undefined, ...overrides },
        { preserveScroll: true, replace: true }
    )
}

// Debounce recherche
watch(search, (val) => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => applyFilter({ search: val }), 380)
})

function setCategory(cat)      { applyFilter({ category: cat }) }
function setQuickFilter(f)     { applyFilter({ filter: f }) }
function setEmployee(empId)    { applyFilter({ userId: empId }) }

function toggleSort(col) {
    const newDir = props.filters.sort === col && props.filters.dir === 'desc' ? 'asc' : 'desc'
    applyFilter({ sort: col, dir: newDir })
    showSort.value = false
}

function clearFilters() {
    search.value = ''
    applyFilter({ search: '', category: 'all', filter: 'all', userId: '', sort: 'created_at', dir: 'desc' })
}

// ─── État calculé ─────────────────────────────────────────────────────────────
const hasActiveFilters = computed(() =>
    (props.filters.search ?? '') !== '' ||
    props.filters.category !== 'all' ||
    props.filters.filter !== 'all' ||
    (props.filters.userId ?? '') !== ''
)

const activeEmployeeName = computed(() => {
    if (!props.filters.userId) return ''
    return props.employees.find(e => e.id == props.filters.userId)?.name ?? ''
})

const currentSortLabel = computed(() =>
    sortOptions.find(o => o.value === props.filters.sort)?.label ?? 'Date d\'ajout'
)

// ─── Demande de signature ─────────────────────────────────────────────────────
const requestingSignatureId = ref(null)

function requestSignature(docId, url) {
    if (requestingSignatureId.value === docId) return
    requestingSignatureId.value = docId
    router.post(url, {}, {
        preserveScroll: true,
        onFinish: () => { requestingSignatureId.value = null },
    })
}

// ─── Suppression ──────────────────────────────────────────────────────────────
const confirmId  = ref(null)
const deleting   = ref(false)

function askDelete(id)  { confirmId.value = id }
function cancelDelete() { confirmId.value = null }

function confirmDelete() {
    if (!confirmId.value || deleting.value) return
    deleting.value = true
    router.delete(route('documents.destroy', confirmId.value), {
        preserveScroll: true,
        onFinish: () => { deleting.value = false; confirmId.value = null },
    })
}
</script>

<template>
    <Head title="Documents" />

    <AppLayout title="Documents">

        <!-- ── En-tête ────────────────────────────────────────────────────── -->
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-5">
            <div>
                <h2 class="text-xl font-display font-bold text-slate-900">Documents</h2>
                <p class="text-sm text-slate-500 mt-0.5">
                    {{ filter_counts.all }}
                    document{{ filter_counts.all !== 1 ? 's' : '' }} ·
                    <span class="inline-flex items-center gap-1">
                        <svg class="w-3 h-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd"/>
                        </svg>
                        Chiffré AES-256
                    </span>
                </p>
            </div>
            <Link
                v-if="is_uploader"
                :href="route('documents.create')"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600 hover:bg-primary-700
                       text-white text-sm font-semibold rounded-xl shadow-sm transition-all
                       active:scale-95 self-start sm:self-auto"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                Importer
            </Link>
        </div>

        <!-- ── Barre de recherche + tri ───────────────────────────────────── -->
        <div class="flex gap-2 mb-4">
            <!-- Recherche -->
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                         stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                </div>
                <input
                    v-model="search"
                    type="search"
                    placeholder="Rechercher par nom, catégorie, notes…"
                    class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm
                           bg-white focus:outline-none focus:ring-2 focus:ring-primary-300
                           focus:border-primary-300 placeholder-slate-400"
                />
                <button v-if="search" @click="search = ''; applyFilter({ search: '' })"
                        class="absolute inset-y-0 right-3 flex items-center text-slate-400
                               hover:text-slate-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Tri dropdown -->
            <div class="relative">
                <button
                    @click="showSort = !showSort"
                    class="flex items-center gap-2 px-3.5 py-2.5 border border-slate-200 rounded-xl
                           bg-white text-sm font-medium text-slate-600 hover:border-slate-300
                           hover:text-slate-800 transition-colors whitespace-nowrap"
                >
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                         stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5" />
                    </svg>
                    <span class="hidden sm:inline">{{ currentSortLabel }}</span>
                    <span class="text-slate-400 text-xs">{{ filters.dir === 'asc' ? '↑' : '↓' }}</span>
                </button>
                <!-- Dropdown tri -->
                <Transition
                    enter-active-class="transition-all duration-150 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-1"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition-all duration-100 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div v-if="showSort" v-click-outside="() => showSort = false"
                         class="absolute right-0 top-full mt-1.5 w-52 bg-white border border-slate-200
                                rounded-xl shadow-lg z-20 py-1.5 overflow-hidden">
                        <button
                            v-for="opt in sortOptions"
                            :key="opt.value"
                            @click="toggleSort(opt.value)"
                            class="w-full flex items-center justify-between px-4 py-2.5 text-sm
                                   hover:bg-slate-50 transition-colors text-left"
                            :class="filters.sort === opt.value ? 'text-primary-700 font-semibold' : 'text-slate-700'"
                        >
                            {{ opt.label }}
                            <span v-if="filters.sort === opt.value" class="text-primary-500 text-xs font-bold">
                                {{ filters.dir === 'asc' ? '↑' : '↓' }}
                            </span>
                        </button>
                    </div>
                </Transition>
            </div>
        </div>

        <!-- ── Filtres catégories ─────────────────────────────────────────── -->
        <div class="flex gap-2 overflow-x-auto pb-1 mb-3 scrollbar-hide">
            <button
                @click="setCategory('all')"
                class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl border text-sm font-medium
                       whitespace-nowrap transition-all shrink-0"
                :class="filters.category === 'all'
                    ? 'bg-slate-800 text-white border-slate-800'
                    : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300'"
            >
                🗂️ Tous
                <span class="text-xs opacity-70">({{ filter_counts.all }})</span>
            </button>
            <button
                v-for="cat in categories.filter(c => category_counts[c.value] > 0)"
                :key="cat.value"
                @click="setCategory(cat.value)"
                class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl border text-sm font-medium
                       whitespace-nowrap transition-all shrink-0"
                :class="filters.category === cat.value
                    ? 'bg-slate-800 text-white border-slate-800'
                    : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300'"
            >
                {{ cat.icon }} {{ cat.label }}
                <span class="text-xs opacity-70">({{ category_counts[cat.value] }})</span>
            </button>
        </div>

        <!-- ── Filtres rapides + sélecteur employé ───────────────────────── -->
        <div class="flex flex-wrap items-center gap-2 mb-5">
            <!-- Quick filters -->
            <button
                v-for="qf in quickFilters.filter(f => f.key === 'all' || filter_counts[f.key] > 0)"
                :key="qf.key"
                @click="setQuickFilter(qf.key)"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold
                       border transition-all"
                :class="filters.filter === qf.key
                    ? 'bg-primary-600 text-white border-primary-600'
                    : 'bg-white text-slate-600 border-slate-200 hover:border-primary-300 hover:text-primary-600'"
            >
                {{ qf.icon }} {{ qf.label }}
                <span v-if="qf.key !== 'all' && filter_counts[qf.key] > 0"
                      class="px-1 rounded"
                      :class="filters.filter === qf.key ? 'bg-white/25 text-white' : 'bg-slate-100'">
                    {{ filter_counts[qf.key] }}
                </span>
            </button>

            <!-- Séparateur -->
            <span v-if="employees.length > 0" class="text-slate-200 select-none">|</span>

            <!-- Sélecteur employé (admin/manager) -->
            <div v-if="employees.length > 0" class="relative">
                <select
                    :value="filters.userId"
                    @change="setEmployee($event.target.value)"
                    class="appearance-none pl-3 pr-8 py-1.5 border border-slate-200 rounded-full
                           text-xs font-semibold text-slate-600 bg-white hover:border-slate-300
                           focus:outline-none focus:ring-2 focus:ring-primary-300 focus:border-primary-300
                           cursor-pointer transition-colors"
                    :class="filters.userId ? 'border-primary-300 text-primary-700 bg-primary-50' : ''"
                >
                    <option value="">👤 Tous les employés</option>
                    <option value="company">🏢 Entreprise uniquement</option>
                    <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                        {{ emp.name }}
                    </option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor"
                         stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </div>
            </div>

            <!-- Effacer tous les filtres -->
            <button
                v-if="hasActiveFilters"
                @click="clearFilters"
                class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-danger-600
                       transition-colors font-medium ml-1"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Effacer les filtres
            </button>
        </div>

        <!-- ── Résultats ──────────────────────────────────────────────────── -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

            <!-- État vide avec contexte -->
            <div v-if="documents.data.length === 0"
                 class="flex flex-col items-center justify-center py-20 text-center px-6">
                <div class="w-20 h-20 rounded-2xl bg-slate-50 border border-slate-100 flex items-center
                            justify-center text-5xl mb-4">
                    {{ hasActiveFilters ? '🔍' : '📂' }}
                </div>
                <p class="text-base font-semibold text-slate-700">
                    {{ hasActiveFilters ? 'Aucun résultat' : 'Aucun document' }}
                </p>
                <p class="text-sm text-slate-400 mt-1 mb-5 max-w-sm">
                    <template v-if="filters.search">
                        Aucun document ne correspond à « {{ filters.search }} ».
                        Essayez d'autres mots-clés.
                    </template>
                    <template v-else-if="hasActiveFilters">
                        Aucun document ne correspond aux filtres sélectionnés.
                    </template>
                    <template v-else-if="is_uploader">
                        Importez votre premier document pour commencer.
                    </template>
                    <template v-else>
                        Aucun document disponible pour votre compte.
                    </template>
                </p>
                <div class="flex gap-2 flex-wrap justify-center">
                    <button
                        v-if="hasActiveFilters"
                        @click="clearFilters"
                        class="px-4 py-2 text-sm font-semibold border border-slate-200 rounded-xl
                               text-slate-600 hover:bg-slate-50 transition-colors"
                    >
                        Effacer les filtres
                    </button>
                    <Link
                        v-if="is_uploader"
                        :href="route('documents.create')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white
                               text-sm font-semibold rounded-xl hover:bg-primary-700 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                        Importer un document
                    </Link>
                </div>
            </div>

            <!-- Liste des documents -->
            <ul v-else class="divide-y divide-slate-50">
                <li
                    v-for="doc in documents.data"
                    :key="doc.id"
                    class="group relative flex items-center gap-4 px-5 py-4
                           hover:bg-slate-50/60 transition-colors"
                >
                    <!-- Icône type fichier -->
                    <div class="w-11 h-11 rounded-xl bg-slate-100 flex items-center justify-center
                                text-2xl shrink-0 group-hover:bg-slate-200 transition-colors">
                        {{ doc.mime_icon }}
                    </div>

                    <!-- Infos principales -->
                    <div class="flex-1 min-w-0">
                        <!-- Ligne 1 : nom + badges -->
                        <div class="flex items-start gap-2 flex-wrap">
                            <Link :href="route('documents.show', doc.id)"
                                  class="text-sm font-semibold text-slate-800 hover:text-primary-600
                                         transition-colors truncate flex-1 min-w-0">
                                {{ doc.name }}
                            </Link>
                            <div class="flex items-center gap-1.5 flex-wrap shrink-0">
                                <!-- Catégorie -->
                                <span class="text-xs px-2 py-0.5 bg-slate-100 text-slate-500 rounded-full font-medium">
                                    {{ doc.category_label }}
                                </span>
                                <!-- Signature -->
                                <span
                                    v-if="doc.requires_signature && doc.signature_status !== 'none' && sigStatusCfg[doc.signature_status]"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold"
                                    :class="[sigStatusCfg[doc.signature_status].bg, sigStatusCfg[doc.signature_status].text]"
                                >
                                    ✍️ {{ sigStatusCfg[doc.signature_status].label }}
                                </span>
                                <!-- Expiré -->
                                <span v-if="doc.is_expired"
                                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                                             text-xs font-semibold bg-danger-50 text-danger-700">
                                    ⚠ Expiré
                                </span>
                                <!-- Expire bientôt -->
                                <span v-else-if="doc.is_expiring_soon"
                                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                                             text-xs font-semibold bg-amber-50 text-amber-700">
                                    ⏰ {{ doc.expires_at_label }}
                                </span>
                            </div>
                        </div>

                        <!-- Ligne 2 : méta -->
                        <div class="flex items-center gap-2 mt-1 text-xs text-slate-400 flex-wrap">
                            <span>{{ doc.file_size_label }}</span>
                            <span class="text-slate-200">·</span>
                            <span>{{ doc.created_at }}</span>
                            <template v-if="doc.user_name">
                                <span class="text-slate-200">·</span>
                                <span class="text-primary-600 font-medium">{{ doc.user_name }}</span>
                            </template>
                            <template v-else-if="doc.is_company_doc">
                                <span class="text-slate-200">·</span>
                                <span class="text-slate-500">🏢 Entreprise</span>
                            </template>
                            <template v-if="doc.uploaded_by_name && doc.user_name !== doc.uploaded_by_name">
                                <span class="text-slate-200">·</span>
                                <span>par {{ doc.uploaded_by_name }}</span>
                            </template>
                        </div>
                    </div>

                    <!-- Actions (révèlées au hover) -->
                    <div class="flex items-center gap-1.5 shrink-0 sm:opacity-0 sm:group-hover:opacity-100
                                transition-opacity">
                        <!-- Demander signature (admin/manager, doc avec signature non complétée) -->
                        <button
                            v-if="is_uploader && doc.requires_signature && doc.signature_status !== 'completed' && doc.request_signature_url"
                            @click="requestSignature(doc.id, doc.request_signature_url)"
                            :disabled="requestingSignatureId === doc.id"
                            class="flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 hover:bg-amber-100
                                   text-amber-700 text-xs font-semibold rounded-lg transition-colors
                                   disabled:opacity-60"
                            title="Envoyer une demande de signature"
                        >
                            <svg v-if="requestingSignatureId === doc.id"
                                 class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                            <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                 stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                            <span class="hidden md:inline">Signer</span>
                        </button>
                        <!-- Télécharger -->
                        <a
                            :href="doc.download_url"
                            class="flex items-center gap-1.5 px-3 py-1.5 bg-primary-50 hover:bg-primary-100
                                   text-primary-700 text-xs font-semibold rounded-lg transition-colors"
                            :download="doc.original_filename"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                 stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            <span class="hidden sm:inline">Télécharger</span>
                        </a>
                        <!-- Supprimer -->
                        <button
                            v-if="is_uploader"
                            @click="askDelete(doc.id)"
                            class="w-8 h-8 flex items-center justify-center rounded-lg
                                   hover:bg-danger-50 text-slate-400 hover:text-danger-600
                                   transition-colors"
                            title="Supprimer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                 stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                            </svg>
                        </button>
                    </div>
                </li>
            </ul>

            <!-- Pagination -->
            <div v-if="documents.last_page > 1"
                 class="flex items-center justify-between px-5 py-3.5 border-t border-slate-100">
                <p class="text-sm text-slate-500">
                    {{ documents.from }}–{{ documents.to }} sur {{ documents.total }}
                </p>
                <div class="flex gap-1.5">
                    <Link v-if="documents.prev_page_url" :href="documents.prev_page_url"
                          class="px-3 py-1.5 text-sm border border-slate-200 rounded-lg
                                 hover:bg-slate-50 text-slate-600 transition-colors">
                        ← Préc.
                    </Link>
                    <template v-for="page in documents.links.slice(1, -1)" :key="page.label">
                        <Link v-if="page.url"
                              :href="page.url"
                              class="px-3 py-1.5 text-sm border rounded-lg transition-colors"
                              :class="page.active
                                  ? 'bg-primary-600 border-primary-600 text-white font-semibold'
                                  : 'border-slate-200 hover:bg-slate-50 text-slate-600'"
                        >{{ page.label }}</Link>
                        <span v-else class="px-2 py-1.5 text-sm text-slate-400">…</span>
                    </template>
                    <Link v-if="documents.next_page_url" :href="documents.next_page_url"
                          class="px-3 py-1.5 text-sm border border-slate-200 rounded-lg
                                 hover:bg-slate-50 text-slate-600 transition-colors">
                        Suiv. →
                    </Link>
                </div>
            </div>
        </div>

        <!-- ── Modal confirmation suppression ─────────────────────────────── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="confirmId !== null"
                     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center
                            bg-slate-900/60 backdrop-blur-sm p-4"
                     @click.self="cancelDelete">
                    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6
                                border border-slate-100 animate-in">
                        <!-- Icône -->
                        <div class="w-12 h-12 rounded-full bg-danger-50 flex items-center
                                    justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-danger-600" fill="none" stroke="currentColor"
                                 stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 text-center mb-1.5">
                            Supprimer ce document ?
                        </h3>
                        <p class="text-sm text-slate-500 text-center mb-6">
                            Le fichier chiffré sera supprimé définitivement.
                            Cette action est irréversible.
                        </p>
                        <div class="flex gap-3">
                            <button
                                @click="cancelDelete"
                                class="flex-1 py-2.5 border border-slate-200 text-slate-700 text-sm
                                       font-semibold rounded-xl hover:bg-slate-50 transition-colors"
                            >
                                Annuler
                            </button>
                            <button
                                @click="confirmDelete"
                                :disabled="deleting"
                                class="flex-1 py-2.5 bg-danger-600 hover:bg-danger-700 text-white
                                       text-sm font-semibold rounded-xl transition-colors
                                       disabled:opacity-60 flex items-center justify-center gap-2"
                            >
                                <svg v-if="deleting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                {{ deleting ? 'Suppression…' : 'Supprimer définitivement' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </AppLayout>
</template>
