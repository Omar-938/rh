<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    notifications: { type: Object,  required: true },
    unread_count:  { type: Number,  default: 0 },
})

function markAsRead(id) {
    router.post(route('notifications.read', id), {}, { preserveScroll: true })
}

function markAllAsRead() {
    router.post(route('notifications.read-all'), {}, { preserveScroll: true })
}

const typeColors = {
    // Congés
    leave_submitted:          { bg: '#EFF6FF', text: '#1D4ED8' },
    leave_approved:           { bg: '#F0FDF4', text: '#15803D' },
    leave_rejected:           { bg: '#FEF2F2', text: '#DC2626' },
    leave_cancelled:          { bg: '#F8FAFC', text: '#64748B' },
    leave_cancelled_by_admin: { bg: '#F8FAFC', text: '#64748B' },
    // Heures supplémentaires
    overtime_submitted:       { bg: '#FFFBEB', text: '#B45309' },
    overtime_approved:        { bg: '#F0FDF4', text: '#15803D' },
    overtime_rejected:        { bg: '#FEF2F2', text: '#DC2626' },
    // Signatures électroniques
    signature_requested:      { bg: '#FEF9C3', text: '#854D0E' },
    document_signed:          { bg: '#F0FDF4', text: '#15803D' },
    signature_declined:       { bg: '#FEF2F2', text: '#DC2626' },
    // Bulletins de paie
    payslip_distributed:      { bg: '#ECFDF5', text: '#065F46' },
    // Défaut
    info:                     { bg: '#F8FAFC', text: '#64748B' },
}
</script>

<template>
    <Head title="Notifications" />

    <AppLayout title="Notifications">

        <!-- ── Header ── -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-display font-bold text-slate-900">Notifications</h2>
                <p v-if="unread_count > 0" class="text-sm text-slate-500 mt-0.5">
                    {{ unread_count }} non lue(s)
                </p>
            </div>
            <button
                v-if="unread_count > 0"
                @click="markAllAsRead"
                class="px-3 py-1.5 text-sm font-medium text-primary-600 border border-primary-200
                       rounded-xl hover:bg-primary-50 transition-colors"
            >
                Tout marquer comme lu
            </button>
        </div>

        <!-- ── Liste ── -->
        <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">

            <!-- Vide -->
            <div v-if="notifications.data.length === 0"
                 class="flex flex-col items-center justify-center py-16">
                <div class="text-5xl mb-3">🔔</div>
                <p class="text-base font-medium text-slate-500">Aucune notification</p>
                <p class="text-sm text-slate-400 mt-1">
                    Vous serez notifié ici lors d'événements importants.
                </p>
            </div>

            <!-- Items -->
            <div v-else class="divide-y divide-slate-50">
                <button
                    v-for="notif in notifications.data"
                    :key="notif.id"
                    @click="markAsRead(notif.id)"
                    class="w-full flex items-start gap-4 px-5 py-4 text-left
                           hover:bg-slate-50/60 transition-colors group"
                    :class="{ 'bg-primary-50/30': !notif.is_read }"
                >
                    <!-- Icône -->
                    <div
                        class="w-10 h-10 rounded-xl flex items-center justify-center text-lg shrink-0"
                        :style="{
                            backgroundColor: (typeColors[notif.type] ?? typeColors.info).bg,
                            color: (typeColors[notif.type] ?? typeColors.info).text,
                        }"
                    >
                        {{ notif.icon }}
                    </div>

                    <!-- Contenu -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-800 leading-snug"
                               :class="{ 'text-primary-800': !notif.is_read }">
                                {{ notif.title }}
                            </p>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <span v-if="!notif.is_read"
                                      class="w-2 h-2 rounded-full bg-primary-500 shrink-0" />
                                <span class="text-xs text-slate-400 whitespace-nowrap">
                                    {{ notif.created_at }}
                                </span>
                            </div>
                        </div>
                        <p class="text-sm text-slate-500 mt-0.5 leading-relaxed">
                            {{ notif.body }}
                        </p>
                    </div>

                    <!-- Flèche -->
                    <svg v-if="notif.action_url"
                         class="w-4 h-4 text-slate-300 group-hover:text-slate-400 shrink-0 mt-0.5
                                transition-colors"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </button>
            </div>

            <!-- Pagination -->
            <div v-if="notifications.last_page > 1"
                 class="flex items-center justify-between px-5 py-3 border-t border-slate-100">
                <p class="text-sm text-slate-500">
                    {{ notifications.from }}–{{ notifications.to }} sur {{ notifications.total }}
                </p>
                <div class="flex gap-1">
                    <Link
                        v-if="notifications.prev_page_url"
                        :href="notifications.prev_page_url"
                        class="px-3 py-1.5 text-sm border border-slate-200 rounded-lg
                               hover:bg-slate-50 text-slate-600 transition-colors"
                    >
                        ← Préc.
                    </Link>
                    <Link
                        v-if="notifications.next_page_url"
                        :href="notifications.next_page_url"
                        class="px-3 py-1.5 text-sm border border-slate-200 rounded-lg
                               hover:bg-slate-50 text-slate-600 transition-colors"
                    >
                        Suiv. →
                    </Link>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
