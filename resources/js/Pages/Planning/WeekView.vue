<script setup>
import { ref, computed, nextTick } from 'vue'
import { Head, useForm, router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    days:         { type: Array,   default: () => [] },
    employees:    { type: Array,   default: () => [] },
    departments:  { type: Array,   default: () => [] },
    stats:        { type: Object,  default: () => ({}) },
    week_start:   { type: String,  required: true },
    week_end:     { type: String,  required: true },
    week_label:   { type: String,  required: true },
    prev_week:    { type: String,  required: true },
    next_week:    { type: String,  required: true },
    can_edit:     { type: Boolean, default: false },
    auth_user_id: { type: Number,  default: null },
})

// ── Config types ──────────────────────────────────────────────────────────────
const scheduleTypes = [
    { value: 'work',     label: 'Travail',     emoji: '💼', bg: '#DCFCE7', text: '#15803D' },
    { value: 'remote',   label: 'Télétravail', emoji: '🏠', bg: '#DBEAFE', text: '#1D4ED8' },
    { value: 'off',      label: 'Repos',       emoji: '😴', bg: '#F1F5F9', text: '#64748B' },
    { value: 'leave',    label: 'Congé',       emoji: '🌴', bg: '#FEF3C7', text: '#92400E' },
    { value: 'sick',     label: 'Maladie',     emoji: '🤒', bg: '#FEE2E2', text: '#991B1B' },
    { value: 'training', label: 'Formation',   emoji: '📚', bg: '#EDE9FE', text: '#5B21B6' },
]
const typeMap = Object.fromEntries(scheduleTypes.map(t => [t.value, t]))

// ── Filtre département ────────────────────────────────────────────────────────
const activeDept = ref(null)
const filteredEmployees = computed(() => {
    if (!activeDept.value) return props.employees
    return props.employees.filter(e => e.department_id === activeDept.value)
})

// ── Modal planning ────────────────────────────────────────────────────────────
const showModal   = ref(false)
const modalMode   = ref('create') // 'create' | 'edit' | 'view'
const editingScheduleId = ref(null)

const form = useForm({
    user_id:       null,
    date:          '',
    type:          'work',
    start_time:    '09:00',
    end_time:      '17:00',
    break_minutes: 60,
    notes:         '',
})

const selectedEmployee = computed(() =>
    props.employees.find(e => e.id === form.user_id)
)

const selectedType = computed(() =>
    typeMap[form.type] ?? scheduleTypes[0]
)

const needsTimes = computed(() =>
    ['work', 'remote', 'training'].includes(form.type)
)

function openCreate(employee, day) {
    if (!props.can_edit) return
    form.reset()
    form.user_id    = employee.id
    form.date       = day.date
    form.type       = 'work'
    form.start_time = '09:00'
    form.end_time   = '17:00'
    form.break_minutes = 60
    form.clearErrors()
    modalMode.value = 'create'
    editingScheduleId.value = null
    showModal.value = true
}

function openEdit(employee, day, schedule) {
    if (!props.can_edit) return
    form.user_id       = employee.id
    form.date          = day.date
    form.type          = schedule.type
    form.start_time    = schedule.start_time ?? '09:00'
    form.end_time      = schedule.end_time   ?? '17:00'
    form.break_minutes = schedule.break_minutes ?? 60
    form.notes         = schedule.notes ?? ''
    form.clearErrors()
    modalMode.value    = 'edit'
    editingScheduleId.value = schedule.id
    showModal.value    = true
}

function openView(employee, day, schedule) {
    form.user_id       = employee.id
    form.date          = day.date
    form.type          = schedule.type
    form.start_time    = schedule.start_time ?? ''
    form.end_time      = schedule.end_time   ?? ''
    form.break_minutes = schedule.break_minutes ?? 60
    form.notes         = schedule.notes ?? ''
    modalMode.value    = 'view'
    showModal.value    = true
}

