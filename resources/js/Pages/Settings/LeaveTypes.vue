<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    leaveTypes: { type: Array, default: () => [] },
})

// ── Palette couleurs ──────────────────────────────────────────────────────────
const colorPalette = [
    '#2E86C1', '#1B4F72', '#9B59B6', '#27AE60', '#F39C12',
    '#E74C3C', '#1ABC9C', '#E67E22', '#95A5A6', '#3498DB',
    '#E91E63', '#FF5722', '#16A085', '#8E44AD', '#2ECC71',
]

// ── Acquisition types config ──────────────────────────────────────────────────
const acquisitionOptions = [
    {
        value: 'monthly',
        label: 'Mensuelle',
        desc: '2,08 j/mois (pour 25 j/an). Acquisition progressive tout au long de l\'année.',
        icon: '📆',
    },
    {
        value: 'annual',
        label: 'Annuelle',
        desc: 'Solde complet crédité au 1er janvier. Idéal pour les RTT forfaitaires.',
        icon: '🗓️',
    },
    {
        value: 'none',
        label: 'Sans acquisition',
        desc: 'Aucun solde automatique. Parfait pour les congés sans solde, arrêts maladie.',
        icon: '🚫',
    },
]

// ── Liste locale (pour la réorganisation optimiste) ───────────────────────────
const localList = ref([...props.leaveTypes])
watch(() => props.leaveTypes, (v) => { localList.value = [...v] }, { deep: true })

// ── Réorganisation (haut / bas) ───────────────────────────────────────────────
function moveUp(index) {
    if (index === 0) return
    const list = [...localList.value]
    ;[list[index - 1], list[index]] = [list[index], list[index - 1]]
    localList.value = list
    sendReorder(list)
}

function moveDown(index) {
    if (index === localList.value.length - 1) return
    const list = [...localList.value]
    ;[list[index], list[index + 1]] = [list[index + 1], list[index]]
    localList.value = list
    sendReorder(list)
}

function sendReorder(list) {
    router.post(route('settings.types-conges.reorder'), {
        ids: list.map(lt => lt.id),
    }, { preserveScroll: true })
}

// ── Modal créer / modifier ────────────────────────────────────────────────────
const showModal    = ref(false)
const isEditing    = ref(false)
const editingId    = ref(null)
const nameInput    = ref(null)

const form = useForm({
    name:                 '',
    color:                '#2E86C1',
    icon:                 '🌴',
    acquisition_type:     'monthly',
    days_per_year:        25,
    requires_approval:    true,
    is_paid:              true,
    is_active:            true,
    max_consecutive_days: '',
    notice_days:          0,
    requires_attachment:  false,
})

const daysDisabled = computed(() => form.acquisition_type === 'none')

watch(() => form.acquisition_type, (v) => {
    if (v === 'none') form.days_per_year = 0
    else if (form.days_per_year === 0) form.days_per_year = 25
})

function openCreate() {
    isEditing.value = false
    editingId.value = null
    form.reset()
    form.color             = '#2E86C1'
    form.icon              = '🌴'
    form.acquisition_type  = 'monthly'
    form.days_per_year     = 25
    form.requires_approval = true
    form.is_paid           = true
    form.is_active         = true
    form.notice_days       = 0
    form.max_consecutive_days = ''
    form.requires_attachment  = false
    form.clearErrors()
    showModal.value = true
    nextTick(() => nameInput.value?.focus())
}

function openEdit(lt) {
    isEditing.value = true
    editingId.value = lt.id
    form.name                 = lt.name
    form.color                = lt.color
    form.icon                 = lt.icon ?? '🌴'
    form.acquisition_type     = lt.acquisition_type
    form.days_per_year        = lt.days_per_year
    form.requires_approval    = lt.requires_approval
    form.is_paid              = lt.is_paid
    form.is_active            = lt.is_active
    form.max_consecutive_days = lt.max_consecutive_days ?? ''
    form.notice_days          = lt.notice_days
    form.requires_attachment  = lt.requires_attachment ?? false
    form.clearErrors()
    showModal.value = true
    nextTick(() => nameInput.value?.focus())
}

