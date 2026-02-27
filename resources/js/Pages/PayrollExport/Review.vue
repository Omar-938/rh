<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

// ── Props ──────────────────────────────────────────────────────────────────────
const props = defineProps({
    export:            { type: Object, required: true },
    lines:             { type: Array,  default: () => [] },
    stats:             { type: Object, required: true },
    accountant_emails: { type: Array,  default: () => [] },
})

// Alias pour éviter le mot réservé `export` dans les expressions de template
const exp = computed(() => props.export)

// ── Directive auto-focus ───────────────────────────────────────────────────────
const vFocus = {
    mounted: (el) => { el.focus(); if (el.select) el.select() },
}

// ── Statuts ────────────────────────────────────────────────────────────────────
const STATUS = {
    draft:     { label: 'Brouillon',  bg: 'bg-slate-100',  text: 'text-slate-600',  ring: 'ring-slate-200'  },
    validated: { label: 'Validé',     bg: 'bg-blue-50',    text: 'text-blue-700',   ring: 'ring-blue-200'   },
    sent:      { label: 'Envoyé',     bg: 'bg-emerald-50', text: 'text-emerald-700',ring: 'ring-emerald-200'},
    corrected: { label: 'Corrigé',    bg: 'bg-amber-50',   text: 'text-amber-700',  ring: 'ring-amber-200'  },
}
const statusCfg = computed(() => STATUS[props.export.status] ?? STATUS.draft)

// ── Expand absences ────────────────────────────────────────────────────────────
const expandedAbsences = ref(new Set())
function toggleAbsences(id) {
    const s = new Set(expandedAbsences.value)
    s.has(id) ? s.delete(id) : s.add(id)
    expandedAbsences.value = s
}

// ── Inline editing ─────────────────────────────────────────────────────────────
const editingCell = ref(null)  // { lineId, field }
const tempValue   = ref('')
const savingCell  = ref(false)

function getEditValue(line, field) {
    if (field === 'days_worked') return String(line.data?.days_worked ?? 0)
    if (field === 'days_absent') return String(line.data?.days_absent ?? 0)
    if (field === 'hours_25')    return String(line.data?.overtime?.hours_25 ?? 0)
    if (field === 'hours_50')    return String(line.data?.overtime?.hours_50 ?? 0)
    if (field === 'notes')       return line.data?.notes ?? ''
    return ''
}

function startEdit(line, field) {
    if (!props.export.can_edit || savingCell.value) return
    editingCell.value = { lineId: line.id, field }
    tempValue.value   = getEditValue(line, field)
}

function isEditing(lineId, field) {
    return editingCell.value?.lineId === lineId && editingCell.value?.field === field
}

function cancelEdit() {
    editingCell.value = null
    tempValue.value   = ''
}

function commitEdit(lineId, field) {
    if (savingCell.value) return

    let payload = {}
    if (field === 'notes') {
        payload.notes = tempValue.value
    } else {
        const num = parseFloat(tempValue.value)
        if (isNaN(num) || num < 0) { cancelEdit(); return }
        if      (field === 'hours_25') payload.hours_25 = num
        else if (field === 'hours_50') payload.hours_50 = num
        else                           payload[field]   = num
    }

    savingCell.value = true
    router.put(
        route('payroll-exports.update-line', { export: props.export.id, line: lineId }),
        payload,
        {
            preserveScroll: true,
            onSuccess: () => { editingCell.value = null; tempValue.value = '' },
            onFinish:  () => { savingCell.value = false },
        },
    )
}

function onCellKeydown(e, lineId, field) {
    if (e.key === 'Enter')  { e.preventDefault(); commitEdit(lineId, field) }
    if (e.key === 'Escape') cancelEdit()
}

function onNotesKeydown(e, lineId) {
    if (e.key === 'Escape') cancelEdit()
    if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) commitEdit(lineId, 'notes')
}

// ── Variables modal ────────────────────────────────────────────────────────────
const varModal   = ref(null)  // { lineId, userName, variables, form }
const savingVars = ref(false)

const VARIABLE_PRESETS = ['Prime', 'Acompte', 'Ticket restaurant', 'Mutuelle', 'Transport', 'Remboursement', 'Autre']

function openVarModal(line) {
    if (!props.export.can_edit) return
    varModal.value = {
        lineId:    line.id,
        userName:  line.user_name,
        variables: (line.data?.variables ?? []).map(v => ({ ...v })),
        form:      { label: '', amount: '' },
    }
}

function applyPreset(preset) {
    varModal.value.form.label = preset
}

function addVariable() {
    const label = varModal.value.form.label.trim()
    if (!label) return
    const raw    = varModal.value.form.amount
    const amount = raw !== '' && raw !== null ? parseFloat(raw) : null
    varModal.value.variables.push({ label, amount: (amount !== null && !isNaN(amount)) ? amount : null })
    varModal.value.form = { label: '', amount: '' }
}

function removeVariable(idx) {
    varModal.value.variables.splice(idx, 1)
}

