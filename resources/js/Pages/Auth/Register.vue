<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Components/Layout/AuthLayout.vue'

const showPassword = ref(false)
const showPasswordConfirm = ref(false)

const form = useForm({
    company_name: '',
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
})

// Indicateur de force du mot de passe
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
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}

const inputClass = (field) => [
    'w-full px-4 py-3 rounded-xl border text-slate-900 placeholder-slate-400',
    'transition-all duration-150 outline-none text-sm',
    form.errors[field]
        ? 'border-danger-400 bg-danger-50 focus:border-danger-500 focus:ring-2 focus:ring-danger-200'
        : 'border-slate-200 bg-white focus:border-primary-500 focus:ring-2 focus:ring-primary-100',
]
</script>

<template>
    <Head title="Créer un compte" />

    <AuthLayout
        title="Démarrer gratuitement"
        subtitle="30 jours d'essai gratuit, sans carte bancaire. Annulable à tout moment."
    >
        <!-- Bouton Google -->
        <a :href="route('auth.google')"
           class="flex items-center justify-center gap-3 w-full py-3 px-4 border border-slate-200
                  bg-white hover:bg-slate-50 rounded-xl text-sm font-semibold text-slate-700
                  transition-all duration-150 active:scale-[0.98] shadow-sm mb-5">
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            S'inscrire avec Google
        </a>

        <!-- Séparateur -->
        <div class="flex items-center gap-3 mb-5">
            <div class="flex-1 h-px bg-slate-200" />
            <span class="text-xs text-slate-400 font-medium">ou avec un email</span>
            <div class="flex-1 h-px bg-slate-200" />
        </div>

        <form @submit.prevent="submit" class="space-y-4" novalidate>

            <!-- Nom de l'entreprise -->
            <div>
                <label for="company_name" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Nom de votre entreprise
                </label>
                <input
                    id="company_name"
                    v-model="form.company_name"
                    type="text"
                    autocomplete="organization"
                    placeholder="Acme SAS"
                    :class="inputClass('company_name')"
                />
                <p v-if="form.errors.company_name" class="mt-1.5 text-xs text-danger-600 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                    {{ form.errors.company_name }}
                </p>
            </div>

            <!-- Prénom + Nom -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Prénom
                    </label>
                    <input
                        id="first_name"
                        v-model="form.first_name"
                        type="text"
                        autocomplete="given-name"
                        placeholder="Marie"
                        :class="inputClass('first_name')"
                    />
                    <p v-if="form.errors.first_name" class="mt-1.5 text-xs text-danger-600">
                        {{ form.errors.first_name }}
                    </p>
                </div>
                <div>
                    <label for="last_name" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Nom
                    </label>
                    <input
                        id="last_name"
                        v-model="form.last_name"
                        type="text"
                        autocomplete="family-name"
                        placeholder="Dupont"
                        :class="inputClass('last_name')"
                    />
                    <p v-if="form.errors.last_name" class="mt-1.5 text-xs text-danger-600">
                        {{ form.errors.last_name }}
                    </p>
                </div>
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Email professionnel
                </label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    placeholder="marie.dupont@acme.fr"
                    :class="inputClass('email')"
                />
                <p v-if="form.errors.email" class="mt-1.5 text-xs text-danger-600 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                    {{ form.errors.email }}
                </p>
            </div>

            <!-- Mot de passe -->
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Mot de passe
                </label>
                <div class="relative">
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        autocomplete="new-password"
                        placeholder="8 caractères minimum"
                        :class="[...inputClass('password'), 'pr-11']"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
                        :aria-label="showPassword ? 'Masquer' : 'Afficher'"
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
                            v-for="i in 5"
                            :key="i"
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
                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                    {{ form.errors.password }}
                </p>
            </div>

            <!-- Confirmation -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Confirmer le mot de passe
                </label>
                <div class="relative">
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        :type="showPasswordConfirm ? 'text' : 'password'"
                        autocomplete="new-password"
                        placeholder="••••••••"
                        :class="[...inputClass('password_confirmation'), 'pr-11']"
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

            <!-- CGU -->
            <label class="flex items-start gap-3 cursor-pointer group">
                <input
                    v-model="form.terms"
                    type="checkbox"
                    :class="[
                        'mt-0.5 w-4 h-4 rounded border cursor-pointer transition-colors',
                        form.errors.terms ? 'border-danger-400' : 'border-slate-300',
                        'accent-primary-600'
                    ]"
                />
                <span class="text-sm text-slate-600 leading-snug">
                    J'accepte les
                    <a href="#" class="text-primary-600 hover:underline font-medium">conditions d'utilisation</a>
                    et la
                    <a href="#" class="text-primary-600 hover:underline font-medium">politique de confidentialité</a>
                </span>
            </label>
            <p v-if="form.errors.terms" class="text-xs text-danger-600 -mt-2">
                {{ form.errors.terms }}
            </p>

            <!-- Bouton -->
            <button
                type="submit"
                :disabled="form.processing"
                class="w-full py-3 px-6 bg-primary-700 hover:bg-primary-800 disabled:opacity-60 disabled:cursor-not-allowed
                       text-white font-semibold rounded-xl transition-all duration-150
                       active:scale-[0.98] shadow-sm shadow-primary-200 hover:shadow-md hover:shadow-primary-200
                       flex items-center justify-center gap-2 mt-2"
            >
                <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                <span>{{ form.processing ? 'Création en cours…' : 'Créer mon compte gratuitement' }}</span>
            </button>
        </form>

        <!-- Badges de réassurance -->
        <div class="mt-6 flex flex-wrap justify-center gap-4 text-xs text-slate-500">
            <span class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-success-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                </svg>
                Données sécurisées
            </span>
            <span class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-success-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
                Sans carte bancaire
            </span>
            <span class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-success-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
                Annulable à tout moment
            </span>
        </div>

        <!-- Lien connexion -->
        <p class="mt-6 text-center text-sm text-slate-500">
            Déjà un compte ?
            <Link :href="route('login')" class="font-semibold text-primary-600 hover:text-primary-700 transition-colors">
                Se connecter
            </Link>
        </p>
    </AuthLayout>
</template>
