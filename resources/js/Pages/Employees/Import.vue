<template>
  <AppLayout title="Importer des collaborateurs" :back-url="route('employees.index')">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6 space-y-6">

      <!-- Header -->
      <div class="flex items-center gap-3">
        <Link :href="route('employees.index')" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
        </Link>
        <div>
          <h1 class="text-2xl font-bold text-slate-900" style="font-family: 'Plus Jakarta Sans', sans-serif;">
            Importer des collaborateurs
          </h1>
          <p class="text-sm text-slate-500 mt-0.5">Importez jusqu'à 500 collaborateurs en une seule fois via un fichier CSV</p>
        </div>
      </div>

      <!-- Stepper -->
      <div class="flex items-center gap-0">
        <template v-for="(s, i) in steps" :key="s.id">
          <div class="flex items-center gap-2">
            <div
              class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold transition-all"
              :class="{
                'bg-primary-700 text-white shadow-md': currentStep === s.id,
                'bg-green-500 text-white': currentStep > s.id,
                'bg-slate-200 text-slate-500': currentStep < s.id,
              }"
            >
              <svg v-if="currentStep > s.id" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
              </svg>
              <span v-else>{{ i + 1 }}</span>
            </div>
            <span
              class="text-sm font-medium hidden sm:block"
              :class="{
                'text-primary-700': currentStep === s.id,
                'text-green-600': currentStep > s.id,
                'text-slate-400': currentStep < s.id,
              }"
            >{{ s.label }}</span>
          </div>
          <div v-if="i < steps.length - 1" class="flex-1 h-px mx-3" :class="currentStep > s.id ? 'bg-green-400' : 'bg-slate-200'" />
        </template>
      </div>

      <!-- ── ÉTAPE 1 : Préparation & Upload ─────────────────────────────────── -->
      <div v-if="currentStep === 1" class="space-y-5">

        <!-- Template download card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <h2 class="text-base font-semibold text-slate-900">Étape 1 — Téléchargez le modèle CSV</h2>
              <p class="text-sm text-slate-500 mt-1">
                Commencez par télécharger notre modèle, remplissez-le avec vos données, puis importez-le ci-dessous.
              </p>
              <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-500">
                <span v-for="col in templateColumns" :key="col.key"
                  class="inline-flex items-center gap-1 px-2 py-1 bg-slate-100 rounded-lg font-mono"
                >
                  <span :class="col.required ? 'text-red-500' : 'text-slate-400'">{{ col.required ? '*' : '○' }}</span>
                  {{ col.key }}
                </span>
              </div>
              <p class="text-xs text-slate-400 mt-2"><span class="text-red-500">*</span> Champs obligatoires</p>
            </div>
            <button
              @click="downloadTemplate"
              class="flex-shrink-0 flex items-center gap-2 px-4 py-2 bg-primary-700 hover:bg-primary-800 text-white text-sm font-semibold rounded-xl transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
              </svg>
              Télécharger
            </button>
          </div>
        </div>

        <!-- Valeurs acceptées -->
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
          <h3 class="text-sm font-semibold text-amber-800 mb-2">Valeurs acceptées pour les colonnes à choix</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-amber-700">
            <div>
              <span class="font-medium font-mono">role</span> :
              <span class="font-mono bg-amber-100 px-1 py-0.5 rounded">admin</span>
              <span class="font-mono bg-amber-100 px-1 py-0.5 rounded mx-1">manager</span>
              <span class="font-mono bg-amber-100 px-1 py-0.5 rounded">employee</span>
            </div>
            <div>
              <span class="font-medium font-mono">contrat</span> :
              <span v-for="c in contractTypes" :key="c.value" class="font-mono bg-amber-100 px-1 py-0.5 rounded mx-0.5">{{ c.value }}</span>
            </div>
            <div>
              <span class="font-medium font-mono">departement</span> : nom exact du département ou laisser vide
            </div>
            <div>
              <span class="font-medium font-mono">date_embauche</span> : format <span class="font-mono bg-amber-100 px-1 py-0.5 rounded">YYYY-MM-DD</span>
            </div>
          </div>
        </div>

        <!-- Drop zone -->
        <div
          class="bg-white rounded-2xl border-2 border-dashed transition-all p-10 text-center cursor-pointer select-none"
          :class="isDragging ? 'border-primary-500 bg-primary-50' : 'border-slate-300 hover:border-primary-400 hover:bg-slate-50'"
          @dragover.prevent="isDragging = true"
          @dragleave.prevent="isDragging = false"
          @drop.prevent="handleDrop"
          @click="$refs.fileInput.click()"
        >
          <input ref="fileInput" type="file" accept=".csv,text/csv" class="hidden" @change="handleFileChange" />
          <div class="flex flex-col items-center gap-3">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center" :class="isDragging ? 'bg-primary-100' : 'bg-slate-100'">
              <svg class="w-7 h-7" :class="isDragging ? 'text-primary-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
              </svg>
            </div>
            <div>
              <p class="text-sm font-semibold text-slate-700">
                {{ isDragging ? 'Déposez le fichier ici' : 'Glissez-déposez votre fichier CSV ici' }}
              </p>
              <p class="text-xs text-slate-400 mt-1">ou cliquez pour parcourir vos fichiers</p>
            </div>
            <span class="text-xs text-slate-400 px-3 py-1 bg-slate-100 rounded-full">.csv — max 500 lignes</span>
          </div>
        </div>

        <!-- Parse error -->
        <div v-if="parseError" class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-xl">
          <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <p class="text-sm text-red-700">{{ parseError }}</p>
        </div>
      </div>

      <!-- ── ÉTAPE 2 : Prévisualisation & Validation ─────────────────────────── -->
      <div v-if="currentStep === 2" class="space-y-5">

        <!-- Summary bar -->
        <div class="grid grid-cols-3 gap-4">
          <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-slate-900">{{ parsedRows.length }}</div>
            <div class="text-xs text-slate-500 mt-0.5">Lignes détectées</div>
          </div>
          <div class="bg-white rounded-xl border border-green-200 shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-green-600">{{ validCount }}</div>
            <div class="text-xs text-slate-500 mt-0.5">Valides</div>
          </div>
          <div class="bg-white rounded-xl border border-red-200 shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-red-500">{{ errorCount }}</div>
            <div class="text-xs text-slate-500 mt-0.5">Erreurs</div>
          </div>
        </div>

        <!-- Filter tabs -->
        <div class="flex items-center gap-2">
          <button
            v-for="tab in previewTabs" :key="tab.id"
            @click="previewFilter = tab.id"
            class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
            :class="previewFilter === tab.id
              ? 'bg-primary-700 text-white'
              : 'text-slate-600 hover:bg-slate-100'"
          >{{ tab.label }} <span class="ml-1 opacity-75">({{ tab.count }})</span></button>
        </div>

        <!-- Preview table -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-slate-200 bg-slate-50">
                  <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">#</th>
                  <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Statut</th>
                  <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Prénom</th>
                  <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Nom</th>
                  <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Email</th>
                  <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Rôle</th>
                  <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Contrat</th>
                  <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Département</th>
                  <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Embauche</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <template v-for="row in filteredRows" :key="row._index">
                  <tr
                    class="transition-colors"
                    :class="{
                      'bg-red-50 hover:bg-red-100': row._status === 'error',
                      'bg-amber-50 hover:bg-amber-100': row._status === 'warning',
                      'hover:bg-slate-50': row._status === 'ok',
                    }"
                  >
                    <td class="px-3 py-2.5 text-slate-400 text-xs font-mono">{{ row._index }}</td>
                    <td class="px-3 py-2.5">
                      <span
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold"
                        :class="{
                          'bg-green-100 text-green-700': row._status === 'ok',
                          'bg-amber-100 text-amber-700': row._status === 'warning',
                          'bg-red-100 text-red-700': row._status === 'error',
                        }"
                      >
                        <span>{{ row._status === 'ok' ? '✓ Valide' : row._status === 'warning' ? '⚠ Attention' : '✗ Erreur' }}</span>
                      </span>
                    </td>
                    <td class="px-3 py-2.5 font-medium text-slate-800">{{ row.first_name || '—' }}</td>
                    <td class="px-3 py-2.5 font-medium text-slate-800">{{ row.last_name || '—' }}</td>
                    <td class="px-3 py-2.5 text-slate-600 max-w-[180px] truncate">{{ row.email || '—' }}</td>
                    <td class="px-3 py-2.5">
                      <span v-if="row.role" class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded text-xs font-mono">{{ row.role }}</span>
                      <span v-else class="text-red-400 text-xs">manquant</span>
                    </td>
                    <td class="px-3 py-2.5">
                      <span v-if="row.contract_type" class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded text-xs font-mono uppercase">{{ row.contract_type }}</span>
                      <span v-else class="text-red-400 text-xs">manquant</span>
                    </td>
                    <td class="px-3 py-2.5 text-slate-600 text-xs">{{ row._dept_name || '—' }}</td>
                    <td class="px-3 py-2.5 text-slate-500 text-xs font-mono">{{ row.hire_date || '—' }}</td>
                  </tr>
                  <!-- Error detail row -->
                  <tr v-if="row._errors.length" :class="row._status === 'error' ? 'bg-red-50' : 'bg-amber-50'">
                    <td colspan="9" class="px-3 pb-2.5 pt-0">
                      <ul class="space-y-0.5">
                        <li
                          v-for="(err, ei) in row._errors"
                          :key="ei"
                          class="text-xs flex items-start gap-1.5"
                          :class="err.type === 'error' ? 'text-red-600' : 'text-amber-600'"
                        >
                          <span class="mt-0.5">{{ err.type === 'error' ? '✗' : '⚠' }}</span>
                          <span>{{ err.message }}</span>
                        </li>
                      </ul>
                    </td>
                  </tr>
                </template>
                <tr v-if="filteredRows.length === 0">
                  <td colspan="9" class="px-4 py-8 text-center text-slate-400 text-sm">Aucune ligne dans cette catégorie</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Error notice -->
        <div v-if="errorCount > 0" class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-xl">
          <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <p class="text-sm text-red-700">
            <strong>{{ errorCount }} ligne(s) contiennent des erreurs</strong> et seront ignorées à l'import.
            Les <strong>{{ validCount }} lignes valides</strong> seront importées.
          </p>
        </div>

        <!-- Navigation -->
        <div class="flex items-center justify-between pt-2">
          <button
            @click="reset"
            class="flex items-center gap-2 px-4 py-2.5 border border-slate-200 rounded-xl text-slate-600 hover:bg-slate-50 text-sm font-medium transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Changer de fichier
          </button>
          <button
            v-if="validCount > 0"
            @click="currentStep = 3"
            class="flex items-center gap-2 px-5 py-2.5 bg-primary-700 hover:bg-primary-800 text-white text-sm font-semibold rounded-xl transition-colors"
          >
            Continuer avec {{ validCount }} collaborateur(s)
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- ── ÉTAPE 3 : Confirmation & Import ────────────────────────────────── -->
      <div v-if="currentStep === 3" class="space-y-5">

        <!-- Récapitulatif -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
          <h2 class="text-base font-semibold text-slate-900 mb-4">Récapitulatif de l'import</h2>
          <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
            <div>
              <dt class="text-xs text-slate-500">À importer</dt>
              <dd class="mt-1 text-2xl font-bold text-primary-700">{{ validRows.length }}</dd>
            </div>
            <div>
              <dt class="text-xs text-slate-500">Ignorées (erreurs)</dt>
              <dd class="mt-1 text-2xl font-bold text-red-500">{{ errorCount }}</dd>
            </div>
            <div>
              <dt class="text-xs text-slate-500">Admins</dt>
              <dd class="mt-1 text-2xl font-bold text-slate-700">{{ validRows.filter(r => r.role === 'admin').length }}</dd>
            </div>
            <div>
              <dt class="text-xs text-slate-500">Employés</dt>
              <dd class="mt-1 text-2xl font-bold text-slate-700">{{ validRows.filter(r => r.role === 'employee').length }}</dd>
            </div>
          </dl>
        </div>

        <!-- Info email -->
        <div class="flex items-start gap-3 p-4 bg-blue-50 border border-blue-200 rounded-xl">
          <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
          </svg>
          <p class="text-sm text-blue-700">
            Chaque collaborateur recevra un <strong>email de bienvenue</strong> avec un lien pour définir son mot de passe.
            Les comptes seront activés dès la première connexion.
          </p>
        </div>

        <!-- Aperçu des comptes à créer -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-700">Comptes à créer</h3>
            <span class="text-xs text-slate-400">{{ validRows.length }} collaborateur(s)</span>
          </div>
          <div class="max-h-64 overflow-y-auto divide-y divide-slate-100">
            <div
              v-for="row in validRows"
              :key="row._index"
              class="flex items-center gap-3 px-5 py-3"
            >
              <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0">
                <span class="text-xs font-bold text-primary-700">
                  {{ row.first_name.charAt(0).toUpperCase() }}{{ row.last_name.charAt(0).toUpperCase() }}
                </span>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-800 truncate">{{ row.first_name }} {{ row.last_name }}</p>
                <p class="text-xs text-slate-400 truncate">{{ row.email }}</p>
              </div>
              <div class="flex items-center gap-2 flex-shrink-0">
                <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-xs font-mono">{{ row.role }}</span>
                <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-xs font-mono uppercase">{{ row.contract_type }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Server error -->
        <div v-if="importError" class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-xl">
          <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <p class="text-sm text-red-700">{{ importError }}</p>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between pt-2">
          <button
            @click="currentStep = 2"
            :disabled="importing"
            class="flex items-center gap-2 px-4 py-2.5 border border-slate-200 rounded-xl text-slate-600 hover:bg-slate-50 text-sm font-medium transition-colors disabled:opacity-50"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour
          </button>
          <button
            @click="runImport"
            :disabled="importing"
            class="flex items-center gap-2 px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
          >
            <svg v-if="importing" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            {{ importing ? 'Import en cours...' : `Importer ${validRows.length} collaborateur(s)` }}
          </button>
        </div>
      </div>

      <!-- ── ÉTAPE 4 : Succès ───────────────────────────────────────────────── -->
      <div v-if="currentStep === 4" class="space-y-5">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-10 text-center space-y-4">
          <div class="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center mx-auto">
            <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <h2 class="text-xl font-bold text-slate-900" style="font-family: 'Plus Jakarta Sans', sans-serif;">
            Import réussi !
          </h2>
          <p class="text-slate-500 max-w-md mx-auto text-sm">
            <strong class="text-slate-800">{{ importResult.created }}</strong> collaborateur(s) ont été créés avec succès.
            <span v-if="importResult.skipped > 0">
              <strong class="text-slate-800">{{ importResult.skipped }}</strong> ligne(s) ont été ignorées (email déjà existant).
            </span>
            Des emails d'activation ont été envoyés à chaque nouveau collaborateur.
          </p>
          <div class="flex items-center justify-center gap-3 pt-2">
            <Link
              :href="route('employees.index')"
              class="flex items-center gap-2 px-5 py-2.5 bg-primary-700 hover:bg-primary-800 text-white text-sm font-semibold rounded-xl transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              Voir les collaborateurs
            </Link>
            <button
              @click="reset"
              class="px-5 py-2.5 border border-slate-200 rounded-xl text-slate-600 hover:bg-slate-50 text-sm font-medium transition-colors"
            >
              Nouvel import
            </button>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
  departments: { type: Array, default: () => [] },
  result:      { type: Object, default: null },
})

