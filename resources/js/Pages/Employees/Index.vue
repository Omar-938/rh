<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    employees:     { type: Array,  default: () => [] },
    departments:   { type: Array,  default: () => [] },
    stats:         { type: Object, default: () => ({}) },
    filters:       { type: Object, default: () => ({}) },
    contract_types:{ type: Array,  default: () => [] },
    roles:         { type: Array,  default: () => [] },
})

// ── Filtres (réactifs, mis à jour avec debounce) ───────────────────────────────
const search     = ref(props.filters.search     ?? '')
const roleFilter = ref(props.filters.role       ?? 'all')
const deptFilter = ref(props.filters.department ?? 0)
const statusFilter = ref(props.filters.status   ?? 'active')

let searchTimer = null
watch(search, (v) => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => applyFilters(), 350)
})
watch([roleFilter, deptFilter, statusFilter], () => applyFilters())

function applyFilters() {
    router.get(route('employees.index'), {
        search:     search.value || undefined,
        role:       roleFilter.value !== 'all' ? roleFilter.value : undefined,
        department: deptFilter.value || undefined,
        status:     statusFilter.value !== 'all' ? statusFilter.value : undefined,
    }, { preserveState: true, preserveScroll: true, replace: true })
}

// ── Onglets statut ────────────────────────────────────────────────────────────
const STATUS_TABS = [
    { key: 'active',   label: 'Actifs',   count: computed(() => props.stats.active)   },
    { key: 'inactive', label: 'Inactifs', count: computed(() => props.stats.inactive) },
    { key: 'all',      label: 'Tous',     count: computed(() => props.stats.total)    },
]

// ── Couleurs par rôle ─────────────────────────────────────────────────────────
const ROLE_COLORS = {
    admin:    'bg-primary-100 text-primary-700',
    manager:  'bg-violet-100 text-violet-700',
    employee: 'bg-slate-100 text-slate-600',
}

const CONTRACT_COLORS = {
    cdi:        'bg-success-50 text-success-700',
    cdd:        'bg-warning-50 text-warning-700',
    interim:    'bg-orange-50 text-orange-700',
    stage:      'bg-blue-50 text-blue-700',
    alternance: 'bg-purple-50 text-purple-700',
}

// ── Modal : Ajouter un collaborateur ─────────────────────────────────────────
const showAddModal = ref(false)

const addForm = useForm({
    first_name:    '',
    last_name:     '',
    email:         '',
    role:          'employee',
    department_id: null,
    contract_type: 'cdi',
    hire_date:     '',
    weekly_hours:  35,
    phone:         '',
    employee_id:   '',
})

function openAddModal() {
    addForm.reset()
    showAddModal.value = true
}

function closeAddModal() {
    showAddModal.value = false
    addForm.reset()
    addForm.clearErrors()
}

function submitAdd() {
    addForm.post(route('employees.store'), {
        onSuccess: closeAddModal,
    })
}

// ── Toggle active ─────────────────────────────────────────────────────────────
function toggleActive(employee) {
    router.patch(route('employees.toggle-active', employee.id), {}, { preserveScroll: true })
}

// ── Escape key ────────────────────────────────────────────────────────────────
function onKeydown(e) {
    if (e.key === 'Escape' && showAddModal.value) closeAddModal()
}
</script>

