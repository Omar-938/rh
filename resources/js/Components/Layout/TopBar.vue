<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'

defineProps({
    title:   { type: String, default: '' },
    backUrl: { type: String, default: null },
})
defineEmits(['toggle-sidebar'])

const page = usePage()
const user = computed(() => page.props.auth?.user)
const unreadCount = computed(() => page.props.unread_notifications_count ?? 0)

// ── User menu ─────────────────────────────────────────────────────────────────
const userMenuOpen  = ref(false)
const notifPanelOpen = ref(false)

// Notifications récentes (chargées à l'ouverture du panel)
const recentNotifs  = ref([])
const notifLoading  = ref(false)
const notifLoaded   = ref(false)

const typeColors = {
    leave_submitted:         { bg: '#EFF6FF', text: '#1D4ED8' },
    leave_approved:          { bg: '#F0FDF4', text: '#15803D' },
    leave_rejected:          { bg: '#FEF2F2', text: '#DC2626' },
    leave_cancelled:         { bg: '#F8FAFC', text: '#64748B' },
    leave_cancelled_by_admin:{ bg: '#F8FAFC', text: '#64748B' },
    info:                    { bg: '#F8FAFC', text: '#64748B' },
}

function logout() {
    userMenuOpen.value = false
    router.post('/logout')
}

// ── Fermeture clic extérieur ──────────────────────────────────────────────────
function handleOutsideClick(e) {
    if (!e.target.closest('[data-user-menu]')) {
        userMenuOpen.value = false
    }
    if (!e.target.closest('[data-notif-panel]')) {
        notifPanelOpen.value = false
    }
}

onMounted(() => document.addEventListener('click', handleOutsideClick))
onBeforeUnmount(() => document.removeEventListener('click', handleOutsideClick))

// ── Notifications ─────────────────────────────────────────────────────────────
async function toggleNotifPanel() {
    if (notifPanelOpen.value) {
        notifPanelOpen.value = false
        return
    }

    notifPanelOpen.value = true

    if (!notifLoaded.value) {
        notifLoading.value = true
        try {
            const res = await fetch(route('notifications.recent'), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
            const data = await res.json()
            recentNotifs.value = data.items
            notifLoaded.value  = true
        } catch {
            recentNotifs.value = []
        } finally {
            notifLoading.value = false
        }
    }
}

function openNotif(notif) {
    // Mark as read + navigate
    router.post(
        route('notifications.read', notif.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                notifLoaded.value = false  // force reload next time
                notifPanelOpen.value = false
                if (notif.action_url) {
                    router.visit(notif.action_url)
                }
            },
        }
    )
}

function markAllRead() {
    router.post(
        route('notifications.read-all'),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                recentNotifs.value.forEach(n => { n.is_read = true })
                notifLoaded.value = false
            },
        }
    )
}

function toggleUserMenu() {
    userMenuOpen.value = !userMenuOpen.value
    if (userMenuOpen.value) notifPanelOpen.value = false
}
</script>