// ── Init from server result (after redirect) ───────────────────────────────
onMounted(() => {
  if (props.result) {
    importResult.value = { created: props.result.created ?? 0, skipped: props.result.skipped ?? 0 }
    currentStep.value  = 4
  }
})

// ── State ──────────────────────────────────────────────────────────────────
const currentStep  = ref(1)
const isDragging   = ref(false)
const parseError   = ref(null)
const parsedRows   = ref([])   // rows enriched with _status, _errors
const previewFilter = ref('all')
const importing    = ref(false)
const importError  = ref(null)
const importResult = ref({ created: 0, skipped: 0 })

// ── Constants ──────────────────────────────────────────────────────────────
const steps = [
  { id: 1, label: 'Préparer' },
  { id: 2, label: 'Prévisualiser' },
  { id: 3, label: 'Confirmer' },
]

const VALID_ROLES     = ['admin', 'manager', 'employee']
const VALID_CONTRACTS = ['cdi', 'cdd', 'interim', 'stage', 'alternance']

const contractTypes = VALID_CONTRACTS.map(v => ({ value: v }))

const templateColumns = [
  { key: 'prenom',        required: true  },
  { key: 'nom',           required: true  },
  { key: 'email',         required: true  },
  { key: 'role',          required: true  },
  { key: 'contrat',       required: true  },
  { key: 'departement',   required: false },
  { key: 'date_embauche', required: false },
  { key: 'heures_semaine',required: false },
  { key: 'telephone',     required: false },
  { key: 'matricule',     required: false },
]

