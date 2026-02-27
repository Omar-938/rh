<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePWA } from '@/Composables/usePWA'

const { canInstall, isStandalone, install, dismiss } = usePWA()

// iOS détection (pas de beforeinstallprompt sur Safari)
const isIos = ref(false)
const isIosDismissed = ref(false)

const showIosBanner = computed(() =>
    isIos.value && !isStandalone.value && !isIosDismissed.value
)

const showAndroidBanner = computed(() => canInstall.value)

function dismissIos() {
    isIosDismissed.value = true
    const expires = Date.now() + 7 * 24 * 60 * 60 * 1000
    localStorage.setItem('pwa_ios_dismissed', String(expires))
}

async function handleInstall() {
    await install()
}

onMounted(() => {
    const ua = navigator.userAgent
    isIos.value = /iphone|ipad|ipod/i.test(ua) && !window.MSStream

    const dismissed = localStorage.getItem('pwa_ios_dismissed')
    if (dismissed && Date.now() < Number(dismissed)) {
        isIosDismissed.value = true
    }
})
</script>

<template>
    <!-- Bannière Android / Chrome (API native beforeinstallprompt) -->
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-full opacity-0"
    >
        <div
            v-if="showAndroidBanner"
            class="fixed bottom-0 inset-x-0 z-50 p-4 pb-[calc(1rem+env(safe-area-inset-bottom))] sm:bottom-6 sm:left-auto sm:right-6 sm:inset-x-auto sm:w-96"
        >
            <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden">
                <!-- Accent top -->
                <div class="h-1 bg-gradient-to-r from-primary-700 to-primary-500" />

                <div class="p-5">
                    <div class="flex items-start gap-4">
                        <!-- Icône app -->
                        <div class="w-14 h-14 bg-gradient-to-br from-primary-700 to-primary-500 rounded-2xl flex items-center justify-center shrink-0 shadow-md shadow-primary-200">
                            <span class="text-white font-display font-bold text-2xl">S</span>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-semibold text-slate-900 text-sm leading-tight">
                                        Installer SimpliRH
                                    </p>
                                    <p class="text-slate-500 text-xs mt-0.5">
                                        Accès rapide depuis votre écran d'accueil
                                    </p>
                                </div>
                                <button
                                    @click="dismiss"
                                    class="text-slate-400 hover:text-slate-600 transition-colors p-0.5 -mt-0.5 shrink-0"
                                    aria-label="Fermer"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Avantages rapides -->
                            <ul class="mt-3 space-y-1">
                                <li class="flex items-center gap-1.5 text-xs text-slate-600">
                                    <svg class="w-3.5 h-3.5 text-success-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                    </svg>
                                    Disponible hors ligne
                                </li>
                                <li class="flex items-center gap-1.5 text-xs text-slate-600">
                                    <svg class="w-3.5 h-3.5 text-success-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                    </svg>
                                    Notifications push
                                </li>
                                <li class="flex items-center gap-1.5 text-xs text-slate-600">
                                    <svg class="w-3.5 h-3.5 text-success-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                    </svg>
                                    Expérience plein écran
                                </li>
                            </ul>

                            <div class="mt-4 flex gap-2">
                                <button
                                    @click="handleInstall"
                                    class="flex-1 py-2 px-4 bg-primary-700 hover:bg-primary-800 text-white text-sm font-semibold rounded-xl transition-all active:scale-95 shadow-sm shadow-primary-200"
                                >
                                    Installer
                                </button>
                                <button
                                    @click="dismiss"
                                    class="py-2 px-3 text-slate-500 hover:text-slate-700 text-sm rounded-xl hover:bg-slate-100 transition-all"
                                >
                                    Plus tard
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Bannière iOS / Safari (instructions manuelles) -->
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-full opacity-0"
    >
        <div
            v-if="showIosBanner"
            class="fixed bottom-0 inset-x-0 z-50 p-4 pb-[calc(1rem+env(safe-area-inset-bottom))]"
        >
            <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden">
                <div class="h-1 bg-gradient-to-r from-primary-700 to-primary-500" />

                <div class="p-5">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 bg-gradient-to-br from-primary-700 to-primary-500 rounded-xl flex items-center justify-center shadow-md shadow-primary-200">
                                <span class="text-white font-display font-bold text-lg">S</span>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-900 text-sm">Installer SimpliRH</p>
                                <p class="text-slate-500 text-xs">Sur votre iPhone / iPad</p>
                            </div>
                        </div>
                        <button
                            @click="dismissIos"
                            class="text-slate-400 hover:text-slate-600 transition-colors"
                            aria-label="Fermer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Étapes iOS -->
                    <ol class="space-y-3">
                        <li class="flex items-center gap-3 text-sm text-slate-700">
                            <span class="w-6 h-6 rounded-full bg-primary-100 text-primary-700 text-xs font-bold flex items-center justify-center shrink-0">1</span>
                            <span>Appuyez sur
                                <svg class="inline w-4 h-4 text-primary-600 mx-0.5 -mt-0.5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/>
                                </svg>
                                <strong>Partager</strong> dans Safari
                            </span>
                        </li>
                        <li class="flex items-center gap-3 text-sm text-slate-700">
                            <span class="w-6 h-6 rounded-full bg-primary-100 text-primary-700 text-xs font-bold flex items-center justify-center shrink-0">2</span>
                            <span>Faites défiler et appuyez sur <strong>« Sur l'écran d'accueil »</strong></span>
                        </li>
                        <li class="flex items-center gap-3 text-sm text-slate-700">
                            <span class="w-6 h-6 rounded-full bg-primary-100 text-primary-700 text-xs font-bold flex items-center justify-center shrink-0">3</span>
                            <span>Appuyez sur <strong>« Ajouter »</strong></span>
                        </li>
                    </ol>

                    <!-- Flèche vers le bas pour pointer la barre Safari -->
                    <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-full">
                        <svg class="w-6 h-6 text-white drop-shadow" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 16L4 8h16l-8 8z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
