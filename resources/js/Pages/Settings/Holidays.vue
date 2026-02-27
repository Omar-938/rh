<script setup>
import { ref, computed, nextTick } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    holidays:     { type: Array,  default: () => [] },
    year:         { type: Number, required: true },
    years:        { type: Array,  default: () => [] },
    yearlyCounts: { type: Object, default: () => ({}) },
})

// ── Changement d'année ────────────────────────────────────────────────────────
function changeYear(y) {
    router.get(route('settings.holidays.index'), { year: y }, { preserveScroll: true })
}

// ── Groupement par mois ───────────────────────────────────────────────────────
const byMonth = computed(() => {
    const map = {}
    props.holidays.forEach(h => {
        if (!map[h.month_num]) {
            map[h.month_num] = { month: h.month, holidays: [] }
        }
        map[h.month_num].holidays.push(h)
    })
    return Object.values(map)
})

// ── Import fériés français ────────────────────────────────────────────────────
const importForm = useForm({ year: props.year })
const importing  = ref(false)

function importFrench() {
    importForm.year = props.year
    importForm.post(route('settings.holidays.import-french'), {
        preserveScroll: true,
    })
}

// ── Ajout / modification ──────────────────────────────────────────────────────
const showModal    = ref(false)
const isEditing    = ref(false)
const editingId    = ref(null)
const nameInputRef = ref(null)

const form = useForm({
    name:         '',
    date:         '',
    is_recurring: false,
})

function openCreate() {
    isEditing.value = false
    editingId.value = null
    form.reset()
    form.date         = `${props.year}-01-01`
    form.is_recurring = false
    showModal.value   = true
    nextTick(() => nameInputRef.value?.focus())
}

function openEdit(holiday) {
    isEditing.value   = true
    editingId.value   = holiday.id
    form.name         = holiday.name
    form.date         = holiday.date
    form.is_recurring = holiday.is_recurring
    form.clearErrors()
    showModal.value   = true
    nextTick(() => nameInputRef.value?.focus())
}

function closeModal() {
    showModal.value = false
    form.reset()
    form.clearErrors()
}

function submitForm() {
    if (isEditing.value) {
        form.put(route('settings.holidays.update', editingId.value), {
            preserveScroll: true,
            onSuccess: closeModal,
        })
    } else {
        form.post(route('settings.holidays.store'), {
            preserveScroll: true,
            onSuccess: closeModal,
        })
    }
}

// ── Suppression ───────────────────────────────────────────────────────────────
const deleteTarget    = ref(null)
const showDeleteModal = ref(false)
const deleteForm      = useForm({})

function confirmDelete(holiday) {
    deleteTarget.value    = holiday
    showDeleteModal.value = true
}

function cancelDelete() {
    showDeleteModal.value = false
    deleteTarget.value    = null
}

function doDelete() {
    deleteForm.delete(route('settings.holidays.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: cancelDelete,
    })
}

// ── Fermeture Escape ──────────────────────────────────────────────────────────
function onKeydown(e) {
    if (e.key !== 'Escape') return
    if (showDeleteModal.value) cancelDelete()
    else if (showModal.value) closeModal()
}

// ── Couleur du jour de semaine ────────────────────────────────────────────────
function weekdayColor(holiday) {
    return holiday.is_recurring
        ? 'bg-primary-50 text-primary-700 border-primary-100'
        : 'bg-violet-50 text-violet-700 border-violet-100'
}
</script>