// ── Computed ───────────────────────────────────────────────────────────────
const validCount = computed(() => parsedRows.value.filter(r => r._status !== 'error').length)
const errorCount = computed(() => parsedRows.value.filter(r => r._status === 'error').length)
const validRows  = computed(() => parsedRows.value.filter(r => r._status !== 'error'))

const previewTabs = computed(() => [
  { id: 'all',     label: 'Toutes',  count: parsedRows.value.length },
  { id: 'ok',      label: 'Valides', count: parsedRows.value.filter(r => r._status === 'ok').length },
  { id: 'warning', label: 'Avertissements', count: parsedRows.value.filter(r => r._status === 'warning').length },
  { id: 'error',   label: 'Erreurs', count: parsedRows.value.filter(r => r._status === 'error').length },
])

const filteredRows = computed(() => {
  if (previewFilter.value === 'all') return parsedRows.value
  return parsedRows.value.filter(r => r._status === previewFilter.value)
})

// ── CSV Template download ──────────────────────────────────────────────────
function downloadTemplate() {
  const header = 'prenom,nom,email,role,contrat,departement,date_embauche,heures_semaine,telephone,matricule'
  const example = 'Marie,Dupont,marie.dupont@entreprise.fr,employee,cdi,Développement,2024-01-15,35,0612345678,EMP001'
  const csv = `${header}\n${example}\n`
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url  = URL.createObjectURL(blob)
  const a    = document.createElement('a')
  a.href     = url
  a.download = 'modele_import_collaborateurs.csv'
  a.click()
  URL.revokeObjectURL(url)
}

