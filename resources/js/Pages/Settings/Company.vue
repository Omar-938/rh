<template>
  <AppLayout title="Paramètres entreprise">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-6 space-y-6">

      <!-- Header -->
      <div class="flex items-center gap-3">
        <Link :href="route('settings.index')" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
        </Link>
        <div>
          <h1 class="text-2xl font-bold text-slate-900" style="font-family: 'Plus Jakarta Sans', sans-serif;">
            Paramètres entreprise
          </h1>
          <p class="text-sm text-slate-500 mt-0.5">Informations légales, branding et configuration RH</p>
        </div>
      </div>

      <!-- Flash -->
      <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
      >
        <div v-if="$page.props.flash?.success"
          class="flex items-center gap-3 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm">
          <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
          {{ $page.props.flash.success }}
        </div>
      </Transition>

      <!-- ── Section 1 : Informations générales ───────────────────────────────── -->
      <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
            </svg>
          </div>
          <div>
            <h2 class="text-sm font-semibold text-slate-800">Informations générales</h2>
            <p class="text-xs text-slate-500">Nom, SIRET et coordonnées de l'entreprise</p>
          </div>
        </div>
        <form @submit.prevent="saveGeneral" class="p-6 space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">
                Nom de l'entreprise <span class="text-red-500">*</span>
              </label>
              <input
                v-model="generalForm.name"
                type="text"
                placeholder="Mon Entreprise SAS"
                class="w-full px-3.5 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 transition-colors"
                :class="generalForm.errors.name ? 'border-red-300 focus:ring-red-200' : 'border-slate-200 focus:ring-primary-200'"
              />
              <p v-if="generalForm.errors.name" class="text-xs text-red-500 mt-1">{{ generalForm.errors.name }}</p>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">SIRET</label>
              <input
                v-model="generalForm.siret"
                type="text"
                maxlength="14"
                placeholder="12345678901234"
                class="w-full px-3.5 py-2.5 border rounded-xl text-sm font-mono focus:outline-none focus:ring-2 transition-colors"
                :class="generalForm.errors.siret ? 'border-red-300 focus:ring-red-200' : 'border-slate-200 focus:ring-primary-200'"
              />
              <p v-if="generalForm.errors.siret" class="text-xs text-red-500 mt-1">{{ generalForm.errors.siret }}</p>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">Téléphone</label>
              <input
                v-model="generalForm.phone"
                type="tel"
                placeholder="01 23 45 67 89"
                class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-200 transition-colors"
              />
            </div>

            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">Adresse</label>
              <input
                v-model="generalForm.address"
                type="text"
                placeholder="12 rue de la Paix"
                class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-200 transition-colors"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">Code postal</label>
              <input
                v-model="generalForm.postal_code"
                type="text"
                maxlength="10"
                placeholder="75001"
                class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-primary-200 transition-colors"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">Ville</label>
              <input
                v-model="generalForm.city"
                type="text"
                placeholder="Paris"
                class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-200 transition-colors"
              />
            </div>
          </div>

          <div class="flex justify-end pt-2">
            <button
              type="submit"
              :disabled="generalForm.processing"
              class="flex items-center gap-2 px-5 py-2.5 bg-primary-700 hover:bg-primary-800 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-60"
            >
              <svg v-if="generalForm.processing" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
              </svg>
              Enregistrer
            </button>
          </div>
        </form>
      </section>

      <!-- ── Section 2 : Apparence & Branding ────────────────────────────────── -->
      <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-violet-50 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4.098 19.902a3.75 3.75 0 005.304 0l6.401-6.402M6.75 21A3.75 3.75 0 013 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 003.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.072M10.5 8.197l2.88-2.88c.438-.439 1.15-.439 1.59 0l3.712 3.713c.44.44.44 1.152 0 1.59l-2.879 2.88"/>
            </svg>
          </div>
          <div>
            <h2 class="text-sm font-semibold text-slate-800">Apparence & Branding</h2>
            <p class="text-xs text-slate-500">Logo et couleur principale de l'interface</p>
          </div>
        </div>
        <form @submit.prevent="saveBranding" class="p-6 space-y-6">

          <!-- Logo -->
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-3">Logo de l'entreprise</label>
            <div class="flex flex-col sm:flex-row gap-4 items-start">

              <!-- Preview actuel ou nouveau -->
              <div class="w-24 h-24 rounded-xl border-2 border-dashed border-slate-200 flex items-center justify-center overflow-hidden bg-slate-50 flex-shrink-0">
                <img
                  v-if="logoPreview || props.company.logo_url"
                  :src="logoPreview || props.company.logo_url"
                  class="w-full h-full object-contain p-2"
                  alt="Logo"
                />
                <svg v-else class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                </svg>
              </div>

              <div class="flex-1 space-y-2">
                <div
                  class="border-2 border-dashed rounded-xl p-4 text-center cursor-pointer transition-colors"
                  :class="isLogoDragging ? 'border-primary-400 bg-primary-50' : 'border-slate-200 hover:border-primary-300 hover:bg-slate-50'"
                  @dragover.prevent="isLogoDragging = true"
                  @dragleave.prevent="isLogoDragging = false"
                  @drop.prevent="handleLogoDrop"
                  @click="$refs.logoInput.click()"
                >
                  <input ref="logoInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="handleLogoChange" />
                  <p class="text-sm text-slate-600 font-medium">{{ isLogoDragging ? 'Déposez ici' : 'Cliquer ou glisser-déposer' }}</p>
                  <p class="text-xs text-slate-400 mt-0.5">JPG, PNG, WebP — max 2 Mo</p>
                </div>

                <div class="flex items-center gap-2">
                  <p v-if="brandingForm.logo" class="text-xs text-green-600 font-medium flex-1 truncate">
                    ✓ {{ brandingForm.logo.name }}
                  </p>
                  <button
                    v-if="props.company.logo_url && !logoPreview"
                    type="button"
                    @click="deleteLogo"
                    class="text-xs text-red-500 hover:text-red-600 font-medium transition-colors"
                  >
                    Supprimer le logo
                  </button>
                  <button
                    v-if="logoPreview"
                    type="button"
                    @click="clearLogo"
                    class="text-xs text-slate-400 hover:text-slate-600 font-medium transition-colors"
                  >
                    Annuler
                  </button>
                </div>
                <p v-if="brandingForm.errors.logo" class="text-xs text-red-500">{{ brandingForm.errors.logo }}</p>
              </div>
            </div>
          </div>

          <!-- Couleur principale -->
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-3">Couleur principale</label>
            <div class="flex flex-col lg:flex-row gap-6 items-start">

              <!-- Picker + palette -->
              <div class="space-y-3 flex-1">
                <!-- Palette prédéfinie -->
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="color in colorPalette"
                    :key="color"
                    type="button"
                    @click="brandingForm.primary_color = color"
                    class="w-8 h-8 rounded-lg transition-all duration-150 ring-offset-1 hover:scale-110"
                    :style="{ backgroundColor: color }"
                    :class="brandingForm.primary_color === color ? 'ring-2 ring-slate-700 scale-110' : 'ring-1 ring-slate-200'"
                    :title="color"
                  />
                </div>

                <!-- Picker hex custom -->
                <div class="flex items-center gap-3">
                  <div class="relative">
                    <input
                      type="color"
                      v-model="brandingForm.primary_color"
                      class="sr-only"
                      id="color-picker"
                    />
                    <label
                      for="color-picker"
                      class="flex items-center gap-2 px-3 py-2 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition-colors text-sm text-slate-600 font-medium"
                    >
                      <span class="w-5 h-5 rounded-md border border-white/20 shadow-sm flex-shrink-0" :style="{ backgroundColor: brandingForm.primary_color }"/>
                      Couleur personnalisée
                    </label>
                  </div>
                  <code class="text-sm font-mono text-slate-500 bg-slate-100 px-2.5 py-1.5 rounded-lg">
                    {{ brandingForm.primary_color }}
                  </code>
                </div>
                <p v-if="brandingForm.errors.primary_color" class="text-xs text-red-500">{{ brandingForm.errors.primary_color }}</p>
              </div>

              <!-- Mini-aperçu sidebar -->
              <div class="flex-shrink-0">
                <p class="text-xs text-slate-400 mb-2 text-center">Aperçu de la barre latérale</p>
                <div
                  class="rounded-xl overflow-hidden shadow-lg border border-slate-200 w-36"
                  :style="{ backgroundColor: brandingForm.primary_color }"
                >
                  <!-- Header mini -->
                  <div class="px-3 py-2.5 border-b border-white/10 flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-white/15 flex items-center justify-center flex-shrink-0 overflow-hidden">
                      <img v-if="logoPreview || props.company.logo_url" :src="logoPreview || props.company.logo_url" class="w-full h-full object-contain p-0.5" />
                      <span v-else class="text-white text-xs font-bold">{{ props.company.name?.charAt(0)?.toUpperCase() ?? 'E' }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="h-2 bg-white/80 rounded w-14 mb-1"></div>
                      <div class="h-1.5 bg-white/30 rounded w-10"></div>
                    </div>
                  </div>
                  <!-- Nav items mini -->
                  <div class="px-2 py-2 space-y-1">
                    <div class="px-2 py-1.5 rounded-lg bg-white/15 flex items-center gap-2">
                      <div class="w-3 h-3 rounded-sm bg-white/60 flex-shrink-0"></div>
                      <div class="h-1.5 bg-white/70 rounded flex-1"></div>
                      <div class="w-1 h-1 rounded-full bg-white/60"></div>
                    </div>
                    <div v-for="i in 4" :key="i" class="px-2 py-1.5 flex items-center gap-2">
                      <div class="w-3 h-3 rounded-sm bg-white/25 flex-shrink-0"></div>
                      <div class="h-1.5 bg-white/25 rounded" :style="{ width: `${40 + i * 8}%` }"></div>
                    </div>
                  </div>
                  <!-- User mini -->
                  <div class="px-2 py-2 border-t border-white/10">
                    <div class="flex items-center gap-2 px-1">
                      <div class="w-5 h-5 rounded-full bg-white/20 flex-shrink-0"></div>
                      <div class="flex-1 space-y-1">
                        <div class="h-1.5 bg-white/50 rounded w-12"></div>
                        <div class="h-1 bg-white/25 rounded w-8"></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-end pt-2 border-t border-slate-100">
            <button
              type="submit"
              :disabled="brandingForm.processing"
              class="flex items-center gap-2 px-5 py-2.5 bg-primary-700 hover:bg-primary-800 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-60"
            >
              <svg v-if="brandingForm.processing" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
              </svg>
              Enregistrer l'apparence
            </button>
          </div>
        </form>
      </section>

      <!-- ── Section 3 : Paramètres de travail ────────────────────────────────── -->
      <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div>
            <h2 class="text-sm font-semibold text-slate-800">Paramètres de travail</h2>
            <p class="text-xs text-slate-500">Heures de travail et fuseau horaire utilisés pour les calculs de congés</p>
          </div>
        </div>
        <form @submit.prevent="saveHr" class="p-6 space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">Heures de travail / jour</label>
              <div class="relative">
                <input
                  v-model.number="hrForm.work_hours_per_day"
                  type="number"
                  step="0.5"
                  min="1"
                  max="24"
                  class="w-full pl-3.5 pr-10 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-200 transition-colors"
                />
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">h/j</span>
              </div>
              <p v-if="hrForm.errors.work_hours_per_day" class="text-xs text-red-500 mt-1">{{ hrForm.errors.work_hours_per_day }}</p>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">Jours de travail / semaine</label>
              <div class="relative">
                <input
                  v-model.number="hrForm.work_days_per_week"
                  type="number"
                  min="1"
                  max="7"
                  class="w-full pl-3.5 pr-10 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-200 transition-colors"
                />
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">j/sem</span>
              </div>
              <p v-if="hrForm.errors.work_days_per_week" class="text-xs text-red-500 mt-1">{{ hrForm.errors.work_days_per_week }}</p>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">Fuseau horaire</label>
              <select
                v-model="hrForm.timezone"
                class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-200 transition-colors bg-white"
              >
                <option v-for="tz in timezones" :key="tz.value" :value="tz.value">{{ tz.label }}</option>
              </select>
            </div>
          </div>

          <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
            <p class="text-xs text-slate-500">
              Ces valeurs sont utilisées pour calculer le nombre de jours de congés, valider les heures supplémentaires et générer les exports de paie.
            </p>
          </div>

          <div class="flex justify-end">
            <button
              type="submit"
              :disabled="hrForm.processing"
              class="flex items-center gap-2 px-5 py-2.5 bg-primary-700 hover:bg-primary-800 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-60"
            >
              <svg v-if="hrForm.processing" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
              </svg>
              Enregistrer
            </button>
          </div>
        </form>
      </section>

      <!-- ── Section 4 : Email comptable ──────────────────────────────────────── -->
      <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-green-50 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
            </svg>
          </div>
          <div>
            <h2 class="text-sm font-semibold text-slate-800">Email comptable</h2>
            <p class="text-xs text-slate-500">Destinataires des exports de paie envoyés automatiquement</p>
          </div>
        </div>
        <form @submit.prevent="saveAccountant" class="p-6 space-y-4">

          <!-- Liste des emails -->
          <div class="space-y-2">
            <div
              v-for="(email, index) in accountantForm.accountant_emails"
              :key="index"
              class="flex items-center gap-2"
            >
              <input
                v-model="accountantForm.accountant_emails[index]"
                type="email"
                :placeholder="`comptable${index + 1}@cabinet.fr`"
                class="flex-1 px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-200 transition-colors"
              />
              <button
                type="button"
                @click="removeEmail(index)"
                class="p-2.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-colors flex-shrink-0"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <p v-if="accountantForm.errors.accountant_emails" class="text-xs text-red-500">{{ accountantForm.errors.accountant_emails }}</p>
          </div>

          <!-- Bouton ajouter -->
          <button
            v-if="accountantForm.accountant_emails.length < 10"
            type="button"
            @click="addEmail"
            class="flex items-center gap-2 px-3.5 py-2 text-sm text-primary-600 hover:text-primary-700 hover:bg-primary-50 rounded-xl transition-colors font-medium border border-dashed border-primary-200"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Ajouter un email
          </button>

          <div v-if="accountantForm.accountant_emails.length === 0" class="text-xs text-slate-400 italic">
            Aucun email configuré — les exports de paie ne seront pas envoyés automatiquement.
          </div>

          <div class="flex justify-end pt-2 border-t border-slate-100">
            <button
              type="submit"
              :disabled="accountantForm.processing"
              class="flex items-center gap-2 px-5 py-2.5 bg-primary-700 hover:bg-primary-800 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-60"
            >
              <svg v-if="accountantForm.processing" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
              </svg>
              Enregistrer
            </button>
          </div>
        </form>
      </section>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
  company: { type: Object, required: true },
})