function closeModal() {
    showModal.value = false
    form.reset()
    form.clearErrors()
}

function submit() {
    const opts = {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    }
    if (isEditing.value) {
        form.put(route('settings.types-conges.update', editingId.value), opts)
    } else {
        form.post(route('settings.types-conges.store'), opts)
    }
}

// ── Toggle actif / inactif ────────────────────────────────────────────────────
const togglingId = ref(null)
function toggle(lt) {
    togglingId.value = lt.id
    router.patch(route('settings.types-conges.toggle', lt.id), {}, {
        preserveScroll: true,
        onFinish: () => { togglingId.value = null },
    })
}

// ── Suppression ───────────────────────────────────────────────────────────────
const deleteTarget    = ref(null)
const showDeleteModal = ref(false)
const deleteForm      = useForm({})

function confirmDelete(lt) {
    deleteTarget.value    = lt
    showDeleteModal.value = true
}
function cancelDelete() {
    showDeleteModal.value = false
    deleteTarget.value    = null
}
function doDelete() {
    deleteForm.delete(route('settings.types-conges.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => cancelDelete(),
    })
}

// ── Escape key ────────────────────────────────────────────────────────────────
function onKeydown(e) {
    if (e.key !== 'Escape') return
    if (showDeleteModal.value) cancelDelete()
    else if (showModal.value) closeModal()
}

// ── Helpers affichage ─────────────────────────────────────────────────────────
function daysLabel(lt) {
    if (lt.acquisition_type === 'none') return null
    return `${lt.days_per_year} j/an`
}

function monthlyAccrual(daysPerYear) {
    return (daysPerYear / 12).toFixed(2)
}
</script>

