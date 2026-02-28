<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    posting:     Object,
    candidates:  Array,
    stages:      Array,
    departments: Array,
})

// ── Stage accent colors (for avatars / column top bar) ────────────────────────
const STAGE_ACCENT = {
    received:    '#64748b',
    shortlisted: '#2e86c1',
    interview:   '#7c3aed',
    selected:    '#d97706',
    hired:       '#27ae60',
    rejected:    '#e74c3c',
}

const STAGE_BG_LIGHT = {
    received:    'bg-slate-100',
    shortlisted: 'bg-blue-50',
    interview:   'bg-violet-50',
    selected:    'bg-amber-50',
    hired:       'bg-success-50',
    rejected:    'bg-danger-50',
}

// ── Candidates grouped by stage (reactive to prop changes) ───────────────────
const candidatesByStage = computed(() => {
    const map = {}
    props.stages.forEach(s => { map[s.value] = [] })
    props.candidates.forEach(c => {
        if (map[c.stage]) map[c.stage].push(c)
    })
    return map
})

// ── Edit posting modal ────────────────────────────────────────────────────────
const showEditPosting = ref(false)
const editForm = useForm({
    title:         props.posting.title,
    department_id: props.posting.department_id,
    contract_type: props.posting.contract_type,
    location:      props.posting.location ?? '',
    salary_range:  props.posting.salary_range ?? '',
    description:   props.posting.description ?? '',
    requirements:  props.posting.requirements ?? '',
    status:        props.posting.status,
})

function submitEditPosting() {
    editForm.patch(route('recruitment.postings.update', props.posting.id), {
        preserveScroll: true,
        onSuccess: () => { showEditPosting.value = false },
    })
}

// ── Add candidate panel ───────────────────────────────────────────────────────
const showAddPanel = ref(false)
const addStageInit = ref('received')
const addForm = useForm({
    job_posting_id: props.posting.id,
    first_name: '',
    last_name:  '',
    email:      '',
    phone:      '',
    stage:      'received',
    notes:      '',
    cv:         null,
})
const addCvInput = ref(null)

function openAddPanel(stage = 'received') {
    addStageInit.value = stage
    addForm.stage = stage
    showAddPanel.value = true
}

function submitAddCandidate() {
    addForm.post(route('recruitment.candidates.store'), {
        preserveScroll: true,
        forceFormData:  true,
        onSuccess: () => {
            showAddPanel.value = false
            addForm.reset()
            addForm.job_posting_id = props.posting.id
        },
    })
}

// ── Candidate detail panel ────────────────────────────────────────────────────
const selectedId = ref(null)
const selectedCandidate = computed(() =>
    props.candidates.find(c => c.id === selectedId.value) ?? null
)

const detailForm = useForm({
    notes:          '',
    rating:         null,
    interview_date: '',
    phone:          '',
    cv:             null,
})
const detailCvInput = ref(null)

function syncDetailForm(c) {
    detailForm.notes          = c.notes ?? ''
    detailForm.rating         = c.rating ?? null
    detailForm.interview_date = c.interview_date ?? ''
    detailForm.phone          = c.phone ?? ''
    detailForm.cv             = null
}

function openDetail(candidate) {
    selectedId.value = candidate.id
    syncDetailForm(candidate)
}

function closeDetail() {
    selectedId.value = null
}

// Re-sync form when candidate data changes (e.g. after stage move)
watch(selectedCandidate, (c) => {
    if (c) syncDetailForm(c)
}, { deep: false })

function submitDetail() {
    detailForm.patch(route('recruitment.candidates.update', selectedCandidate.value.id), {
        preserveScroll: true,
        forceFormData:  true,
        onSuccess: closeDetail,
    })
}

function confirmDelete() {
    if (!confirm(`Supprimer ${selectedCandidate.value.full_name} du pipeline ? Cette action est irréversible.`)) return
    const id = selectedCandidate.value.id
    closeDetail()
    router.delete(route('recruitment.candidates.destroy', id), { preserveScroll: true })
}

// ── Stage move ────────────────────────────────────────────────────────────────
function setStage(candidate, stageValue) {
    if (stageValue === candidate.stage) return
    router.patch(
        route('recruitment.candidates.update-stage', candidate.id),
        { stage: stageValue },
        { preserveScroll: true }
    )
}

