<template>
  <div class="min-h-screen bg-[#F8FAFC] flex flex-col">

    <!-- ── En-tête SimpliRH ──────────────────────────────────────────────── -->
    <header class="bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-3">
      <div class="w-8 h-8 rounded-lg flex items-center justify-center"
           style="background: linear-gradient(135deg, #1B4F72 0%, #2E86C1 100%)">
        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
        </svg>
      </div>
      <div>
        <p class="text-sm font-semibold text-[#1B4F72]">SimpliRH</p>
        <p class="text-xs text-gray-500">Signature électronique</p>
      </div>
    </header>

    <!-- ── Contenu principal ─────────────────────────────────────────────── -->
    <main class="flex-1 flex items-start justify-center p-4 sm:p-8">
      <div class="w-full max-w-xl">

        <!-- Statut : déjà signé ─────────────────────────────────────────── -->
        <div v-if="signature.status === 'signed'" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="bg-gradient-to-br from-green-50 to-emerald-50 px-6 py-10 text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
            </div>
            <h1 class="text-xl font-bold text-green-800 mb-2">Document signé</h1>
            <p class="text-green-700 text-sm">
              Vous avez signé ce document le {{ signature.signed_at }}.
            </p>
          </div>
          <div class="px-6 py-5">
            <DocumentInfoCard :document="document" />
          </div>
        </div>

        <!-- Statut : refusé ─────────────────────────────────────────────── -->
        <div v-else-if="signature.status === 'declined'" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="bg-gradient-to-br from-red-50 to-rose-50 px-6 py-10 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </div>
            <h1 class="text-xl font-bold text-red-800 mb-2">Signature refusée</h1>
            <p class="text-red-700 text-sm">Vous avez refusé de signer ce document.</p>
            <p v-if="signature.declined_reason" class="mt-3 text-sm text-gray-600 bg-white rounded-lg px-4 py-2 border border-red-200">
              Motif : {{ signature.declined_reason }}
            </p>
          </div>
        </div>

        <!-- Statut : expiré ─────────────────────────────────────────────── -->
        <div v-else-if="signature.status === 'expired'" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="bg-gradient-to-br from-gray-50 to-slate-50 px-6 py-10 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <h1 class="text-xl font-bold text-gray-700 mb-2">Lien expiré</h1>
            <p class="text-gray-600 text-sm">Ce lien de signature n'est plus valide. Contactez votre responsable RH.</p>
          </div>
        </div>

        <!-- ── Formulaire de signature ─────────────────────────────────── -->
        <template v-else>

          <!-- Bannière flash -->
          <Transition name="slide-down">
            <div v-if="flash?.type" class="mb-4 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2"
                 :class="flash.type === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-blue-50 text-blue-800 border border-blue-200'">
              <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      :d="flash.type === 'success' ? 'M5 13l4 4L19 7' : 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'"/>
              </svg>
              {{ flash.message }}
            </div>
          </Transition>

          <!-- Card document ────────────────────────────────────────────── -->
          <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-4">
            <!-- En-tête document -->
            <div class="px-6 py-5 border-b border-gray-100">
              <div class="flex items-start gap-4">
                <!-- Icône -->
                <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0"
                     style="background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%)">
                  <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                  </svg>
                </div>
                <div class="min-w-0 flex-1">
                  <h2 class="font-semibold text-[#1E293B] text-base truncate">{{ document.name }}</h2>
                  <p class="text-sm text-gray-500 mt-0.5">{{ document.category }} · {{ document.file_size_label }}</p>
                  <p class="text-xs text-gray-400 mt-1">
                    Envoyé par <span class="font-medium text-gray-600">{{ document.uploaded_by }}</span>
                    <span v-if="document.company_name"> · {{ document.company_name }}</span>
                  </p>
                </div>
              </div>

              <!-- Bandeau destinataire -->
              <div class="mt-4 flex items-center gap-3 px-3 py-2.5 bg-[#F8FAFC] rounded-lg border border-gray-200">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0"
                     style="background: linear-gradient(135deg, #1B4F72 0%, #2E86C1 100%)">
                  {{ signer.initials }}
                </div>
                <div>
                  <p class="text-xs text-gray-500">Signataire</p>
                  <p class="text-sm font-medium text-[#1E293B]">{{ signer.name }}</p>
                </div>
                <div class="ml-auto text-right">
                  <p class="text-xs text-gray-500">Expire le</p>
                  <p class="text-xs font-medium text-gray-700">{{ signature.expires_at }}</p>
                </div>
              </div>
            </div>

            <!-- Corps : mode de signature ───────────────────────────────── -->
            <div class="px-6 py-5">

              <!-- Onglets Dessiner / Taper ────────────────────────────── -->
              <div class="flex rounded-xl overflow-hidden border border-gray-200 mb-5">
                <button type="button"
                        @click="mode = 'drawn'"
                        class="flex-1 py-2.5 text-sm font-medium transition-colors flex items-center justify-center gap-2"
                        :class="mode === 'drawn'
                          ? 'bg-[#1B4F72] text-white'
                          : 'bg-white text-gray-600 hover:bg-gray-50'">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                  </svg>
                  Dessiner
                </button>
                <button type="button"
                        @click="mode = 'typed'"
                        class="flex-1 py-2.5 text-sm font-medium transition-colors flex items-center justify-center gap-2 border-l border-gray-200"
                        :class="mode === 'typed'
                          ? 'bg-[#1B4F72] text-white'
                          : 'bg-white text-gray-600 hover:bg-gray-50'">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                  Taper
                </button>
              </div>

              <!-- Canvas de dessin ─────────────────────────────────────── -->
              <div v-show="mode === 'drawn'">
                <p class="text-xs text-gray-500 mb-2 text-center">Signez dans le cadre ci-dessous en maintenant le bouton enfoncé</p>
                <div class="relative rounded-xl border-2 transition-colors overflow-hidden"
                     :class="hasDrawing ? 'border-[#1B4F72]' : 'border-dashed border-gray-300'">
                  <canvas
                    ref="canvasRef"
                    class="w-full block touch-none cursor-crosshair"
                    style="height: 180px"
                    @mousedown="startDraw"
                    @mousemove="draw"
                    @mouseup="stopDraw"
                    @mouseleave="stopDraw"
                    @touchstart.prevent="startDrawTouch"
                    @touchmove.prevent="drawTouch"
                    @touchend="stopDraw"
                  />
                  <p v-if="!hasDrawing" class="absolute inset-0 flex items-center justify-center text-gray-300 text-sm pointer-events-none select-none">
                    Votre signature ici
                  </p>
                </div>
                <button v-if="hasDrawing" type="button"
                        @click="clearCanvas"
                        class="mt-2 text-xs text-gray-500 hover:text-red-600 flex items-center gap-1 transition-colors">
                  <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                  Effacer et recommencer
                </button>
              </div>

              <!-- Signature tapée ──────────────────────────────────────── -->
              <div v-show="mode === 'typed'">
                <p class="text-xs text-gray-500 mb-2">Tapez votre nom complet tel qu'il apparaîtra sur le document</p>
                <input
                  v-model="typedName"
                  type="text"
                  :placeholder="signer.name"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#1B4F72]/30 focus:border-[#1B4F72] transition text-lg"
                  style="font-family: 'Dancing Script', 'Brush Script MT', cursive; font-size: 1.4rem"
                />
                <!-- Aperçu -->
                <div v-if="typedName" class="mt-3 px-4 py-3 bg-[#F8FAFC] rounded-xl border border-gray-200">
                  <p class="text-xs text-gray-500 mb-1">Aperçu</p>
                  <p class="text-[#1B4F72] text-2xl" style="font-family: 'Dancing Script', 'Brush Script MT', cursive">
                    {{ typedName }}
                  </p>
                </div>
              </div>

              <!-- Engagement légal ─────────────────────────────────────── -->
              <div class="mt-5 flex items-start gap-2">
                <input id="consent" v-model="consented" type="checkbox"
                       class="w-4 h-4 mt-0.5 shrink-0 accent-[#1B4F72]" />
                <label for="consent" class="text-xs text-gray-600 leading-relaxed cursor-pointer">
                  En signant ce document, je confirme avoir lu et compris son contenu, et j'accepte que ma signature
                  électronique ait la même valeur juridique qu'une signature manuscrite conformément à la réglementation en vigueur.
                </label>
              </div>

              <!-- Erreur validation -->
              <p v-if="validationError" class="mt-3 text-sm text-red-600 flex items-center gap-1.5">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ validationError }}
              </p>
            </div>

            <!-- Pied de carte : boutons d'action ────────────────────────── -->
            <div class="px-6 py-4 bg-[#F8FAFC] border-t border-gray-100 flex gap-3">
              <!-- Signer -->
              <button
                type="button"
                @click="submitSign"
                :disabled="signing || !consented"
                class="flex-1 py-3 rounded-xl text-sm font-semibold flex items-center justify-center gap-2 transition-all"
                :class="consented
                  ? 'bg-[#27AE60] hover:bg-[#229954] text-white shadow-sm hover:shadow-md active:scale-[0.98]'
                  : 'bg-gray-200 text-gray-400 cursor-not-allowed'">
                <svg v-if="!signing" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                {{ signing ? 'Enregistrement…' : 'Signer le document' }}
              </button>
            </div>
          </div>

          <!-- Bouton refuser (discret, séparé) ─────────────────────────── -->
          <div class="text-center">
            <button type="button" @click="showDeclineModal = true"
                    class="text-sm text-gray-400 hover:text-red-600 underline-offset-2 hover:underline transition-colors">
              Refuser de signer ce document
            </button>
          </div>

        </template>

        <!-- Pied de page légal ──────────────────────────────────────────── -->
        <div class="mt-8 text-center text-xs text-gray-400 space-y-1">
          <p>Signature sécurisée via <strong class="text-gray-500">SimpliRH</strong></p>
          <p>Horodatage · Adresse IP · Empreinte document SHA-256</p>
        </div>

      </div>
    </main>

    <!-- ── Modal refus ────────────────────────────────────────────────────── -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showDeclineModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
             @click.self="showDeclineModal = false">
          <!-- Overlay -->
          <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showDeclineModal = false"/>

          <!-- Panneau -->
          <div class="relative z-10 bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
              <h3 class="font-semibold text-[#1E293B]">Refuser de signer</h3>
              <p class="text-sm text-gray-500 mt-1">Vous pouvez indiquer un motif (optionnel).</p>
            </div>
            <div class="px-6 py-5">
              <textarea
                v-model="declineReason"
                rows="3"
                placeholder="Ex : Je n'ai pas été informé de cette modification…"
                class="w-full px-3 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-red-300 focus:border-red-400 text-sm resize-none transition"
              />
            </div>
            <div class="px-6 py-4 bg-[#F8FAFC] border-t border-gray-100 flex gap-3">
              <button type="button" @click="showDeclineModal = false"
                      class="flex-1 py-2.5 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
                Annuler
              </button>
              <button type="button" @click="submitDecline"
                      :disabled="declining"
                      class="flex-1 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-medium transition flex items-center justify-center gap-2">
                <svg v-if="declining" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                {{ declining ? 'Envoi…' : 'Confirmer le refus' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

// ── Props ─────────────────────────────────────────────────────────────────
const props = defineProps({
  signature: { type: Object, required: true },
  document:  { type: Object, required: true },
  signer:    { type: Object, required: true },
})

// ── Flash ─────────────────────────────────────────────────────────────────
const flash = computed(() => usePage().props.flash)

// ── Mode signature ────────────────────────────────────────────────────────
const mode        = ref('drawn')
const consented   = ref(false)
const typedName   = ref('')
const validationError = ref('')

// ── Canvas ────────────────────────────────────────────────────────────────
const canvasRef  = ref(null)
const isDrawing  = ref(false)
const hasDrawing = ref(false)
let ctx = null

onMounted(() => {
  nextTick(initCanvas)
})

function initCanvas() {
  const canvas = canvasRef.value
  if (!canvas) return
  // Résolution physique (Retina)
  const rect = canvas.getBoundingClientRect()
  const dpr  = window.devicePixelRatio || 1
  canvas.width  = rect.width  * dpr
  canvas.height = rect.height * dpr
  ctx = canvas.getContext('2d')
  ctx.scale(dpr, dpr)
  ctx.strokeStyle = '#1B4F72'
  ctx.lineWidth   = 2.5
  ctx.lineCap     = 'round'
  ctx.lineJoin    = 'round'
}

function getPos(e, canvas) {
  const rect = canvas.getBoundingClientRect()
  return {
    x: e.clientX - rect.left,
    y: e.clientY - rect.top,
  }
}

function startDraw(e) {
  isDrawing.value = true
  const pos = getPos(e, canvasRef.value)
  ctx.beginPath()
  ctx.moveTo(pos.x, pos.y)
}

function draw(e) {
  if (!isDrawing.value) return
  const pos = getPos(e, canvasRef.value)
  ctx.lineTo(pos.x, pos.y)
  ctx.stroke()
  hasDrawing.value = true
}

function stopDraw() {
  isDrawing.value = false
}

function startDrawTouch(e) {
  const touch = e.touches[0]
  const pos   = getPos(touch, canvasRef.value)
  isDrawing.value = true
  ctx.beginPath()
  ctx.moveTo(pos.x, pos.y)
}

function drawTouch(e) {
  if (!isDrawing.value) return
  const touch = e.touches[0]
  const pos   = getPos(touch, canvasRef.value)
  ctx.lineTo(pos.x, pos.y)
  ctx.stroke()
  hasDrawing.value = true
}

function clearCanvas() {
  const canvas = canvasRef.value
  ctx.clearRect(0, 0, canvas.width, canvas.height)
  hasDrawing.value = false
}

// ── Soumission signature ──────────────────────────────────────────────────
const signing = ref(false)

function submitSign() {
  validationError.value = ''

  let data = ''
  let type = mode.value

  if (type === 'drawn') {
    if (!hasDrawing.value) {
      validationError.value = 'Veuillez dessiner votre signature dans le cadre.'
      return
    }
    data = canvasRef.value.toDataURL('image/png')
  } else {
    if (!typedName.value.trim()) {
      validationError.value = 'Veuillez saisir votre nom complet.'
      return
    }
    data = typedName.value.trim()
  }

  if (!consented.value) {
    validationError.value = 'Veuillez cocher la case d\'engagement.'
    return
  }

  signing.value = true
  router.post(
    route('signature.sign', props.signature.token),
    { type, data },
    {
      preserveScroll: true,
      onFinish: () => { signing.value = false },
    }
  )
}

// ── Refus ─────────────────────────────────────────────────────────────────
const showDeclineModal = ref(false)
const declineReason    = ref('')
const declining        = ref(false)

function submitDecline() {
  declining.value = true
  router.post(
    route('signature.decline', props.signature.token),
    { reason: declineReason.value || null },
    {
      preserveScroll: true,
      onFinish:  () => { declining.value = false },
      onSuccess: () => { showDeclineModal.value = false },
    }
  )
}
</script>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active {
  transition: all 0.3s ease;
}
.slide-down-enter-from, .slide-down-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
