<script setup>
import { Link } from '@inertiajs/vue3'
import { useNavigation } from '@/Composables/useNavigation'

const { mobileItems, isActive } = useNavigation()
</script>

<template>
    <!-- Barre de navigation fixe en bas — mobile uniquement -->
    <nav
        class="lg:hidden fixed bottom-0 inset-x-0 z-30
               bg-white border-t border-slate-200
               pb-[env(safe-area-inset-bottom)]
               overflow-visible"
        aria-label="Navigation principale"
    >
        <div class="flex items-end h-14">
            <template v-for="item in mobileItems" :key="item.key">

                <!-- ── Pointage : bouton central surélevé ── -->
                <div
                    v-if="item.featured"
                    class="flex-1 flex flex-col items-center"
                >
                    <Link
                        :href="item.href"
                        class="-translate-y-4 flex flex-col items-center gap-1"
                        :aria-current="isActive(item.href) ? 'page' : undefined"
                        :aria-label="item.label"
                    >
                        <span
                            :class="[
                                'w-[52px] h-[52px] rounded-full flex items-center justify-center',
                                'ring-[3px] ring-white shadow-md',
                                'transition-all duration-150 active:scale-95',
                                isActive(item.href) ? 'bg-primary-800' : 'bg-primary-700',
                            ]"
                        >
                            <span class="w-[22px] h-[22px] text-white" v-html="item.icon" />
                        </span>
                        <span
                            :class="[
                                'text-[10px] font-semibold leading-none',
                                isActive(item.href) ? 'text-primary-700' : 'text-slate-500',
                            ]"
                        >
                            {{ item.label }}
                        </span>
                    </Link>
                </div>

                <!-- ── Items normaux ── -->
                <Link
                    v-else
                    :href="item.href"
                    :class="[
                        'flex-1 flex flex-col items-center justify-center gap-1 pb-1 min-w-0',
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

                    <span
                        class="w-5 h-5 transition-transform duration-150"
                        :class="isActive(item.href) ? 'scale-110' : ''"
                        v-html="item.icon"
                    />
                    <span class="text-[10px] font-medium leading-none truncate max-w-full px-1">
                        {{ item.label }}
                    </span>
                </Link>

            </template>
        </div>
    </nav>
</template>