<template>
    <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-slate-100 shrink-0">
        <div class="flex items-center gap-3 h-14 px-4 sm:px-6">

            <!-- Hamburger (mobile, toujours visible) -->
            <button
                @click="$emit('toggle-sidebar')"
                class="lg:hidden p-2 -ml-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100
                       rounded-lg transition-colors shrink-0"
                aria-label="Ouvrir le menu"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>

            <!-- Retour (mobile, en complément du hamburger si backUrl fourni) -->
            <Link
                v-if="backUrl"
                :href="backUrl"
                class="lg:hidden p-2 -mx-1 text-slate-400 hover:text-slate-700 hover:bg-slate-100
                       rounded-lg transition-colors shrink-0"
                aria-label="Retour"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </Link>

            <!-- Titre de la page -->
            <div class="flex-1 min-w-0">
                <h1 v-if="title" class="text-base font-semibold text-slate-900 truncate">
                    {{ title }}
                </h1>
                <slot name="title" />
            </div>

            <!-- Actions contextuelles -->
            <div class="hidden sm:flex items-center gap-2">
                <slot name="actions" />
            </div>

            <!-- ── Cloche notifications ── -->
            <div class="relative" data-notif-panel>
                <button
                    @click="toggleNotifPanel"
                    class="relative p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100
                           rounded-lg transition-colors"
                    aria-label="Notifications"
                    :aria-expanded="notifPanelOpen"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                    </svg>
                    <!-- Badge -->
                    <Transition
                        enter-active-class="transition-all duration-200"
                        enter-from-class="scale-0 opacity-0"
                        enter-to-class="scale-100 opacity-100"
                        leave-active-class="transition-all duration-150"
                        leave-from-class="scale-100 opacity-100"
                        leave-to-class="scale-0 opacity-0"
                    >
                        <span
                            v-if="unreadCount > 0"
                            class="absolute top-1 right-1 min-w-[18px] h-[18px] flex items-center
                                   justify-center px-1 bg-danger-500 text-white text-[10px]
                                   font-bold rounded-full ring-2 ring-white leading-none"
                        >
                            {{ unreadCount > 99 ? '99+' : unreadCount }}
                        </span>
                    </Transition>
                </button>

                <!-- ── Panel notifications ── -->
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 -translate-y-1"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 -translate-y-1"
                >
                    <div
                        v-if="notifPanelOpen"
                        class="absolute right-0 top-full mt-2 w-80 sm:w-96 bg-white rounded-2xl
                               shadow-xl shadow-slate-200/80 border border-slate-100 overflow-hidden
                               origin-top-right z-50"
                    >
                        <!-- En-tête panel -->
                        <div class="flex items-center justify-between px-4 py-3 border-b
                                    border-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-slate-900">
                                    Notifications
                                </span>
                                <span
                                    v-if="unreadCount > 0"
                                    class="px-1.5 py-0.5 bg-danger-100 text-danger-600 text-[10px]
                                           font-bold rounded-full leading-none"
                                >
                                    {{ unreadCount }}
                                </span>
                            </div>
                            <button
                                v-if="unreadCount > 0"
                                @click="markAllRead"
                                class="text-xs text-primary-600 hover:text-primary-700 font-medium
                                       transition-colors"
                            >
                                Tout lire
                            </button>
                        </div>

                        <!-- Corps -->
                        <div class="max-h-[360px] overflow-y-auto">

                            <!-- Chargement -->
                            <div v-if="notifLoading"
                                 class="flex items-center justify-center py-10">
                                <div class="w-6 h-6 border-2 border-primary-200 border-t-primary-600
                                            rounded-full animate-spin" />
                            </div>

                            <!-- Vide -->
                            <div v-else-if="recentNotifs.length === 0"
                                 class="flex flex-col items-center py-10 text-slate-400">
                                <span class="text-3xl mb-2">🔔</span>
                                <p class="text-sm">Aucune notification</p>
                            </div>

                            <!-- Items -->
                            <div v-else class="divide-y divide-slate-50">
                                <button
                                    v-for="notif in recentNotifs"
                                    :key="notif.id"
                                    @click="openNotif(notif)"
                                    class="w-full flex items-start gap-3 px-4 py-3 text-left
                                           hover:bg-slate-50/70 transition-colors"
                                    :class="{ 'bg-primary-50/40': !notif.is_read }"
                                >
                                    <!-- Icône -->
                                    <div
                                        class="w-8 h-8 rounded-lg flex items-center justify-center
                                               text-base shrink-0 mt-0.5"
                                        :style="{
                                            backgroundColor: (typeColors[notif.type] ?? typeColors.info).bg,
                                            color: (typeColors[notif.type] ?? typeColors.info).text,
                                        }"
                                    >
                                        {{ notif.icon }}
                                    </div>

                                    <!-- Texte -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between gap-1">
                                            <p class="text-xs font-semibold text-slate-800
                                                       leading-snug line-clamp-1"
                                               :class="{ 'text-primary-800': !notif.is_read }">
                                                {{ notif.title }}
                                            </p>
                                            <span v-if="!notif.is_read"
                                                  class="w-1.5 h-1.5 rounded-full bg-primary-500
                                                         shrink-0 mt-1" />
                                        </div>
                                        <p class="text-xs text-slate-500 mt-0.5 line-clamp-2
                                                   leading-relaxed">
                                            {{ notif.body }}
                                        </p>
                                        <p class="text-[10px] text-slate-300 mt-1">
                                            {{ notif.created_at }}
                                        </p>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- Pied du panel -->
                        <div class="border-t border-slate-100 px-4 py-2.5">
                            <Link
                                :href="route('notifications.index')"
                                @click="notifPanelOpen = false"
                                class="block text-center text-xs font-medium text-primary-600
                                       hover:text-primary-700 transition-colors py-0.5"
                            >
                                Voir toutes les notifications →
                            </Link>
                        </div>
                    </div>
                </Transition>
            </div>

            <!-- ── Avatar + menu utilisateur ── -->
            <div class="relative" data-user-menu>
                <button
                    @click="toggleUserMenu"
                    class="flex items-center gap-2.5 p-1 rounded-xl hover:bg-slate-100
                           transition-colors group"
                    :aria-expanded="userMenuOpen"
                    aria-haspopup="true"
                >
                    <div class="w-8 h-8 rounded-full bg-primary-700 flex items-center justify-center
                                ring-2 ring-transparent group-hover:ring-primary-200 transition-all
                                overflow-hidden">
                        <img v-if="user?.avatar_url"
                             :src="user.avatar_url"
                             class="w-full h-full object-cover"
                             :alt="user?.full_name" />
                        <span v-else class="text-white text-xs font-bold">
                            {{ user?.initials ?? '?' }}
                        </span>
                    </div>
                    <span class="hidden sm:block text-sm font-medium text-slate-700
                                 max-w-[120px] truncate">
                        {{ user?.first_name }}
                    </span>
                    <svg class="hidden sm:block w-4 h-4 text-slate-400 transition-transform
                                duration-150"
                         :class="{ 'rotate-180': userMenuOpen }"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>

                <!-- Dropdown menu -->
                <Transition
                    enter-active-class="transition duration-150 ease-out"
                    enter-from-class="opacity-0 scale-95 -translate-y-1"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-100 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 -translate-y-1"
                >
                    <div
                        v-if="userMenuOpen"
                        class="absolute right-0 top-full mt-2 w-56 bg-white rounded-xl
                               shadow-lg shadow-slate-200/80 border border-slate-100 py-1.5
                               origin-top-right z-50"
                        role="menu"
                    >
                        <div class="px-4 py-2.5 border-b border-slate-100 mb-1">
                            <p class="text-sm font-semibold text-slate-900 truncate">
                                {{ user?.full_name }}
                            </p>
                            <p class="text-xs text-slate-500 truncate">{{ user?.email }}</p>
                        </div>

                        <Link
                            href="/profil"
                            class="flex items-center gap-3 px-4 py-2 text-sm text-slate-700
                                   hover:bg-slate-50 transition-colors w-full text-left"
                            role="menuitem"
                            @click="userMenuOpen = false"
                        >
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                 stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                            Mon profil
                        </Link>

                        <Link
                            :href="route('notifications.index')"
                            class="flex items-center justify-between gap-3 px-4 py-2 text-sm
                                   text-slate-700 hover:bg-slate-50 transition-colors w-full
                                   text-left"
                            role="menuitem"
                            @click="userMenuOpen = false"
                        >
                            <div class="flex items-center gap-3">
                                <svg class="w-4 h-4 text-slate-400" fill="none"
                                     stroke="currentColor" stroke-width="1.75"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                </svg>
                                Notifications
                            </div>
                            <span v-if="unreadCount > 0"
                                  class="px-1.5 py-0.5 bg-danger-100 text-danger-600 text-[10px]
                                         font-bold rounded-full leading-none">
                                {{ unreadCount }}
                            </span>
                        </Link>

                        <Link
                            v-if="user?.role === 'admin'"
                            href="/parametres"
                            class="flex items-center gap-3 px-4 py-2 text-sm text-slate-700
                                   hover:bg-slate-50 transition-colors w-full text-left"
                            role="menuitem"
                            @click="userMenuOpen = false"
                        >
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                 stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Paramètres
                        </Link>

                        <div class="border-t border-slate-100 mt-1 pt-1">
                            <button
                                @click="logout"
                                class="flex items-center gap-3 px-4 py-2 text-sm text-danger-600
                                       hover:bg-danger-50 transition-colors w-full text-left"
                                role="menuitem"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                     stroke-width="1.75" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                                </svg>
                                Se déconnecter
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>

        <!-- Actions mobiles -->
        <div class="sm:hidden px-4 pb-3 flex gap-2" v-if="$slots.actions">
            <slot name="actions" />
        </div>
    </header>
</template>