// ── Palette de couleurs ────────────────────────────────────────────────────
const colorPalette = [
  '#1B4F72', '#2E86C1', '#1565C0', '#0D47A1',
  '#1B5E20', '#2E7D32', '#388E3C', '#27AE60',
  '#4A148C', '#7B1FA2', '#9B59B6', '#E91E63',
  '#B71C1C', '#E74C3C', '#37474F', '#212121',
]

// ── Fuseaux horaires ───────────────────────────────────────────────────────
const timezones = [
  { value: 'Europe/Paris',    label: 'Europe/Paris (UTC+1/+2)' },
  { value: 'Europe/London',   label: 'Europe/London (UTC+0/+1)' },
  { value: 'Europe/Brussels', label: 'Europe/Brussels (UTC+1/+2)' },
  { value: 'Europe/Berlin',   label: 'Europe/Berlin (UTC+1/+2)' },
  { value: 'Europe/Madrid',   label: 'Europe/Madrid (UTC+1/+2)' },
  { value: 'Europe/Rome',     label: 'Europe/Rome (UTC+1/+2)' },
  { value: 'Europe/Zurich',   label: 'Europe/Zurich (UTC+1/+2)' },
  { value: 'America/New_York',   label: 'America/New_York (UTC-5/-4)' },
  { value: 'America/Martinique', label: 'America/Martinique (UTC-4)' },
  { value: 'America/Guadeloupe', label: 'America/Guadeloupe (UTC-4)' },
  { value: 'Indian/Reunion',  label: 'Indian/Reunion (UTC+4)' },
  { value: 'Indian/Mayotte',  label: 'Indian/Mayotte (UTC+3)' },
  { value: 'Pacific/Noumea',  label: 'Pacific/Noumea (UTC+11)' },
  { value: 'Pacific/Tahiti',  label: 'Pacific/Tahiti (UTC-10)' },
]

