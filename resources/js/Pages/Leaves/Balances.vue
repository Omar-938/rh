<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    leave_types:  { type: Array,   default: () => [] },
    employees:    { type: Array,   default: () => [] },
    balances:     { type: Object,  default: () => ({}) },
    is_reviewer:  { type: Boolean, default: false },
    can_adjust:   { type: Boolean, default: false },
    year:         { type: Number,  required: true },
})

// ── État ─────────────────────────────────────────────────────────────────────
const selectedEmployee = ref(props.employees[0]?.id ?? null)

// Modal ajustement (admin)
const showAdjustModal   = ref(false)
const adjustTarget      = ref(null)   // { employee, leaveType, balance }

const adjustForm = useForm({
    user_id:       null,
    leave_type_id: null,
    year:          props.year,
    delta:         '',
    reason:        '',
})

// ── Helpers solde ─────────────────────────────────────────────────────────────
function getBalance(employeeId, leaveTypeId) {
    if (props.is_reviewer) {
        return props.balances?.[employeeId]?.[leaveTypeId] ?? null
    }
    // Vue employé : le solde est embarqué dans le leave_type
    return props.leave_types.find(t => t.id === leaveTypeId)?.balance ?? null
}

function getBalanceForCard(lt) {
    if (props.is_reviewer) {
        return props.balances?.[selectedEmployee.value]?.[lt.id] ?? null
    }
    return lt.balance ?? null
}

function usedPct(balance) {
    if (!balance || balance.allocated === 0) return 0
    return Math.min(100, Math.round((balance.used / balance.allocated) * 100))
}

function pendingPct(balance) {
    if (!balance || balance.allocated === 0) return 0
    return Math.min(
        100 - usedPct(balance),
        Math.round((balance.pending / balance.allocated) * 100)
    )
}

// ── Types à afficher pour l'employé courant ───────────────────────────────────
const currentEmployeeId = computed(() =>
    props.is_reviewer ? selectedEmployee.value : null
)

const displayedLeaveTypes = computed(() =>
    props.is_reviewer
        ? props.leave_types
        : props.leave_types.filter(lt => lt.balance !== null || lt.days_per_year > 0)
)

// ── Modal ajustement ─────────────────────────────────────────────────────────
function openAdjustModal(employee, leaveType) {
    const balance = getBalance(employee.id, leaveType.id)
    adjustTarget.value  = { employee, leaveType, balance }
    adjustForm.user_id       = employee.id
    adjustForm.leave_type_id = leaveType.id
    adjustForm.year          = props.year
    adjustForm.delta         = ''
    adjustForm.reason        = ''
    adjustForm.clearErrors()
    showAdjustModal.value = true
}

function submitAdjust() {
    adjustForm.post(route('settings.balances.adjust'), {
        onSuccess: () => {
            showAdjustModal.value = false
        },
    })
}

// ── Résumé total (vue employé) ────────────────────────────────────────────────
const totalRemaining = computed(() => {
    if (props.is_reviewer) return null
    return props.leave_types.reduce((sum, lt) => {
        const b = lt.balance
        return b ? sum + b.effective_remaining : sum
    }, 0)
})
</script>

