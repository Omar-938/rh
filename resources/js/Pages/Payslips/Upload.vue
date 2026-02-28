<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

// ── Props ──────────────────────────────────────────────────────────────────────
const props = defineProps({
    employees:     { type: Array,  required: true },
    current_year:  { type: Number, required: true },
    current_month: { type: Number, required: true },
})

// ── Constantes ─────────────────────────────────────────────────────────────────
const MONTHS = [
    { value: 1,  label: 'Janvier'   }, { value: 2,  label: 'Février'    },
    { value: 3,  label: 'Mars'      }, { value: 4,  label: 'Avril'      },
    { value: 5,  label: 'Mai'       }, { value: 6,  label: 'Juin'       },
    { value: 7,  label: 'Juillet'   }, { value: 8,  label: 'Août'       },
    { value: 9,  label: 'Septembre' }, { value: 10, label: 'Octobre'    },
    { value: 11, label: 'Novembre'  }, { value: 12, label: 'Décembre'   },
]

const MONTH_MAP = {
    'janvier': 1, 'jan': 1,
    'fevrier': 2, 'février': 2, 'fev': 2, 'fév': 2,
    'mars': 3,    'mar': 3,
    'avril': 4,   'avr': 4,
    'mai': 5,
    'juin': 6,
    'juillet': 7, 'juil': 7, 'jul': 7,
    'aout': 8,    'août': 8,
    'septembre': 9, 'sep': 9, 'sept': 9,
    'octobre': 10,  'oct': 10,
    'novembre': 11, 'nov': 11,
    'decembre': 12, 'décembre': 12, 'dec': 12, 'déc': 12,
}

const STOP_WORDS = new Set([
    'bulletin', 'bulletins', 'salaire', 'paie', 'paye', 'fiche', 'fiches',
    'bp', 'bs', 'rh', 'hr', 'salary', 'de', 'du', 'le', 'la', 'les', 'et',
    'january', 'february', 'march', 'april', 'june', 'july', 'august',
    'september', 'october', 'november', 'december',
])

// ── État ───────────────────────────────────────────────────────────────────────
const items      = ref([])  // chaque item = { id, file, ...parsed, user_id, period_year, period_month, notes }
const dragging   = ref(false)
const submitting = ref(false)
let   uid        = 0

// ── Parsing client-side ────────────────────────────────────────────────────────
function parseFilenameForPeriod(filename) {
    const base = filename.replace(/\.[^.]+$/, '')

    // Format YYYYMM collé ex: 202501, 20250112
    const yyyymmMatch = base.match(/(\d{4})(0[1-9]|1[0-2])(?!\d)/)
    if (yyyymmMatch) {
        const y = parseInt(yyyymmMatch[1])
        if (y >= 2000 && y <= 2099) {
            return { year: y, month: parseInt(yyyymmMatch[2]) }
        }
    }

    const tokens = base.toLowerCase().split(/[\s_\-\.]+/).filter(Boolean)
    let year = null, month = null

    for (const token of tokens) {
        if (MONTH_MAP[token] !== undefined) {
            month = MONTH_MAP[token]
            continue
        }
        if (/^\d{4}$/.test(token)) {
            const n = parseInt(token)
            if (n >= 2000 && n <= 2099) year = n
        }
        if (month === null && /^(0?[1-9]|1[0-2])$/.test(token)) {
            month = parseInt(token)
        }
    }
    return { year, month }
}

