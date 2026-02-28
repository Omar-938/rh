<script setup>
import { ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Sidebar from '@/Components/Layout/Sidebar.vue'
import TopBar from '@/Components/Layout/TopBar.vue'
import MobileNav from '@/Components/Layout/MobileNav.vue'
import Toast from '@/Components/UI/Toast.vue'
import InstallBanner from '@/Components/PWA/InstallBanner.vue'

defineProps({
    title:   { type: String, default: '' },
    backUrl: { type: String, default: null },
})

const sidebarOpen = ref(false)
const page = usePage()

// Ferme le drawer mobile à chaque navigation Inertia
watch(() => page.url, () => { sidebarOpen.value = false })
</script>

<template>
    <div class="flex h-screen bg-app-bg overflow-hidden">

        <!-- ── Sidebar ── -->
        <Sidebar
            :mobile-open="sidebarOpen"
            @close="sidebarOpen = false"
        />

        <!-- ── Colonne principale ── -->
        <div class="flex flex-col flex-1 min-w-0 overflow-hidden">

            <!-- TopBar -->
            <TopBar
                :title="title"
                :back-url="backUrl"
                @toggle-sidebar="sidebarOpen = !sidebarOpen"
            >
                <template v-if="$slots.title" #title>
                    <slot name="title" />
                </template>
                <template v-if="$slots.actions" #actions>
                    <slot name="actions" />
                </template>
            </TopBar>

            <!-- Zone de contenu scrollable -->
            <main class="flex-1 overflow-y-auto">
                <!-- Transition de page Inertia -->
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    mode="out-in"
                >
                    <div
                        :key="page.url"
                        class="px-4 py-6 sm:px-6 lg:px-8
                               pb-[calc(3.5rem+env(safe-area-inset-bottom))] lg:pb-8
                               max-w-screen-2xl mx-auto w-full"
                    >
                        <slot />
                    </div>
                </Transition>
            </main>
        </div>

        <!-- ── Navigation mobile bas ── -->
        <MobileNav />

        <!-- ── Toast notifications ── -->
        <Toast />

        <!-- ── Bannière PWA installation ── -->
        <InstallBanner />
    </div>
</template>