<template>
    <Head :title="`Jours fériés ${year} — Paramètres`" />

    <AppLayout title="Paramètres">
        <div @keydown="onKeydown" tabindex="-1" class="outline-none">

            <!-- ── En-tête ───────────────────────────────────────────────────── -->
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                        <span>Paramètres</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                        </svg>
                        <span class="text-slate-900 font-medium">Jours fériés</span>
                    </div>
                    <h1 class="text-2xl font-display font-bold text-slate-900">Jours fériés</h1>
                    <p class="text-slate-500 text-sm mt-1">
                        {{ holidays.length }} jour{{ holidays.length !== 1 ? 's' : '' }} férié{{ holidays.length !== 1 ? 's' : '' }}
                        en {{ year }} — exclus du calcul des congés.
                    </p>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0 flex-wrap">
                    <!-- Import fériés français -->
                    <button @click="importFrench" :disabled="importForm.processing"
                            class="inline-flex items-center gap-2 px-3.5 py-2 bg-white border border-slate-200
                                   text-slate-700 text-sm font-medium rounded-xl hover:bg-slate-50
                                   disabled:opacity-60 transition-all shadow-sm">
                        <span class="text-base leading-none">🇫🇷</span>
                        {{ importForm.processing ? 'Import…' : `Importer les fériés ${year}` }}
                    </button>

                    <!-- Ajouter un férié -->
                    <button @click="openCreate"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-primary-700 hover:bg-primary-800
                                   text-white text-sm font-semibold rounded-xl transition-all active:scale-95
                                   shadow-sm shadow-primary-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Ajouter
                    </button>
                </div>
            </div>

            <!-- ── Sélecteur d'année ──────────────────────────────────────────── -->
            <div class="flex items-center gap-1 mb-8 bg-slate-100 p-1 rounded-2xl w-fit">
                <button v-for="y in years" :key="y" @click="changeYear(y)"
                        class="relative px-4 py-1.5 text-sm font-medium rounded-xl transition-all"
                        :class="y === year
                            ? 'bg-white text-slate-900 shadow-sm'
                            : 'text-slate-500 hover:text-slate-700'">
                    {{ y }}
                    <span v-if="yearlyCounts[y]"
                          class="absolute -top-1.5 -right-1.5 w-4 h-4 text-[10px] font-bold rounded-full
                                 flex items-center justify-center"
                          :class="y === year
                              ? 'bg-primary-600 text-white'
                              : 'bg-slate-400 text-white'">
                        {{ yearlyCounts[y] > 9 ? '9+' : yearlyCounts[y] }}
                    </span>
                </button>
            </div>

            <!-- ── État vide ─────────────────────────────────────────────────── -->
            <div v-if="holidays.length === 0"
                 class="bg-white rounded-2xl border border-slate-100 shadow-sm p-16 text-center">
                <div class="text-5xl mb-4">🗓️</div>
                <p class="text-lg font-semibold text-slate-700 mb-1">
                    Aucun jour férié pour {{ year }}
                </p>
                <p class="text-sm text-slate-400 mb-6 max-w-sm mx-auto">
                    Importez les 11 jours fériés légaux français en un clic, ou ajoutez des jours spécifiques à votre entreprise.
                </p>
                <div class="flex items-center justify-center gap-3 flex-wrap">
                    <button @click="importFrench" :disabled="importForm.processing"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-slate-200
                                   text-slate-700 text-sm font-semibold rounded-xl hover:bg-slate-50 shadow-sm
                                   disabled:opacity-60 transition-all">
                        <span class="text-lg">🇫🇷</span>
                        {{ importForm.processing ? 'Import…' : `Importer les fériés ${year}` }}
                    </button>
                    <button @click="openCreate"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-700 hover:bg-primary-800
                                   text-white text-sm font-semibold rounded-xl transition-all active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Ajouter un jour personnalisé
                    </button>
                </div>
            </div>

            <!-- ── Liste groupée par mois ────────────────────────────────────── -->
            <div v-else class="space-y-6">
                <div v-for="group in byMonth" :key="group.month">

                    <!-- En-tête du mois -->
                    <div class="flex items-center gap-3 mb-3">
                        <h2 class="text-sm font-bold text-slate-700 capitalize">{{ group.month }}</h2>
                        <div class="flex-1 h-px bg-slate-200"></div>
                        <span class="text-xs text-slate-400 font-medium">{{ group.holidays.length }} jour{{ group.holidays.length > 1 ? 's' : '' }}</span>
                    </div>

                    <!-- Holidays du mois -->
                    <div class="space-y-2">
                        <div v-for="holiday in group.holidays" :key="holiday.id"
                             class="group flex items-center gap-4 bg-white rounded-xl border border-slate-100
                                    px-4 py-3 hover:border-slate-200 hover:shadow-sm transition-all">

                            <!-- Date badge -->
                            <div class="flex-shrink-0 w-14 text-center">
                                <div class="text-xl font-bold text-slate-800 leading-none">
                                    {{ holiday.date.slice(8, 10) }}
                                </div>
                                <div class="text-xs text-slate-400 mt-0.5 truncate">
                                    {{ holiday.day_of_week.slice(0, 3) }}.
                                </div>
                            </div>

                            <!-- Séparateur vertical -->
                            <div class="w-px h-10 bg-slate-200 flex-shrink-0"></div>

                            <!-- Nom + badge -->
                            <div class="flex-1 min-w-0 flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-semibold text-slate-900 truncate">{{ holiday.name }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full border font-medium flex-shrink-0"
                                      :class="weekdayColor(holiday)">
                                    {{ holiday.is_recurring ? 'Récurrent' : 'Date fixe' }}
                                </span>
                            </div>

                            <!-- Actions (apparaissent au hover) -->
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0">
                                <button @click="openEdit(holiday)"
                                        class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition-colors"
                                        title="Modifier">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                                    </svg>
                                </button>
                                <button @click="confirmDelete(holiday)"
                                        class="p-1.5 rounded-lg hover:bg-danger-50 text-slate-400 hover:text-danger-600 transition-colors"
                                        title="Supprimer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Légende -->
                <div class="flex items-center gap-4 pt-2 text-xs text-slate-400">
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-primary-100 border border-primary-200 inline-block"></span>
                        Récurrent (même date chaque année)
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-violet-100 border border-violet-200 inline-block"></span>
                        Date fixe (ex: Pâques, Ascension)
                    </div>
                </div>
            </div>

        </div>


        <!-- ════════════════════════════════════════════════════════════════════ -->
        <!-- Modal : Ajouter / Modifier                                           -->
        <!-- ════════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0"
                        enter-to-class="opacity-100"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0">
                <div v-if="showModal"
                     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
                    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="closeModal"/>

                    <Transition enter-active-class="transition duration-200 ease-out"
                                enter-from-class="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
                                enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                                leave-active-class="transition duration-150 ease-in"
                                leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                                leave-to-class="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95">
                        <div v-if="showModal"
                             class="relative w-full sm:max-w-md bg-white rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">

                            <!-- Handle mobile -->
                            <div class="sm:hidden flex justify-center pt-3 pb-1">
                                <div class="w-10 h-1 rounded-full bg-slate-200"/>
                            </div>

                            <!-- Header -->
                            <div class="flex items-center justify-between px-6 pt-4 sm:pt-6 pb-4 border-b border-slate-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-primary-50 flex items-center justify-center text-lg">
                                        🗓️
                                    </div>
                                    <h2 class="text-base font-display font-bold text-slate-900">
                                        {{ isEditing ? 'Modifier le jour férié' : 'Nouveau jour férié' }}
                                    </h2>
                                </div>
                                <button @click="closeModal"
                                        class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center
                                               text-slate-400 hover:text-slate-700 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Corps -->
                            <form @submit.prevent="submitForm" class="px-6 py-5 space-y-5">

                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        Nom <span class="text-danger-500">*</span>
                                    </label>
                                    <input ref="nameInputRef" v-model="form.name" type="text"
                                           placeholder="ex: Pont du 2 mai, Journée d'entreprise…"
                                           maxlength="255" required
                                           :class="['w-full px-4 py-2.5 border rounded-xl text-sm transition-all focus:outline-none focus:ring-2 focus:border-transparent',
                                               form.errors.name ? 'border-danger-300 focus:ring-danger-500 bg-danger-50' : 'border-slate-200 focus:ring-primary-500']"/>
                                    <p v-if="form.errors.name" class="mt-1 text-xs text-danger-600">{{ form.errors.name }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        Date <span class="text-danger-500">*</span>
                                    </label>
                                    <input v-model="form.date" type="date" required
                                           :class="['w-full px-4 py-2.5 border rounded-xl text-sm transition-all focus:outline-none focus:ring-2 focus:border-transparent',
                                               form.errors.date ? 'border-danger-300 focus:ring-danger-500 bg-danger-50' : 'border-slate-200 focus:ring-primary-500']"/>
                                    <p v-if="form.errors.date" class="mt-1 text-xs text-danger-600">{{ form.errors.date }}</p>
                                </div>

                                <div class="flex items-start gap-3 p-4 bg-slate-50 rounded-xl">
                                    <input id="is_recurring" v-model="form.is_recurring" type="checkbox"
                                           class="w-4 h-4 mt-0.5 text-primary-600 rounded border-slate-300 cursor-pointer"/>
                                    <label for="is_recurring" class="cursor-pointer">
                                        <span class="text-sm font-semibold text-slate-700 block">Récurrent</span>
                                        <span class="text-xs text-slate-500">
                                            Indicatif : ce jour férié tombe à la même date chaque année (ex: 1er janvier, Noël…).
                                            Les jours mobiles (Pâques…) ne sont pas récurrents.
                                        </span>
                                    </label>
                                </div>

                                <div class="flex gap-3 pt-1">
                                    <button type="button" @click="closeModal"
                                            class="flex-1 px-4 py-2.5 border border-slate-200 rounded-xl text-sm
                                                   font-semibold text-slate-700 hover:bg-slate-50 transition-all">
                                        Annuler
                                    </button>
                                    <button type="submit" :disabled="form.processing"
                                            class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5
                                                   bg-primary-700 hover:bg-primary-800 disabled:opacity-60
                                                   text-white text-sm font-semibold rounded-xl transition-all">
                                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                        </svg>
                                        {{ form.processing ? 'Enregistrement…' : (isEditing ? 'Enregistrer' : 'Ajouter') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>


        <!-- ════════════════════════════════════════════════════════════════════ -->
        <!-- Modal : Confirmation suppression                                     -->
        <!-- ════════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0"
                        enter-to-class="opacity-100"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0">
                <div v-if="showDeleteModal"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="cancelDelete"/>

                    <Transition enter-active-class="transition duration-200 ease-out"
                                enter-from-class="opacity-0 scale-95"
                                enter-to-class="opacity-100 scale-100"
                                leave-active-class="transition duration-150 ease-in"
                                leave-from-class="opacity-100 scale-100"
                                leave-to-class="opacity-0 scale-95">
                        <div v-if="showDeleteModal"
                             class="relative bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 text-center">

                            <div class="w-12 h-12 rounded-full bg-danger-50 flex items-center justify-center mx-auto mb-4">
                                <svg class="w-6 h-6 text-danger-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                </svg>
                            </div>

                            <h3 class="text-base font-display font-bold text-slate-900 mb-1">Supprimer ce jour férié ?</h3>
                            <p class="text-sm text-slate-500 mb-5">
                                <strong class="text-slate-800">{{ deleteTarget?.name }}</strong>
                                <span v-if="deleteTarget"> — {{ deleteTarget.date_label }}</span>
                                <br/>Cette action est irréversible.
                            </p>

                            <div class="flex gap-3">
                                <button @click="cancelDelete"
                                        class="flex-1 px-4 py-2.5 border border-slate-200 rounded-xl text-sm
                                               font-semibold text-slate-700 hover:bg-slate-50 transition-all">
                                    Annuler
                                </button>
                                <button @click="doDelete" :disabled="deleteForm.processing"
                                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5
                                               bg-danger-600 hover:bg-danger-700 disabled:opacity-60
                                               text-white text-sm font-semibold rounded-xl transition-all">
                                    <svg v-if="deleteForm.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                    </svg>
                                    {{ deleteForm.processing ? 'Suppression…' : 'Supprimer' }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

    </AppLayout>
</template>