function parseFilenameForEmployee(filename) {
    const base      = filename.replace(/\.[^.]+$/, '').toLowerCase()
    const tokens    = base.split(/[\s_\-\.]+/).filter(Boolean)
    const nameTokens = tokens.filter(t =>
        !STOP_WORDS.has(t) && !/^\d+$/.test(t) && t.length >= 2
    )

    let bestMatch = null
    let bestScore = 0

    for (const emp of props.employees) {
        const parts = emp.name.toLowerCase().split(/\s+/)
        let score   = 0

        for (const part of parts) {
            const partShort = part.substring(0, 4)
            for (const token of nameTokens) {
                if (token === part) {
                    score += 3
                } else if (partShort.length >= 3 &&
                          (token.startsWith(partShort) || part.startsWith(token.substring(0, 4)))) {
                    score += 2
                } else if (partShort.length >= 2 && token.startsWith(partShort)) {
                    score += 1
                }
            }
        }

        if (score > bestScore) {
            bestScore = score
            bestMatch = emp
        }
    }

    // Seuil minimum anti faux-positifs
    return bestScore >= 2 ? bestMatch : null
}

// ── Ajout de fichiers ──────────────────────────────────────────────────────────
function addFiles(fileList) {
    const files = Array.from(fileList).filter(f => f.type === 'application/pdf')

    for (const file of files) {
        // Éviter les doublons
        if (items.value.some(i => i.file.name === file.name && i.file.size === file.size)) continue

        const { year, month } = parseFilenameForPeriod(file.name)
        const emp             = parseFilenameForEmployee(file.name)

        items.value.push({
            id:           uid++,
            file,
            filename:     file.name,
            size:         file.size,
            user_id:      emp?.id ?? null,
            period_year:  year  ?? props.current_year,
            period_month: month ?? props.current_month,
            notes:        '',
            recognized:   emp !== null,
            confidence:   emp ? (month && year ? 'high' : 'medium') : 'none',
        })
    }
}

function removeItem(id) {
    items.value = items.value.filter(i => i.id !== id)
}

// ── Drag & drop ────────────────────────────────────────────────────────────────
function onDragOver(e)  { e.preventDefault(); dragging.value = true }
function onDragLeave()  { dragging.value = false }
function onDrop(e)      { e.preventDefault(); dragging.value = false; addFiles(e.dataTransfer.files) }
function onInputChange(e) { addFiles(e.target.files); e.target.value = '' }

// ── Validation ─────────────────────────────────────────────────────────────────
const isItemValid  = (item) => item.period_year && item.period_month
const allValid     = computed(() => items.value.length > 0 && items.value.every(isItemValid))
const totalSize    = computed(() => {
    const bytes = items.value.reduce((s, i) => s + i.size, 0)
    if (bytes < 1_048_576) return (bytes / 1024).toFixed(0) + ' Ko'
    return (bytes / 1_048_576).toFixed(1) + ' Mo'
})
const recognizedCount = computed(() => items.value.filter(i => i.recognized).length)

// ── Soumission ─────────────────────────────────────────────────────────────────
function submit() {
    if (submitting.value || !allValid.value) return
    submitting.value = true

    const formData = new FormData()
    items.value.forEach((item, idx) => {
        formData.append(`files[${idx}]`, item.file)
        if (item.user_id)      formData.append(`entries[${idx}][user_id]`,      item.user_id)
        formData.append(`entries[${idx}][period_year]`,  item.period_year)
        formData.append(`entries[${idx}][period_month]`, item.period_month)
        if (item.notes)        formData.append(`entries[${idx}][notes]`,         item.notes)
    })

    router.post(route('payslips.store'), formData, {
        forceFormData: true,
        onFinish:  () => { submitting.value = false },
        onSuccess: () => { items.value = [] },
    })
}

// ── Helpers UI ────────────────────────────────────────────────────────────────
const formatSize = (bytes) => bytes < 1_048_576
    ? (bytes / 1024).toFixed(0) + ' Ko'
    : (bytes / 1_048_576).toFixed(1) + ' Mo'

const monthLabel = (m) => MONTHS.find(mo => mo.value === parseInt(m))?.label ?? '?'
</script>

