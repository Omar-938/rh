<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    departments: { type: Array, default: () => [] },
    managers:    { type: Array, default: () => [] },
})

// ── Palette de couleurs prédéfinies ──────────────────────────────────────────
const colorPalette = [
    '#2E86C1', // Bleu SimpliRH
    '#1B4F72', // Bleu marine
    '#9B59B6', // Violet
    '#27AE60', // Vert
    '#F39C12', // Orange
    '#E74C3C', // Rouge
    '#1ABC9C', // Turquoise
    '#E67E22', // Orange foncé
    '#95A5A6', // Gris
    '#7F8C8D', // Gris foncé
    '#16A085', // Vert teal
    '#2ECC71', // Vert clair
    '#3498DB', // Bleu clair
    '#E91E63', // Rose
    '#FF5722', // Rouge orangé
]

// ── Modal état ───────────────────────────────────────────────────────────────
const showModal      = ref(false)
const isEditing      = ref(false)
const editingId      = ref(null)
const nameInput      = ref(null)

// ── Formulaire Inertia ───────────────────────────────────────────────────────
const form = useForm({
    name:        '',
    color:       '#2E86C1',
    manager_id:  null,
    description: '',
})

function openCreate() {
    isEditing.value = false
    editingId.value = null
    form.reset()
    form.color = '#2E86C1'
    showModal.value = true
    nextTick(() => nameInput.value?.focus())
}