// ── Logo handling ──────────────────────────────────────────────────────────
const logoPreview    = ref(null)
const isLogoDragging = ref(false)

function handleLogoDrop(e) {
  isLogoDragging.value = false
  const file = e.dataTransfer.files[0]
  if (file) setLogoFile(file)
}

function handleLogoChange(e) {
  const file = e.target.files[0]
  if (file) setLogoFile(file)
  e.target.value = ''
}

function setLogoFile(file) {
  const allowed = ['image/jpeg', 'image/png', 'image/webp']
  if (!allowed.includes(file.type)) return
  if (file.size > 2 * 1024 * 1024) return
  brandingForm.logo = file
  const reader = new FileReader()
  reader.onload = (e) => { logoPreview.value = e.target.result }
  reader.readAsDataURL(file)
}

function clearLogo() {
  brandingForm.logo = null
  logoPreview.value = null
}

function deleteLogo() {
  router.delete(route('settings.company.delete-logo'), {
    preserveScroll: true,
  })
}

// ── Forms ──────────────────────────────────────────────────────────────────
const generalForm = useForm({
  section:     'general',
  name:        props.company.name        || '',
  siret:       props.company.siret       || '',
  address:     props.company.address     || '',
  city:        props.company.city        || '',
  postal_code: props.company.postal_code || '',
  phone:       props.company.phone       || '',
})

