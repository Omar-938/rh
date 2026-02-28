<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    leave_types: { type: Array, default: () => [] },
    balances:    { type: Object, default: () => ({}) },
})

const form = useForm({
    leave_type_id:    null,
    start_date:       '',
    end_date:         '',
    start_half:       null,
    end_half:         null,
    employee_comment: '',
    attachment:       null,
})

// Demi-journée (visible uniquement si jour unique)
const halfDay = ref(null) // null = journée entière | 'morning' | 'afternoon'

const attachmentInput = ref(null)
const attachmentName  = ref('')

function onAttachmentChange(event) {
    const file = event.target.files?.[0] ?? null
    form.attachment = file
    attachmentName.value = file ? file.name : ''
}

function removeAttachment() {
    form.attachment = null
    attachmentName.value = ''
    if (attachmentInput.value) attachmentInput.value.value = ''
}

const selectedType = computed(() =>
    props.leave_types.find(t => t.id === form.leave_type_id) ?? null
)

const balance = computed(() =>
    form.leave_type_id ? (props.balances[form.leave_type_id] ?? null) : null
)

// ── Calcul client-side des jours ouvrés ─────────────────────────────────────
function isWeekend(date) {
    const d = date.getDay()
    return d === 0 || d === 6
}

const workingDays = computed(() => {
    if (!form.start_date || !form.end_date) return 0

    const start = new Date(form.start_date + 'T00:00:00')
    const end   = new Date(form.end_date   + 'T00:00:00')

    if (end < start) return 0

    let days = 0
    const cur = new Date(start)
    while (cur <= end) {
        if (!isWeekend(cur)) days++
        cur.setDate(cur.getDate() + 1)
    }

    if (form.start_half === 'afternoon' && days > 0) days -= 0.5
    if (form.end_half   === 'morning'   && days > 0) days -= 0.5

    return Math.max(0, days)
})

// Synchronise end_date si elle devient < start_date
watch(() => form.start_date, (val) => {
    if (form.end_date && form.end_date < val) {
        form.end_date = val
    }
})

const isSingleDay = computed(() =>
    form.start_date && form.end_date && form.start_date === form.end_date
)

// Quand on passe en plage multi-jours, on remet en journées pleines
watch(isSingleDay, (val) => {
    if (!val) {
        halfDay.value    = null
        form.start_half  = null
        form.end_half    = null
    }
})

// Synchronise halfDay → champs form (pour compatibilité backend)
watch(halfDay, (val) => {
    if (val === 'morning') {
        form.start_half = null
        form.end_half   = 'morning'
    } else if (val === 'afternoon') {
        form.start_half = 'afternoon'
        form.end_half   = null
    } else {
        form.start_half = null
        form.end_half   = null
    }
})

const requiresAttachment = computed(() => selectedType.value?.requires_attachment ?? false)

const hasEnoughBalance = computed(() => {
    if (!balance.value || !selectedType.value?.needs_balance) return true
    return balance.value.effective_remaining >= workingDays.value
})

const balanceWarning = computed(() => {
    if (!balance.value || !selectedType.value?.needs_balance) return null
    if (workingDays.value <= 0) return null
    const rem = balance.value.effective_remaining
    if (rem < workingDays.value) {
        return `Solde insuffisant : ${rem} j disponible(s), ${workingDays.value} j demandé(s).`
    }
    return null
})

const today = new Date().toISOString().slice(0, 10)

function submit() {
    form.post(route('leaves.store'), { forceFormData: true })
}
</script>