<template>
    <Head title="Importer des bulletins" />
    <AppLayout title="Bulletins de paie" :back-url="route('payslips.index')">

        <!-- En-tête ─────────────────────────────────────────────────────────── -->
        <div class="flex items-start justify-between mb-6">
            <div>
                <nav class="flex items-center gap-2 text-sm text-slate-500 mb-2">
                    <Link :href="route('payslips.index')" class="hover:text-primary-600 transition-colors">
                        Bulletins de paie
                    </Link>
                    <svg class="w-3.5 h-3.5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-slate-700 font-medium">Import en lot</span>
                </nav>
                <h2 class="text-xl font-display font-bold text-slate-900">Importer des bulletins de paie</h2>
                <p class="text-sm text-slate-500 mt-0.5">
                    Glissez vos fichiers PDF — l'employé et la période sont détectés automatiquement.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- ── Zone d'upload ─────────────────────────────────────────────── -->
            <div class="lg:col-span-2 space-y-4">

                <!-- Drop zone ───────────────────────────────────────────────── -->
                <div
                    class="relative border-2 border-dashed rounded-2xl transition-all duration-200 cursor-pointer"
                    :class="dragging
                        ? 'border-primary-400 bg-primary-50 scale-[1.01]'
                        : items.length === 0 ? 'border-slate-300 hover:border-primary-300 bg-white hover:bg-primary-50/30' : 'border-slate-200 bg-slate-50'"
                    @dragover="onDragOver"
                    @dragleave="onDragLeave"
                    @drop="onDrop"
                    @click="$refs.fileInput.click()"
                >
                    <input ref="fileInput" type="file" class="hidden"
                           accept=".pdf,application/pdf" multiple @change="onInputChange" />

                    <div v-if="items.length === 0" class="flex flex-col items-center justify-center py-16 px-6 text-center">
                        <!-- Icône animée -->
                        <div class="relative mb-5">
                            <div class="w-20 h-20 rounded-2xl flex items-center justify-center"
                                 :class="dragging ? 'bg-primary-100' : 'bg-slate-100'">
                                <svg class="w-10 h-10 transition-colors"
                                     :class="dragging ? 'text-primary-500' : 'text-slate-400'"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                                </svg>
                            </div>
                            <!-- Petits badges flottants -->
                            <div class="absolute -top-1 -right-1 w-6 h-6 bg-red-500 rounded-full flex items-center
                                        justify-center text-white text-xs font-bold shadow-sm">PDF</div>
                        </div>
                        <p class="text-base font-semibold text-slate-700">
                            {{ dragging ? 'Relâchez pour importer' : 'Glissez vos PDFs ici' }}
                        </p>
                        <p class="text-sm text-slate-400 mt-1">ou <span class="text-primary-600 font-medium">cliquez pour sélectionner</span></p>
                        <p class="text-xs text-slate-400 mt-3">
                            PDF uniquement · 20 Mo max par fichier · Jusqu'à 200 fichiers
                        </p>
                        <div class="mt-4 flex items-center gap-1.5 text-xs text-slate-500 bg-slate-100 rounded-full px-3 py-1.5">
                            <svg class="w-3.5 h-3.5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            Reconnaissance automatique employé + période
                        </div>
                    </div>

                    <!-- Mode compact quand des fichiers sont chargés -->
                    <div v-else class="flex items-center gap-3 px-5 py-4">
                        <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 4v16m8-8H4"/>
                        </svg>
                        <span class="text-sm text-slate-500">Cliquez ou glissez pour ajouter d'autres fichiers</span>
                    </div>
                </div>

                <!-- ── Table des fichiers ──────────────────────────────────────── -->
                <div v-if="items.length > 0"
                     class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

                    <!-- En-tête table -->
                    <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <p class="text-sm font-semibold text-slate-800">
                                {{ items.length }} fichier{{ items.length > 1 ? 's' : '' }}
                                <span class="text-slate-400 font-normal ml-1">({{ totalSize }})</span>
                            </p>
                            <!-- Badge reconnaissance -->
                            <span v-if="recognizedCount > 0"
                                  class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-50 text-emerald-700
                                         border border-emerald-200 rounded-full text-xs font-medium">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ recognizedCount }}/{{ items.length }} reconnu{{ recognizedCount > 1 ? 's' : '' }}
                            </span>
                        </div>
                        <button @click.stop="items = []"
                                class="text-xs text-slate-400 hover:text-red-500 transition-colors font-medium">
                            Tout retirer
                        </button>
                    </div>

                    <!-- Lignes fichiers -->
                    <div class="divide-y divide-slate-50">
                        <div v-for="item in items" :key="item.id"
                             class="px-5 py-4 group">

                            <!-- Ligne principale -->
                            <div class="flex items-center gap-3 mb-3">
                                <!-- Icône PDF -->
                                <div class="w-9 h-9 rounded-lg bg-red-50 border border-red-200 flex items-center
                                            justify-center text-red-600 font-bold text-xs shrink-0">
                                    PDF
                                </div>

                                <!-- Nom fichier + taille -->
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-700 truncate" :title="item.filename">
                                        {{ item.filename }}
                                    </p>
                                    <p class="text-xs text-slate-400">{{ formatSize(item.size) }}</p>
                                </div>

                                <!-- Indicateur confiance -->
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <span v-if="item.confidence === 'high'"
                                          class="flex items-center gap-1 px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded-full text-xs font-medium">
                                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"/>
                                        Reconnu
                                    </span>
                                    <span v-else-if="item.confidence === 'medium'"
                                          class="flex items-center gap-1 px-2 py-0.5 bg-amber-50 text-amber-700 rounded-full text-xs font-medium">
                                        <span class="w-1.5 h-1.5 bg-amber-400 rounded-full"/>
                                        Partiel
                                    </span>
                                    <span v-else
                                          class="flex items-center gap-1 px-2 py-0.5 bg-slate-100 text-slate-500 rounded-full text-xs">
                                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"/>
                                        À compléter
                                    </span>
                                </div>

                                <!-- Supprimer -->
                                <button @click="removeItem(item.id)"
                                        class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-300
                                               hover:text-red-500 hover:bg-red-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Contrôles édition ──────────────────────────── -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 ml-12">
                                <!-- Employé -->
                                <div class="sm:col-span-1">
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Employé</label>
                                    <select
                                        v-model="item.user_id"
                                        class="w-full text-sm border border-slate-200 rounded-lg px-2.5 py-1.5
                                               focus:outline-none focus:ring-2 focus:ring-primary-300
                                               focus:border-primary-300 bg-white transition"
                                        :class="item.user_id ? 'text-slate-800' : 'text-slate-400'"
                                    >
                                        <option :value="null">— Aucun (non associé) —</option>
                                        <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                            {{ emp.name }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Mois -->
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">
                                        Mois <span class="text-red-400">*</span>
                                    </label>
                                    <select
                                        v-model="item.period_month"
                                        class="w-full text-sm border rounded-lg px-2.5 py-1.5
                                               focus:outline-none focus:ring-2 focus:ring-primary-300 transition"
                                        :class="item.period_month
                                            ? 'border-slate-200 focus:border-primary-300 text-slate-800 bg-white'
                                            : 'border-red-300 bg-red-50 text-red-700'"
                                    >
                                        <option :value="null" disabled>Mois…</option>
                                        <option v-for="m in MONTHS" :key="m.value" :value="m.value">
                                            {{ m.label }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Année -->
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">
                                        Année <span class="text-red-400">*</span>
                                    </label>
                                    <input
                                        v-model.number="item.period_year"
                                        type="number"
                                        min="2000"
                                        max="2099"
                                        placeholder="2025"
                                        class="w-full text-sm border rounded-lg px-2.5 py-1.5
                                               focus:outline-none focus:ring-2 focus:ring-primary-300 transition"
                                        :class="item.period_year
                                            ? 'border-slate-200 focus:border-primary-300 text-slate-800 bg-white'
                                            : 'border-red-300 bg-red-50'"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ── Panneau résumé + actions ──────────────────────────────────── -->
            <div class="space-y-4">

                <!-- Résumé -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <h3 class="font-semibold text-slate-800 mb-4">Récapitulatif</h3>

                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Fichiers sélectionnés</span>
                            <span class="font-semibold text-slate-800">{{ items.length }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Taille totale</span>
                            <span class="font-medium text-slate-700">{{ totalSize }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Employés reconnus</span>
                            <span class="font-medium"
                                  :class="recognizedCount === items.length && items.length > 0 ? 'text-emerald-600' : 'text-amber-600'">
                                {{ recognizedCount }}/{{ items.length }}
                            </span>
                        </div>

                        <div v-if="items.length > 0" class="pt-2">
                            <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full transition-all duration-500"
                                     :style="{ width: items.length ? (recognizedCount / items.length * 100) + '%' : '0%' }"/>
                            </div>
                            <p class="text-xs text-slate-400 mt-1 text-right">
                                {{ items.length - recognizedCount > 0
                                    ? `${items.length - recognizedCount} à vérifier`
                                    : 'Tous reconnus ✓' }}
                            </p>
                        </div>
                    </div>

                    <!-- Bouton submit -->
                    <button
                        @click="submit"
                        :disabled="!allValid || submitting"
                        class="mt-5 w-full py-3 rounded-xl text-sm font-semibold flex items-center justify-center
                               gap-2 transition-all"
                        :class="allValid && !submitting
                            ? 'bg-primary-600 hover:bg-primary-700 text-white shadow-sm hover:shadow-md active:scale-[0.98]'
                            : 'bg-slate-200 text-slate-400 cursor-not-allowed'"
                    >
                        <svg v-if="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                        <svg v-else-if="allValid" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ submitting ? 'Chiffrement et import…'
                          : allValid ? `Importer ${items.length} bulletin${items.length > 1 ? 's' : ''}`
                          : 'Complétez les champs requis' }}
                    </button>

                    <p v-if="!allValid && items.length > 0"
                       class="text-xs text-amber-600 text-center mt-2 flex items-center justify-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Mois et année requis pour chaque fichier
                    </p>
                </div>

                <!-- Conseils nommage ───────────────────────────────────────── -->
                <div class="bg-blue-50 border border-blue-200 rounded-2xl p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        <p class="text-xs font-semibold text-blue-800">Formats de nommage reconnus</p>
                    </div>
                    <div class="space-y-1.5 text-xs font-mono text-blue-700">
                        <p class="bg-white/60 rounded-lg px-2.5 py-1.5">dupont_jean_202501.pdf</p>
                        <p class="bg-white/60 rounded-lg px-2.5 py-1.5">BP_MARTIN_Marie_2025_01.pdf</p>
                        <p class="bg-white/60 rounded-lg px-2.5 py-1.5">bulletin_durand_janvier_2025.pdf</p>
                        <p class="bg-white/60 rounded-lg px-2.5 py-1.5">2025_01_Leblanc_Sophie.pdf</p>
                    </div>
                    <p class="text-xs text-blue-600 mt-2">
                        La reconnaissance est sensible aux séparateurs <code class="bg-white/60 px-1 rounded">_</code>
                        <code class="bg-white/60 px-1 rounded">-</code> et aux espaces.
                    </p>
                </div>

                <!-- Sécurité -->
                <div class="flex items-start gap-2.5 text-xs text-slate-500 px-1">
                    <svg class="w-4 h-4 text-emerald-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span>Les fichiers sont chiffrés <strong class="text-slate-600">AES-256</strong> avant stockage et ne sont jamais accessibles sans authentification.</span>
                </div>

            </div>
        </div>

    </AppLayout>
</template>
