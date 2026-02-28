<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    postings:       { type: Array,  default: () => [] },
    departments:    { type: Array,  default: () => [] },
    stats:          { type: Object, required: true },
    filter:         { type: String, default: 'all' },
    contract_types: { type: Array,  default: () => [] },
})

// ── Filtre ─────────────────────────────────────────────────────────────────────
const activeFilter = ref(props.filter)
function setFilter(f) {
    activeFilter.value = f
    router.get(route('recruitment.index'), { status: f === 'all' ? undefined : f }, { preserveState: true, replace: true })
}

// ── Modal nouvelle offre ───────────────────────────────────────────────────────
const showForm  = ref(false)
const submitting = ref(false)
const form = ref({
    title: '', department_id: '', contract_type: 'cdi',
    location: '', salary_range: '', description: '', requirements: '',
    status: 'open',
})

function submitForm() {
    if (submitting.value) return
    submitting.value = true
    router.post(route('recruitment.postings.store'), form.value, {
        onSuccess: () => {
            showForm.value = false
            form.value = { title: '', department_id: '', contract_type: 'cdi', location: '', salary_range: '', description: '', requirements: '', status: 'open' }
        },
        onFinish: () => { submitting.value = false },
    })
}

// ── Suppression offre ──────────────────────────────────────────────────────────
const isAdmin = computed(() => usePage().props.auth?.user?.role === 'admin')
const confirmDelete = ref(null)   // { id, title, candidates_count }
const deleting      = ref(false)

function askDelete(posting, e) {
    e.preventDefault()
    e.stopPropagation()
    confirmDelete.value = { id: posting.id, title: posting.title, candidates_count: posting.candidates_count, delete_url: posting.delete_url }
}

function doDelete() {
    if (deleting.value) return
    deleting.value = true
    router.delete(confirmDelete.value.delete_url, {
        onSuccess: () => { confirmDelete.value = null },
        onFinish:  () => { deleting.value = false },
    })
}

// ── Helpers ────────────────────────────────────────────────────────────────────
const STATUS_CFG = {
    draft:  { bg: 'bg-slate-100',   text: 'text-slate-600',  dot: 'bg-slate-400'  },
    open:   { bg: 'bg-emerald-50',  text: 'text-emerald-700',dot: 'bg-emerald-500'},
    closed: { bg: 'bg-red-50',      text: 'text-red-700',    dot: 'bg-red-400'    },
}

const STAGE_COLORS = {
    received: 'bg-slate-200',   shortlisted: 'bg-blue-400',
    interview: 'bg-amber-400',  selected: 'bg-indigo-400',
    hired: 'bg-emerald-500',    rejected: 'bg-red-400',
}
const STAGE_ORDER = ['received','shortlisted','interview','selected','hired','rejected']

const FILTERS = [
    { key: 'all',    label: 'Toutes',    count: computed(() => props.stats.total)  },
    { key: 'open',   label: 'Ouvertes',  count: computed(() => props.stats.open)   },
    { key: 'draft',  label: 'Brouillons',count: computed(() => props.stats.draft)  },
    { key: 'closed', label: 'Fermées',   count: computed(() => props.stats.closed) },
]
</script>