const brandingForm = useForm({
  section:       'branding',
  logo:          null,
  primary_color: props.company.primary_color || '#1B4F72',
})

const hrForm = useForm({
  section:             'hr',
  work_hours_per_day:  props.company.work_hours_per_day  ?? 7,
  work_days_per_week:  props.company.work_days_per_week  ?? 5,
  timezone:            props.company.timezone            || 'Europe/Paris',
})

const accountantForm = useForm({
  section:            'accountant',
  accountant_emails:  props.company.accountant_emails?.length
    ? [...props.company.accountant_emails]
    : [],
})

// ── Submit handlers ────────────────────────────────────────────────────────
function saveGeneral() {
  generalForm.post(route('settings.company.update'), { preserveScroll: true })
}

function saveBranding() {
  brandingForm.post(route('settings.company.update'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      logoPreview.value = null
      brandingForm.logo = null
    },
  })
}

function saveHr() {
  hrForm.post(route('settings.company.update'), { preserveScroll: true })
}

function saveAccountant() {
  accountantForm.post(route('settings.company.update'), { preserveScroll: true })
}

// ── Email list helpers ─────────────────────────────────────────────────────
function addEmail() {
  if (accountantForm.accountant_emails.length < 10) {
    accountantForm.accountant_emails.push('')
  }
}

function removeEmail(index) {
  accountantForm.accountant_emails.splice(index, 1)
}
</script>