function openEdit(dept) {
    isEditing.value    = true
    editingId.value    = dept.id
    form.name          = dept.name
    form.color         = dept.color
    form.manager_id    = dept.manager?.id ?? null
    form.description   = dept.description ?? ''
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
    if (isEditing.value) {
        form.put(route('settings.departements.update', editingId.value), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        })
    } else {
        form.post(route('settings.departements.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        })
    }
}

// ── Suppression ──────────────────────────────────────────────────────────────
const deleteTarget   = ref(null)
const showDeleteModal = ref(false)
const deleteForm     = useForm({})

function confirmDelete(dept) {
    deleteTarget.value  = dept
    showDeleteModal.value = true
}

function cancelDelete() {
    showDeleteModal.value = false
    deleteTarget.value   = null
}

function doDelete() {
    deleteForm.delete(route('settings.departements.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => cancelDelete(),
    })
}

// ── Recherche ────────────────────────────────────────────────────────────────
const search = ref('')
const filtered = computed(() => {
    if (!search.value.trim()) return props.departments
    const q = search.value.toLowerCase()
    return props.departments.filter(d =>
        d.name.toLowerCase().includes(q) ||
        (d.description ?? '').toLowerCase().includes(q) ||
        (d.manager?.name ?? '').toLowerCase().includes(q)
    )
})

// Ferme les modals avec Escape
function onKeydown(e) {
    if (e.key === 'Escape') {
        if (showDeleteModal.value) cancelDelete()
        else if (showModal.value) closeModal()
    }
}
</script>

<template>
    <Head title="Départements — Paramètres" />

    <AppLayout title="Paramètres">
        <div @keydown="onKeydown" tabindex="-1" class="outline-none">

            <!-- ── En-tête de page ── -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                        <span>Paramètres</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                        <span class="text-slate-900 font-medium">Départements</span>
                    </div>
                    <h1 class="text-2xl font-display font-bold text-slate-900">Départements</h1>
                    <p class="text-slate-500 text-sm mt-1">
                        Organisez votre entreprise en {{ departments.length }}
                        département{{ departments.length !== 1 ? 's' : '' }}.
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
                    Nouveau département
                </button>
            </div>

            <!-- ── Barre de recherche ── -->
            <div v-if="departments.length > 0" class="relative mb-6 max-w-sm">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Rechercher un département…"
                    class="w-full pl-9 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm
                           placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500
                           focus:border-transparent transition-all"
                />
            </div>

            <!-- ── État vide ── -->
            <div v-if="departments.length === 0"
                 class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-20 h-20 rounded-2xl bg-primary-50 flex items-center justify-center mb-5">
                    <svg class="w-10 h-10 text-primary-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 mb-2">Aucun département</h3>
                <p class="text-slate-500 text-sm mb-6 max-w-sm">
                    Créez votre premier département pour organiser vos équipes et affecter des responsables.
                </p>
                <button
                    @click="openCreate"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-700 hover:bg-primary-800
                           text-white text-sm font-semibold rounded-xl transition-all active:scale-95"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Créer le premier département
                </button>
            </div>

            <!-- ── Aucun résultat de recherche ── -->
            <div v-else-if="filtered.length === 0"
                 class="flex flex-col items-center justify-center py-16 text-center">
                <div class="text-4xl mb-4 opacity-40">🔍</div>
                <p class="text-slate-600 font-medium">Aucun résultat pour « {{ search }} »</p>
                <button @click="search = ''" class="text-sm text-primary-600 hover:underline mt-2">
                    Effacer la recherche
                </button>
            </div>

            <!-- ── Grille des départements ── -->
            <div v-else class="grid sm:grid-cols-2 xl:grid-cols-3 gap-5">
                <TransitionGroup
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                    tag="div"
                    class="contents"
                >
                    <div
                        v-for="dept in filtered"
                        :key="dept.id"
                        class="group bg-white rounded-2xl border border-slate-100 overflow-hidden
                               hover:shadow-md hover:-translate-y-0.5 transition-all duration-200"
                    >
                        <!-- Barre de couleur -->
                        <div class="h-1.5" :style="{ backgroundColor: dept.color }" />

                        <div class="p-5">
                            <!-- En-tête carte -->
                            <div class="flex items-start justify-between gap-3 mb-4">
                                <div class="flex items-center gap-3 min-w-0">
                                    <!-- Icône colorée -->
                                    <div
                                        class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                        :style="{ backgroundColor: dept.color + '20' }"
                                    >
                                        <svg class="w-5 h-5" :style="{ color: dept.color }"
                                             fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="font-semibold text-slate-900 truncate">{{ dept.name }}</h3>
                                        <p v-if="dept.description"
                                           class="text-xs text-slate-400 truncate mt-0.5">
                                            {{ dept.description }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-1 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button
                                        @click="openEdit(dept)"
                                        class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition-colors"
                                        title="Modifier"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="confirmDelete(dept)"
                                        class="p-1.5 rounded-lg hover:bg-danger-50 text-slate-400 hover:text-danger-600 transition-colors"
                                        title="Supprimer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Responsable -->
                            <div class="flex items-center gap-2 mb-4">
                                <template v-if="dept.manager">
                                    <div class="w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center shrink-0">
                                        <span class="text-primary-700 text-[10px] font-bold">{{ dept.manager.initials }}</span>
                                    </div>
                                    <span class="text-xs text-slate-600 font-medium">{{ dept.manager.name }}</span>
                                    <span class="text-xs text-slate-400">· Responsable</span>
                                </template>
                                <template v-else>
                                    <div class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center shrink-0">
                                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                        </svg>
                                    </div>
                                    <span class="text-xs text-slate-400 italic">Aucun responsable</span>
                                </template>
                            </div>

                            <!-- Séparateur -->
                            <div class="border-t border-slate-50 mb-3" />

                            <!-- Membres -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <!-- Avatars empilés -->
                                    <div v-if="dept.members.length > 0" class="flex -space-x-2">
                                        <div
                                            v-for="(m, i) in dept.members"
                                            :key="m.id"
                                            :style="{ zIndex: dept.members.length - i }"
                                            class="relative w-7 h-7 rounded-full border-2 border-white
                                                   flex items-center justify-center text-[10px] font-bold"
                                            :class="[
                                                i % 4 === 0 ? 'bg-primary-100 text-primary-700' :
                                                i % 4 === 1 ? 'bg-success-100 text-success-700' :
                                                i % 4 === 2 ? 'bg-warning-100 text-warning-700' :
                                                              'bg-danger-100 text-danger-700'
                                            ]"
                                            :title="m.name"
                                        >
                                            {{ m.initials }}
                                        </div>
                                    </div>
                                    <span class="text-sm font-semibold text-slate-700">
                                        {{ dept.users_count }}
                                    </span>
                                    <span class="text-xs text-slate-400">
                                        {{ dept.users_count === 0 ? 'membre' : dept.users_count <= 1 ? 'membre' : 'membres' }}
                                    </span>
                                </div>

                                <!-- Extra count badge -->
                                <span
                                    v-if="dept.users_count > 5"
                                    class="text-xs text-slate-400 font-medium"
                                >
                                    +{{ dept.users_count - 5 }} autres
                                </span>
                            </div>
                        </div>
                    </div>
                </TransitionGroup>
            </div>

        </div>

        <!-- ════════════════════════════════════════════════════════
             Modal : Créer / Modifier un département
        ════════════════════════════════════════════════════════ -->
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

                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="closeModal" />

                    <!-- Panneau modal -->
                    <Transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
                        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-to-class="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
                    >
                        <div v-if="showModal"
                             class="relative w-full sm:max-w-lg bg-white rounded-t-3xl sm:rounded-2xl
                                    shadow-2xl overflow-hidden">

                            <!-- Handle mobile -->
                            <div class="sm:hidden flex justify-center pt-3 pb-1">
                                <div class="w-10 h-1 rounded-full bg-slate-200" />
                            </div>

                            <!-- Header -->
                            <div class="flex items-center justify-between px-6 pt-4 sm:pt-6 pb-4 border-b border-slate-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                                         :style="{ backgroundColor: form.color + '25' }">
                                        <svg class="w-5 h-5" :style="{ color: form.color }"
                                             fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                        </svg>
                                    </div>
                                    <h2 class="text-lg font-display font-bold text-slate-900">
                                        {{ isEditing ? 'Modifier le département' : 'Nouveau département' }}
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

                            <!-- Corps du formulaire -->
                            <form @submit.prevent="submit" class="px-6 py-5 space-y-5">

                                <!-- Nom -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        Nom du département <span class="text-danger-500">*</span>
                                    </label>
                                    <input
                                        ref="nameInput"
                                        v-model="form.name"
                                        type="text"
                                        placeholder="ex: Informatique, Commercial, RH…"
                                        maxlength="255"
                                        :class="[
                                            'w-full px-4 py-2.5 border rounded-xl text-sm transition-all',
                                            'focus:outline-none focus:ring-2 focus:border-transparent',
                                            form.errors.name
                                                ? 'border-danger-300 focus:ring-danger-500 bg-danger-50'
                                                : 'border-slate-200 focus:ring-primary-500 bg-white',
                                        ]"
                                    />
                                    <p v-if="form.errors.name" class="mt-1.5 text-xs text-danger-600 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                        </svg>
                                        {{ form.errors.name }}
                                    </p>
                                </div>

                                <!-- Couleur -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        Couleur <span class="text-danger-500">*</span>
                                    </label>
                                    <!-- Palette de couleurs -->
                                    <div class="flex flex-wrap gap-2 mb-3">
                                        <button
                                            v-for="c in colorPalette"
                                            :key="c"
                                            type="button"
                                            @click="form.color = c"
                                            class="w-8 h-8 rounded-lg transition-all hover:scale-110 focus:outline-none"
                                            :style="{ backgroundColor: c }"
                                            :class="form.color === c ? 'ring-2 ring-offset-2 ring-slate-400 scale-110' : ''"
                                            :title="c"
                                        />
                                    </div>
                                    <!-- Input HEX personnalisé -->
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg border-2 border-slate-200 shrink-0 transition-all"
                                             :style="{ backgroundColor: form.color }" />
                                        <input
                                            v-model="form.color"
                                            type="text"
                                            placeholder="#2E86C1"
                                            maxlength="7"
                                            :class="[
                                                'flex-1 px-3 py-2 border rounded-lg text-sm font-mono transition-all',
                                                'focus:outline-none focus:ring-2 focus:border-transparent',
                                                form.errors.color
                                                    ? 'border-danger-300 focus:ring-danger-500'
                                                    : 'border-slate-200 focus:ring-primary-500',
                                            ]"
                                        />
                                    </div>
                                    <p v-if="form.errors.color" class="mt-1.5 text-xs text-danger-600 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                        </svg>
                                        {{ form.errors.color }}
                                    </p>
                                </div>

                                <!-- Responsable -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        Responsable
                                        <span class="text-slate-400 font-normal text-xs">(optionnel)</span>
                                    </label>
                                    <select
                                        v-model="form.manager_id"
                                        :class="[
                                            'w-full px-4 py-2.5 border rounded-xl text-sm transition-all',
                                            'focus:outline-none focus:ring-2 focus:border-transparent',
                                            form.errors.manager_id
                                                ? 'border-danger-300 focus:ring-danger-500 bg-danger-50'
                                                : 'border-slate-200 focus:ring-primary-500 bg-white',
                                        ]"
                                    >
                                        <option :value="null">— Aucun responsable —</option>
                                        <option v-for="m in managers" :key="m.id" :value="m.id">
                                            {{ m.name }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors.manager_id" class="mt-1.5 text-xs text-danger-600">
                                        {{ form.errors.manager_id }}
                                    </p>
                                </div>

                                <!-- Description -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        Description
                                        <span class="text-slate-400 font-normal text-xs">(optionnel)</span>
                                    </label>
                                    <textarea
                                        v-model="form.description"
                                        rows="3"
                                        placeholder="Décrivez le rôle de ce département…"
                                        maxlength="1000"
                                        :class="[
                                            'w-full px-4 py-2.5 border rounded-xl text-sm resize-none transition-all',
                                            'focus:outline-none focus:ring-2 focus:border-transparent',
                                            form.errors.description
                                                ? 'border-danger-300 focus:ring-danger-500 bg-danger-50'
                                                : 'border-slate-200 focus:ring-primary-500 bg-white',
                                        ]"
                                    />
                                    <div class="flex justify-between items-center mt-1">
                                        <p v-if="form.errors.description" class="text-xs text-danger-600">
                                            {{ form.errors.description }}
                                        </p>
                                        <span class="text-xs text-slate-400 ml-auto">
                                            {{ form.description.length }}/1000
                                        </span>
                                    </div>
                                </div>

                                <!-- Boutons -->
                                <div class="flex gap-3 pt-1">
                                    <button
                                        type="button"
                                        @click="closeModal"
                                        class="flex-1 px-4 py-2.5 border border-slate-200 rounded-xl text-sm
                                               font-semibold text-slate-700 hover:bg-slate-50 transition-all active:scale-[.98]"
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
                                        <svg v-if="form.processing"
                                             class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                                    stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                        </svg>
                                        {{ form.processing ? 'Enregistrement…' : (isEditing ? 'Enregistrer' : 'Créer le département') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

        <!-- ════════════════════════════════════════════════════════
             Modal : Confirmation de suppression
        ════════════════════════════════════════════════════════ -->
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
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div v-if="showDeleteModal"
                             class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 text-center">

                            <!-- Icône danger -->
                            <div class="w-14 h-14 rounded-full bg-danger-50 flex items-center justify-center mx-auto mb-4">
                                <svg class="w-7 h-7 text-danger-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>

                            <h3 class="text-lg font-display font-bold text-slate-900 mb-2">
                                Supprimer ce département ?
                            </h3>

                            <p class="text-slate-500 text-sm mb-2">
                                Vous êtes sur le point de supprimer
                                <strong class="text-slate-900">{{ deleteTarget?.name }}</strong>.
                            </p>

                            <p v-if="deleteTarget?.users_count > 0"
                               class="text-sm text-warning-700 bg-warning-50 rounded-xl px-4 py-2.5 mb-4">
                                <svg class="w-4 h-4 inline-block mr-1.5 -mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                </svg>
                                Les {{ deleteTarget.users_count }} membre{{ deleteTarget.users_count > 1 ? 's' : '' }}
                                seront désaffecté{{ deleteTarget.users_count > 1 ? 's' : '' }} mais conservé{{ deleteTarget.users_count > 1 ? 's' : '' }}.
                            </p>
                            <p v-else class="text-slate-400 text-xs mb-4">
                                Cette action est irréversible.
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
                                    @click="doDelete"
                                    :disabled="deleteForm.processing"
                                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5
                                           bg-danger-600 hover:bg-danger-700 disabled:opacity-60
                                           text-white text-sm font-semibold rounded-xl transition-all"
                                >
                                    <svg v-if="deleteForm.processing"
                                         class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4"/>
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
