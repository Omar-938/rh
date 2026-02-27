<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
})

const features = [
    { icon: '📅', label: 'Congés & absences', desc: 'Validez les demandes en 1 clic' },
    { icon: '⏱️', label: 'Pointage mobile', desc: 'Clock-in depuis n\'importe où' },
    { icon: '📄', label: 'Documents RH', desc: 'Bulletins et contrats sécurisés' },
    { icon: '📊', label: 'Export paie', desc: 'Variables compilées automatiquement' },
]
</script>

<template>
    <div class="min-h-screen flex">
        <!-- ── Panneau gauche (branding) — caché sur mobile ── -->
        <div class="hidden lg:flex lg:w-[46%] xl:w-[42%] flex-col justify-between
                    bg-gradient-to-br from-primary-900 via-primary-800 to-primary-600
                    p-10 xl:p-14 relative overflow-hidden">

            <!-- Cercles décoratifs -->
            <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-white/5 pointer-events-none" />
            <div class="absolute -bottom-32 -left-16 w-80 h-80 rounded-full bg-white/5 pointer-events-none" />
            <div class="absolute top-1/2 right-8 w-32 h-32 rounded-full bg-primary-500/20 pointer-events-none" />

            <!-- Logo -->
            <div>
                <Link href="/" class="inline-flex items-center gap-3 group">
                    <div class="w-11 h-11 bg-white/15 backdrop-blur rounded-xl flex items-center justify-center
                                group-hover:bg-white/25 transition-colors shadow-lg">
                        <span class="text-white font-display font-bold text-xl">S</span>
                    </div>
                    <span class="text-white font-display font-bold text-xl tracking-tight">SimpliRH</span>
                </Link>

                <div class="mt-12 xl:mt-16">
                    <h2 class="text-white font-display text-3xl xl:text-4xl font-bold leading-tight">
                        La RH simplifiée<br>pour votre équipe
                    </h2>
                    <p class="mt-4 text-primary-200 text-base leading-relaxed max-w-sm">
                        Tout ce dont vous avez besoin pour gérer vos collaborateurs,
                        sans complexité inutile.
                    </p>
                </div>
            </div>

            <!-- Features -->
            <div class="space-y-4 relative z-10">
                <div
                    v-for="f in features"
                    :key="f.label"
                    class="flex items-start gap-4 p-4 rounded-xl bg-white/8 backdrop-blur-sm
                           border border-white/10 hover:bg-white/12 transition-colors"
                >
                    <span class="text-2xl leading-none mt-0.5 shrink-0">{{ f.icon }}</span>
                    <div>
                        <p class="text-white font-semibold text-sm">{{ f.label }}</p>
                        <p class="text-primary-300 text-xs mt-0.5">{{ f.desc }}</p>
                    </div>
                </div>
            </div>

            <!-- Footer branding -->
            <p class="text-primary-400 text-xs relative z-10">
                © {{ new Date().getFullYear() }} SimpliRH · Pour TPE et PME françaises
            </p>
        </div>

        <!-- ── Panneau droit (formulaire) ── -->
        <div class="flex-1 flex flex-col min-h-screen bg-white">
            <!-- Logo mobile uniquement -->
            <div class="lg:hidden flex items-center gap-2.5 px-6 pt-8">
                <div class="w-9 h-9 bg-primary-700 rounded-xl flex items-center justify-center shadow-sm">
                    <span class="text-white font-display font-bold text-base">S</span>
                </div>
                <span class="text-primary-700 font-display font-bold text-lg tracking-tight">SimpliRH</span>
            </div>

            <!-- Contenu formulaire centré -->
            <div class="flex-1 flex items-center justify-center px-6 py-10 sm:px-10 lg:px-12 xl:px-16">
                <div class="w-full max-w-md">
                    <!-- En-tête -->
                    <div class="mb-8">
                        <h1 class="text-2xl sm:text-3xl font-display font-bold text-slate-900 leading-tight">
                            {{ title }}
                        </h1>
                        <p v-if="subtitle" class="mt-2 text-slate-500 text-sm leading-relaxed">
                            {{ subtitle }}
                        </p>
                    </div>

                    <!-- Slot formulaire -->
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
