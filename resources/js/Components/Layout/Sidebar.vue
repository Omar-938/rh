<script setup>
import { computed } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import { useNavigation } from '@/Composables/useNavigation'

defineProps({
    mobileOpen: { type: Boolean, default: false },
})
defineEmits(['close'])

const page = usePage()
const user = computed(() => page.props.auth?.user)
const company = computed(() => page.props.auth?.company)

const { navItems, sidebarBottomItems, isActive } = useNavigation()

function logout() { router.post('/logout') }

const trialDaysLeft = computed(() => {
    if (!company.value?.trial_ends_at || company.value?.plan !== 'trial') return null
    const diff = Math.ceil((new Date(company.value.trial_ends_at) - Date.now()) / 86400000)
    return Math.max(0, diff)
})

// Couleur de la sidebar : couleur primaire de l'entreprise ou bleu par défaut
const sidebarStyle = computed(() => ({
    backgroundColor: company.value?.primary_color || '#1B4F72',
}))
</script>

<template>
    <!-- ── Desktop : sidebar fixe ── -->
    <aside
        class="hidden lg:flex flex-col w-64 xl:w-72 shrink-0 h-screen sticky top-0 overflow-y-auto"
        :style="sidebarStyle"
    >
        <SidebarInner
            :user="user" :company="company"
            :nav-items="navItems" :bottom-items="sidebarBottomItems"
            :trial-days-left="trialDaysLeft" :is-active="isActive"
            @logout="logout"
        />
    </aside>

    <!-- ── Mobile : overlay + drawer ── -->
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="mobileOpen"
                 class="lg:hidden fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm"
                 @click="$emit('close')" />
        </Transition>
        <Transition name="slide-drawer">
            <div v-if="mobileOpen"
                 class="lg:hidden fixed inset-y-0 left-0 z-50 w-72 flex flex-col overflow-y-auto shadow-2xl"
                 :style="sidebarStyle">
                <SidebarInner
                    :user="user" :company="company"
                    :nav-items="navItems" :bottom-items="sidebarBottomItems"
                    :trial-days-left="trialDaysLeft" :is-active="isActive"
                    @logout="logout" @navigate="$emit('close')"
                />
            </div>
        </Transition>
    </Teleport>
</template>

<!-- Sous-composant : contenu partagé desktop/mobile -->
<script>
import { defineComponent, h } from 'vue'
import { Link } from '@inertiajs/vue3'

const SidebarInner = defineComponent({
    name: 'SidebarInner',
    props: {
        user: Object, company: Object,
        navItems: Array, bottomItems: Array,
        trialDaysLeft: { type: Number, default: null },
        isActive: Function,
    },
    emits: ['logout', 'navigate'],
    setup(props, { emit }) {
        return () => {
            const active = (href) => props.isActive?.(href) ?? false

            const navLink = (item) => h(Link, {
                href: item.href,
                class: [
                    'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 group',
                    active(item.href)
                        ? 'bg-white/15 text-white shadow-sm'
                        : 'text-primary-200 hover:bg-white/8 hover:text-white',
                ],
                'aria-current': active(item.href) ? 'page' : undefined,
                onClick: () => emit('navigate'),
            }, () => [
                h('span', {
                    class: ['w-5 h-5 shrink-0 transition-colors [&>svg]:w-5 [&>svg]:h-5',
                        active(item.href) ? 'text-white' : 'text-primary-300 group-hover:text-white'],
                    innerHTML: item.icon,
                }),
                h('span', { class: 'truncate flex-1' }, item.label),
                active(item.href) ? h('span', { class: 'ml-auto w-1.5 h-1.5 rounded-full bg-white/70 shrink-0' }) : null,
            ])

            return h('div', { class: 'flex flex-col h-full text-white' }, [
                // Header
                h('div', { class: 'px-5 py-5 border-b border-white/10 shrink-0' },
                    h(Link, { href: '/dashboard', class: 'flex items-center gap-3 group', onClick: () => emit('navigate') }, () => [
                        h('div', { class: 'w-9 h-9 bg-white/15 rounded-xl flex items-center justify-center group-hover:bg-white/25 transition-colors shrink-0 overflow-hidden' },
                            props.company?.logo_url
                                ? h('img', { src: props.company.logo_url, class: 'w-full h-full object-contain p-1', alt: props.company.name })
                                : h('span', { class: 'text-white font-display font-bold text-base' }, props.company?.name?.charAt(0)?.toUpperCase() ?? 'S')
                        ),
                        h('div', { class: 'min-w-0' }, [
                            h('p', { class: 'text-white font-semibold text-sm truncate' }, props.company?.name ?? 'SimpliRH'),
                            h('p', { class: 'text-white/50 text-xs truncate mt-0.5' }, 'SimpliRH'),
                        ]),
                    ])
                ),

                // Bannière essai
                props.trialDaysLeft !== null && props.trialDaysLeft <= 14
                    ? h('div', { class: 'mx-3 mt-3 px-3 py-2.5 rounded-xl bg-warning-500/15 border border-warning-400/30 shrink-0' }, [
                        h('p', { class: 'text-warning-200 text-xs font-semibold' },
                            `⏳ Essai : ${props.trialDaysLeft} jour${props.trialDaysLeft !== 1 ? 's' : ''} restant${props.trialDaysLeft !== 1 ? 's' : ''}`
                        ),
                        h(Link, { href: '/parametres/facturation', class: 'text-warning-300 text-xs underline mt-0.5 inline-block hover:text-warning-200', onClick: () => emit('navigate') }, () => 'Choisir un abonnement →'),
                    ])
                    : null,

                // Nav
                h('nav', { class: 'flex-1 px-3 py-4 space-y-0.5 overflow-y-auto' },
                    (props.navItems ?? []).map(navLink)
                ),

                // Bas de sidebar
                h('div', { class: 'px-3 pb-safe-bottom pb-4 border-t border-white/10 pt-3 shrink-0 space-y-0.5' }, [
                    ...(props.bottomItems ?? []).map(navLink),

                    // User card
                    props.user ? h('div', { class: 'mt-2 pt-3 border-t border-white/10' },
                        h('div', { class: 'flex items-center gap-3 px-3 py-2' }, [
                            h('div', { class: 'w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center shrink-0 ring-2 ring-white/20 overflow-hidden' },
                                props.user.avatar_url
                                    ? h('img', { src: props.user.avatar_url, class: 'w-full h-full object-cover', alt: props.user.full_name })
                                    : h('span', { class: 'text-white text-xs font-bold' }, props.user.initials)
                            ),
                            h('div', { class: 'flex-1 min-w-0' }, [
                                h('p', { class: 'text-white text-sm font-semibold truncate' }, props.user.full_name),
                                h('p', { class: 'text-primary-300 text-xs capitalize' }, props.user.role),
                            ]),
                            h('button', {
                                class: 'p-1.5 text-primary-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors',
                                title: 'Se déconnecter',
                                onClick: () => emit('logout'),
                            }, h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' },
                                h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75' })
                            )),
                        ])
                    ) : null,
                ]),
            ])
        }
    }
})
export { SidebarInner }
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .25s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-drawer-enter-active { transition: transform .28s ease-out; }
.slide-drawer-leave-active { transition: transform .2s ease-in; }
.slide-drawer-enter-from, .slide-drawer-leave-to { transform: translateX(-100%); }
</style>