<template>
    <Head title="Recrutement" />
    <AppLayout title="Recrutement" :back-url="route('dashboard')">

        <!-- ── En-tête ────────────────────────────────────────────────────── -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-display font-bold text-slate-900">Recrutement</h1>
                <p class="text-sm text-slate-400 mt-0.5">
                    {{ stats.open }} offre{{ stats.open > 1 ? 's' : '' }} ouverte{{ stats.open > 1 ? 's' : '' }}
                    · {{ stats.total }} au total
                </p>
            </div>
            <button @click="showForm = true"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600 hover:bg-primary-700
                           text-white text-sm font-semibold rounded-xl shadow-sm transition-all active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Nouvelle offre
            </button>
        </div>

        <!-- ── Filtres ────────────────────────────────────────────────────── -->
        <div class="flex items-center gap-1.5 mb-6 bg-slate-100 p-1 rounded-xl w-fit">
            <button v-for="f in FILTERS" :key="f.key"
                    @click="setFilter(f.key)"
                    class="px-3.5 py-1.5 text-sm font-medium rounded-lg transition-all"
                    :class="activeFilter === f.key
                        ? 'bg-white text-slate-800 shadow-sm'
                        : 'text-slate-500 hover:text-slate-700'">
                {{ f.label }}
                <span class="ml-1 text-xs font-bold"
                      :class="activeFilter === f.key ? 'text-primary-600' : 'text-slate-400'">
                    {{ f.count.value }}
                </span>
            </button>
        </div>

        <!-- ── État vide ─────────────────────────────────────────────────── -->
        <div v-if="postings.length === 0"
             class="bg-white rounded-2xl border border-slate-100 shadow-sm p-16 text-center">
            <div class="text-5xl mb-4">🎯</div>
            <p class="text-lg font-semibold text-slate-700">Aucune offre d'emploi</p>
            <p class="text-sm text-slate-400 mt-1 mb-5">
                Créez votre première offre pour commencer à gérer vos candidatures.
            </p>
            <button @click="showForm = true"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600 hover:bg-primary-700
                           text-white text-sm font-semibold rounded-xl transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Créer une offre
            </button>
        </div>

        <!-- ── Grille des offres ──────────────────────────────────────────── -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <Link v-for="posting in postings" :key="posting.id"
                  :href="posting.show_url"
                  class="group block bg-white rounded-2xl border border-slate-100 shadow-sm
                         hover:border-primary-200 hover:shadow-md transition-all p-5">

                <!-- Header card -->
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="min-w-0">
                        <h3 class="text-sm font-bold text-slate-800 group-hover:text-primary-700 transition-colors truncate">
                            {{ posting.title }}
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5 truncate">
                            {{ posting.department_name ?? 'Tous départements' }}
                            <span class="mx-1 text-slate-300">·</span>
                            {{ posting.contract_label }}
                        </p>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-semibold"
                              :class="[STATUS_CFG[posting.status]?.bg, STATUS_CFG[posting.status]?.text]">
                            <span class="w-1.5 h-1.5 rounded-full"
                                  :class="STATUS_CFG[posting.status]?.dot" />
                            {{ posting.status_label }}
                        </span>
                        <!-- Bouton supprimer (admin uniquement) -->
                        <button v-if="isAdmin"
                                @click="askDelete(posting, $event)"
                                title="Supprimer l'offre"
                                class="opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded-lg
                                       text-slate-400 hover:text-red-600 hover:bg-red-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Location + salary -->
                <div v-if="posting.location || posting.salary_range"
                     class="flex flex-wrap gap-x-3 gap-y-1 mb-3 text-xs text-slate-500">
                    <span v-if="posting.location" class="flex items-center gap-1">
                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                        </svg>
                        {{ posting.location }}
                    </span>
                    <span v-if="posting.salary_range" class="flex items-center gap-1">
                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75"/>
                        </svg>
                        {{ posting.salary_range }}
                    </span>
                </div>

                <!-- Stage mini-bars -->
                <div class="mb-3">
                    <div class="flex items-center gap-0.5 h-1.5 rounded-full overflow-hidden bg-slate-100">
                        <template v-for="stage in STAGE_ORDER" :key="stage">
                            <div v-if="posting.stage_counts?.[stage]"
                                 class="h-full rounded-sm transition-all"
                                 :class="STAGE_COLORS[stage]"
                                 :style="{ width: `${Math.max(4, (posting.stage_counts[stage] / Math.max(posting.candidates_count, 1)) * 100)}%` }" />
                        </template>
                    </div>
                </div>

                <!-- Stats candidats -->
                <div class="flex items-center justify-between">
                    <span class="text-xs text-slate-500 font-medium">
                        {{ posting.candidates_count || 'Aucun' }} candidat{{ posting.candidates_count > 1 ? 's' : '' }}
                    </span>
                    <div class="flex items-center gap-2 text-xs text-slate-400">
                        <span v-if="posting.stage_counts?.hired"
                              class="font-semibold text-emerald-600">
                            {{ posting.stage_counts.hired }} embauché{{ posting.stage_counts.hired > 1 ? 's' : '' }}
                        </span>
                        <span v-if="posting.stage_counts?.interview"
                              class="font-semibold text-amber-600">
                            {{ posting.stage_counts.interview }} entretien{{ posting.stage_counts.interview > 1 ? 's' : '' }}
                        </span>
                        <span class="text-slate-300">{{ posting.created_at }}</span>
                    </div>
                </div>
            </Link>
        </div>

    </AppLayout>

    <!-- ── Modal Confirmation Suppression ────────────────────────────────────── -->
    <Teleport to="body">
        <Transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-100 ease-in"
                    leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <div v-if="confirmDelete" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="!deleting && (confirmDelete = null)" />

                <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden">
                    <!-- Icône danger -->
                    <div class="flex flex-col items-center pt-8 pb-5 px-6 text-center">
                        <div class="w-14 h-14 rounded-full bg-red-50 flex items-center justify-center mb-4">
                            <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-slate-800 mb-1">Supprimer l'offre ?</h3>
                        <p class="text-sm text-slate-500 mb-1">
                            <span class="font-semibold text-slate-700">« {{ confirmDelete.title }} »</span>
                        </p>
                        <p v-if="confirmDelete.candidates_count > 0" class="text-xs text-red-500 font-medium mt-1">
                            ⚠ {{ confirmDelete.candidates_count }} candidat{{ confirmDelete.candidates_count > 1 ? 's' : '' }} seront également supprimés.
                        </p>
                        <p class="text-xs text-slate-400 mt-2">Cette action est irréversible.</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2 px-6 pb-6">
                        <button @click="confirmDelete = null" :disabled="deleting"
                                class="flex-1 px-4 py-2.5 text-sm font-semibold border border-slate-200 text-slate-700
                                       rounded-xl hover:bg-slate-50 transition-colors disabled:opacity-60">
                            Annuler
                        </button>
                        <button @click="doDelete" :disabled="deleting"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5
                                       bg-red-600 hover:bg-red-700 text-white text-sm font-semibold
                                       rounded-xl transition-colors disabled:opacity-60">
                            <svg v-if="deleting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ deleting ? 'Suppression…' : 'Supprimer' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- ── Modal Nouvelle offre ───────────────────────────────────────────────── -->
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <div v-if="showForm" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="!submitting && (showForm = false)" />

                <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-800">Nouvelle offre d'emploi</h3>
                        <button @click="showForm = false" :disabled="submitting"
                                class="p-1.5 hover:bg-slate-100 rounded-lg text-slate-400 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">

                        <!-- Titre -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">
                                Intitulé du poste <span class="text-red-500">*</span>
                            </label>
                            <input v-model="form.title" type="text" required
                                   placeholder="Ex: Développeur Full-Stack, Commercial terrain…"
                                   class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl
                                          focus:outline-none focus:ring-2 focus:ring-primary-300 focus:border-primary-400" />
                        </div>

                        <!-- Département + Contrat -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Département</label>
                                <select v-model="form.department_id"
                                        class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl
                                               focus:outline-none focus:ring-2 focus:ring-primary-300 bg-white">
                                    <option value="">Tous départements</option>
                                    <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">
                                    Type de contrat <span class="text-red-500">*</span>
                                </label>
                                <select v-model="form.contract_type"
                                        class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl
                                               focus:outline-none focus:ring-2 focus:ring-primary-300 bg-white">
                                    <option v-for="ct in contract_types" :key="ct.value" :value="ct.value">
                                        {{ ct.label }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Lieu + Salaire -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Lieu</label>
                                <input v-model="form.location" type="text" placeholder="Paris, Remote, Lyon…"
                                       class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl
                                              focus:outline-none focus:ring-2 focus:ring-primary-300" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Fourchette salariale</label>
                                <input v-model="form.salary_range" type="text" placeholder="30k–40k€, Selon profil…"
                                       class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl
                                              focus:outline-none focus:ring-2 focus:ring-primary-300" />
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Description du poste</label>
                            <textarea v-model="form.description" rows="4" placeholder="Missions, contexte, équipe…"
                                      class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl
                                             focus:outline-none focus:ring-2 focus:ring-primary-300 resize-none" />
                        </div>

                        <!-- Profil recherché -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Profil recherché</label>
                            <textarea v-model="form.requirements" rows="3"
                                      placeholder="Expérience requise, compétences, diplôme…"
                                      class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl
                                             focus:outline-none focus:ring-2 focus:ring-primary-300 resize-none" />
                        </div>

                        <!-- Statut initial -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Publier immédiatement ?</label>
                            <div class="flex gap-3">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input v-model="form.status" type="radio" value="open"
                                           class="w-4 h-4 text-primary-600" />
                                    <span class="text-sm font-medium text-slate-700">Ouvrir maintenant</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input v-model="form.status" type="radio" value="draft"
                                           class="w-4 h-4 text-slate-500" />
                                    <span class="text-sm font-medium text-slate-500">Enregistrer en brouillon</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-slate-100 bg-slate-50/40">
                        <button @click="showForm = false" :disabled="submitting"
                                class="px-4 py-2 text-sm font-medium border border-slate-200 text-slate-700
                                       rounded-xl hover:bg-slate-50 transition-colors disabled:opacity-60">
                            Annuler
                        </button>
                        <button @click="submitForm" :disabled="submitting || !form.title.trim()"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700
                                       text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-60">
                            <svg v-if="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ submitting ? 'Création…' : form.status === 'open' ? 'Publier l\'offre' : 'Enregistrer brouillon' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