<template>
    <Head title="Soldes de congés" />

    <AppLayout title="Congés">

        <!-- ── Header ── -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-6">
            <div class="flex-1">
                <h2 class="text-xl font-display font-bold text-slate-900">
                    Soldes de congés
                </h2>
                <p class="text-sm text-slate-500 mt-0.5">
                    Exercice {{ year }}
                    <span v-if="!is_reviewer && totalRemaining !== null"
                          class="ml-2 font-semibold text-primary-700">
                        — {{ totalRemaining }} j disponibles au total
                    </span>
                </p>
            </div>
            <Link
                :href="route('leaves.index')"
                class="text-sm text-slate-500 hover:text-slate-700 transition-colors"
            >
                ← Retour aux demandes
            </Link>
        </div>

        <!-- ── Sélecteur employé (reviewer) ── -->
        <div v-if="is_reviewer && employees.length > 0" class="mb-6">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                Employé
            </p>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="emp in employees"
                    :key="emp.id"
                    @click="selectedEmployee = emp.id"
                    class="flex items-center gap-2 px-3 py-1.5 rounded-xl border text-sm
                           font-medium transition-all"
                    :class="selectedEmployee === emp.id
                        ? 'bg-primary-700 border-primary-700 text-white shadow-sm'
                        : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300'"
                >
                    <span
                        class="w-5 h-5 rounded-full flex items-center justify-center
                               text-[10px] font-bold shrink-0"
                        :class="selectedEmployee === emp.id
                            ? 'bg-white/20 text-white'
                            : 'bg-slate-100 text-slate-600'"
                    >
                        {{ emp.initials }}
                    </span>
                    {{ emp.name }}
                </button>
            </div>
        </div>

        <!-- ── Grille des soldes ── -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="lt in displayedLeaveTypes"
                :key="lt.id"
                class="bg-white rounded-2xl border border-slate-100 p-5 flex flex-col gap-4
                       transition-shadow hover:shadow-md"
            >
                <!-- En-tête type -->
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shrink-0"
                            :style="{ backgroundColor: lt.color + '20' }"
                        >
                            {{ lt.icon ?? '🌴' }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800 leading-tight">
                                {{ lt.name }}
                            </p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{ lt.days_per_year }} j / an
                            </p>
                        </div>
                    </div>

                    <!-- Bouton ajustement admin -->
                    <button
                        v-if="is_reviewer && selectedEmployee && can_adjust"
                        @click="openAdjustModal(
                            employees.find(e => e.id === selectedEmployee),
                            lt
                        )"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-primary-600
                               hover:bg-primary-50 transition-colors shrink-0"
                        title="Ajuster le solde"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                             stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 4v16m8-8H4" />
                        </svg>
                    </button>
                </div>

                <template v-if="getBalanceForCard(lt)">
                    <!-- Barre de progression -->
                    <div>
                        <div class="relative h-3 bg-slate-100 rounded-full overflow-hidden">
                            <!-- Utilisés -->
                            <div
                                class="absolute left-0 top-0 h-full rounded-full transition-all
                                       duration-700 ease-out"
                                :style="{
                                    width: usedPct(getBalanceForCard(lt)) + '%',
                                    backgroundColor: lt.color || '#2E86C1',
                                }"
                            />
                            <!-- En attente -->
                            <div
                                class="absolute top-0 h-full rounded-full bg-amber-300
                                       transition-all duration-700 ease-out"
                                :style="{
                                    left: usedPct(getBalanceForCard(lt)) + '%',
                                    width: pendingPct(getBalanceForCard(lt)) + '%',
                                }"
                            />
                        </div>
                        <div class="flex justify-between text-[10px] text-slate-400 mt-1.5">
                            <span>0</span>
                            <span>{{ getBalanceForCard(lt).allocated }} j alloués</span>
                        </div>
                    </div>

                    <!-- Chiffres clés -->
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-xl p-2.5"
                             :style="{ backgroundColor: lt.color + '15' }">
                            <p class="text-lg font-display font-bold"
                               :style="{ color: lt.color }">
                                {{ getBalanceForCard(lt).used }}
                            </p>
                            <p class="text-[10px] text-slate-400 mt-0.5">Pris</p>
                        </div>
                        <div class="bg-amber-50 rounded-xl p-2.5">
                            <p class="text-lg font-display font-bold text-amber-600">
                                {{ getBalanceForCard(lt).pending }}
                            </p>
                            <p class="text-[10px] text-slate-400 mt-0.5">En att.</p>
                        </div>
                        <div class="rounded-xl p-2.5"
                             :class="getBalanceForCard(lt).effective_remaining > 0
                                 ? 'bg-success-50'
                                 : 'bg-danger-50'"
                        >
                            <p class="text-lg font-display font-bold"
                               :class="getBalanceForCard(lt).effective_remaining > 0
                                   ? 'text-success-700'
                                   : 'text-danger-600'"
                            >
                                {{ getBalanceForCard(lt).effective_remaining }}
                            </p>
                            <p class="text-[10px] text-slate-400 mt-0.5">Dispo.</p>
                        </div>
                    </div>

                    <!-- Détail alloués -->
                    <div class="pt-3 border-t border-slate-100 space-y-1.5 text-xs">
                        <div class="flex justify-between text-slate-500">
                            <span>Acquisition</span>
                            <span class="font-medium">
                                {{ getBalanceForCard(lt).allocated }} j
                            </span>
                        </div>
                        <div
                            v-if="getBalanceForCard(lt).carried_over > 0"
                            class="flex justify-between text-slate-500"
                        >
                            <span>Report N-1</span>
                            <span class="font-medium text-sky-600">
                                +{{ getBalanceForCard(lt).carried_over }} j
                            </span>
                        </div>
                        <div
                            v-if="getBalanceForCard(lt).adjustment !== 0"
                            class="flex justify-between text-slate-500"
                        >
                            <span>Ajustement</span>
                            <span
                                class="font-semibold"
                                :class="getBalanceForCard(lt).adjustment > 0
                                    ? 'text-success-600'
                                    : 'text-danger-500'"
                            >
                                {{ getBalanceForCard(lt).adjustment > 0 ? '+' : '' }}
                                {{ getBalanceForCard(lt).adjustment }} j
                            </span>
                        </div>
                    </div>
                </template>

                <!-- Pas encore de solde -->
                <div v-else class="flex flex-col items-center py-4 text-slate-300">
                    <p class="text-sm text-slate-400">Aucun solde {{ year }}</p>
                    <button
                        v-if="is_reviewer && selectedEmployee && can_adjust"
                        @click="openAdjustModal(
                            employees.find(e => e.id === selectedEmployee),
                            lt
                        )"
                        class="mt-2 text-xs text-primary-600 hover:underline"
                    >
                        Initialiser le solde
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Légende ── -->
        <div class="flex flex-wrap gap-4 mt-5 text-xs text-slate-400">
            <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-sm bg-primary-500" />
                Jours utilisés
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-sm bg-amber-300" />
                En attente de validation
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-sm bg-slate-100 border border-slate-200" />
                Disponibles
            </div>
        </div>

        <!-- ── Note admin : lancement manuel des acquisitions ── -->
        <div v-if="is_reviewer"
             class="mt-6 bg-slate-50 border border-slate-200 rounded-2xl p-4">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-primary-100 text-primary-700
                            flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                         stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-700">Acquisition automatique</p>
                    <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                        Les jours de congés payés (acquisition mensuelle) sont crédités automatiquement
                        le <strong>1er de chaque mois</strong> via la commande
                        <code class="bg-slate-200 px-1 rounded text-slate-700">leaves:accrue-monthly</code>.
                        L'allocation annuelle (RTT, etc.) est créditée le
                        <strong>1er janvier</strong> via
                        <code class="bg-slate-200 px-1 rounded text-slate-700">leaves:process-year-start</code>.
                    </p>
                    <p class="text-xs text-slate-400 mt-1.5">
                        Pour un ajustement ponctuel, utilisez le bouton
                        <span class="font-semibold">+</span> sur chaque carte.
                    </p>
                </div>
            </div>
        </div>

        <!-- ── Modal Ajustement ── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showAdjustModal"
                     class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50
                            flex items-center justify-center p-4"
                     @click.self="showAdjustModal = false">
                    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6" @click.stop>

                        <h3 class="text-lg font-display font-bold text-slate-900 mb-1">
                            Ajuster le solde
                        </h3>
                        <p v-if="adjustTarget" class="text-sm text-slate-500 mb-5">
                            <span class="font-medium text-slate-700">
                                {{ adjustTarget.employee?.name }}
                            </span>
                            · {{ adjustTarget.leaveType?.name }}
                            · {{ year }}
                        </p>

                        <!-- Solde actuel -->
                        <div v-if="adjustTarget?.balance"
                             class="grid grid-cols-3 gap-2 text-center mb-5">
                            <div class="bg-slate-50 rounded-xl p-2.5">
                                <p class="text-sm font-bold text-slate-700">
                                    {{ adjustTarget.balance.allocated }}
                                </p>
                                <p class="text-[10px] text-slate-400">Alloués</p>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-2.5">
                                <p class="text-sm font-bold text-slate-700">
                                    {{ adjustTarget.balance.used }}
                                </p>
                                <p class="text-[10px] text-slate-400">Utilisés</p>
                            </div>
                            <div class="bg-success-50 rounded-xl p-2.5">
                                <p class="text-sm font-bold text-success-700">
                                    {{ adjustTarget.balance.effective_remaining }}
                                </p>
                                <p class="text-[10px] text-slate-400">Disponibles</p>
                            </div>
                        </div>

                        <!-- Delta -->
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                Ajustement <span class="text-danger-500">*</span>
                            </label>
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    @click="adjustForm.delta = adjustForm.delta
                                        ? (parseFloat(adjustForm.delta) - 1).toString()
                                        : '-1'"
                                    class="w-10 h-10 flex items-center justify-center border
                                           border-slate-200 rounded-xl text-slate-600
                                           hover:bg-slate-50 transition-colors font-bold"
                                >
                                    −
                                </button>
                                <input
                                    v-model="adjustForm.delta"
                                    type="number"
                                    step="0.5"
                                    placeholder="ex: +2 ou -1"
                                    class="flex-1 px-3 py-2.5 border border-slate-200 rounded-xl
                                           text-center text-lg font-bold text-slate-800
                                           focus:outline-none focus:ring-2 focus:ring-primary-300
                                           transition-shadow"
                                    :class="adjustForm.errors.delta ? 'border-danger-300 bg-danger-50' : ''"
                                />
                                <button
                                    type="button"
                                    @click="adjustForm.delta = adjustForm.delta
                                        ? (parseFloat(adjustForm.delta) + 1).toString()
                                        : '1'"
                                    class="w-10 h-10 flex items-center justify-center border
                                           border-slate-200 rounded-xl text-slate-600
                                           hover:bg-slate-50 transition-colors font-bold"
                                >
                                    +
                                </button>
                            </div>
                            <p v-if="adjustForm.errors.delta"
                               class="mt-1 text-xs text-danger-600">
                                {{ adjustForm.errors.delta }}
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
                                Positif pour ajouter des jours, négatif pour en retirer.
                                Valeurs décimales acceptées (ex : 0.5).
                            </p>
                        </div>

                        <!-- Motif -->
                        <div class="mb-5">
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                Motif
                                <span class="font-normal text-slate-400">(facultatif)</span>
                            </label>
                            <input
                                v-model="adjustForm.reason"
                                type="text"
                                placeholder="Ex: Régularisation CP, Récupération..."
                                class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm
                                       text-slate-800 focus:outline-none focus:ring-2
                                       focus:ring-primary-300 transition-shadow"
                            />
                        </div>

                        <!-- Boutons -->
                        <div class="flex gap-3">
                            <button
                                @click="showAdjustModal = false"
                                class="flex-1 px-4 py-2 text-sm font-medium text-slate-600
                                       border border-slate-200 rounded-xl hover:bg-slate-50
                                       transition-colors"
                            >
                                Annuler
                            </button>
                            <button
                                @click="submitAdjust"
                                :disabled="adjustForm.processing || !adjustForm.delta"
                                class="flex-1 px-4 py-2 bg-primary-700 text-white text-sm
                                       font-semibold rounded-xl hover:bg-primary-800
                                       transition-colors disabled:opacity-50"
                            >
                                <span v-if="adjustForm.processing">Enregistrement…</span>
                                <span v-else>Appliquer</span>
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </AppLayout>
</template>
