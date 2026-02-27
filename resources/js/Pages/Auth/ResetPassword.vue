<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Components/Layout/AuthLayout.vue'

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
})

const showPassword = ref(false)
const showPasswordConfirm = ref(false)

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

const passwordStrength = computed(() => {
    const p = form.password
    if (!p) return { score: 0, label: '', color: '' }
    let score = 0
    if (p.length >= 8) score++
    if (p.length >= 12) score++
    if (/[A-Z]/.test(p)) score++
    if (/[0-9]/.test(p)) score++
    if (/[^A-Za-z0-9]/.test(p)) score++
    const levels = [
        { label: '',           color: '' },
        { label: 'Faible',     color: 'bg-danger-400' },
        { label: 'Moyen',      color: 'bg-warning-400' },
        { label: 'Bon',        color: 'bg-warning-300' },
        { label: 'Fort',       color: 'bg-success-400' },
        { label: 'Très fort',  color: 'bg-success-500' },
    ]
    return { score, ...levels[score] }
})

function submit() {
    form.post(route('password.update'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
    <Head title="Nouveau mot de passe" />

    <AuthLayout
        title="Nouveau mot de passe"
        subtitle="Choisissez un mot de passe sécurisé pour votre compte."
    >
        <form @submit.prevent="submit" class="space-y-5" novalidate>
            <!-- Email (pré-rempli, readonly) -->
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Adresse email
                </label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    readonly
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-500
                           text-sm outline-none cursor-not-allowed"
                />
            </div>

            <!-- Nouveau mot de passe -->
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Nouveau mot de passe
                </label>
                <div class="relative">
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        autocomplete="new-password"
                        placeholder="8 caractères minimum"
                        autofocus
                        :class="[
                            'w-full px-4 py-3 pr-11 rounded-xl border text-slate-900 placeholder-slate-400',
                            'transition-all duration-150 outline-none text-sm',
                            form.errors.password
                                ? 'border-danger-400 bg-danger-50 focus:border-danger-500 focus:ring-2 focus:ring-danger-200'
                                : 'border-slate-200 bg-white focus:border-primary-500 focus:ring-2 focus:ring-primary-100',
                        ]"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
                    >
                        <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>

                <!-- Barre de force -->
                <div v-if="form.password" class="mt-2 space-y-1.5">
                    <div class="flex gap-1">
                        <div
                            v-for="i in 5" :key="i"
                            :class="[
                                'h-1 flex-1 rounded-full transition-all duration-300',
                                i <= passwordStrength.score ? passwordStrength.color : 'bg-slate-200'
                            ]"
                        />
                    </div>
                    <p class="text-xs text-slate-500">
                        Force : <span :class="[
                            'font-medium',
                            passwordStrength.score <= 1 ? 'text-danger-600' :
                            passwordStrength.score <= 3 ? 'text-warning-600' : 'text-success-600'
                        ]">{{ passwordStrength.label }}</span>
                    </p>
                </div>

                <p v-if="form.errors.password" class="mt-1.5 text-xs text-danger-600 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                    {{ form.errors.password }}
                </p>
            </div>

            <!-- Confirmation -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Confirmer le nouveau mot de passe
                </label>
                <div class="relative">
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        :type="showPasswordConfirm ? 'text' : 'password'"
                        autocomplete="new-password"
                        placeholder="••••••••"
                        :class="[
                            'w-full px-4 py-3 pr-11 rounded-xl border text-slate-900 placeholder-slate-400',
                            'transition-all duration-150 outline-none text-sm',
                            form.errors.password_confirmation
                                ? 'border-danger-400 bg-danger-50 focus:border-danger-500 focus:ring-2 focus:ring-danger-200'
                                : 'border-slate-200 bg-white focus:border-primary-500 focus:ring-2 focus:ring-primary-100',
                        ]"
                    />
                    <button
                        type="button"
                        @click="showPasswordConfirm = !showPasswordConfirm"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
                    >
                        <svg v-if="!showPasswordConfirm" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
                <p v-if="form.errors.password_confirmation" class="mt-1.5 text-xs text-danger-600">
                    {{ form.errors.password_confirmation }}
                </p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full py-3 px-6 bg-primary-700 hover:bg-primary-800 disabled:opacity-60 disabled:cursor-not-allowed
                       text-white font-semibold rounded-xl transition-all duration-150
                       active:scale-[0.98] shadow-sm shadow-primary-200 hover:shadow-md hover:shadow-primary-200
                       flex items-center justify-center gap-2"
            >
                <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                <span>{{ form.processing ? 'Enregistrement…' : 'Enregistrer le nouveau mot de passe' }}</span>
            </button>
        </form>

        <div class="mt-8 text-center">
            <Link
                :href="route('login')"
                class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Retour à la connexion
            </Link>
        </div>
    </AuthLayout>
</template>