<template>
    <Head title="Demande de congé" />

    <AppLayout title="Congés" :back-url="route('leaves.index')">

        <!-- ── Breadcrumb ── -->
        <div class="flex items-center gap-2 text-sm text-slate-500 mb-6">
            <Link :href="route('leaves.index')" class="hover:text-slate-700 transition-colors">
                Congés
            </Link>
            <span>/</span>
            <span class="text-slate-800 font-medium">Nouvelle demande</span>
        </div>

        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">

                <!-- ── En-tête ── -->
                <div class="px-6 py-5 border-b border-slate-100">
                    <h2 class="text-lg font-display font-bold text-slate-900">Demande de congé</h2>
                    <p class="text-sm text-slate-500 mt-0.5">
                        Renseignez les dates et le type de congé souhaité.
                    </p>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-6">

                    <!-- ── Type de congé ── -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-3">
                            Type de congé <span class="text-danger-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <button
                                v-for="lt in leave_types"
                                :key="lt.id"
                                type="button"
                                @click="form.leave_type_id = lt.id"
                                class="relative flex flex-col items-center gap-1.5 p-3 rounded-xl border-2
                                       text-center transition-all"
                                :class="form.leave_type_id === lt.id
                                    ? 'border-primary-500 bg-primary-50'
                                    : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'"
                            >
                                <span class="text-2xl">{{ lt.icon }}</span>
                                <span class="text-xs font-semibold text-slate-700 leading-tight">
                                    {{ lt.name }}
                                </span>
                                <!-- Solde disponible -->
                                <span
                                    v-if="lt.needs_balance && balances[lt.id]"
                                    class="text-[10px] font-medium"
                                    :class="balances[lt.id].effective_remaining > 0
                                        ? 'text-success-600'
                                        : 'text-danger-500'"
                                >
                                    {{ balances[lt.id].effective_remaining }} j
                                </span>
                                <!-- Coche sélection -->
                                <div
                                    v-if="form.leave_type_id === lt.id"
                                    class="absolute top-1.5 right-1.5 w-4 h-4 bg-primary-600
                                           rounded-full flex items-center justify-center"
                                >
                                    <svg class="w-2.5 h-2.5 text-white" fill="none"
                                         stroke="currentColor" stroke-width="3"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            </button>
                        </div>
                        <p v-if="form.errors.leave_type_id"
                           class="mt-1.5 text-xs text-danger-600 font-medium">
                            {{ form.errors.leave_type_id }}
                        </p>
                    </div>

                    <!-- ── Dates ── -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                Date de début <span class="text-danger-500">*</span>
                            </label>
                            <input
                                v-model="form.start_date"
                                type="date"
                                :min="today"
                                class="w-full px-3 py-2 border rounded-xl text-sm text-slate-800
                                       focus:outline-none focus:ring-2 focus:ring-primary-300
                                       transition-shadow"
                                :class="form.errors.start_date
                                    ? 'border-danger-300 bg-danger-50'
                                    : 'border-slate-200'"
                            />
                            <p v-if="form.errors.start_date"
                               class="mt-1 text-xs text-danger-600">
                                {{ form.errors.start_date }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                Date de fin <span class="text-danger-500">*</span>
                            </label>
                            <input
                                v-model="form.end_date"
                                type="date"
                                :min="form.start_date || today"
                                class="w-full px-3 py-2 border rounded-xl text-sm text-slate-800
                                       focus:outline-none focus:ring-2 focus:ring-primary-300
                                       transition-shadow"
                                :class="form.errors.end_date
                                    ? 'border-danger-300 bg-danger-50'
                                    : 'border-slate-200'"
                            />
                            <p v-if="form.errors.end_date"
                               class="mt-1 text-xs text-danger-600">
                                {{ form.errors.end_date }}
                            </p>
                        </div>
                    </div>

                    <!-- ── Demi-journée (uniquement si même jour) ── -->
                    <div v-if="isSingleDay">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Durée
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                type="button"
                                @click="halfDay = null"
                                class="px-3 py-2.5 rounded-xl text-sm font-semibold border-2 transition-all"
                                :class="halfDay === null
                                    ? 'border-primary-500 bg-primary-50 text-primary-700'
                                    : 'border-slate-200 text-slate-500 hover:border-slate-300 hover:bg-slate-50'"
                            >
                                Journée entière
                            </button>
                            <button
                                type="button"
                                @click="halfDay = 'morning'"
                                class="px-3 py-2.5 rounded-xl text-sm font-semibold border-2 transition-all"
                                :class="halfDay === 'morning'
                                    ? 'border-primary-500 bg-primary-50 text-primary-700'
                                    : 'border-slate-200 text-slate-500 hover:border-slate-300 hover:bg-slate-50'"
                            >
                                Matin
                            </button>
                            <button
                                type="button"
                                @click="halfDay = 'afternoon'"
                                class="px-3 py-2.5 rounded-xl text-sm font-semibold border-2 transition-all"
                                :class="halfDay === 'afternoon'
                                    ? 'border-primary-500 bg-primary-50 text-primary-700'
                                    : 'border-slate-200 text-slate-500 hover:border-slate-300 hover:bg-slate-50'"
                            >
                                Après-midi
                            </button>
                        </div>
                    </div>

                    <!-- ── Récap jours + solde ── -->
                    <div v-if="workingDays > 0 || form.start_date"
                         class="rounded-xl p-4 border"
                         :class="balanceWarning
                             ? 'bg-danger-50 border-danger-200'
                             : 'bg-slate-50 border-slate-200'"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold"
                                   :class="balanceWarning ? 'text-danger-700' : 'text-slate-700'">
                                    Durée calculée
                                </p>
                                <p class="text-xs mt-0.5"
                                   :class="balanceWarning ? 'text-danger-500' : 'text-slate-400'">
                                    <span v-if="isSingleDay && halfDay === 'morning'">Matin uniquement</span>
                                    <span v-else-if="isSingleDay && halfDay === 'afternoon'">Après-midi uniquement</span>
                                    <span v-else>Jours ouvrés (hors week-ends)</span>
                                </p>
                            </div>
                            <span class="text-2xl font-display font-bold"
                                  :class="balanceWarning ? 'text-danger-600' : 'text-primary-700'">
                                {{ workingDays }} j
                            </span>
                        </div>
                        <!-- Solde restant après -->
                        <div v-if="balance && selectedType?.needs_balance"
                             class="mt-3 pt-3 border-t flex items-center justify-between text-sm"
                             :class="balanceWarning ? 'border-danger-200' : 'border-slate-200'"
                        >
                            <span :class="balanceWarning ? 'text-danger-600' : 'text-slate-500'">
                                Solde disponible
                            </span>
                            <span class="font-semibold"
                                  :class="balanceWarning ? 'text-danger-700' : 'text-slate-700'">
                                {{ balance.effective_remaining }} j
                                <span v-if="workingDays > 0" class="text-xs font-normal">
                                    → {{ Math.max(0, balance.effective_remaining - workingDays) }} j restant(s)
                                </span>
                            </span>
                        </div>
                        <!-- Avertissement solde insuffisant -->
                        <p v-if="balanceWarning"
                           class="mt-2 text-xs font-semibold text-danger-600">
                            ⚠️ {{ balanceWarning }}
                        </p>
                    </div>

                    <!-- ── Commentaire ── -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Commentaire
                            <span class="font-normal text-slate-400">(facultatif)</span>
                        </label>
                        <textarea
                            v-model="form.employee_comment"
                            rows="3"
                            placeholder="Précisez si nécessaire…"
                            class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm
                                   text-slate-800 resize-none focus:outline-none
                                   focus:ring-2 focus:ring-primary-300 transition-shadow"
                        />
                        <p v-if="form.errors.employee_comment"
                           class="mt-1 text-xs text-danger-600">
                            {{ form.errors.employee_comment }}
                        </p>
                    </div>

                    <!-- ── Pièce jointe ── -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Justificatif
                            <span v-if="requiresAttachment" class="ml-1 text-xs font-semibold text-white bg-danger-500 px-1.5 py-0.5 rounded-md">
                                Obligatoire
                            </span>
                            <span v-else class="font-normal text-slate-400">(facultatif)</span>
                        </label>

                        <!-- Indicateur type arrêt maladie -->
                        <p v-if="requiresAttachment" class="text-xs text-danger-600 mb-2 font-medium">
                            Un arrêt de travail ou un justificatif médical est requis pour ce type de congé.
                        </p>

                        <!-- Zone de dépôt -->
                        <div v-if="!attachmentName"
                             class="relative border-2 border-dashed rounded-xl p-5 text-center transition-all cursor-pointer"
                             :class="form.errors.attachment
                                 ? 'border-danger-300 bg-danger-50'
                                 : requiresAttachment
                                     ? 'border-primary-300 bg-primary-50/50 hover:border-primary-400'
                                     : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'"
                             @click="attachmentInput?.click()"
                        >
                            <input
                                ref="attachmentInput"
                                type="file"
                                class="sr-only"
                                accept=".pdf,.jpg,.jpeg,.png"
                                @change="onAttachmentChange"
                            />
                            <svg class="w-8 h-8 mx-auto mb-2"
                                 :class="requiresAttachment ? 'text-primary-400' : 'text-slate-300'"
                                 fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                            </svg>
                            <p class="text-sm font-medium"
                               :class="requiresAttachment ? 'text-primary-700' : 'text-slate-600'">
                                Cliquez pour ajouter un fichier
                            </p>
                            <p class="text-xs text-slate-400 mt-0.5">PDF, JPG ou PNG — max 10 Mo</p>
                        </div>

                        <!-- Fichier sélectionné -->
                        <div v-else
                             class="flex items-center gap-3 p-3 bg-success-50 border border-success-200 rounded-xl">
                            <div class="w-9 h-9 rounded-lg bg-success-100 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-success-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-success-800 truncate">{{ attachmentName }}</p>
                                <p class="text-xs text-success-600">Prêt à être envoyé</p>
                            </div>
                            <button
                                type="button"
                                @click="removeAttachment"
                                class="p-1.5 rounded-lg hover:bg-success-100 text-success-600 transition-colors shrink-0"
                                title="Supprimer"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <p v-if="form.errors.attachment"
                           class="mt-1.5 text-xs text-danger-600 font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                            {{ form.errors.attachment }}
                        </p>
                    </div>

                    <!-- ── Actions ── -->
                    <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                        <Link
                            :href="route('leaves.index')"
                            class="px-4 py-2 text-sm font-medium text-slate-600 border
                                   border-slate-200 rounded-xl hover:bg-slate-50 transition-colors"
                        >
                            Annuler
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing || workingDays <= 0 || !form.leave_type_id || !!balanceWarning || (requiresAttachment && !form.attachment)"
                            class="px-5 py-2 bg-primary-700 text-white text-sm font-semibold
                                   rounded-xl hover:bg-primary-800 transition-colors shadow-sm
                                   disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="form.processing">Envoi…</span>
                            <span v-else>Soumettre la demande</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>

    </AppLayout>
</template>
