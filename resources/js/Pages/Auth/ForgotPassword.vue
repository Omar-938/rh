<script setup>
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Components/Layout/AuthLayout.vue'

const props = defineProps({
    status: { type: String, default: null },
})

const form = useForm({ email: '' })

const emailSent = computed(() => !!props.status)

function submit() {
    form.post(route('password.email'))
}
</script>

<template>
    <Head title="Mot de passe oublié" />

    <AuthLayout
        :title="emailSent ? 'Vérifiez vos emails' : 'Mot de passe oublié ?'"
        :subtitle="emailSent
            ? ''
            : 'Saisissez votre adresse email et nous vous enverrons un lien pour réinitialiser votre mot de passe.'"
    >
        <!-- État : email envoyé -->
        <Transition
            enter-active-class="transition duration-400 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
        >
            <div v-if="emailSent" class="text-center">
                <!-- Icône animée -->
                <div class="w-20 h-20 mx-auto mb-6 bg-success-50 rounded-full flex items-center justify-center">
                    <svg class="w-10 h-10 text-success-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                    </svg>
                </div>
                <p class="text-slate-700 text-sm leading-relaxed mb-2">
                    Un email a été envoyé à <strong>{{ form.email || 'votre adresse' }}</strong>.
                </p>
                <p class="text-slate-500 text-sm leading-relaxed mb-8">
                    Vérifiez votre boîte de réception (et vos spams). Le lien est valable 60 minutes.
                </p>

                <!-- Renvoyer -->
                <form @submit.prevent="submit">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3 px-6 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold
                               rounded-xl transition-all duration-150 active:scale-[0.98] text-sm
                               flex items-center justify-center gap-2"
                    >
                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <span>{{ form.processing ? 'Envoi en cours…' : 'Renvoyer l\'email' }}</span>
                    </button>
                </form>
            </div>
        </Transition>

        <!-- État : formulaire -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
        >
            <form v-if="!emailSent" @submit.prevent="submit" class="space-y-5" novalidate>
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
                        autofocus
                        :class="[
                            'w-full px-4 py-3 rounded-xl border text-slate-900 placeholder-slate-400',
                            'transition-all duration-150 outline-none text-sm',
                            form.errors.email
                                ? 'border-danger-400 bg-danger-50 focus:border-danger-500 focus:ring-2 focus:ring-danger-200'
                                : 'border-slate-200 bg-white focus:border-primary-500 focus:ring-2 focus:ring-primary-100',
                        ]"
                    />
                    <p v-if="form.errors.email" class="mt-1.5 text-xs text-danger-600 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                        {{ form.errors.email }}
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
                    <span>{{ form.processing ? 'Envoi…' : 'Envoyer le lien de réinitialisation' }}</span>
                </button>
            </form>
        </Transition>

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
