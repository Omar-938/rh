<script setup>
import { watch, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useToast } from '@/Composables/useToast'

const { toasts, success, error, warning, info, dismiss } = useToast()
const page = usePage()

// Écoute les messages flash Inertia
watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return
        if (flash.success) success(flash.success)
        if (flash.error)   error(flash.error)
        if (flash.warning) warning(flash.warning)
        if (flash.info)    info(flash.info)
    },
    { deep: true }
)

const styles = {
    success: {
        bg:   'bg-white border-success-200',
        icon: 'text-success-500',
        bar:  'bg-success-500',
        svg: `<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />`,
    },
    error: {
        bg:   'bg-white border-danger-200',
        icon: 'text-danger-500',
        bar:  'bg-danger-500',
        svg: `<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />`,
    },
    warning: {
        bg:   'bg-white border-warning-200',
        icon: 'text-warning-500',
        bar:  'bg-warning-500',
        svg: `<path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />`,
    },
    info: {
        bg:   'bg-white border-primary-200',
        icon: 'text-primary-500',
        bar:  'bg-primary-500',
        svg: `<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />`,
    },
}
</script>

<template>
    <!-- Conteneur positionné en haut à droite (desktop) ou en haut (mobile) -->
    <div
        class="fixed top-4 right-4 z-[200] flex flex-col gap-2.5 w-full max-w-sm pointer-events-none"
        aria-live="polite"
        aria-label="Notifications"
    >
        <TransitionGroup
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-x-full opacity-0"
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transition duration-200 ease-in absolute w-full"
            leave-from-class="translate-x-0 opacity-100"
            leave-to-class="translate-x-full opacity-0"
            move-class="transition-all duration-300"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                :class="[
                    'relative overflow-hidden rounded-xl border shadow-lg shadow-slate-200/60 pointer-events-auto',
                    styles[toast.type]?.bg ?? 'bg-white border-slate-200'
                ]"
                role="alert"
            >
                <!-- Barre colorée gauche -->
                <div :class="['absolute left-0 top-0 bottom-0 w-1', styles[toast.type]?.bar ?? 'bg-slate-400']" />

                <div class="flex items-start gap-3 px-4 py-3.5 pl-5">
                    <!-- Icône -->
                    <svg
                        :class="['w-5 h-5 shrink-0 mt-0.5', styles[toast.type]?.icon ?? 'text-slate-500']"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        v-html="styles[toast.type]?.svg"
                    />

                    <!-- Message -->
                    <p class="flex-1 text-sm text-slate-800 font-medium leading-snug">
                        {{ toast.message }}
                    </p>

                    <!-- Fermer -->
                    <button
                        @click="dismiss(toast.id)"
                        class="shrink-0 text-slate-400 hover:text-slate-600 transition-colors -mt-0.5 -mr-1"
                        aria-label="Fermer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </TransitionGroup>
    </div>
</template>
