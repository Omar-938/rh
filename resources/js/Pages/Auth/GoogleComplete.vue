<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Components/Layout/AuthLayout.vue'

const props = defineProps({
    google_user: { type: Object, required: true },
})

const form = useForm({
    company_name: '',
    terms: false,
})

function submit() {
    form.post(route('auth.google.complete.post'))
}
</script>

<template>
    <Head title="Dernière étape" />

    <AuthLayout
        title="Presque terminé 🎉"
        subtitle="Il ne reste plus qu'à nommer votre entreprise."
    >
        <!-- Avatar Google -->
        <div class="flex items-center gap-3 p-4 bg-slate-50 rounded-xl border border-slate-100 mb-6">
            <img v-if="google_user.avatar"
                 :src="google_user.avatar"
                 :alt="google_user.first_name"
                 class="w-10 h-10 rounded-full object-cover ring-2 ring-white shadow-sm" />
            <div v-else class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-bold text-sm">
                {{ google_user.first_name?.[0] }}{{ google_user.last_name?.[0] }}
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-800">
                    {{ google_user.first_name }} {{ google_user.last_name }}
                </p>
                <p class="text-xs text-slate-500">{{ google_user.email }}</p>
            </div>
            <!-- Badge Google -->
            <div class="ml-auto flex items-center gap-1.5 text-xs font-medium text-slate-500 bg-white border border-slate-200 rounded-full px-2.5 py-1">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Connecté via Google
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-5" novalidate>
            <!-- Nom de l'entreprise -->
            <div>
                <label for="company_name" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Nom de votre entreprise <span class="text-danger-500">*</span>
                </label>
                <input
                    id="company_name"
                    v-model="form.company_name"
                    type="text"
                    autofocus
                    placeholder="Ma Société SAS, Dupont & Fils…"
                    :class="[
                        'w-full px-4 py-3 rounded-xl border text-slate-900 placeholder-slate-400',
                        'transition-all duration-150 outline-none text-sm',
                        form.errors.company_name
                            ? 'border-danger-400 bg-danger-50 focus:border-danger-500 focus:ring-2 focus:ring-danger-200'
                            : 'border-slate-200 bg-white focus:border-primary-500 focus:ring-2 focus:ring-primary-100',
                    ]"
                />
                <p v-if="form.errors.company_name" class="mt-1.5 text-xs text-danger-600 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                    {{ form.errors.company_name }}
                </p>
            </div>

            <!-- CGU -->
            <label class="flex items-start gap-3 cursor-pointer group">
                <div class="relative mt-0.5 shrink-0">
                    <input v-model="form.terms" type="checkbox" class="sr-only peer" />
                    <div :class="[
                        'w-5 h-5 rounded border-2 transition-colors duration-150 flex items-center justify-center',
                        form.terms ? 'bg-primary-600 border-primary-600' : 'bg-white border-slate-300',
                        form.errors.terms ? 'border-danger-400' : '',
                    ]">
                        <svg v-if="form.terms" class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </div>
                </div>
                <span class="text-sm text-slate-600 leading-relaxed">
                    J'accepte les
                    <a href="#" class="text-primary-600 hover:text-primary-700 font-medium underline-offset-2 hover:underline">conditions d'utilisation</a>
                    et la
                    <a href="#" class="text-primary-600 hover:text-primary-700 font-medium underline-offset-2 hover:underline">politique de confidentialité</a>
                    de SimpliRH.
                </span>
            </label>
            <p v-if="form.errors.terms" class="text-xs text-danger-600">{{ form.errors.terms }}</p>

            <!-- Bouton -->
            <button
                type="submit"
                :disabled="form.processing || !form.company_name.trim() || !form.terms"
                class="w-full py-3 px-6 bg-primary-700 hover:bg-primary-800 disabled:opacity-60 disabled:cursor-not-allowed
                       text-white font-semibold rounded-xl transition-all duration-150
                       active:scale-[0.98] shadow-sm shadow-primary-200 hover:shadow-md
                       flex items-center justify-center gap-2"
            >
                <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                {{ form.processing ? 'Création de votre espace…' : 'Créer mon espace SimpliRH' }}
            </button>
        </form>
    </AuthLayout>
</template>