// ── File handling ──────────────────────────────────────────────────────────
function handleDrop(e) {
  isDragging.value = false
  const file = e.dataTransfer.files[0]
  if (file) processFile(file)
}

function handleFileChange(e) {
  const file = e.target.files[0]
  if (file) processFile(file)
  e.target.value = ''
}

function processFile(file) {
  parseError.value = null

  if (!file.name.toLowerCase().endsWith('.csv') && file.type !== 'text/csv') {
    parseError.value = 'Veuillez sélectionner un fichier CSV (.csv)'
    return
  }

  const reader = new FileReader()
  reader.onload = (e) => {
    const text = e.target.result
    parseCSV(text)
  }
  reader.onerror = () => {
    parseError.value = 'Impossible de lire le fichier.'
  }
  reader.readAsText(file, 'UTF-8')
}

// ── CSV Parser ─────────────────────────────────────────────────────────────
function parseCSV(text) {
  // Normalize line endings
  const lines = text.replace(/\r\n/g, '\n').replace(/\r/g, '\n').split('\n')
  const nonEmpty = lines.filter(l => l.trim())

  if (nonEmpty.length < 2) {
    parseError.value = 'Le fichier CSV est vide ou ne contient qu\'un en-tête.'
    return
  }

  if (nonEmpty.length > 501) {
    parseError.value = `Le fichier contient ${nonEmpty.length - 1} lignes, ce qui dépasse la limite de 500.`
    return
  }

  const headers = splitCSVLine(nonEmpty[0]).map(h => h.trim().toLowerCase())

  // Check required columns
  const required = ['prenom', 'nom', 'email', 'role', 'contrat']
  const missing  = required.filter(col => !headers.includes(col))
  if (missing.length > 0) {
    parseError.value = `Colonnes manquantes dans l'en-tête : ${missing.join(', ')}`
    return
  }

  // Build department name → id map
  const deptMap = {}
  props.departments.forEach(d => {
    deptMap[d.name.toLowerCase().trim()] = d.id
  })

  const rows = []
  for (let i = 1; i < nonEmpty.length; i++) {
    const cells  = splitCSVLine(nonEmpty[i])
    const obj    = {}
    headers.forEach((h, idx) => { obj[h] = (cells[idx] || '').trim() })

    const row = validateRow(obj, i, deptMap)
    rows.push(row)
  }

  parsedRows.value  = rows
  previewFilter.value = 'all'
  currentStep.value = 2
}

