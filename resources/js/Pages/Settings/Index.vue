<template>
  <AppLayout title="Paramètres">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-6 space-y-8">

      <!-- Header -->
      <div>
        <h1 class="text-2xl font-bold text-slate-900" style="font-family: 'Plus Jakarta Sans', sans-serif;">
          Paramètres
        </h1>
        <p class="text-sm text-slate-500 mt-1">Configurez et personnalisez SimpliRH pour votre entreprise.</p>
      </div>

      <!-- Grille de cartes -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <Link
          v-for="item in settingsItems"
          :key="item.route"
          :href="route(item.route)"
          class="group bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-start gap-4 hover:border-primary-300 hover:shadow-md transition-all duration-200"
        >
          <div
            class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 transition-colors group-hover:scale-105 duration-200"
            :class="item.bgClass"
          >
            <span class="w-5 h-5" :class="item.iconClass" v-html="item.icon" />
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2">
              <h3 class="text-sm font-semibold text-slate-800 group-hover:text-primary-700 transition-colors">
                {{ item.label }}
              </h3>
              <svg class="w-4 h-4 text-slate-300 group-hover:text-primary-400 flex-shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </div>
            <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">{{ item.description }}</p>
          </div>
        </Link>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const settingsItems = [
  {
    route: 'settings.company.index',
    label: 'Entreprise',
    description: 'Logo, couleurs, coordonnées et informations légales.',
    bgClass: 'bg-blue-50',
    iconClass: 'text-blue-600',
    icon: `<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>`,
  },
  {
    route: 'settings.departements.index',
    label: 'Départements',
    description: 'Gérez les départements et leurs responsables.',
    bgClass: 'bg-purple-50',
    iconClass: 'text-purple-600',
    icon: `<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/></svg>`,
  },
  {
    route: 'settings.types-conges.index',
    label: 'Types de congés',
    description: 'Configurez les types de congés et leurs règles d\'acquisition.',
    bgClass: 'bg-green-50',
    iconClass: 'text-green-600',
    icon: `<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>`,
  },
  {
    route: 'settings.holidays.index',
    label: 'Jours fériés',
    description: 'Importez les jours fériés français ou ajoutez des jours personnalisés.',
    bgClass: 'bg-red-50',
    iconClass: 'text-red-600',
    icon: `<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>`,
  },
  {
    route: 'settings.overtime-rules.index',
    label: 'Heures supplémentaires',
    description: 'Définissez les seuils de déclenchement et les règles de majoration.',
    bgClass: 'bg-orange-50',
    iconClass: 'text-orange-600',
    icon: `<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
  },
  {
    route: 'settings.billing.index',
    label: 'Facturation',
    description: 'Gérez votre abonnement, vos moyens de paiement et l\'historique des factures.',
    bgClass: 'bg-emerald-50',
    iconClass: 'text-emerald-600',
    icon: `<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>`,
  },
]
</script>