function moveStage(candidate, direction, e) {
    if (e) e.stopPropagation()
    const idx    = props.stages.findIndex(s => s.value === candidate.stage)
    const newIdx = direction === 'prev' ? idx - 1 : idx + 1
    if (newIdx < 0 || newIdx >= props.stages.length) return
    setStage(candidate, props.stages[newIdx].value)
}

// ── Rating helper ─────────────────────────────────────────────────────────────
function toggleRating(n) {
    detailForm.rating = detailForm.rating === n ? null : n
}
</script>

<template>
    <AppLayout :title="`Pipeline — ${posting.title}`" :back-url="route('recruitment.index')">

        <!-- ── Sticky header ──────────────────────────────────────────────────── -->
        <div class="sticky top-0 z-20 bg-white/95 backdrop-blur border-b border-slate-200 px-4 sm:px-6 py-3">
            <div class="max-w-screen-2xl mx-auto flex items-center gap-3 flex-wrap">

                <Link :href="route('recruitment.index')"
                      class="flex-shrink-0 p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                </Link>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-base font-bold text-slate-900 truncate font-display">{{ posting.title }}</h1>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold"
                              :style="{ backgroundColor: STAGE_ACCENT[posting.status] + '22', color: STAGE_ACCENT[posting.status] ?? '#64748b' }">
                            <span class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: STAGE_ACCENT[posting.status] ?? '#64748b' }"></span>
                            {{ posting.status_label }}
                        </span>
                        <span v-if="posting.department_name" class="text-xs text-slate-400">{{ posting.department_name }}</span>
                        <span v-if="posting.contract_label" class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full">{{ posting.contract_label }}</span>
                        <span v-if="posting.location" class="text-xs text-slate-400">📍 {{ posting.location }}</span>
                    </div>
                    <p class="text-xs text-slate-400 mt-0.5">
                        {{ posting.candidates_count }} candidat{{ posting.candidates_count !== 1 ? 's' : '' }}
                        · Créé le {{ posting.created_at }}
                        <template v-if="posting.salary_range"> · 💰 {{ posting.salary_range }}</template>
                    </p>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                    <button @click="showEditPosting = true"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium border border-slate-200
                                   text-slate-700 rounded-xl hover:bg-slate-50 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828 9 16.5l.672-2.828z"/>
                        </svg>
                        Modifier
                    </button>
                    <button @click="openAddPanel()"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-primary-600 hover:bg-primary-700
                                   text-white text-xs font-semibold rounded-xl shadow-sm transition-all active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Ajouter un candidat
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Kanban board ───────────────────────────────────────────────────── -->
        <div class="overflow-x-auto min-h-[calc(100vh-128px)] p-4 sm:p-6">
            <div class="flex gap-4 min-w-max pb-4 items-start">

                <div v-for="stage in stages" :key="stage.value"
                     class="w-72 flex flex-col rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden">

                    <!-- Column top accent -->
                    <div class="h-1 w-full" :style="{ backgroundColor: STAGE_ACCENT[stage.value] }"></div>

                    <!-- Column header -->
                    <div class="px-3 py-2.5 flex items-center gap-2 border-b border-slate-100">
                        <span class="text-lg leading-none select-none">{{ stage.emoji }}</span>
                        <span class="text-sm font-semibold text-slate-700">{{ stage.label }}</span>
                        <span class="ml-auto inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-bold"
                              :style="{ backgroundColor: STAGE_ACCENT[stage.value] + '22', color: STAGE_ACCENT[stage.value] }">
                            {{ (candidatesByStage[stage.value] || []).length }}
                        </span>
                    </div>

                    <!-- Candidate cards -->
                    <div class="flex-1 overflow-y-auto p-2 space-y-2" style="max-height: calc(100vh - 250px)">

                        <div v-for="candidate in candidatesByStage[stage.value]" :key="candidate.id"
                             @click="openDetail(candidate)"
                             class="bg-white border border-slate-200 rounded-xl p-3 cursor-pointer group
                                    hover:border-primary-300 hover:shadow-sm transition-all">

                            <!-- Avatar + name -->
                            <div class="flex items-start gap-2.5">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                                            flex-shrink-0 text-white"
                                     :style="{ backgroundColor: STAGE_ACCENT[candidate.stage] }">
                                    {{ candidate.initials }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-slate-800 truncate leading-tight">
                                        {{ candidate.full_name }}
                                    </p>
                                    <p class="text-xs text-slate-400 truncate">{{ candidate.email }}</p>
                                </div>
                            </div>

                            <!-- Badges -->
                            <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                                <span v-if="candidate.has_cv"
                                      class="inline-flex items-center gap-0.5 text-xs bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded-md">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    CV
                                </span>
                                <span v-if="candidate.interview_label"
                                      class="inline-flex items-center gap-0.5 text-xs bg-violet-50 text-violet-600 px-1.5 py-0.5 rounded-md max-w-[130px] truncate">
                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="truncate">{{ candidate.interview_label }}</span>
                                </span>
                                <span v-if="candidate.rating" class="ml-auto text-xs leading-none">
                                    <span v-for="i in 5" :key="i" :class="i <= candidate.rating ? 'text-amber-400' : 'text-slate-200'">★</span>
                                </span>
                            </div>

                            <!-- Stage move buttons (appear on hover) -->
                            <div class="flex items-center gap-1 mt-2 pt-2 border-t border-slate-100
                                        opacity-0 group-hover:opacity-100 transition-opacity"
                                 @click.stop>
                                <button v-if="stages.findIndex(s => s.value === candidate.stage) > 0"
                                        @click="moveStage(candidate, 'prev', $event)"
                                        class="flex-1 text-xs text-slate-500 hover:text-slate-700 hover:bg-slate-50
                                               rounded-lg px-1.5 py-1 text-center transition-colors truncate">
                                    ← {{ stages[stages.findIndex(s => s.value === candidate.stage) - 1]?.label }}
                                </button>
                                <button v-if="stages.findIndex(s => s.value === candidate.stage) < stages.length - 1"
                                        @click="moveStage(candidate, 'next', $event)"
                                        class="flex-1 text-xs text-primary-600 hover:text-primary-700 hover:bg-primary-50
                                               rounded-lg px-1.5 py-1 text-center transition-colors font-semibold truncate">
                                    {{ stages[stages.findIndex(s => s.value === candidate.stage) + 1]?.label }} →
                                </button>
                            </div>
                        </div>

                        <!-- Empty column CTA -->
                        <button v-if="(candidatesByStage[stage.value] || []).length === 0"
                                @click="openAddPanel(stage.value)"
                                class="w-full border-2 border-dashed border-slate-200 rounded-xl py-6
                                       text-xs text-slate-400 hover:border-primary-300 hover:text-primary-500
                                       transition-colors text-center">
                            + Ajouter un candidat
                        </button>
                    </div>
                </div>
            </div>
        </div>


        <!-- ════════════════════════════════════════════════════════════════════ -->
        <!-- Edit Posting Modal                                                   -->
        <!-- ════════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition duration-150 ease-in"
                        leave-to-class="opacity-0">
                <div v-if="showEditPosting" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showEditPosting = false"></div>

                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col
                                animate-[scale-in_0.15s_ease-out]">
                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 flex-shrink-0">
                            <h2 class="text-base font-bold text-slate-900 font-display">Modifier l'offre</h2>
                            <button @click="showEditPosting = false"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Body -->
                        <form @submit.prevent="submitEditPosting" class="overflow-y-auto flex-1">
                            <div class="px-6 py-5 space-y-5">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">
                                        Intitulé du poste *
                                    </label>
                                    <input v-model="editForm.title" type="text" required
                                           class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl
                                                  focus:outline-none focus:ring-2 focus:ring-primary-300" />
                                    <p v-if="editForm.errors.title" class="text-danger-500 text-xs mt-1">{{ editForm.errors.title }}</p>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Département</label>
                                        <select v-model="editForm.department_id"
                                                class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl
                                                       focus:outline-none focus:ring-2 focus:ring-primary-300">
                                            <option :value="null">— Aucun —</option>
                                            <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Contrat *</label>
                                        <select v-model="editForm.contract_type" required
                                                class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl
                                                       focus:outline-none focus:ring-2 focus:ring-primary-300">
                                            <option value="cdi">CDI</option>
                                            <option value="cdd">CDD</option>
                                            <option value="interim">Intérim</option>
                                            <option value="stage">Stage</option>
                                            <option value="alternance">Alternance</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Lieu</label>
                                        <input v-model="editForm.location" type="text" placeholder="Paris, France"
                                               class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl
                                                      focus:outline-none focus:ring-2 focus:ring-primary-300" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Fourchette salariale</label>
                                        <input v-model="editForm.salary_range" type="text" placeholder="35 000 – 42 000 €"
                                               class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl
                                                      focus:outline-none focus:ring-2 focus:ring-primary-300" />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Description du poste</label>
                                    <textarea v-model="editForm.description" rows="4"
                                              class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl
                                                     focus:outline-none focus:ring-2 focus:ring-primary-300 resize-none"></textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Profil recherché</label>
                                    <textarea v-model="editForm.requirements" rows="3"
                                              class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl
                                                     focus:outline-none focus:ring-2 focus:ring-primary-300 resize-none"></textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Statut</label>
                                    <div class="flex gap-4">
                                        <label v-for="s in [{ v: 'draft', l: '📝 Brouillon' }, { v: 'open', l: '✅ Ouverte' }, { v: 'closed', l: '🔒 Fermée' }]"
                                               :key="s.v" class="flex items-center gap-2 cursor-pointer">
                                            <input type="radio" v-model="editForm.status" :value="s.v"
                                                   class="w-4 h-4 text-primary-600" />
                                            <span class="text-sm text-slate-700">{{ s.l }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-slate-100 bg-slate-50/40 flex-shrink-0">
                                <button type="button" @click="showEditPosting = false"
                                        class="px-4 py-2 text-sm font-medium border border-slate-200 text-slate-700
                                               rounded-xl hover:bg-slate-50 transition-colors">
                                    Annuler
                                </button>
                                <button type="submit" :disabled="editForm.processing"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700
                                               text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-60">
                                    <svg v-if="editForm.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    {{ editForm.processing ? 'Enregistrement…' : 'Enregistrer' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>


        <!-- ════════════════════════════════════════════════════════════════════ -->
        <!-- Add Candidate Slide Panel                                            -->
        <!-- ════════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition duration-150 ease-in"
                        leave-to-class="opacity-0">
                <div v-if="showAddPanel" class="fixed inset-0 z-50 flex">
                    <div class="flex-1 bg-black/30 backdrop-blur-sm" @click="showAddPanel = false"></div>

                    <div class="w-full max-w-md bg-white shadow-2xl flex flex-col overflow-hidden">
                        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 flex-shrink-0">
                            <h2 class="text-base font-bold text-slate-900 font-display">Ajouter un candidat</h2>
                            <button @click="showAddPanel = false"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <form @submit.prevent="submitAddCandidate" class="flex-1 overflow-y-auto px-5 py-5 space-y-4">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Prénom *</label>
                                    <input v-model="addForm.first_name" type="text" required
                                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl
                                                  focus:outline-none focus:ring-2 focus:ring-primary-300" />
                                    <p v-if="addForm.errors.first_name" class="text-danger-500 text-xs mt-1">{{ addForm.errors.first_name }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Nom *</label>
                                    <input v-model="addForm.last_name" type="text" required
                                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl
                                                  focus:outline-none focus:ring-2 focus:ring-primary-300" />
                                    <p v-if="addForm.errors.last_name" class="text-danger-500 text-xs mt-1">{{ addForm.errors.last_name }}</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Email *</label>
                                <input v-model="addForm.email" type="email" required
                                       class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl
                                              focus:outline-none focus:ring-2 focus:ring-primary-300" />
                                <p v-if="addForm.errors.email" class="text-danger-500 text-xs mt-1">{{ addForm.errors.email }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Téléphone</label>
                                <input v-model="addForm.phone" type="tel" placeholder="+33 6 12 34 56 78"
                                       class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl
                                              focus:outline-none focus:ring-2 focus:ring-primary-300" />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Étape initiale</label>
                                <select v-model="addForm.stage"
                                        class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl
                                               focus:outline-none focus:ring-2 focus:ring-primary-300">
                                    <option v-for="stage in stages" :key="stage.value" :value="stage.value">
                                        {{ stage.emoji }} {{ stage.label }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Notes</label>
                                <textarea v-model="addForm.notes" rows="3" placeholder="Source, impressions, contexte…"
                                          class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl
                                                 focus:outline-none focus:ring-2 focus:ring-primary-300 resize-none"></textarea>
                            </div>

                            <!-- CV upload -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">
                                    CV <span class="text-slate-400 normal-case font-normal">(PDF, DOC, DOCX — max 5 Mo)</span>
                                </label>
                                <button type="button" @click="addCvInput?.click()"
                                        class="w-full border-2 border-dashed border-slate-200 rounded-xl p-4 text-center
                                               hover:border-primary-300 hover:bg-primary-50/30 transition-colors">
                                    <svg class="w-7 h-7 text-slate-300 mx-auto mb-1.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                                    </svg>
                                    <p class="text-xs text-slate-500">
                                        <span v-if="addForm.cv" class="font-medium text-primary-600">{{ addForm.cv.name }}</span>
                                        <span v-else>Cliquez pour déposer un fichier</span>
                                    </p>
                                </button>
                                <input ref="addCvInput" type="file" accept=".pdf,.doc,.docx" class="hidden"
                                       @change="e => addForm.cv = e.target.files[0]" />
                                <p v-if="addForm.errors.cv" class="text-danger-500 text-xs mt-1">{{ addForm.errors.cv }}</p>
                            </div>

                            <div class="flex gap-3 pt-3 border-t border-slate-100">
                                <button type="button" @click="showAddPanel = false"
                                        class="flex-1 px-4 py-2.5 text-sm font-medium border border-slate-200
                                               text-slate-700 rounded-xl hover:bg-slate-50 transition-colors">
                                    Annuler
                                </button>
                                <button type="submit" :disabled="addForm.processing"
                                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5
                                               bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold
                                               rounded-xl transition-colors disabled:opacity-60">
                                    <svg v-if="addForm.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    {{ addForm.processing ? 'Ajout…' : 'Ajouter au pipeline' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>


        <!-- ════════════════════════════════════════════════════════════════════ -->
        <!-- Candidate Detail Slide Panel                                         -->
        <!-- ════════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition duration-150 ease-in"
                        leave-to-class="opacity-0">
                <div v-if="selectedCandidate" class="fixed inset-0 z-50 flex">
                    <div class="flex-1 bg-black/30 backdrop-blur-sm" @click="closeDetail"></div>

                    <div class="w-full max-w-md bg-white shadow-2xl flex flex-col overflow-hidden">

                        <!-- Header -->
                        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 flex-shrink-0">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold
                                            text-white flex-shrink-0"
                                     :style="{ backgroundColor: STAGE_ACCENT[selectedCandidate.stage] }">
                                    {{ selectedCandidate.initials }}
                                </div>
                                <div class="min-w-0">
                                    <h2 class="font-bold text-slate-900 truncate">{{ selectedCandidate.full_name }}</h2>
                                    <p class="text-xs text-slate-500">{{ selectedCandidate.stage_emoji }} {{ selectedCandidate.stage_label }}</p>
                                </div>
                            </div>
                            <button @click="closeDetail"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Stage quick-move bar -->
                        <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-100 flex items-center gap-1.5 overflow-x-auto flex-shrink-0">
                            <span class="text-xs text-slate-400 flex-shrink-0 mr-0.5">Étape :</span>
                            <button v-for="stage in stages" :key="stage.value"
                                    @click="setStage(selectedCandidate, stage.value)"
                                    class="flex-shrink-0 text-xs px-2.5 py-1 rounded-full font-medium transition-all"
                                    :class="stage.value === selectedCandidate.stage
                                        ? 'text-white'
                                        : 'bg-white text-slate-500 border border-slate-200 hover:border-slate-300'"
                                    :style="stage.value === selectedCandidate.stage
                                        ? { backgroundColor: STAGE_ACCENT[stage.value] }
                                        : {}">
                                {{ stage.emoji }} {{ stage.label }}
                            </button>
                        </div>

                        <!-- Detail form -->
                        <form @submit.prevent="submitDetail" class="flex-1 overflow-y-auto px-5 py-5 space-y-5">

                            <!-- Contact info block -->
                            <div class="space-y-2.5">
                                <a :href="`mailto:${selectedCandidate.email}`"
                                   class="flex items-center gap-2 text-sm text-primary-600 hover:underline">
                                    <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    {{ selectedCandidate.email }}
                                </a>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.948V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    <input v-model="detailForm.phone" type="tel" placeholder="Téléphone"
                                           class="flex-1 px-3 py-1.5 text-sm border border-slate-200 rounded-lg
                                                  focus:outline-none focus:ring-2 focus:ring-primary-300" />
                                </div>
                                <p class="text-xs text-slate-400">Candidat ajouté le {{ selectedCandidate.created_at }}</p>
                            </div>

                            <!-- Rating -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Évaluation</label>
                                <div class="flex items-center gap-0.5">
                                    <button v-for="i in 5" :key="i" type="button" @click="toggleRating(i)"
                                            class="text-2xl transition-transform hover:scale-110 leading-none"
                                            :class="i <= (detailForm.rating ?? 0) ? 'text-amber-400' : 'text-slate-200'">
                                        ★
                                    </button>
                                    <button v-if="detailForm.rating" type="button" @click="detailForm.rating = null"
                                            class="ml-2 text-xs text-slate-400 hover:text-slate-600 transition-colors">
                                        Effacer
                                    </button>
                                </div>
                            </div>

                            <!-- Interview date -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Date d'entretien</label>
                                <input v-model="detailForm.interview_date" type="datetime-local"
                                       class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl
                                              focus:outline-none focus:ring-2 focus:ring-primary-300" />
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Notes</label>
                                <textarea v-model="detailForm.notes" rows="5"
                                          placeholder="Impressions, points forts, points faibles, source…"
                                          class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl
                                                 focus:outline-none focus:ring-2 focus:ring-primary-300 resize-none"></textarea>
                                <p v-if="detailForm.errors.notes" class="text-danger-500 text-xs mt-1">{{ detailForm.errors.notes }}</p>
                            </div>

                            <!-- CV -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">
                                    Curriculum Vitae
                                </label>
                                <div v-if="selectedCandidate.has_cv"
                                     class="flex items-center gap-2.5 mb-2 px-3 py-2.5 bg-primary-50 rounded-xl border border-primary-100">
                                    <svg class="w-5 h-5 text-primary-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                    </svg>
                                    <span class="text-sm text-primary-700 truncate flex-1">
                                        {{ selectedCandidate.cv_original_name || 'cv.pdf' }}
                                    </span>
                                    <a :href="selectedCandidate.cv_url" target="_blank"
                                       class="flex-shrink-0 px-2.5 py-1 text-xs font-medium border border-primary-200
                                              text-primary-700 rounded-lg hover:bg-primary-100 transition-colors">
                                        Télécharger
                                    </a>
                                </div>
                                <button type="button" @click="detailCvInput?.click()"
                                        class="w-full border-2 border-dashed border-slate-200 rounded-xl p-3 text-center
                                               hover:border-primary-300 hover:bg-primary-50/30 transition-colors">
                                    <p class="text-xs text-slate-500">
                                        <span v-if="detailForm.cv" class="font-medium text-primary-600">{{ detailForm.cv.name }}</span>
                                        <span v-else-if="selectedCandidate.has_cv">Remplacer le CV</span>
                                        <span v-else>Déposer un CV (PDF, DOC, DOCX — max 5 Mo)</span>
                                    </p>
                                </button>
                                <input ref="detailCvInput" type="file" accept=".pdf,.doc,.docx" class="hidden"
                                       @change="e => detailForm.cv = e.target.files[0]" />
                            </div>

                            <!-- Action buttons -->
                            <div class="flex items-center gap-2 pt-3 border-t border-slate-100">
                                <button type="button" @click="confirmDelete"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium
                                               text-danger-600 border border-danger-200 rounded-xl hover:bg-danger-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Supprimer
                                </button>
                                <div class="flex-1"></div>
                                <button type="button" @click="closeDetail"
                                        class="px-3 py-2 text-sm font-medium border border-slate-200 text-slate-700
                                               rounded-xl hover:bg-slate-50 transition-colors">
                                    Fermer
                                </button>
                                <button type="submit" :disabled="detailForm.processing"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700
                                               text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-60">
                                    <svg v-if="detailForm.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    {{ detailForm.processing ? 'Enregistrement…' : 'Enregistrer' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </AppLayout>
</template>
