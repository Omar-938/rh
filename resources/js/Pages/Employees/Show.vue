<script setup>
import { ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    employee:       Object,
    departments:    { type: Array, default: () => [] },
    managers:       { type: Array, default: () => [] },
    contract_types: { type: Array, default: () => [] },
    roles:          { type: Array, default: () => [] },
})

// ── Edition par section ───────────────────────────────────────────────────────
const editSection = ref(null) // 'identity' | 'contract' | 'personal' | 'emergency'

const form = useForm({
    first_name:              props.employee.first_name,
    last_name:               props.employee.last_name,
    email:                   props.employee.email,
    phone:                   props.employee.phone ?? '',
    role:                    props.employee.role,
    department_id:           props.employee.department_id,
    manager_id:              props.employee.manager_id,
    contract_type:           props.employee.contract_type,
    hire_date:               props.employee.hire_date_raw ?? '',
    contract_end_date:       props.employee.contract_end_date ?? '',
    trial_end_date:          props.employee.trial_end_date ?? '',
    weekly_hours:            props.employee.weekly_hours ?? 35,
    employee_id:             props.employee.employee_id ?? '',
    birth_date:              props.employee.birth_date ?? '',
    address:                 props.employee.address ?? '',
    city:                    props.employee.city ?? '',
    postal_code:             props.employee.postal_code ?? '',
    emergency_contact_name:  props.employee.emergency_contact_name ?? '',
    emergency_contact_phone: props.employee.emergency_contact_phone ?? '',
})

function openEdit(section) {
    editSection.value = section
    form.clearErrors()
}

function cancelEdit() {
    editSection.value = null
    form.clearErrors()
}

function saveSection() {
    form.patch(route('employees.update', props.employee.id), {
        preserveScroll: true,
        onSuccess: () => { editSection.value = null },
    })
}

// ── Actions rapides ───────────────────────────────────────────────────────────
function toggleActive() {
    const verb = props.employee.is_active ? 'Désactiver' : 'Réactiver'
    if (!confirm(`${verb} ${props.employee.full_name} ?`)) return
    router.patch(route('employees.toggle-active', props.employee.id), {}, { preserveScroll: true })
}

function sendResetPassword() {
    if (!confirm(`Envoyer un email de réinitialisation à ${props.employee.email} ?`)) return
    router.post(route('employees.reset-password', props.employee.id), {}, { preserveScroll: true })
}

// ── Couleurs rôle ─────────────────────────────────────────────────────────────
const ROLE_COLORS = {
    admin:    'bg-primary-100 text-primary-700',
    manager:  'bg-violet-100 text-violet-700',
    employee: 'bg-slate-100 text-slate-600',
}
</script>