<template>
    <Head title="Types de congés — Paramètres" />

    <AppLayout title="Paramètres" :back-url="route('settings.index')">
        <div @keydown="onKeydown" tabindex="-1" class="outline-none">

            <!-- ── En-tête ── -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                        <span>Paramètres</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                        <span class="text-slate-900 font-medium">Types de congés</span>
                    </div>
                    <h1 class="text-2xl font-display font-bold text-slate-900">Types de congés</h1>
                    <p class="text-slate-500 text-sm mt-1">
                        {{ leaveTypes.length }} type{{ leaveTypes.length !== 1 ? 's' : '' }} configuré{{ leaveTypes.length !== 1 ? 's' : '' }}.
                        Glissez pour réorganiser l'ordre d'affichage.
                    </p>
                </div>
                <button
                    @click="openCreate"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-700 hover:bg-primary-800
                           text-white text-sm font-semibold rounded-xl transition-all active:scale-95
                           shadow-sm shadow-primary-200 hover:shadow-md self-start sm:self-auto shrink-0"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nouveau type
                </button>
            </div>

            <!-- ── État vide ── -->
            <div v-if="leaveTypes.length === 0"
                 class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-20 h-20 rounded-2xl bg-success-50 flex items-center justify-center mb-5">
                    <svg class="w-10 h-10 text-success-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 mb-2">Aucun type de congé</h3>
                <p class="text-slate-500 text-sm mb-6 max-w-sm">
                    Créez vos types de congés (Congés payés, RTT, Sans solde…) pour que vos employés
                    puissent soumettre des demandes.
                </p>
                <button
                    @click="openCreate"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-700 hover:bg-primary-800
                           text-white text-sm font-semibold rounded-xl transition-all active:scale-95"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Créer le premier type
                </button>
            </div>

            <!-- ── Liste ordonnée ── -->
            <div v-else class="space-y-3">
                <TransitionGroup
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0 -translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-200 ease-in absolute w-full"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0 translate-y-2"
                    tag="div"
                    class="space-y-3"
                >
                    <div
                        v-for="(lt, index) in localList"
                        :key="lt.id"
                        class="group bg-white rounded-2xl border transition-all duration-200"
                        :class="lt.is_active
                            ? 'border-slate-100 hover:shadow-md hover:-translate-y-0.5'
                            : 'border-slate-100 opacity-60'"
                    >
                        <div class="flex items-center gap-4 p-4 sm:p-5">

                            <!-- Poignées de tri (haut / bas) -->
                            <div class="flex flex-col gap-1 shrink-0">
                                <button
                                    @click="moveUp(index)"
                                    :disabled="index === 0"
                                    class="p-1 rounded text-slate-300 hover:text-slate-600 disabled:opacity-20
                                           hover:bg-slate-100 transition-all"
                                    title="Monter"
                                >
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 17a.75.75 0 01-.75-.75V5.612L5.29 9.77a.75.75 0 01-1.08-1.04l5.25-5.5a.75.75 0 011.08 0l5.25 5.5a.75.75 0 11-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0110 17z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <button
                                    @click="moveDown(index)"
                                    :disabled="index === localList.length - 1"
                                    class="p-1 rounded text-slate-300 hover:text-slate-600 disabled:opacity-20
                                           hover:bg-slate-100 transition-all"
                                    title="Descendre"
                                >
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.638l3.96-4.158a.75.75 0 111.08 1.04l-5.25 5.5a.75.75 0 01-1.08 0l-5.25-5.5a.75.75 0 111.08-1.04l3.96 4.158V3.75A.75.75 0 0110 3z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Indicateur couleur -->
                            <div
                                class="w-3 h-12 rounded-full shrink-0"
                                :style="{ backgroundColor: lt.color }"
                            />

                            <!-- Informations principales -->
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                                    <h3 class="font-semibold text-slate-900 text-base">{{ lt.name }}</h3>

                                    <!-- Badge inactif -->
                                    <span v-if="!lt.is_active"
                                          class="inline-flex items-center gap-1 text-xs font-semibold
                                                 px-2 py-0.5 bg-slate-100 text-slate-500 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400" />
                                        Inactif
                                    </span>
                                </div>

                                <!-- Attributs -->
                                <div class="flex flex-wrap gap-2">
                                    <!-- Jours par an -->
                                    <span v-if="daysLabel(lt)"
                                          class="inline-flex items-center gap-1.5 text-xs font-semibold
                                                 px-2.5 py-1 rounded-full"
                                          :style="{ backgroundColor: lt.color + '20', color: lt.color }">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" />
                                        </svg>
                                        {{ daysLabel(lt) }}
                                    </span>

                                    <!-- Acquisition -->
                                    <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1
                                                 bg-slate-100 text-slate-600 rounded-full font-medium">
                                        {{ acquisitionOptions.find(o => o.value === lt.acquisition_type)?.icon }}
                                        {{ lt.acquisition_label }}
                                    </span>

                                    <!-- Payé -->
                                    <span class="inline-flex items-center text-xs px-2.5 py-1 rounded-full font-medium"
                                          :class="lt.is_paid
                                              ? 'bg-success-50 text-success-700'
                                              : 'bg-slate-100 text-slate-500'">
                                        {{ lt.is_paid ? '💶 Payé' : '🚫 Non payé' }}
                                    </span>

                                    <!-- Approbation -->
                                    <span v-if="lt.requires_approval"
                                          class="inline-flex items-center text-xs px-2.5 py-1
                                                 bg-warning-50 text-warning-700 rounded-full font-medium">
                                        ✅ Approbation requise
                                    </span>

                                    <!-- Justificatif obligatoire -->
                                    <span v-if="lt.requires_attachment"
                                          class="inline-flex items-center text-xs px-2.5 py-1
                                                 bg-primary-50 text-primary-700 rounded-full font-medium">
                                        📎 Justificatif requis
                                    </span>

                                    <!-- Limite jours consécutifs -->
                                    <span v-if="lt.max_consecutive_days"
                                          class="inline-flex items-center text-xs px-2.5 py-1
                                                 bg-slate-100 text-slate-500 rounded-full">
                                        Max {{ lt.max_consecutive_days }}j consécutifs
                                    </span>

                                    <!-- Délai de prévenance -->
                                    <span v-if="lt.notice_days > 0"
                                          class="inline-flex items-center text-xs px-2.5 py-1
                                                 bg-slate-100 text-slate-500 rounded-full">
                                        {{ lt.notice_days }}j de préavis
                                    </span>
                                </div>
                            </div>

                            <!-- Accrual info (desktop) -->
                            <div class="hidden lg:block text-right shrink-0 w-28">
                                <template v-if="lt.acquisition_type === 'monthly' && lt.days_per_year > 0">
                                    <p class="text-lg font-display font-bold text-slate-900">
                                        {{ monthlyAccrual(lt.days_per_year) }}
                                    </p>
                                    <p class="text-xs text-slate-400">j / mois</p>
                                </template>
                                <template v-else-if="lt.acquisition_type === 'annual' && lt.days_per_year > 0">
                                    <p class="text-lg font-display font-bold text-slate-900">
                                        {{ lt.days_per_year }}
                                    </p>
                                    <p class="text-xs text-slate-400">j / an</p>
                                </template>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-1.5 shrink-0 transition-opacity">
                                <!-- Toggle actif -->
                                <button
                                    @click="toggle(lt)"
                                    :disabled="togglingId === lt.id"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full
                                           transition-colors focus:outline-none shrink-0"
                                    :class="lt.is_active ? 'bg-success-500' : 'bg-slate-200'"
                                    :title="lt.is_active ? 'Désactiver' : 'Activer'"
                                >
                                    <span
                                        class="inline-block w-4 h-4 transform rounded-full bg-white shadow
                                               transition-transform duration-200"
                                        :class="lt.is_active ? 'translate-x-6' : 'translate-x-1'"
                                    />
                                </button>

                                <!-- Modifier -->
                                <button
                                    @click="openEdit(lt)"
                                    class="p-2 rounded-lg hover:bg-slate-100 text-slate-400
                                           hover:text-slate-700 transition-colors"
                                    title="Modifier"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                                    </svg>
                                </button>

                                <!-- Supprimer -->
                                <button
                                    @click="confirmDelete(lt)"
                                    class="p-2 rounded-lg hover:bg-danger-50 text-slate-400
                                           hover:text-danger-600 transition-colors"
                                    title="Supprimer"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </TransitionGroup>

                <!-- Info de fin de liste -->
                <p class="text-xs text-slate-400 text-center pt-2">
                    Utilisez les flèches ↑↓ pour modifier l'ordre d'affichage dans les formulaires.
                </p>
            </div>

        </div>

        <!-- ══════════════════════════════════════════════════════════
             Modal : Créer / Modifier un type de congé
        ══════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showModal"
                     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
                     @click.self="closeModal">

                    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="closeModal" />

                    <Transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
                        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    >
                        <div v-if="showModal"
                             class="relative w-full sm:max-w-2xl bg-white rounded-t-3xl sm:rounded-2xl
                                    shadow-2xl overflow-hidden max-h-[92vh] flex flex-col">

                            <!-- Handle mobile -->
                            <div class="sm:hidden flex justify-center pt-3 pb-0 shrink-0">
                                <div class="w-10 h-1 rounded-full bg-slate-200" />
                            </div>

                            <!-- Header fixe -->
                            <div class="flex items-center justify-between px-6 pt-4 sm:pt-6 pb-4
                                        border-b border-slate-100 shrink-0">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                                         :style="{ backgroundColor: form.color + '25' }">
                                        <svg class="w-5 h-5" :style="{ color: form.color }"
                                             fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                        </svg>
                                    </div>
                                    <h2 class="text-lg font-display font-bold text-slate-900">
                                        {{ isEditing ? 'Modifier le type de congé' : 'Nouveau type de congé' }}
                                    </h2>
                                </div>
                                <button @click="closeModal"
                                        class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center
                                               text-slate-400 hover:text-slate-700 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Corps scrollable -->
                            <form @submit.prevent="submit" class="overflow-y-auto flex-1">
                                <div class="px-6 py-5 space-y-6">

                                    <!-- Nom + couleur côte à côte -->
                                    <div class="grid sm:grid-cols-2 gap-5">
                                        <!-- Nom -->
                                        <div class="sm:col-span-2">
                                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                                Nom <span class="text-danger-500">*</span>
                                            </label>
                                            <input
                                                ref="nameInput"
                                                v-model="form.name"
                                                type="text"
                                                placeholder="ex : Congés payés, RTT, Sans solde…"
                                                :class="[
                                                    'w-full px-4 py-2.5 border rounded-xl text-sm transition-all',
                                                    'focus:outline-none focus:ring-2 focus:border-transparent',
                                                    form.errors.name
                                                        ? 'border-danger-300 focus:ring-danger-500 bg-danger-50'
                                                        : 'border-slate-200 focus:ring-primary-500',
                                                ]"
                                            />
                                            <p v-if="form.errors.name" class="mt-1 text-xs text-danger-600">
                                                {{ form.errors.name }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Icône -->
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                                            Icône (emoji)
                                        </label>
                                        <input
                                            v-model="form.icon"
                                            type="text"
                                            maxlength="4"
                                            placeholder="🌴"
                                            class="w-20 px-3 py-2 border border-slate-200 rounded-xl text-2xl
                                                   text-center focus:outline-none focus:ring-2
                                                   focus:ring-primary-300 transition-shadow"
                                        />
                                        <p class="mt-1 text-xs text-slate-400">
                                            Collez un emoji pour identifier le type.
                                        </p>
                                    </div>

                                    <!-- Couleur -->
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                                            Couleur <span class="text-danger-500">*</span>
                                        </label>
                                        <div class="flex flex-wrap gap-2 mb-3">
                                            <button
                                                v-for="c in colorPalette"
                                                :key="c"
                                                type="button"
                                                @click="form.color = c"
                                                class="w-8 h-8 rounded-lg transition-all hover:scale-110 focus:outline-none"
                                                :style="{ backgroundColor: c }"
                                                :class="form.color === c ? 'ring-2 ring-offset-2 ring-slate-400 scale-110' : ''"
                                            />
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-lg border-2 border-slate-200 shrink-0 transition-all"
                                                 :style="{ backgroundColor: form.color }" />
                                            <input
                                                v-model="form.color"
                                                type="text"
                                                placeholder="#2E86C1"
                                                maxlength="7"
                                                class="w-32 px-3 py-2 border border-slate-200 rounded-lg text-sm font-mono
                                                       focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                            />
                                        </div>
                                    </div>

                                    <!-- Type d'acquisition -->
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                                            Mode d'acquisition <span class="text-danger-500">*</span>
                                        </label>
                                        <div class="space-y-2">
                                            <label
                                                v-for="opt in acquisitionOptions"
                                                :key="opt.value"
                                                class="flex items-start gap-3 p-3.5 border rounded-xl cursor-pointer
                                                       transition-all"
                                                :class="form.acquisition_type === opt.value
                                                    ? 'border-primary-400 bg-primary-50 ring-1 ring-primary-400'
                                                    : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'"
                                            >
                                                <input
                                                    type="radio"
                                                    :value="opt.value"
                                                    v-model="form.acquisition_type"
                                                    class="mt-0.5 accent-primary-600 shrink-0"
                                                />
                                                <div>
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-base">{{ opt.icon }}</span>
                                                        <span class="text-sm font-semibold text-slate-900">
                                                            {{ opt.label }}
                                                        </span>
                                                    </div>
                                                    <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                                                        {{ opt.desc }}
                                                    </p>
                                                </div>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Jours par an + délai préavis -->
                                    <div class="grid sm:grid-cols-2 gap-5">
                                        <!-- Jours par an -->
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                                Jours par an
                                                <span v-if="!daysDisabled" class="text-danger-500">*</span>
                                            </label>
                                            <div class="relative">
                                                <input
                                                    v-model.number="form.days_per_year"
                                                    type="number"
                                                    min="0"
                                                    max="365"
                                                    step="0.5"
                                                    :disabled="daysDisabled"
                                                    :class="[
                                                        'w-full px-4 py-2.5 border rounded-xl text-sm transition-all',
                                                        'focus:outline-none focus:ring-2 focus:border-transparent',
                                                        daysDisabled
                                                            ? 'bg-slate-50 text-slate-400 cursor-not-allowed border-slate-100'
                                                            : form.errors.days_per_year
                                                                ? 'border-danger-300 focus:ring-danger-500 bg-danger-50'
                                                                : 'border-slate-200 focus:ring-primary-500',
                                                    ]"
                                                />
                                                <span class="absolute right-4 top-1/2 -translate-y-1/2
                                                             text-xs text-slate-400 pointer-events-none">
                                                    {{ daysDisabled ? 'N/A' : 'jours' }}
                                                </span>
                                            </div>
                                            <p v-if="form.acquisition_type === 'monthly' && form.days_per_year > 0"
                                               class="mt-1 text-xs text-slate-400">
                                                ≈ {{ monthlyAccrual(form.days_per_year) }} j acquis par mois
                                            </p>
                                            <p v-if="form.errors.days_per_year" class="mt-1 text-xs text-danger-600">
                                                {{ form.errors.days_per_year }}
                                            </p>
                                        </div>

                                        <!-- Délai de prévenance -->
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                                Délai de prévenance <span class="text-danger-500">*</span>
                                            </label>
                                            <div class="relative">
                                                <input
                                                    v-model.number="form.notice_days"
                                                    type="number"
                                                    min="0"
                                                    max="365"
                                                    :class="[
                                                        'w-full px-4 py-2.5 border rounded-xl text-sm transition-all',
                                                        'focus:outline-none focus:ring-2 focus:border-transparent',
                                                        form.errors.notice_days
                                                            ? 'border-danger-300 focus:ring-danger-500 bg-danger-50'
                                                            : 'border-slate-200 focus:ring-primary-500',
                                                    ]"
                                                />
                                                <span class="absolute right-4 top-1/2 -translate-y-1/2
                                                             text-xs text-slate-400 pointer-events-none">
                                                    jours
                                                </span>
                                            </div>
                                            <p class="mt-1 text-xs text-slate-400">
                                                Délai minimum avant le début du congé.
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Limite jours consécutifs -->
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                            Limite de jours consécutifs
                                            <span class="text-slate-400 font-normal text-xs">(optionnel)</span>
                                        </label>
                                        <div class="relative max-w-xs">
                                            <input
                                                v-model.number="form.max_consecutive_days"
                                                type="number"
                                                min="1"
                                                max="365"
                                                placeholder="Illimité"
                                                :class="[
                                                    'w-full px-4 py-2.5 border rounded-xl text-sm transition-all',
                                                    'focus:outline-none focus:ring-2 focus:border-transparent',
                                                    form.errors.max_consecutive_days
                                                        ? 'border-danger-300 focus:ring-danger-500 bg-danger-50'
                                                        : 'border-slate-200 focus:ring-primary-500',
                                                ]"
                                            />
                                            <span class="absolute right-4 top-1/2 -translate-y-1/2
                                                         text-xs text-slate-400 pointer-events-none">
                                                jours max
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Toggles -->
                                    <div class="grid sm:grid-cols-2 gap-3">

                                        <!-- Approbation requise -->
                                        <label class="flex items-center gap-3 p-4 border rounded-xl cursor-pointer
                                                       transition-all hover:bg-slate-50"
                                               :class="form.requires_approval ? 'border-warning-300 bg-warning-50/50' : 'border-slate-200'">
                                            <button
                                                type="button"
                                                @click="form.requires_approval = !form.requires_approval"
                                                class="relative inline-flex h-6 w-11 items-center rounded-full
                                                       transition-colors shrink-0 focus:outline-none"
                                                :class="form.requires_approval ? 'bg-warning-500' : 'bg-slate-200'"
                                            >
                                                <span class="inline-block w-4 h-4 transform rounded-full bg-white shadow
                                                             transition-transform duration-200"
                                                      :class="form.requires_approval ? 'translate-x-6' : 'translate-x-1'" />
                                            </button>
                                            <div>
                                                <p class="text-sm font-semibold text-slate-700">Approbation</p>
                                                <p class="text-xs text-slate-400">Validation manager requise</p>
                                            </div>
                                        </label>

                                        <!-- Payé -->
                                        <label class="flex items-center gap-3 p-4 border rounded-xl cursor-pointer
                                                       transition-all hover:bg-slate-50"
                                               :class="form.is_paid ? 'border-success-300 bg-success-50/50' : 'border-slate-200'">
                                            <button
                                                type="button"
                                                @click="form.is_paid = !form.is_paid"
                                                class="relative inline-flex h-6 w-11 items-center rounded-full
                                                       transition-colors shrink-0 focus:outline-none"
                                                :class="form.is_paid ? 'bg-success-500' : 'bg-slate-200'"
                                            >
                                                <span class="inline-block w-4 h-4 transform rounded-full bg-white shadow
                                                             transition-transform duration-200"
                                                      :class="form.is_paid ? 'translate-x-6' : 'translate-x-1'" />
                                            </button>
                                            <div>
                                                <p class="text-sm font-semibold text-slate-700">Payé</p>
                                                <p class="text-xs text-slate-400">Maintien du salaire</p>
                                            </div>
                                        </label>

                                        <!-- Actif -->
                                        <label class="flex items-center gap-3 p-4 border rounded-xl cursor-pointer
                                                       transition-all hover:bg-slate-50"
                                               :class="form.is_active ? 'border-primary-300 bg-primary-50/50' : 'border-slate-200'">
                                            <button
                                                type="button"
                                                @click="form.is_active = !form.is_active"
                                                class="relative inline-flex h-6 w-11 items-center rounded-full
                                                       transition-colors shrink-0 focus:outline-none"
                                                :class="form.is_active ? 'bg-primary-600' : 'bg-slate-200'"
                                            >
                                                <span class="inline-block w-4 h-4 transform rounded-full bg-white shadow
                                                             transition-transform duration-200"
                                                      :class="form.is_active ? 'translate-x-6' : 'translate-x-1'" />
                                            </button>
                                            <div>
                                                <p class="text-sm font-semibold text-slate-700">Actif</p>
                                                <p class="text-xs text-slate-400">Visible aux employés</p>
                                            </div>
                                        </label>

                                        <!-- Justificatif requis -->
                                        <label class="flex items-center gap-3 p-4 border rounded-xl cursor-pointer
                                                       transition-all hover:bg-slate-50"
                                               :class="form.requires_attachment ? 'border-primary-300 bg-primary-50/50' : 'border-slate-200'">
                                            <button
                                                type="button"
                                                @click="form.requires_attachment = !form.requires_attachment"
                                                class="relative inline-flex h-6 w-11 items-center rounded-full
                                                       transition-colors shrink-0 focus:outline-none"
                                                :class="form.requires_attachment ? 'bg-primary-600' : 'bg-slate-200'"
                                            >
                                                <span class="inline-block w-4 h-4 transform rounded-full bg-white shadow
                                                             transition-transform duration-200"
                                                      :class="form.requires_attachment ? 'translate-x-6' : 'translate-x-1'" />
                                            </button>
                                            <div>
                                                <p class="text-sm font-semibold text-slate-700">Justificatif</p>
                                                <p class="text-xs text-slate-400">Pièce jointe obligatoire</p>
                                            </div>
                                        </label>

                                    </div>

                                </div>

                                <!-- Pied de modal fixe -->
                                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/60 flex gap-3 shrink-0">
                                    <button
                                        type="button"
                                        @click="closeModal"
                                        class="flex-1 px-4 py-2.5 border border-slate-200 rounded-xl text-sm
                                               font-semibold text-slate-700 hover:bg-white transition-all active:scale-[.98]"
                                    >
                                        Annuler
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5
                                               bg-primary-700 hover:bg-primary-800 disabled:opacity-60
                                               text-white text-sm font-semibold rounded-xl transition-all active:scale-[.98]"
                                    >
                                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                        </svg>
                                        {{ form.processing
                                            ? 'Enregistrement…'
                                            : isEditing ? 'Enregistrer les modifications' : 'Créer le type de congé' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

        <!-- ══════════════════════════════════════════════════════════
             Modal : Confirmation suppression
        ══════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showDeleteModal"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4"
                     @click.self="cancelDelete">

                    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="cancelDelete" />

                    <Transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                    >
                        <div v-if="showDeleteModal"
                             class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 text-center">

                            <div class="w-14 h-14 rounded-full bg-danger-50 flex items-center justify-center mx-auto mb-4">
                                <svg class="w-7 h-7 text-danger-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>

                            <h3 class="text-lg font-display font-bold text-slate-900 mb-2">
                                Supprimer ce type de congé ?
                            </h3>

                            <p class="text-slate-500 text-sm mb-2">
                                Vous êtes sur le point de supprimer
                                <strong class="text-slate-900">{{ deleteTarget?.name }}</strong>.
                            </p>

                            <div v-if="deleteTarget?.leave_requests_count > 0"
                                 class="text-sm text-warning-700 bg-warning-50 rounded-xl px-4 py-3 mb-4 text-left">
                                <div class="flex items-start gap-2">
                                    <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                    </svg>
                                    <div>
                                        <p class="font-semibold">Suppression impossible</p>
                                        <p class="text-xs mt-0.5">
                                            {{ deleteTarget.leave_requests_count }} demande{{ deleteTarget.leave_requests_count > 1 ? 's' : '' }}
                                            de congé exist{{ deleteTarget.leave_requests_count > 1 ? 'ent' : 'e' }} pour ce type.
                                            Désactivez-le à la place.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-slate-400 text-xs mb-4">
                                Cette action est irréversible. Les soldes associés seront également supprimés.
                            </p>

                            <div class="flex gap-3">
                                <button
                                    @click="cancelDelete"
                                    class="flex-1 px-4 py-2.5 border border-slate-200 rounded-xl text-sm
                                           font-semibold text-slate-700 hover:bg-slate-50 transition-all"
                                >
                                    Annuler
                                </button>
                                <button
                                    v-if="deleteTarget?.leave_requests_count === 0"
                                    @click="doDelete"
                                    :disabled="deleteForm.processing"
                                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5
                                           bg-danger-600 hover:bg-danger-700 disabled:opacity-60
                                           text-white text-sm font-semibold rounded-xl transition-all"
                                >
                                    <svg v-if="deleteForm.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                    </svg>
                                    {{ deleteForm.processing ? 'Suppression…' : 'Supprimer' }}
                                </button>
                                <button
                                    v-else
                                    @click="() => { cancelDelete(); openEdit(deleteTarget) }"
                                    class="flex-1 px-4 py-2.5 bg-warning-500 hover:bg-warning-600
                                           text-white text-sm font-semibold rounded-xl transition-all"
                                >
                                    Désactiver à la place
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

    </AppLayout>
</template>
