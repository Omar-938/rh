<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Components/Layout/AuthLayout.vue'

const props = defineProps({
    canResetPassword: { type: Boolean, default: false },
    status: { type: String, default: null },
})

const showPassword = ref(false)

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

function submit() {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <Head title="Connexion" />

    <AuthLayout
        title="Bon retour 👋"
        subtitle="Connectez-vous à votre espace SimpliRH."
    >
        <!-- Message de statut (reset password, etc.) -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
        >
            <div
                v-if="status"
                class="mb-6 flex items-start gap-3 p-4 bg-success-50 border border-success-200 rounded-xl"
            >
                <svg class="w-5 h-5 text-success-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
                <p class="text-success-700 text-sm">{{ status }}</p>
            </div>
        </Transition>

        <form @submit.prevent="submit" class="space-y-5" novalidate>
            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Adresse email
                </label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    placeholder="vous@entreprise.fr"
                    :class="[
                        'w-full px-4 py-3 rounded-xl border text-slate-900 placeholder-slate-400',
                        'transition-all duration-150 outline-none text-sm',
                        form.errors.email
                            ? 'border-danger-400 bg-danger-50 focus:border-danger-500 focus:ring-2 focus:ring-danger-200'
                            : 'border-slate-200 bg-white focus:border-primary-500 focus:ring-2 focus:ring-primary-100',
                    ]"
                />
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                >
                    <p v-if="form.errors.email" class="mt-1.5 text-xs text-danger-600 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                        {{ form.errors.email }}
                    </p>
                </Transition>
            </div>

            <!-- Mot de passe -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-sm font-medium text-slate-700">
                        Mot de passe
                    </label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs text-primary-600 hover:text-primary-700 font-medium transition-colors"
                    >
                        Mot de passe oublié ?
                    </Link>
                </div>
                <div class="relative">
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        autocomplete="current-password"
                        placeholder="••••••••"
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
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors p-0.5"
                        :aria-label="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
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
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                >
                    <p v-if="form.errors.password" class="mt-1.5 text-xs text-danger-600 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                        {{ form.errors.password }}
                    </p>
                </Transition>
            </div>

            <!-- Se souvenir de moi -->
            <label class="flex items-center gap-3 cursor-pointer group">
                <div class="relative">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="sr-only peer"
                    />
                    <div :class="[
                        'w-10 h-5 rounded-full transition-colors duration-200',
                        form.remember ? 'bg-primary-600' : 'bg-slate-200',
                        'peer-focus-visible:ring-2 peer-focus-visible:ring-primary-300 peer-focus-visible:ring-offset-1'
                    ]" />
                    <div :class="[
                        'absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200',
                        form.remember ? 'translate-x-5' : 'translate-x-0.5'
                    ]" />
                </div>
                <span class="text-sm text-slate-600 group-hover:text-slate-900 transition-colors select-none">
                    Rester connecté
                </span>
            </label>

            <!-- Bouton de connexion -->
            <button
                type="submit"
                :disabled="form.processing"
                class="w-full py-3 px-6 bg-primary-700 hover:bg-primary-800 disabled:opacity-60 disabled:cursor-not-allowed
                       text-white font-semibold rounded-xl transition-all duration-150
                       active:scale-[0.98] shadow-sm shadow-primary-200 hover:shadow-md hover:shadow-primary-200
                       flex items-center justify-center gap-2"
            >
                <svg
                    v-if="form.processing"
                    class="w-4 h-4 animate-spin"
                    fill="none" viewBox="0 0 24 24"
                >
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                <span>{{ form.processing ? 'Connexion…' : 'Se connecter' }}</span>
            </button>
        </form>

        <!-- Lien inscription -->
        <p class="mt-8 text-center text-sm text-slate-500">
            Pas encore de compte ?
            <Link
                :href="route('register')"
                class="font-semibold text-primary-600 hover:text-primary-700 transition-colors"
            >
                Créer un compte gratuit
            </Link>
        </p>
    </AuthLayout>
</template>