function splitCSVLine(line) {
  const result = []
  let current  = ''
  let inQuotes = false

  for (let i = 0; i < line.length; i++) {
    const ch = line[i]
    if (ch === '"') {
      if (inQuotes && line[i + 1] === '"') {
        current += '"'
        i++
      } else {
        inQuotes = !inQuotes
      }
    } else if (ch === ',' && !inQuotes) {
      result.push(current)
      current = ''
    } else {
      current += ch
    }
  }
  result.push(current)
  return result
}

// ── Row validation ─────────────────────────────────────────────────────────
function validateRow(obj, lineNum, deptMap) {
  const errors = []

  const firstName    = obj['prenom']   || ''
  const lastName     = obj['nom']      || ''
  const email        = obj['email']    || ''
  const role         = obj['role']     || ''
  const contractRaw  = obj['contrat']  || ''
  const deptName     = obj['departement'] || ''
  const hireDateRaw  = obj['date_embauche'] || ''
  const hoursRaw     = obj['heures_semaine'] || ''
  const phone        = obj['telephone']  || ''
  const employeeId   = obj['matricule']  || ''

  // Required fields
  if (!firstName) errors.push({ type: 'error', message: 'Prénom manquant' })
  else if (firstName.length > 255) errors.push({ type: 'error', message: 'Prénom trop long (max 255 caractères)' })

  if (!lastName) errors.push({ type: 'error', message: 'Nom manquant' })
  else if (lastName.length > 255) errors.push({ type: 'error', message: 'Nom trop long (max 255 caractères)' })

  if (!email) {
    errors.push({ type: 'error', message: 'Email manquant' })
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    errors.push({ type: 'error', message: `Email invalide : "${email}"` })
  } else if (email.length > 255) {
    errors.push({ type: 'error', message: 'Email trop long (max 255 caractères)' })
  }

  const roleLower = role.toLowerCase()
  if (!role) {
    errors.push({ type: 'error', message: 'Rôle manquant' })
  } else if (!VALID_ROLES.includes(roleLower)) {
    errors.push({ type: 'error', message: `Rôle invalide : "${role}" — valeurs : admin, manager, employee` })
  }

  const contractLower = contractRaw.toLowerCase()
  if (!contractRaw) {
    errors.push({ type: 'error', message: 'Type de contrat manquant' })
  } else if (!VALID_CONTRACTS.includes(contractLower)) {
    errors.push({ type: 'error', message: `Contrat invalide : "${contractRaw}" — valeurs : ${VALID_CONTRACTS.join(', ')}` })
  }

  // Optional: department
  let deptId   = null
  let deptDisplayName = ''
  if (deptName) {
    const found = deptMap[deptName.toLowerCase().trim()]
    if (found !== undefined) {
      deptId = found
      deptDisplayName = deptName
    } else {
      errors.push({ type: 'warning', message: `Département introuvable : "${deptName}" — sera ignoré` })
      deptDisplayName = `⚠ ${deptName}`
    }
  }

  // Optional: hire date
  let hireDate = null
  if (hireDateRaw) {
    const d = new Date(hireDateRaw)
    if (isNaN(d.getTime())) {
      errors.push({ type: 'warning', message: `Date d'embauche invalide : "${hireDateRaw}" — attendu YYYY-MM-DD` })
    } else {
      hireDate = hireDateRaw
    }
  }

  // Optional: weekly hours
  let weeklyHours = null
  if (hoursRaw) {
    const h = parseFloat(hoursRaw)
    if (isNaN(h) || h < 1 || h > 60) {
      errors.push({ type: 'warning', message: `Heures hebdomadaires invalides : "${hoursRaw}" — doit être entre 1 et 60` })
    } else {
      weeklyHours = h
    }
  }

  // Optional: phone
  if (phone && phone.length > 20) {
    errors.push({ type: 'warning', message: 'Téléphone trop long (max 20 caractères) — sera tronqué' })
  }

  const hasError   = errors.some(e => e.type === 'error')
  const hasWarning = errors.some(e => e.type === 'warning')

  return {
    _index:        lineNum,
    _status:       hasError ? 'error' : hasWarning ? 'warning' : 'ok',
    _errors:       errors,
    _dept_name:    deptDisplayName,
    first_name:    firstName,
    last_name:     lastName,
    email:         email,
    role:          roleLower || role,
    contract_type: contractLower || contractRaw,
    department_id: deptId,
    hire_date:     hireDate,
    weekly_hours:  weeklyHours,
    phone:         phone ? phone.substring(0, 20) : null,
    employee_id:   employeeId ? employeeId.substring(0, 50) : null,
  }
}

// ── Import ─────────────────────────────────────────────────────────────────
function runImport() {
  importing.value  = true
  importError.value = null

  const payload = validRows.value.map(r => ({
    first_name:    r.first_name,
    last_name:     r.last_name,
    email:         r.email,
    role:          r.role,
    contract_type: r.contract_type,
    department_id: r.department_id,
    hire_date:     r.hire_date,
    weekly_hours:  r.weekly_hours,
    phone:         r.phone,
    employee_id:   r.employee_id,
  }))

  router.post(route('employees.import.confirm'), { employees: payload }, {
    onError: (errors) => {
      importError.value = Object.values(errors)[0] || 'Une erreur est survenue lors de l\'import.'
      importing.value = false
    },
    onFinish: () => {
      importing.value = false
    },
  })
}

// ── Reset ──────────────────────────────────────────────────────────────────
function reset() {
  currentStep.value   = 1
  parsedRows.value    = []
  parseError.value    = null
  importError.value   = null
  previewFilter.value = 'all'
  importing.value     = false
  importResult.value  = { created: 0, skipped: 0 }
}
</script>