function handleCellClick(employee, day) {
    const sched = employee.schedules?.[day.date]
    if (!sched) {
        if (props.can_edit) openCreate(employee, day)
    } else {
        if (props.can_edit) openEdit(employee, day, sched)
        else if (employee.id === props.auth_user_id) openView(employee, day, sched)
    }
}

function closeModal() {
    showModal.value = false
    form.reset()
    form.clearErrors()
}

function submit() {
    if (modalMode.value === 'edit') {
        form.put(route('planning.schedules.update', editingScheduleId.value), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        })
    } else {
        form.post(route('planning.schedules.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        })
    }
}

const deletingId = ref(null)
function deleteSchedule() {
    deletingId.value = editingScheduleId.value
    router.delete(route('planning.schedules.destroy', editingScheduleId.value), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal()
            deletingId.value = null
        },
    })
}

// ── Escape key ────────────────────────────────────────────────────────────────
function onKeydown(e) {
    if (e.key === 'Escape' && showModal.value) closeModal()
}

// ── Formatage date ────────────────────────────────────────────────────────────
function formatDateLabel(dateStr) {
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('fr-FR', {
        weekday: 'long', day: 'numeric', month: 'long'
    })
}

// ── Stats totaux ──────────────────────────────────────────────────────────────
const totalEntries = computed(() =>
    Object.values(props.stats).reduce((a, b) => a + b, 0)
)
</script>