<template>
    <Head title="Collaborateurs" />
    <AppLayout title="Collaborateurs" :back-url="route('dashboard')">
        <div @keydown="onKeydown" tabindex="-1" class="outline-none">

            <!-- ── En-tête ───────────────────────────────────────────────────── -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-display font-bold text-slate-900">Collaborateurs</h1>
                    <p class="text-slate-500 text-sm mt-1">
                        {{ stats.active }} actif{{ stats.active !== 1 ? 's' : '' }}
                        sur {{ stats.total }} au total
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <Link :href="route('employees.import')"
                          class="inline-flex items-center gap-2 px-3.5 py-2 bg-white border border-slate-200
                                 text-slate-700 text-sm font-medium rounded-xl hover:bg-slate-50 transition-all shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                        </svg>
                        Importer CSV
                    </Link>
                    <button @click="openAddModal"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-primary-700 hover:bg-primary-800
                                   text-white text-sm font-semibold rounded-xl shadow-sm shadow-primary-200
                                   transition-all active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Ajouter
                    </button>
                </div>
            </div>

            <!-- ── Onglets statut ─────────────────────────────────────────────── -->
            <div class="flex items-center gap-1 mb-5 bg-slate-100 p-1 rounded-2xl w-fit">
                <button v-for="tab in STATUS_TABS" :key="tab.key"
                        @click="statusFilter = tab.key"
                        class="px-3.5 py-1.5 text-sm font-medium rounded-xl transition-all"
                        :class="statusFilter === tab.key ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                    {{ tab.label }}
                    <span class="ml-1 text-xs font-bold"
                          :class="statusFilter === tab.key ? 'text-primary-600' : 'text-slate-400'">
                        {{ tab.count.value }}
                    </span>
                </button>
            </div>

            <!-- ── Filtres ────────────────────────────────────────────────────── -->
            <div class="flex flex-wrap items-center gap-3 mb-6">
                <!-- Recherche -->
                <div class="relative flex-1 min-w-48 max-w-xs">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                    <input v-model="search" type="text" placeholder="Rechercher…"
                           class="w-full pl-9 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm
                                  placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500
                                  focus:border-transparent transition-all"/>
                </div>

                <!-- Rôle -->
                <select v-model="roleFilter"
                        class="px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm
                               focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all">
                    <option value="all">Tous les rôles</option>
                    <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
                </select>

                <!-- Département -->
                <select v-model="deptFilter"
                        class="px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm
                               focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all">
                    <option :value="0">Tous les départements</option>
                    <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                </select>

                <!-- Reset -->
                <button v-if="search || roleFilter !== 'all' || deptFilter"
                        @click="search = ''; roleFilter = 'all'; deptFilter = 0"
                        class="text-sm text-slate-500 hover:text-slate-700 px-2 py-1 rounded-lg hover:bg-slate-100 transition-colors">
                    Réinitialiser
                </button>
            </div>

            <!-- ── État vide ─────────────────────────────────────────────────── -->
            <div v-if="employees.length === 0"
                 class="bg-white rounded-2xl border border-slate-100 shadow-sm p-16 text-center">
                <div class="text-5xl mb-4">👥</div>
                <p class="text-lg font-semibold text-slate-700 mb-1">Aucun collaborateur</p>
                <p class="text-sm text-slate-400 mb-6">
                    {{ search ? `Aucun résultat pour « ${search} »` : 'Ajoutez votre première équipe.' }}
                </p>
                <div class="flex items-center justify-center gap-3">
                    <button v-if="search" @click="search = ''"
                            class="px-4 py-2 text-sm font-medium border border-slate-200 text-slate-700 rounded-xl hover:bg-slate-50">
                        Effacer la recherche
                    </button>
                    <button @click="openAddModal"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-primary-700 hover:bg-primary-800
                                   text-white text-sm font-semibold rounded-xl">
                        Ajouter un collaborateur
                    </button>
                </div>
            </div>

            <!-- ── Grille des collaborateurs ─────────────────────────────────── -->
            <div v-else class="grid sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4">
                <Link v-for="emp in employees" :key="emp.id"
                      :href="emp.show_url"
                      class="group bg-white rounded-2xl border border-slate-100 shadow-sm p-4
                             hover:border-primary-200 hover:shadow-md transition-all duration-200"
                      :class="!emp.is_active ? 'opacity-60' : ''">

                    <div class="flex items-start justify-between gap-3 mb-3">
                        <!-- Avatar -->
                        <div class="flex items-center gap-3 min-w-0">
                            <div v-if="emp.avatar_url"
                                 class="w-11 h-11 rounded-full bg-cover bg-center flex-shrink-0 ring-2 ring-white shadow-sm"
                                 :style="{ backgroundImage: `url(${emp.avatar_url})` }">
                            </div>
                            <div v-else
                                 class="w-11 h-11 rounded-full bg-primary-600 flex items-center justify-center
                                        text-white text-sm font-bold flex-shrink-0 ring-2 ring-white shadow-sm">
                                {{ emp.initials }}
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900 truncate text-sm group-hover:text-primary-700 transition-colors">
                                    {{ emp.full_name }}
                                </p>
                                <p class="text-xs text-slate-400 truncate">{{ emp.email }}</p>
                            </div>
                        </div>

                        <!-- Indicateur inactif -->
                        <div v-if="!emp.is_active"
                             class="flex-shrink-0 w-2 h-2 rounded-full bg-slate-300 mt-1" title="Inactif"></div>
                        <div v-else class="flex-shrink-0 w-2 h-2 rounded-full bg-success-400 mt-1" title="Actif"></div>
                    </div>

                    <!-- Badges -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                              :class="ROLE_COLORS[emp.role] ?? 'bg-slate-100 text-slate-600'">
                            {{ emp.role_label }}
                        </span>
                        <span v-if="emp.department_name"
                              class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                            {{ emp.department_name }}
                        </span>
                        <span v-if="emp.contract_label"
                              class="text-xs px-2 py-0.5 rounded-full font-medium ml-auto"
                              :class="CONTRACT_COLORS[emp.contract_type] ?? 'bg-slate-100 text-slate-600'">
                            {{ emp.contract_label }}
                        </span>
                    </div>

                    <!-- Footer -->
                    <div class="mt-3 pt-3 border-t border-slate-50 flex items-center gap-3 text-xs text-slate-400">
                        <span v-if="emp.hire_date">📅 {{ emp.hire_date }}</span>
                        <span v-if="emp.weekly_hours" class="ml-auto">{{ emp.weekly_hours }}h/sem.</span>
                    </div>
                </Link>
            </div>

        </div>


        <!-- ════════════════════════════════════════════════════════════════════ -->
        <!-- Modal : Ajouter un collaborateur                                     -->
        <!-- ════════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
                        leave-active-class="transition duration-150 ease-in" leave-to-class="opacity-0">
                <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
                    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="closeAddModal"/>

                    <Transition enter-active-class="transition duration-200 ease-out"
                                enter-from-class="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
                                enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                                leave-active-class="transition duration-150 ease-in"
                                leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                                leave-to-class="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95">
                        <div v-if="showAddModal"
                             class="relative w-full sm:max-w-xl bg-white rounded-t-3xl sm:rounded-2xl shadow-2xl
                                    max-h-[90vh] flex flex-col overflow-hidden">

                            <!-- Handle mobile -->
                            <div class="sm:hidden flex justify-center pt-3 pb-1 flex-shrink-0">
                                <div class="w-10 h-1 rounded-full bg-slate-200"/>
                            </div>

                            <!-- Header -->
                            <div class="flex items-center justify-between px-6 pt-4 sm:pt-6 pb-4 border-b border-slate-100 flex-shrink-0">
                                <h2 class="text-base font-display font-bold text-slate-900">Nouveau collaborateur</h2>
                                <button @click="closeAddModal"
                                        class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center
                                               text-slate-400 hover:text-slate-700 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Corps -->
                            <form @submit.prevent="submitAdd" class="overflow-y-auto flex-1 px-6 py-5 space-y-4">

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">
                                            Prénom <span class="text-danger-500">*</span>
                                        </label>
                                        <input v-model="addForm.first_name" type="text" required
                                               :class="['w-full px-3.5 py-2.5 border rounded-xl text-sm transition-all focus:outline-none focus:ring-2 focus:border-transparent',
                                                   addForm.errors.first_name ? 'border-danger-300 focus:ring-danger-500 bg-danger-50' : 'border-slate-200 focus:ring-primary-500']"/>
                                        <p v-if="addForm.errors.first_name" class="text-danger-600 text-xs mt-1">{{ addForm.errors.first_name }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">
                                            Nom <span class="text-danger-500">*</span>
                                        </label>
                                        <input v-model="addForm.last_name" type="text" required
                                               :class="['w-full px-3.5 py-2.5 border rounded-xl text-sm transition-all focus:outline-none focus:ring-2 focus:border-transparent',
                                                   addForm.errors.last_name ? 'border-danger-300 focus:ring-danger-500 bg-danger-50' : 'border-slate-200 focus:ring-primary-500']"/>
                                        <p v-if="addForm.errors.last_name" class="text-danger-600 text-xs mt-1">{{ addForm.errors.last_name }}</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">
                                        Email professionnel <span class="text-danger-500">*</span>
                                    </label>
                                    <input v-model="addForm.email" type="email" required placeholder="prenom.nom@entreprise.fr"
                                           :class="['w-full px-3.5 py-2.5 border rounded-xl text-sm transition-all focus:outline-none focus:ring-2 focus:border-transparent',
                                               addForm.errors.email ? 'border-danger-300 focus:ring-danger-500 bg-danger-50' : 'border-slate-200 focus:ring-primary-500']"/>
                                    <p v-if="addForm.errors.email" class="text-danger-600 text-xs mt-1">{{ addForm.errors.email }}</p>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Rôle</label>
                                        <select v-model="addForm.role"
                                                class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all">
                                            <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Département</label>
                                        <select v-model="addForm.department_id"
                                                class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all">
                                            <option :value="null">— Aucun —</option>
                                            <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Type de contrat</label>
                                        <select v-model="addForm.contract_type"
                                                class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all">
                                            <option v-for="ct in contract_types" :key="ct.value" :value="ct.value">{{ ct.label }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Date d'embauche</label>
                                        <input v-model="addForm.hire_date" type="date"
                                               class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all"/>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Téléphone</label>
                                        <input v-model="addForm.phone" type="tel" placeholder="+33 6 12 34 56 78"
                                               class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all"/>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Heures / semaine</label>
                                        <input v-model.number="addForm.weekly_hours" type="number" min="1" max="60" step="0.5"
                                               class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all"/>
                                    </div>
                                </div>

                                <div class="p-3.5 bg-primary-50 rounded-xl border border-primary-100 flex items-start gap-2.5 text-xs text-primary-700">
                                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                                    </svg>
                                    Un email sera envoyé à ce collaborateur pour qu'il définisse son mot de passe.
                                </div>

                                <div class="flex gap-3 pt-2 border-t border-slate-100">
                                    <button type="button" @click="closeAddModal"
                                            class="flex-1 px-4 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">
                                        Annuler
                                    </button>
                                    <button type="submit" :disabled="addForm.processing"
                                            class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5
                                                   bg-primary-700 hover:bg-primary-800 disabled:opacity-60
                                                   text-white text-sm font-semibold rounded-xl transition-all">
                                        <svg v-if="addForm.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                        </svg>
                                        {{ addForm.processing ? 'Création…' : 'Créer le collaborateur' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

    </AppLayout>
</template>