function saveVariables() {
    if (savingVars.value) return
    savingVars.value = true
    router.put(
        route('payroll-exports.update-line', { export: props.export.id, line: varModal.value.lineId }),
        { variables: varModal.value.variables },
        {
            preserveScroll: true,
            onSuccess: () => { varModal.value = null },
            onFinish:  () => { savingVars.value = false },
        },
    )
}

// ── Modal envoi comptable ──────────────────────────────────────────────────────
const sendModal  = ref(false)
const sendForm   = ref({ emails: [], newEmail: '', format: 'pdf' })
const sendingExport = ref(false)

function openSendModal() {
    sendForm.value = {
        emails:   [...(props.accountant_emails.length ? props.accountant_emails : [])],
        newEmail: '',
        format:   props.export.format ?? 'pdf',
    }
    sendModal.value = true
}

function addSendEmail() {
    const email = sendForm.value.newEmail.trim()
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return
    if (!sendForm.value.emails.includes(email)) sendForm.value.emails.push(email)
    sendForm.value.newEmail = ''
}

function removeSendEmail(idx) {
    sendForm.value.emails.splice(idx, 1)
}

function submitSend() {
    if (sendingExport.value || sendForm.value.emails.length === 0) return
    sendingExport.value = true
    router.post(
        route('payroll-exports.send', props.export.id),
        { emails: sendForm.value.emails, format: sendForm.value.format },
        {
            preserveScroll: true,
            onSuccess: () => { sendModal.value = false },
            onFinish:  () => { sendingExport.value = false },
        },
    )
}

const FORMAT_LABELS = { pdf: 'PDF', xlsx: 'Excel (.xlsx)', csv: 'CSV' }

// ── Actions principales ────────────────────────────────────────────────────────
const recompiling = ref(false)
const validating  = ref(false)

function recompile() {
    if (recompiling.value) return
    recompiling.value = true
    router.post(route('payroll-exports.recompile', props.export.id), {}, {
        onFinish: () => { recompiling.value = false },
    })
}

function validateExport() {
    if (validating.value || !props.export.can_edit) return
    if (!confirm(`Valider l'export de ${props.export.period_label} ? Les données seront verrouillées.`)) return
    validating.value = true
    router.post(route('payroll-exports.validate', props.export.id), {}, {
        onFinish: () => { validating.value = false },
    })
}

// ── Helpers ────────────────────────────────────────────────────────────────────
function fmtDays(n) {
    const v = parseFloat(n ?? 0)
    return v === 0 ? '—' : `${v} j`
}
function fmtHours(n) {
    const v = parseFloat(n ?? 0)
    if (v === 0) return '—'
    const h = Math.floor(v)
    const m = Math.round((v - h) * 60)
    return m > 0 ? `${h}h${String(m).padStart(2, '0')}` : `${h}h`
}

const CONTRACT_LABELS = {
    cdi: 'CDI', cdd: 'CDD', interim: 'Intérim',
    stage: 'Stage', alternance: 'Alternance',
}

function presenceRate(line) {
    const wd     = parseFloat(line.data?.working_days ?? 0)
    const worked = parseFloat(line.data?.days_worked ?? 0)
    return wd > 0 ? Math.round((worked / wd) * 100) : 100
}
</script>