<template>
    <Head title="Planning" />

    <AppLayout title="Planning">
        <div @keydown="onKeydown" tabindex="-1" class="outline-none">

            <!-- ── Toolbar ── -->
            <div class="flex flex-col gap-4 mb-6">
                <!-- Ligne 1 : navigation + titre + switch vue -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <!-- Navigation semaine -->
                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('planning.week', { date: prev_week })"
                            class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50
                                   text-slate-600 transition-all active:scale-95"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                            </svg>
                        </Link>
                        <Link
                            :href="route('planning.week', { date: new Date().toISOString().slice(0,10) })"
                            class="px-3 py-2 text-xs font-semibold border border-slate-200 rounded-xl
                                   hover:bg-slate-50 text-slate-600 transition-all"
                        >
                            Aujourd'hui
                        </Link>
                        <Link
                            :href="route('planning.week', { date: next_week })"
                            class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50
                                   text-slate-600 transition-all active:scale-95"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </Link>
                    </div>

                    <!-- Titre semaine -->
                    <h2 class="flex-1 text-lg font-display font-bold text-slate-900">
                        {{ week_label }}
                    </h2>

                    <!-- Switch Semaine / Mois -->
                    <div class="flex items-center rounded-xl border border-slate-200 p-1 bg-slate-50 self-start sm:self-auto">
                        <span class="px-3 py-1.5 rounded-lg bg-white shadow-sm text-sm font-semibold text-primary-700">
                            Semaine
                        </span>
                        <Link
                            :href="route('planning.month', { date: week_start })"
                            class="px-3 py-1.5 text-sm font-medium text-slate-500 hover:text-slate-700 transition-colors"
                        >
                            Mois
                        </Link>
                    </div>
                </div>

                <!-- Ligne 2 : filtres départements + stats -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Filtre "Tous" -->
                    <button
                        @click="activeDept = null"
                        :class="[
                            'px-3 py-1.5 rounded-full text-xs font-semibold transition-all',
                            !activeDept
                                ? 'bg-primary-700 text-white'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200',
                        ]"
                    >
                        Tous ({{ employees.length }})
                    </button>

                    <!-- Filtres par département -->
                    <button
                        v-for="dept in departments"
                        :key="dept.id"
                        @click="activeDept = activeDept === dept.id ? null : dept.id"
                        :class="[
                            'px-3 py-1.5 rounded-full text-xs font-semibold transition-all',
                            activeDept === dept.id
                                ? 'text-white'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200',
                        ]"
                        :style="activeDept === dept.id ? { backgroundColor: dept.color } : {}"
                    >
                        <span class="w-2 h-2 rounded-full inline-block mr-1.5"
                              :style="{ backgroundColor: dept.color }" />
                        {{ dept.name }}
                    </button>

                    <!-- Stats -->
                    <div class="ml-auto flex items-center gap-3">
                        <div v-for="t in scheduleTypes.filter(t => (stats[t.value] ?? 0) > 0)"
                             :key="t.value"
                             class="flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full"
                             :style="{ backgroundColor: t.bg, color: t.text }">
                            <span>{{ t.emoji }}</span>
                            <span>{{ stats[t.value] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Grille planning ── -->
            <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[700px]">
                        <!-- En-tête : jours -->
                        <thead>
                            <tr class="border-b border-slate-100">
                                <!-- Colonne Employé (sticky) -->
                                <th class="sticky left-0 z-10 bg-white w-44 px-4 py-3 text-left border-r border-slate-100">
                                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                                        {{ filteredEmployees.length }} employé{{ filteredEmployees.length !== 1 ? 's' : '' }}
                                    </span>
                                </th>

                                <!-- Colonnes jours -->
                                <th
                                    v-for="day in days"
                                    :key="day.date"
                                    class="w-[calc((100%-176px)/7)] px-2 py-3 text-center"
                                    :class="day.is_weekend ? 'bg-slate-50/70' : ''"
                                >
                                    <div :class="[
                                        'inline-flex flex-col items-center gap-0.5',
                                    ]">
                                        <span class="text-xs font-semibold uppercase tracking-wider"
                                              :class="day.is_today ? 'text-primary-600' : 'text-slate-400'">
                                            {{ day.label }}
                                        </span>
                                        <span
                                            :class="[
                                                'w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold',
                                                day.is_today
                                                    ? 'bg-primary-700 text-white'
                                                    : day.is_weekend
                                                        ? 'text-slate-400'
                                                        : 'text-slate-700',
                                            ]"
                                        >
                                            {{ day.day_number }}
                                        </span>
                                    </div>
                                </th>
                            </tr>
                        </thead>

                        <!-- Corps : employés -->
                        <tbody class="divide-y divide-slate-50">
                            <!-- État vide -->
                            <tr v-if="filteredEmployees.length === 0">
                                <td :colspan="days.length + 1" class="py-16 text-center">
                                    <div class="text-3xl mb-3 opacity-40">👥</div>
                                    <p class="text-sm text-slate-500">Aucun employé dans ce département</p>
                                </td>
                            </tr>

                            <tr
                                v-for="employee in filteredEmployees"
                                :key="employee.id"
                                class="group hover:bg-slate-50/50 transition-colors"
                            >
                                <!-- Nom employé (sticky) -->
                                <td class="sticky left-0 z-10 bg-white group-hover:bg-slate-50/50
                                           border-r border-slate-100 px-4 py-2.5 transition-colors">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <!-- Avatar -->
                                        <div class="w-7 h-7 rounded-full flex items-center justify-center
                                                    text-[10px] font-bold shrink-0"
                                             :style="employee.department
                                                 ? { backgroundColor: employee.department.color + '25', color: employee.department.color }
                                                 : { backgroundColor: '#E2E8F0', color: '#64748B' }">
                                            {{ employee.initials }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-slate-800 truncate leading-tight">
                                                {{ employee.name }}
                                            </p>
                                            <p v-if="employee.department"
                                               class="text-[10px] text-slate-400 truncate leading-tight">
                                                {{ employee.department.name }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Cellules planning -->
                                <td
                                    v-for="day in days"
                                    :key="day.date"
                                    class="px-1.5 py-2 text-center align-middle"
                                    :class="[
                                        day.is_weekend ? 'bg-slate-50/50' : '',
                                        (can_edit || employee.id === auth_user_id) && employee.schedules?.[day.date]
                                            ? 'cursor-pointer'
                                            : can_edit ? 'cursor-pointer' : '',
                                    ]"
                                    @click="handleCellClick(employee, day)"
                                >
                                    <!-- Entrée existante -->
                                    <template v-if="employee.schedules?.[day.date]">
                                        <div
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-lg
                                                   text-xs font-semibold transition-all hover:scale-105 max-w-full"
                                            :style="{
                                                backgroundColor: employee.schedules[day.date].hex_bg,
                                                color: employee.schedules[day.date].hex_text,
                                            }"
                                        >
                                            <span>{{ employee.schedules[day.date].emoji }}</span>
                                            <span class="hidden sm:inline truncate max-w-[60px]">
                                                {{ employee.schedules[day.date].label }}
                                            </span>
                                        </div>
                                        <!-- Horaires (desktop) -->
                                        <p v-if="employee.schedules[day.date].duration"
                                           class="text-[10px] text-slate-400 mt-0.5 hidden lg:block">
                                            {{ employee.schedules[day.date].start_time }} – {{ employee.schedules[day.date].end_time }}
                                        </p>
                                    </template>

                                    <!-- Cellule vide (admin/manager) -->
                                    <template v-else-if="can_edit && !day.is_weekend">
                                        <div class="w-full h-8 rounded-lg border-2 border-dashed border-slate-100
                                                    hover:border-primary-200 hover:bg-primary-50/50 transition-all
                                                    flex items-center justify-center opacity-0 group-hover:opacity-100">
                                            <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                        </div>
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Légende -->
                <div class="border-t border-slate-100 px-4 py-3 flex flex-wrap gap-3">
                    <span class="text-xs text-slate-400 font-medium mr-1">Légende :</span>
                    <div v-for="t in scheduleTypes" :key="t.value"
                         class="flex items-center gap-1.5 text-xs"
                         :style="{ color: t.text }">
                        <span class="w-2.5 h-2.5 rounded-sm" :style="{ backgroundColor: t.bg, outline: `1px solid ${t.text}30` }" />
                        {{ t.emoji }} {{ t.label }}
                    </div>
                </div>
            </div>

        </div>

        <!-- ══════════════════════════════════════════════════════════
             Modal : Créer / Modifier / Voir un planning
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
                             class="relative w-full sm:max-w-md bg-white rounded-t-3xl sm:rounded-2xl
                                    shadow-2xl overflow-hidden">

                            <!-- Handle -->
                            <div class="sm:hidden flex justify-center pt-3 pb-1">
                                <div class="w-10 h-1 rounded-full bg-slate-200" />
                            </div>

                            <!-- Header -->
                            <div class="flex items-center justify-between px-5 pt-4 sm:pt-5 pb-4 border-b border-slate-100">
                                <div>
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <div class="w-3 h-3 rounded-full"
                                             :style="{ backgroundColor: selectedType.text }" />
                                        <h3 class="text-base font-display font-bold text-slate-900">
                                            {{ modalMode === 'view' ? 'Détail' : modalMode === 'edit' ? 'Modifier' : 'Planifier' }}
                                        </h3>
                                    </div>
                                    <p class="text-xs text-slate-500">
                                        {{ selectedEmployee?.name }} ·
                                        {{ new Date(form.date + 'T00:00:00').toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' }) }}
                                    </p>
                                </div>
                                <button @click="closeModal"
                                        class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center
                                               text-slate-400 hover:text-slate-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Corps -->
                            <form @submit.prevent="submit" class="px-5 py-4 space-y-4">

                                <!-- Sélecteur de type -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Type</label>
                                    <div class="grid grid-cols-3 gap-2">
                                        <button
                                            v-for="t in scheduleTypes"
                                            :key="t.value"
                                            type="button"
                                            :disabled="modalMode === 'view'"
                                            @click="form.type = t.value"
                                            :class="[
                                                'flex flex-col items-center gap-1 py-2.5 px-2 rounded-xl border-2 text-xs font-semibold transition-all',
                                                form.type === t.value
                                                    ? 'border-transparent scale-105 shadow-sm'
                                                    : 'border-transparent bg-slate-50 text-slate-500 hover:bg-slate-100',
                                                modalMode === 'view' ? 'cursor-default' : 'cursor-pointer',
                                            ]"
                                            :style="form.type === t.value
                                                ? { backgroundColor: t.bg, color: t.text }
                                                : {}"
                                        >
                                            <span class="text-xl">{{ t.emoji }}</span>
                                            <span>{{ t.label }}</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Horaires (si type travail / télétravail / formation) -->
                                <div v-if="needsTimes || (modalMode === 'view' && form.start_time)"
                                     class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Début</label>
                                        <input
                                            v-model="form.start_time"
                                            type="time"
                                            :disabled="modalMode === 'view'"
                                            class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm
                                                   focus:outline-none focus:ring-2 focus:ring-primary-500
                                                   disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-default"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Fin</label>
                                        <input
                                            v-model="form.end_time"
                                            type="time"
                                            :disabled="modalMode === 'view'"
                                            class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm
                                                   focus:outline-none focus:ring-2 focus:ring-primary-500
                                                   disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-default"
                                        />
                                    </div>
                                    <!-- Pause -->
                                    <div class="col-span-2">
                                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Pause (minutes)</label>
                                        <input
                                            v-model.number="form.break_minutes"
                                            type="number"
                                            min="0"
                                            max="480"
                                            :disabled="modalMode === 'view'"
                                            class="w-28 px-3 py-2 border border-slate-200 rounded-xl text-sm
                                                   focus:outline-none focus:ring-2 focus:ring-primary-500
                                                   disabled:bg-slate-50 disabled:text-slate-500"
                                        />
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">
                                        Notes <span class="font-normal text-slate-400">(optionnel)</span>
                                    </label>
                                    <textarea
                                        v-model="form.notes"
                                        rows="2"
                                        :disabled="modalMode === 'view'"
                                        placeholder="Informations complémentaires…"
                                        class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm resize-none
                                               focus:outline-none focus:ring-2 focus:ring-primary-500
                                               disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-default"
                                    />
                                </div>

                                <!-- Boutons -->
                                <div v-if="modalMode !== 'view'" class="flex gap-2 pt-1">
                                    <!-- Supprimer (mode edit) -->
                                    <button
                                        v-if="modalMode === 'edit'"
                                        type="button"
                                        @click="deleteSchedule"
                                        :disabled="!!deletingId"
                                        class="px-3 py-2.5 rounded-xl border border-danger-200 text-danger-600
                                               hover:bg-danger-50 text-sm font-semibold transition-all disabled:opacity-60"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                    </button>

                                    <button
                                        type="button"
                                        @click="closeModal"
                                        class="flex-1 px-4 py-2.5 border border-slate-200 rounded-xl text-sm
                                               font-semibold text-slate-700 hover:bg-slate-50 transition-all"
                                    >
                                        Annuler
                                    </button>

                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5
                                               bg-primary-700 hover:bg-primary-800 disabled:opacity-60
                                               text-white text-sm font-semibold rounded-xl transition-all"
                                    >
                                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                        </svg>
                                        {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                                    </button>
                                </div>

                                <button
                                    v-else
                                    type="button"
                                    @click="closeModal"
                                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm
                                           font-semibold text-slate-700 hover:bg-slate-50 transition-all"
                                >
                                    Fermer
                                </button>

                                <!-- Erreurs -->
                                <p v-if="form.errors.type || form.errors.date"
                                   class="text-xs text-danger-600">
                                    {{ form.errors.type || form.errors.date }}
                                </p>
                            </form>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

    </AppLayout>
</template>