<template>
    <Head :title="`${employee.full_name} — Collaborateurs`" />
    <AppLayout title="Collaborateurs" :back-url="route('employees.index')">

        <!-- ── Fil d'Ariane + actions ────────────────────────────────────────── -->
        <div class="flex items-center justify-between gap-3 mb-6 flex-wrap">
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <Link :href="route('employees.index')" class="hover:text-primary-600 transition-colors">
                    Collaborateurs
                </Link>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
                <span class="text-slate-900 font-medium truncate">{{ employee.full_name }}</span>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <button @click="sendResetPassword"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium border border-slate-200
                               text-slate-600 rounded-xl hover:bg-slate-50 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                    </svg>
                    Réinitialiser le mot de passe
                </button>
                <button @click="toggleActive"
                        :class="[
                            'inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl transition-colors',
                            employee.is_active
                                ? 'border border-danger-200 text-danger-600 hover:bg-danger-50'
                                : 'bg-success-600 hover:bg-success-700 text-white'
                        ]">
                    {{ employee.is_active ? 'Désactiver' : 'Réactiver' }}
                </button>
            </div>
        </div>

        <!-- ── Profil header ─────────────────────────────────────────────────── -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 mb-6">
            <div class="flex items-start gap-5 flex-wrap sm:flex-nowrap">
                <div v-if="employee.avatar_url"
                     class="w-20 h-20 rounded-2xl bg-cover bg-center flex-shrink-0 ring-4 ring-white shadow"
                     :style="{ backgroundImage: `url(${employee.avatar_url})` }"/>
                <div v-else
                     class="w-20 h-20 rounded-2xl bg-primary-600 flex items-center justify-center
                            text-white text-2xl font-bold flex-shrink-0 ring-4 ring-white shadow">
                    {{ employee.initials }}
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 flex-wrap mb-1">
                        <h1 class="text-xl font-display font-bold text-slate-900">{{ employee.full_name }}</h1>
                        <span class="text-xs px-2.5 py-1 rounded-full font-semibold"
                              :class="ROLE_COLORS[employee.role] ?? 'bg-slate-100 text-slate-600'">
                            {{ employee.role_label }}
                        </span>
                        <span v-if="!employee.is_active"
                              class="text-xs px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 font-medium">
                            Inactif
                        </span>
                    </div>
                    <p class="text-slate-500 text-sm">{{ employee.email }}</p>
                    <div class="flex items-center gap-4 mt-2 text-xs text-slate-400 flex-wrap">
                        <span v-if="employee.department_name">🏢 {{ employee.department_name }}</span>
                        <span v-if="employee.contract_label">📄 {{ employee.contract_label }}</span>
                        <span v-if="employee.hire_date">📅 Depuis le {{ employee.hire_date }}</span>
                        <span v-if="employee.last_login_at">🟢 Connecté {{ employee.last_login_at }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Grille des sections ───────────────────────────────────────────── -->
        <div class="grid lg:grid-cols-2 gap-6">

            <!-- ── Identité & Coordonnées ─────────────────────────────────────── -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                    <h2 class="font-semibold text-slate-800">Identité & Coordonnées</h2>
                    <button v-if="editSection !== 'identity'" @click="openEdit('identity')"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                        </svg>
                    </button>
                </div>

                <!-- Vue lecture -->
                <div v-if="editSection !== 'identity'" class="divide-y divide-slate-50">
                    <div v-for="row in [
                        { label: 'Prénom',       value: employee.first_name },
                        { label: 'Nom',          value: employee.last_name },
                        { label: 'Email',        value: employee.email },
                        { label: 'Téléphone',    value: employee.phone },
                        { label: 'Rôle',         value: employee.role_label },
                        { label: 'Département',  value: employee.department_name },
                        { label: 'Manager',      value: employee.manager_name },
                    ]" :key="row.label"
                       class="flex items-start justify-between gap-4 px-6 py-3">
                        <span class="text-xs font-medium text-slate-400 w-32 flex-shrink-0 pt-0.5">{{ row.label }}</span>
                        <span class="text-sm text-slate-800 text-right min-w-0 break-words">{{ row.value ?? '—' }}</span>
                    </div>
                </div>

                <!-- Formulaire -->
                <form v-else @submit.prevent="saveSection" class="px-6 py-5 space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Prénom</label>
                            <input v-model="form.first_name" type="text" required class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500"/>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Nom</label>
                            <input v-model="form.last_name" type="text" required class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500"/>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Email</label>
                        <input v-model="form.email" type="email" required
                               :class="['w-full px-3 py-2 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:border-transparent', form.errors.email ? 'border-danger-300 focus:ring-danger-500' : 'border-slate-200 focus:ring-primary-500']"/>
                        <p v-if="form.errors.email" class="text-danger-600 text-xs mt-1">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Téléphone</label>
                        <input v-model="form.phone" type="tel" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500"/>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Rôle</label>
                            <select v-model="form.role" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500">
                                <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Département</label>
                            <select v-model="form.department_id" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500">
                                <option :value="null">— Aucun —</option>
                                <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Manager direct</label>
                        <select v-model="form.manager_id" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <option :value="null">— Aucun —</option>
                            <option v-for="m in managers" :key="m.id" :value="m.id">{{ m.name }}</option>
                        </select>
                    </div>
                    <div class="flex gap-2 pt-1">
                        <button type="button" @click="cancelEdit" class="flex-1 px-3 py-2 text-sm font-medium border border-slate-200 text-slate-700 rounded-xl hover:bg-slate-50">Annuler</button>
                        <button type="submit" :disabled="form.processing" class="flex-1 px-3 py-2 text-sm font-semibold bg-primary-700 hover:bg-primary-800 text-white rounded-xl disabled:opacity-60 transition-all">
                            {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- ── Informations contractuelles ───────────────────────────────── -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                    <h2 class="font-semibold text-slate-800">Informations contractuelles</h2>
                    <button v-if="editSection !== 'contract'" @click="openEdit('contract')"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                        </svg>
                    </button>
                </div>

                <div v-if="editSection !== 'contract'" class="divide-y divide-slate-50">
                    <div v-for="row in [
                        { label: 'Matricule',         value: employee.employee_id },
                        { label: 'Type de contrat',   value: employee.contract_label },
                        { label: 'Date d\'embauche',  value: employee.hire_date },
                        { label: 'Fin de contrat',    value: employee.contract_end_date },
                        { label: 'Fin essai',         value: employee.trial_end_date },
                        { label: 'Heures / semaine',  value: employee.weekly_hours ? `${employee.weekly_hours}h` : null },
                    ]" :key="row.label"
                       class="flex items-start justify-between gap-4 px-6 py-3">
                        <span class="text-xs font-medium text-slate-400 w-32 flex-shrink-0 pt-0.5">{{ row.label }}</span>
                        <span class="text-sm text-slate-800 text-right min-w-0">{{ row.value ?? '—' }}</span>
                    </div>
                </div>

                <form v-else @submit.prevent="saveSection" class="px-6 py-5 space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Matricule</label>
                            <input v-model="form.employee_id" type="text" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500"/>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Type de contrat</label>
                            <select v-model="form.contract_type" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500">
                                <option v-for="ct in contract_types" :key="ct.value" :value="ct.value">{{ ct.label }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Date d'embauche</label>
                            <input v-model="form.hire_date" type="date" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500"/>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Fin de contrat</label>
                            <input v-model="form.contract_end_date" type="date" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500"/>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Fin période d'essai</label>
                            <input v-model="form.trial_end_date" type="date" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500"/>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Heures / semaine</label>
                            <input v-model.number="form.weekly_hours" type="number" min="1" max="60" step="0.5" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500"/>
                        </div>
                    </div>
                    <div class="flex gap-2 pt-1">
                        <button type="button" @click="cancelEdit" class="flex-1 px-3 py-2 text-sm font-medium border border-slate-200 text-slate-700 rounded-xl hover:bg-slate-50">Annuler</button>
                        <button type="submit" :disabled="form.processing" class="flex-1 px-3 py-2 text-sm font-semibold bg-primary-700 hover:bg-primary-800 text-white rounded-xl disabled:opacity-60 transition-all">
                            {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- ── Informations personnelles ─────────────────────────────────── -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                    <h2 class="font-semibold text-slate-800">Informations personnelles</h2>
                    <button v-if="editSection !== 'personal'" @click="openEdit('personal')"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                        </svg>
                    </button>
                </div>

                <div v-if="editSection !== 'personal'" class="divide-y divide-slate-50">
                    <div v-for="row in [
                        { label: 'Date de naissance', value: employee.birth_date },
                        { label: 'Adresse',           value: employee.address },
                        { label: 'Ville',             value: employee.city },
                        { label: 'Code postal',       value: employee.postal_code },
                    ]" :key="row.label"
                       class="flex items-start justify-between gap-4 px-6 py-3">
                        <span class="text-xs font-medium text-slate-400 w-32 flex-shrink-0 pt-0.5">{{ row.label }}</span>
                        <span class="text-sm text-slate-800 text-right min-w-0">{{ row.value ?? '—' }}</span>
                    </div>
                </div>

                <form v-else @submit.prevent="saveSection" class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Date de naissance</label>
                        <input v-model="form.birth_date" type="date" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500"/>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Adresse</label>
                        <input v-model="form.address" type="text" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500"/>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Ville</label>
                            <input v-model="form.city" type="text" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500"/>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Code postal</label>
                            <input v-model="form.postal_code" type="text" maxlength="10" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500"/>
                        </div>
                    </div>
                    <div class="flex gap-2 pt-1">
                        <button type="button" @click="cancelEdit" class="flex-1 px-3 py-2 text-sm font-medium border border-slate-200 text-slate-700 rounded-xl hover:bg-slate-50">Annuler</button>
                        <button type="submit" :disabled="form.processing" class="flex-1 px-3 py-2 text-sm font-semibold bg-primary-700 hover:bg-primary-800 text-white rounded-xl disabled:opacity-60 transition-all">
                            {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- ── Contact d'urgence ──────────────────────────────────────────── -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                    <h2 class="font-semibold text-slate-800">Contact d'urgence</h2>
                    <button v-if="editSection !== 'emergency'" @click="openEdit('emergency')"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                        </svg>
                    </button>
                </div>

                <div v-if="editSection !== 'emergency'" class="divide-y divide-slate-50">
                    <div v-for="row in [
                        { label: 'Nom du contact',  value: employee.emergency_contact_name },
                        { label: 'Téléphone',       value: employee.emergency_contact_phone },
                    ]" :key="row.label"
                       class="flex items-start justify-between gap-4 px-6 py-3">
                        <span class="text-xs font-medium text-slate-400 w-32 flex-shrink-0 pt-0.5">{{ row.label }}</span>
                        <span class="text-sm text-slate-800 text-right min-w-0">{{ row.value ?? '—' }}</span>
                    </div>
                </div>

                <form v-else @submit.prevent="saveSection" class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Nom du contact</label>
                        <input v-model="form.emergency_contact_name" type="text" placeholder="Prénom Nom (relation)"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500"/>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Téléphone</label>
                        <input v-model="form.emergency_contact_phone" type="tel"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500"/>
                    </div>
                    <div class="flex gap-2 pt-1">
                        <button type="button" @click="cancelEdit" class="flex-1 px-3 py-2 text-sm font-medium border border-slate-200 text-slate-700 rounded-xl hover:bg-slate-50">Annuler</button>
                        <button type="submit" :disabled="form.processing" class="flex-1 px-3 py-2 text-sm font-semibold bg-primary-700 hover:bg-primary-800 text-white rounded-xl disabled:opacity-60 transition-all">
                            {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AppLayout>
</template>