<template>
    <Head :title="`Export ${exp.period_label}`" />
    <AppLayout :title="`Export ${exp.period_label}`">

        <!-- ── Fil d'Ariane ──────────────────────────────────────────────── -->
        <nav class="flex items-center gap-2 text-sm text-slate-400 mb-5">
            <Link :href="route('payroll-exports.index')"
                  class="hover:text-primary-600 transition-colors font-medium">
                Export paie
            </Link>
            <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
            </svg>
            <span class="text-slate-700 font-semibold capitalize">{{ exp.period_label }}</span>
        </nav>

        <!-- ── En-tête ────────────────────────────────────────────────────── -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="text-xl font-display font-bold text-slate-900 capitalize">
                    {{ exp.period_label }}
                </h2>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1"
                      :class="[statusCfg.bg, statusCfg.text, statusCfg.ring]">
                    <span class="w-1.5 h-1.5 rounded-full"
                          :class="exp.status === 'sent'      ? 'bg-emerald-500' :
                                  exp.status === 'validated' ? 'bg-blue-500' :
                                  exp.status === 'corrected' ? 'bg-amber-400' : 'bg-slate-400'" />
                    {{ exp.status_label }}
                </span>
                <span v-if="exp.is_correction"
                      class="px-2 py-0.5 bg-orange-50 text-orange-700 text-xs font-semibold rounded-full ring-1 ring-orange-200">
                    Correction
                </span>
            </div>

            <!-- Barre d'actions -->
            <div class="flex items-center gap-2 flex-wrap">
                <!-- Recompiler (brouillon seulement) -->
                <button v-if="exp.can_edit"
                        @click="recompile"
                        :disabled="recompiling"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 border border-slate-200
                               text-slate-700 text-sm font-semibold rounded-xl hover:bg-slate-50
                               transition-colors disabled:opacity-60">
                    <svg v-if="recompiling" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <svg v-else class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                    </svg>
                    {{ recompiling ? 'Recalcul…' : 'Recompiler' }}
                </button>

                <!-- Valider (brouillon seulement) -->
                <button v-if="exp.can_edit && lines.length > 0"
                        @click="validateExport"
                        :disabled="validating"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700
                               text-white text-sm font-semibold rounded-xl shadow-sm transition-all
                               active:scale-95 disabled:opacity-60">
                    <svg v-if="validating" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ validating ? 'Validation…' : 'Valider l\'export' }}
                </button>

                <!-- Télécharger (tous statuts sauf brouillon vide) -->
                <div v-if="lines.length > 0 && exp.status !== 'draft'"
                     class="flex items-center gap-1 border border-slate-200 rounded-xl overflow-hidden">
                    <span class="px-2.5 text-xs text-slate-400 font-medium">Télécharger</span>
                    <a :href="route('payroll-exports.download', { export: exp.id, format: 'pdf' })"
                       target="_blank"
                       class="px-2.5 py-2 text-xs font-semibold text-slate-600 hover:bg-red-50 hover:text-red-600
                              border-l border-slate-200 transition-colors">
                        PDF
                    </a>
                    <a :href="route('payroll-exports.download', { export: exp.id, format: 'xlsx' })"
                       target="_blank"
                       class="px-2.5 py-2 text-xs font-semibold text-slate-600 hover:bg-emerald-50 hover:text-emerald-600
                              border-l border-slate-200 transition-colors">
                        Excel
                    </a>
                    <a :href="route('payroll-exports.download', { export: exp.id, format: 'csv' })"
                       target="_blank"
                       class="px-2.5 py-2 text-xs font-semibold text-slate-600 hover:bg-blue-50 hover:text-blue-600
                              border-l border-slate-200 transition-colors">
                        CSV
                    </a>
                </div>

                <!-- Envoyer au comptable (validé ou déjà envoyé = renvoi) -->
                <button v-if="exp.status === 'validated' || exp.status === 'sent'"
                        @click="openSendModal"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700
                               text-white text-sm font-semibold rounded-xl shadow-sm transition-all active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                    </svg>
                    {{ exp.status === 'sent' ? 'Renvoyer' : 'Envoyer au comptable' }}
                </button>
            </div>
        </div>

        <!-- ── Méta (si validé/envoyé) ───────────────────────────────────── -->
        <div v-if="exp.validated_at || exp.sent_at"
             class="flex flex-wrap gap-3 mb-5 text-xs text-slate-500">
            <span v-if="exp.validated_at"
                  class="inline-flex items-center gap-1.5 bg-blue-50 text-blue-700 px-3 py-1.5 rounded-full">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Validé le {{ exp.validated_at }}
                <template v-if="exp.validated_by"> par {{ exp.validated_by }}</template>
            </span>
            <span v-if="exp.sent_at"
                  class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 px-3 py-1.5 rounded-full">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                </svg>
                Envoyé le {{ exp.sent_at }}
                <template v-if="exp.sent_to?.length"> → {{ exp.sent_to.join(', ') }}</template>
            </span>
        </div>

        <!-- ── Bandeau mode édition ───────────────────────────────────────── -->
        <div v-if="exp.can_edit"
             class="flex items-center gap-2 mb-4 px-4 py-2.5 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800">
            <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/>
            </svg>
            <span>
                <strong>Mode édition :</strong>
                cliquez sur l'icône <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 bg-amber-100 rounded text-amber-700 font-medium">✎</span>
                d'une ligne pour modifier les valeurs, ou utilisez <strong>+ Variable</strong> pour ajouter des primes, acomptes, etc.
            </span>
        </div>

        <!-- ── Stats résumé ───────────────────────────────────────────────── -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-4 py-3.5">
                <p class="text-xs font-medium text-slate-400 mb-0.5">Employés</p>
                <p class="text-2xl font-bold text-slate-900">{{ stats.employees }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-4 py-3.5">
                <p class="text-xs font-medium text-slate-400 mb-0.5">Jours travaillés</p>
                <p class="text-2xl font-bold text-slate-700">{{ stats.days_worked }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-4 py-3.5">
                <p class="text-xs font-medium text-slate-400 mb-0.5">Jours d'absence</p>
                <p class="text-2xl font-bold"
                   :class="stats.days_absent > 0 ? 'text-amber-600' : 'text-slate-400'">
                    {{ stats.days_absent || '—' }}
                </p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-4 py-3.5">
                <p class="text-xs font-medium text-slate-400 mb-0.5">HS 25%</p>
                <p class="text-2xl font-bold"
                   :class="stats.hours_25 > 0 ? 'text-blue-600' : 'text-slate-400'">
                    {{ stats.hours_25 > 0 ? stats.hours_25 + 'h' : '—' }}
                </p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-4 py-3.5">
                <p class="text-xs font-medium text-slate-400 mb-0.5">HS 50%</p>
                <p class="text-2xl font-bold"
                   :class="stats.hours_50 > 0 ? 'text-purple-600' : 'text-slate-400'">
                    {{ stats.hours_50 > 0 ? stats.hours_50 + 'h' : '—' }}
                </p>
            </div>
        </div>

        <!-- ── Tableau ────────────────────────────────────────────────────── -->
        <div v-if="lines.length === 0"
             class="bg-white rounded-2xl border border-slate-100 shadow-sm p-12 text-center">
            <div class="text-4xl mb-3">⚡</div>
            <p class="text-base font-semibold text-slate-700">Aucune donnée compilée</p>
            <p class="text-sm text-slate-400 mt-1 max-w-sm mx-auto">
                Cliquez sur "Recompiler" pour générer les données de cet exp.
            </p>
            <button @click="recompile" :disabled="recompiling"
                    class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white
                           text-sm font-semibold rounded-xl hover:bg-primary-700 transition-colors
                           disabled:opacity-60">
                <svg v-if="recompiling" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ recompiling ? 'Compilation…' : 'Compiler les données' }}
            </button>
        </div>

        <div v-else class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[960px]">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/60">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide w-60">
                                Employé
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide w-32">
                                Présences
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide w-32">
                                Absences
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide w-24">
                                HS +25%
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide w-24">
                                HS +50%
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">
                                Variables
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide w-40">
                                Notes
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <template v-for="line in lines" :key="line.id">

                            <!-- ── Ligne principale ──────────────────────── -->
                            <tr class="group hover:bg-slate-50/50 transition-colors"
                                :class="line.is_modified ? 'bg-amber-50/20' : ''">

                                <!-- Employé -->
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-primary-100 text-primary-700 text-xs
                                                    font-bold flex items-center justify-center shrink-0">
                                            {{ line.user_initials }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-slate-800 truncate">
                                                {{ line.user_name }}
                                            </p>
                                            <p class="text-xs text-slate-400 truncate">
                                                {{ line.department ?? 'Sans département' }}
                                                <span v-if="line.data?.contract_type" class="ml-1 text-slate-300">·</span>
                                                <span v-if="line.data?.contract_type">
                                                    {{ CONTRACT_LABELS[line.data.contract_type] ?? line.data.contract_type }}
                                                </span>
                                            </p>
                                        </div>
                                        <span v-if="line.is_modified"
                                              title="Modifié manuellement"
                                              class="ml-auto w-2 h-2 rounded-full bg-amber-400 shrink-0" />
                                    </div>
                                </td>

                                <!-- ── Présences (jours travaillés) ──────── -->
                                <td class="px-4 py-3.5 text-center">
                                    <!-- Mode édition -->
                                    <div v-if="isEditing(line.id, 'days_worked')" class="flex flex-col items-center gap-1">
                                        <input v-model="tempValue"
                                               v-focus
                                               type="number" min="0" max="31" step="0.5"
                                               class="w-16 text-center text-sm font-bold border border-blue-400 rounded-lg
                                                      px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                               @click.stop
                                               @keydown="(e) => onCellKeydown(e, line.id, 'days_worked')"
                                               @blur="commitEdit(line.id, 'days_worked')" />
                                        <p class="text-xs text-slate-400">↵ valider · Éch. annuler</p>
                                    </div>
                                    <!-- Mode affichage -->
                                    <template v-else>
                                        <div class="flex items-center justify-center gap-1">
                                            <div>
                                                <p class="text-sm font-bold text-slate-800">
                                                    {{ fmtDays(line.data?.days_worked) }}
                                                </p>
                                                <p class="text-xs text-slate-400">
                                                    / {{ line.data?.working_days ?? '?' }} ouvrés
                                                </p>
                                            </div>
                                            <button v-if="exp.can_edit"
                                                    @click="startEdit(line, 'days_worked')"
                                                    title="Modifier"
                                                    class="opacity-0 group-hover:opacity-100 transition-opacity p-1
                                                           hover:bg-blue-50 rounded-lg text-slate-400 hover:text-blue-500 shrink-0">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                                                </svg>
                                            </button>
                                        </div>
                                        <!-- Mini barre présence -->
                                        <div class="mt-1.5 h-1 bg-slate-100 rounded-full w-12 mx-auto overflow-hidden">
                                            <div class="h-full rounded-full transition-all"
                                                 :class="presenceRate(line) >= 90 ? 'bg-emerald-500' :
                                                         presenceRate(line) >= 70 ? 'bg-amber-400' : 'bg-red-400'"
                                                 :style="{ width: presenceRate(line) + '%' }" />
                                        </div>
                                    </template>
                                </td>

                                <!-- ── Absences (jours absents) ──────────── -->
                                <td class="px-4 py-3.5 text-center">
                                    <!-- Mode édition -->
                                    <div v-if="isEditing(line.id, 'days_absent')" class="flex flex-col items-center gap-1">
                                        <input v-model="tempValue"
                                               v-focus
                                               type="number" min="0" max="31" step="0.5"
                                               class="w-16 text-center text-sm font-bold border border-blue-400 rounded-lg
                                                      px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                               @click.stop
                                               @keydown="(e) => onCellKeydown(e, line.id, 'days_absent')"
                                               @blur="commitEdit(line.id, 'days_absent')" />
                                        <p class="text-xs text-slate-400">↵ valider</p>
                                    </div>
                                    <!-- Mode affichage -->
                                    <template v-else>
                                        <div class="flex items-center justify-center gap-1">
                                            <!-- Bouton expand si absences détaillées -->
                                            <button v-if="line.data?.absences?.length > 0"
                                                    @click="toggleAbsences(line.id)"
                                                    class="text-sm font-bold text-amber-600 hover:text-amber-700
                                                           transition-colors flex items-center gap-0.5">
                                                {{ fmtDays(line.data?.days_absent) }}
                                                <svg class="w-3.5 h-3.5 transition-transform"
                                                     :class="expandedAbsences.has(line.id) ? 'rotate-180' : ''"
                                                     fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                                                </svg>
                                            </button>
                                            <span v-else class="text-sm text-slate-300">
                                                {{ fmtDays(line.data?.days_absent) }}
                                            </span>
                                            <button v-if="exp.can_edit"
                                                    @click="startEdit(line, 'days_absent')"
                                                    title="Modifier"
                                                    class="opacity-0 group-hover:opacity-100 transition-opacity p-1
                                                           hover:bg-blue-50 rounded-lg text-slate-400 hover:text-blue-500 shrink-0">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                </td>

                                <!-- ── HS +25% ────────────────────────────── -->
                                <td class="px-4 py-3.5 text-center">
                                    <div v-if="isEditing(line.id, 'hours_25')" class="flex flex-col items-center gap-1">
                                        <input v-model="tempValue"
                                               v-focus
                                               type="number" min="0" max="500" step="0.25"
                                               placeholder="heures"
                                               class="w-16 text-center text-sm font-bold border border-blue-400 rounded-lg
                                                      px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                               @click.stop
                                               @keydown="(e) => onCellKeydown(e, line.id, 'hours_25')"
                                               @blur="commitEdit(line.id, 'hours_25')" />
                                        <p class="text-xs text-slate-400">décimal (ex: 2.5)</p>
                                    </div>
                                    <template v-else>
                                        <div class="flex items-center justify-center gap-1">
                                            <span class="text-sm font-bold"
                                                  :class="(line.data?.overtime?.hours_25 ?? 0) > 0 ? 'text-blue-600' : 'text-slate-300'">
                                                {{ fmtHours(line.data?.overtime?.hours_25) }}
                                            </span>
                                            <button v-if="exp.can_edit"
                                                    @click="startEdit(line, 'hours_25')"
                                                    title="Modifier"
                                                    class="opacity-0 group-hover:opacity-100 transition-opacity p-1
                                                           hover:bg-blue-50 rounded-lg text-slate-400 hover:text-blue-500 shrink-0">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                </td>

                                <!-- ── HS +50% ────────────────────────────── -->
                                <td class="px-4 py-3.5 text-center">
                                    <div v-if="isEditing(line.id, 'hours_50')" class="flex flex-col items-center gap-1">
                                        <input v-model="tempValue"
                                               v-focus
                                               type="number" min="0" max="500" step="0.25"
                                               placeholder="heures"
                                               class="w-16 text-center text-sm font-bold border border-blue-400 rounded-lg
                                                      px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                               @click.stop
                                               @keydown="(e) => onCellKeydown(e, line.id, 'hours_50')"
                                               @blur="commitEdit(line.id, 'hours_50')" />
                                        <p class="text-xs text-slate-400">décimal (ex: 1.5)</p>
                                    </div>
                                    <template v-else>
                                        <div class="flex items-center justify-center gap-1">
                                            <span class="text-sm font-bold"
                                                  :class="(line.data?.overtime?.hours_50 ?? 0) > 0 ? 'text-purple-600' : 'text-slate-300'">
                                                {{ fmtHours(line.data?.overtime?.hours_50) }}
                                            </span>
                                            <button v-if="exp.can_edit"
                                                    @click="startEdit(line, 'hours_50')"
                                                    title="Modifier"
                                                    class="opacity-0 group-hover:opacity-100 transition-opacity p-1
                                                           hover:bg-blue-50 rounded-lg text-slate-400 hover:text-blue-500 shrink-0">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                </td>

                                <!-- ── Variables ──────────────────────────── -->
                                <td class="px-4 py-3.5">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span v-for="v in line.data?.variables" :key="v.label"
                                              class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary-50
                                                     text-primary-700 text-xs font-medium rounded-full ring-1 ring-primary-100">
                                            {{ v.label }}
                                            <template v-if="v.amount != null">
                                                <span class="font-bold"
                                                      :class="v.amount >= 0 ? 'text-emerald-600' : 'text-red-500'">
                                                    {{ v.amount > 0 ? '+' : '' }}{{ v.amount }}€
                                                </span>
                                            </template>
                                        </span>
                                        <!-- Bouton ajouter variable -->
                                        <button v-if="exp.can_edit"
                                                @click="openVarModal(line)"
                                                class="inline-flex items-center gap-1 px-2 py-0.5 bg-slate-100
                                                       hover:bg-primary-50 text-slate-500 hover:text-primary-600
                                                       text-xs font-medium rounded-full transition-colors ring-1 ring-slate-200 hover:ring-primary-200">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                            </svg>
                                            Variable
                                        </button>
                                        <span v-else-if="!line.data?.variables?.length"
                                              class="text-xs text-slate-300 italic">—</span>
                                    </div>
                                </td>

                                <!-- ── Notes ──────────────────────────────── -->
                                <td class="px-4 py-3.5">
                                    <!-- Mode édition -->
                                    <div v-if="isEditing(line.id, 'notes')" class="flex flex-col gap-1">
                                        <textarea v-model="tempValue"
                                                  v-focus
                                                  rows="3"
                                                  placeholder="Notes pour le comptable…"
                                                  class="w-full text-xs border border-blue-400 rounded-xl px-2.5 py-2
                                                         resize-none focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                  @keydown="(e) => onNotesKeydown(e, line.id)"
                                                  @blur="commitEdit(line.id, 'notes')" />
                                        <p class="text-xs text-slate-400">⌘↵ sauvegarder · Éch. annuler</p>
                                    </div>
                                    <!-- Mode affichage -->
                                    <template v-else>
                                        <p v-if="line.data?.notes"
                                           class="text-xs text-slate-600 line-clamp-2"
                                           :class="exp.can_edit ? 'cursor-text hover:text-blue-600 transition-colors' : ''"
                                           :title="line.data.notes"
                                           @click="exp.can_edit && startEdit(line, 'notes')">
                                            {{ line.data.notes }}
                                        </p>
                                        <button v-else-if="exp.can_edit"
                                                @click="startEdit(line, 'notes')"
                                                class="text-xs text-slate-300 italic hover:text-blue-400 transition-colors">
                                            + Ajouter une note
                                        </button>
                                        <span v-else class="text-xs text-slate-300 italic">—</span>
                                    </template>
                                </td>
                            </tr>

                            <!-- ── Détail absences (expandable) ─────────── -->
                            <tr v-if="expandedAbsences.has(line.id) && line.data?.absences?.length > 0"
                                class="bg-amber-50/40">
                                <td colspan="7" class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2 pl-11">
                                        <span v-for="abs in line.data.absences" :key="abs.type"
                                              class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white
                                                     border border-amber-200 text-amber-800 text-xs font-medium
                                                     rounded-lg shadow-sm">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400" />
                                            {{ abs.type }} :
                                            <span class="font-bold">{{ abs.days }} j</span>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- ── Ligne totaux ───────────────────────────────── -->
                        <tr class="bg-slate-50/80 border-t-2 border-slate-200">
                            <td class="px-4 py-3.5">
                                <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">TOTAUX</p>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <p class="text-sm font-bold text-slate-800">{{ stats.days_worked }} j</p>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <p class="text-sm font-bold"
                                   :class="stats.days_absent > 0 ? 'text-amber-600' : 'text-slate-400'">
                                    {{ stats.days_absent > 0 ? stats.days_absent + ' j' : '—' }}
                                </p>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <p class="text-sm font-bold"
                                   :class="stats.hours_25 > 0 ? 'text-blue-600' : 'text-slate-400'">
                                    {{ stats.hours_25 > 0 ? stats.hours_25 + 'h' : '—' }}
                                </p>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <p class="text-sm font-bold"
                                   :class="stats.hours_50 > 0 ? 'text-purple-600' : 'text-slate-400'">
                                    {{ stats.hours_50 > 0 ? stats.hours_50 + 'h' : '—' }}
                                </p>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="text-xs text-slate-400">
                                    {{ stats.variables > 0 ? stats.variables + ' variable(s)' : '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5" />
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Footer -->
            <div v-if="exp.can_edit"
                 class="flex items-center justify-between px-5 py-3.5 border-t border-slate-100 bg-slate-50/40">
                <p class="text-xs text-slate-400">
                    <span class="font-medium text-amber-600">⚠ Brouillon</span>
                    — Les données peuvent encore changer. Validez une fois prêt à envoyer.
                </p>
                <button @click="validateExport" :disabled="validating || lines.length === 0"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700
                               text-white text-sm font-semibold rounded-xl shadow-sm transition-all
                               active:scale-95 disabled:opacity-60">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Valider l'export
                </button>
            </div>
        </div>

    </AppLayout>

    <!-- ── Modal Envoi comptable ─────────────────────────────────────────────── -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95">

            <div v-if="sendModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4">
                <!-- Overlay -->
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"
                     @click="!sendingExport && (sendModal = false)" />

                <!-- Panneau -->
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden">

                    <!-- En-tête -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Envoyer au comptable</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Export {{ exp.period_label }}</p>
                        </div>
                        <button @click="sendModal = false" :disabled="sendingExport"
                                class="p-1.5 hover:bg-slate-100 rounded-lg text-slate-400 transition-colors disabled:opacity-40">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="px-5 py-5 space-y-5">

                        <!-- Destinataires -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">
                                Destinataires
                            </label>

                            <!-- Chips emails -->
                            <div v-if="sendForm.emails.length > 0" class="flex flex-wrap gap-1.5 mb-2">
                                <span v-for="(email, idx) in sendForm.emails" :key="email"
                                      class="inline-flex items-center gap-1.5 pl-3 pr-1.5 py-1.5 bg-emerald-50
                                             border border-emerald-200 text-emerald-800 text-xs rounded-full font-medium">
                                    <svg class="w-3 h-3 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                                    </svg>
                                    {{ email }}
                                    <button @click="removeSendEmail(idx)"
                                            class="p-0.5 hover:bg-emerald-100 rounded-full text-emerald-500 hover:text-red-500 transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </span>
                            </div>
                            <p v-else class="text-xs text-amber-600 font-medium mb-2">
                                ⚠ Aucun destinataire — ajoutez au moins un email.
                            </p>

                            <!-- Champ ajout email -->
                            <div class="flex gap-2">
                                <input v-model="sendForm.newEmail"
                                       type="email"
                                       placeholder="comptable@cabinet-comptable.fr"
                                       class="flex-1 px-3 py-2 text-sm border border-slate-200 rounded-xl
                                              focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:border-emerald-400"
                                       @keydown.enter.prevent="addSendEmail" />
                                <button @click="addSendEmail"
                                        :disabled="!sendForm.newEmail.trim()"
                                        class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl
                                               transition-colors disabled:opacity-40">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                    </svg>
                                </button>
                            </div>
                            <p class="text-xs text-slate-400 mt-1.5">
                                L'expéditeur recevra automatiquement une copie (CC).
                            </p>
                        </div>

                        <!-- Format -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">
                                Format du fichier joint
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <label v-for="(label, fmt) in FORMAT_LABELS" :key="fmt"
                                       class="flex flex-col items-center gap-1.5 px-3 py-3 rounded-xl border-2 cursor-pointer transition-all"
                                       :class="sendForm.format === fmt
                                           ? 'border-emerald-500 bg-emerald-50'
                                           : 'border-slate-200 hover:border-slate-300 bg-white'">
                                    <input v-model="sendForm.format" type="radio" :value="fmt" class="sr-only">
                                    <span class="text-xl">
                                        {{ fmt === 'pdf' ? '📄' : fmt === 'xlsx' ? '📊' : '📋' }}
                                    </span>
                                    <span class="text-xs font-semibold"
                                          :class="sendForm.format === fmt ? 'text-emerald-700' : 'text-slate-600'">
                                        {{ label }}
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Aperçu/téléchargement avant envoi -->
                        <div class="bg-slate-50 rounded-xl px-4 py-3">
                            <p class="text-xs font-semibold text-slate-500 mb-2">Aperçu avant envoi :</p>
                            <div class="flex gap-2 flex-wrap">
                                <a :href="route('payroll-exports.download', { export: exp.id, format: 'pdf' })"
                                   target="_blank"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium
                                          bg-white border border-slate-200 text-slate-600 rounded-lg hover:border-red-300
                                          hover:text-red-600 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                    </svg>
                                    PDF
                                </a>
                                <a :href="route('payroll-exports.download', { export: exp.id, format: 'xlsx' })"
                                   target="_blank"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium
                                          bg-white border border-slate-200 text-slate-600 rounded-lg hover:border-emerald-300
                                          hover:text-emerald-600 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                    </svg>
                                    Excel
                                </a>
                                <a :href="route('payroll-exports.download', { export: exp.id, format: 'csv' })"
                                   target="_blank"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium
                                          bg-white border border-slate-200 text-slate-600 rounded-lg hover:border-blue-300
                                          hover:text-blue-600 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                    </svg>
                                    CSV
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Footer modal -->
                    <div class="flex items-center justify-between px-5 py-4 border-t border-slate-100 bg-slate-50/40">
                        <p class="text-xs text-slate-400">
                            Envoi en file d'attente — vous recevrez une copie CC.
                        </p>
                        <div class="flex gap-2">
                            <button @click="sendModal = false" :disabled="sendingExport"
                                    class="px-4 py-2 text-sm font-medium border border-slate-200 text-slate-700
                                           rounded-xl hover:bg-slate-50 transition-colors disabled:opacity-60">
                                Annuler
                            </button>
                            <button @click="submitSend"
                                    :disabled="sendingExport || sendForm.emails.length === 0"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700
                                           text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-60">
                                <svg v-if="sendingExport" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                                </svg>
                                {{ sendingExport ? 'Envoi en cours…' : 'Envoyer' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- ── Modal Variables ───────────────────────────────────────────────────── -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95">

            <div v-if="varModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4">
                <!-- Overlay -->
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"
                     @click="!savingVars && (varModal = null)" />

                <!-- Panneau -->
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">
                    <!-- En-tête modal -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Variables de paie</h3>
                            <p class="text-xs text-slate-400 mt-0.5">{{ varModal.userName }}</p>
                        </div>
                        <button @click="varModal = null"
                                class="p-1.5 hover:bg-slate-100 rounded-lg text-slate-400 hover:text-slate-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="px-5 py-4 space-y-4 max-h-[70vh] overflow-y-auto">

                        <!-- Liste des variables existantes -->
                        <div v-if="varModal.variables.length > 0" class="space-y-2">
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">
                                Variables ({{ varModal.variables.length }})
                            </p>
                            <TransitionGroup tag="div" class="space-y-2"
                                             enter-active-class="transition duration-150"
                                             enter-from-class="opacity-0 -translate-y-1"
                                             enter-to-class="opacity-100 translate-y-0"
                                             leave-active-class="transition duration-100"
                                             leave-from-class="opacity-100"
                                             leave-to-class="opacity-0">
                                <div v-for="(v, idx) in varModal.variables" :key="`${idx}-${v.label}`"
                                     class="flex items-center justify-between gap-3 px-3.5 py-2.5
                                            bg-slate-50 rounded-xl border border-slate-100">
                                    <div class="min-w-0 flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-primary-400 shrink-0" />
                                        <span class="text-sm font-medium text-slate-700 truncate">{{ v.label }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span v-if="v.amount != null"
                                              class="text-sm font-bold tabular-nums"
                                              :class="v.amount >= 0 ? 'text-emerald-600' : 'text-red-500'">
                                            {{ v.amount > 0 ? '+' : '' }}{{ v.amount }}€
                                        </span>
                                        <span v-else class="text-xs text-slate-400 italic">sans montant</span>
                                        <button @click="removeVariable(idx)"
                                                class="p-1 hover:bg-red-50 hover:text-red-500 text-slate-300
                                                       rounded-lg transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </TransitionGroup>
                        </div>
                        <p v-else class="text-xs text-slate-400 italic text-center py-2">
                            Aucune variable pour ce salarié. Ajoutez-en ci-dessous.
                        </p>

                        <!-- Saisie rapide (presets) -->
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">
                                Saisie rapide
                            </p>
                            <div class="flex flex-wrap gap-1.5">
                                <button v-for="preset in VARIABLE_PRESETS" :key="preset"
                                        @click="applyPreset(preset)"
                                        class="px-2.5 py-1 text-xs bg-slate-100 hover:bg-primary-50
                                               hover:text-primary-700 text-slate-600 rounded-lg transition-colors">
                                    {{ preset }}
                                </button>
                            </div>
                        </div>

                        <!-- Formulaire ajout -->
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">
                                Ajouter une variable
                            </p>
                            <div class="flex gap-2">
                                <input v-model="varModal.form.label"
                                       type="text"
                                       placeholder="Libellé (ex: Prime vacances)…"
                                       class="flex-1 px-3 py-2 text-sm border border-slate-200 rounded-xl
                                              focus:outline-none focus:ring-2 focus:ring-primary-300 focus:border-primary-400"
                                       @keydown.enter="addVariable" />
                                <input v-model="varModal.form.amount"
                                       type="number"
                                       placeholder="€"
                                       step="0.01"
                                       class="w-24 px-3 py-2 text-sm border border-slate-200 rounded-xl text-center
                                              focus:outline-none focus:ring-2 focus:ring-primary-300 focus:border-primary-400"
                                       @keydown.enter="addVariable" />
                                <button @click="addVariable"
                                        :disabled="!varModal.form.label.trim()"
                                        class="px-3 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl
                                               transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                    </svg>
                                </button>
                            </div>
                            <p class="text-xs text-slate-400 mt-1.5">
                                Le montant est optionnel (utile pour les avantages en nature non chiffrés).
                            </p>
                        </div>
                    </div>

                    <!-- Footer modal -->
                    <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-slate-100 bg-slate-50/40">
                        <button @click="varModal = null"
                                :disabled="savingVars"
                                class="px-4 py-2 text-sm font-medium border border-slate-200 text-slate-700
                                       rounded-xl hover:bg-slate-50 transition-colors disabled:opacity-60">
                            Annuler
                        </button>
                        <button @click="saveVariables"
                                :disabled="savingVars"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700
                                       text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-60">
                            <svg v-if="savingVars" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ savingVars ? 'Enregistrement…' : 'Enregistrer les variables' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
