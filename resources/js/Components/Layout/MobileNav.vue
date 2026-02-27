<script setup>
import { Link } from '@inertiajs/vue3'
import { useNavigation } from '@/Composables/useNavigation'

const { mobileItems, isActive } = useNavigation()
</script>

<template>
    <!-- Barre de navigation fixe en bas — mobile uniquement -->
    <nav
        class="lg:hidden fixed bottom-0 inset-x-0 z-30 bg-white/95 backdrop-blur-md
               border-t border-slate-100 pb-[env(safe-area-inset-bottom)]"
        aria-label="Navigation principale"
    >
        <div class="flex items-stretch h-14">
            <Link
                v-for="item in mobileItems"
                :key="item.key"
                :href="item.href"
                :class="[
                    'flex-1 flex flex-col items-center justify-center gap-1 pt-2 pb-1 min-w-0',
                    'transition-colors duration-150 relative',
                    isActive(item.href) ? 'text-primary-700' : 'text-slate-400 hover:text-slate-600',
                ]"
                :aria-current="isActive(item.href) ? 'page' : undefined"
                :aria-label="item.label"
            >
                <!-- Indicateur actif (barre en haut) -->
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="scale-x-0 opacity-0"
                    enter-to-class="scale-x-100 opacity-100"
                >
                    <span
                        v-if="isActive(item.href)"
                        class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-0.5 rounded-full bg-primary-700"
                    />
                </Transition>

                <!-- Icône -->
                <span
                    class="w-5 h-5 transition-transform duration-150"
                    :class="isActive(item.href) ? 'scale-110' : ''"
                    v-html="item.icon"
                />

                <!-- Label tronqué -->
                <span class="text-[10px] font-medium leading-none truncate max-w-full px-1">
                    {{ item.label }}
                </span>
            </Link>
        </div>
    </nav>
</template>
