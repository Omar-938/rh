<template>
  <div class="min-h-screen bg-white text-slate-900 antialiased" style="font-family: 'Inter', sans-serif;">
    <Head title="SimpliRH — Logiciel RH pour TPE et PME françaises" />

    <!-- ══ NAVIGATION ════════════════════════════════════════════════════════ -->
    <header
      class="fixed top-0 inset-x-0 z-50 transition-all duration-300"
      :class="scrolled ? 'bg-white/95 backdrop-blur-md shadow-sm border-b border-slate-100' : 'bg-transparent'"
    >
      <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-6">
        <!-- Logo -->
        <a href="/" class="flex items-center gap-2.5 group flex-shrink-0">
          <div class="w-8 h-8 rounded-xl flex items-center justify-center shadow-sm"
            :class="scrolled ? 'bg-[#1B4F72]' : 'bg-white/20 backdrop-blur'">
            <span class="font-bold text-sm" :class="scrolled ? 'text-white' : 'text-white'">S</span>
          </div>
          <span class="font-bold text-base" :class="scrolled ? 'text-slate-900' : 'text-white'">SimpliRH</span>
        </a>

        <!-- Nav links desktop -->
        <nav class="hidden md:flex items-center gap-1">
          <a v-for="link in navLinks" :key="link.href" :href="link.href"
            class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors"
            :class="scrolled ? 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' : 'text-white/80 hover:text-white hover:bg-white/10'"
          >{{ link.label }}</a>
        </nav>

        <!-- CTA -->
        <div class="flex items-center gap-3 flex-shrink-0">
          <!-- Mobile : bouton Connexion uniquement -->
          <a href="/login"
            class="sm:hidden px-4 py-2 text-sm font-semibold rounded-xl transition-all shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0"
            :class="scrolled ? 'bg-[#1B4F72] hover:bg-[#154060] text-white' : 'bg-white text-[#1B4F72] hover:bg-white/90'"
          >
            Connexion
          </a>

          <!-- Desktop : lien Se connecter + bouton Essai gratuit -->
          <a href="/login" class="hidden sm:block text-sm font-medium transition-colors"
            :class="scrolled ? 'text-slate-600 hover:text-slate-900' : 'text-white/80 hover:text-white'">
            Se connecter
          </a>
          <a href="/register"
            class="hidden sm:block px-4 py-2 text-sm font-semibold rounded-xl transition-all shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0"
            :class="scrolled ? 'bg-[#1B4F72] hover:bg-[#154060] text-white' : 'bg-white text-[#1B4F72] hover:bg-white/90'"
          >
            Essai gratuit
          </a>
        </div>
      </div>
    </header>

    <!-- ══ HERO ═══════════════════════════════════════════════════════════════ -->
    <section class="relative overflow-hidden min-h-screen flex items-center pt-16"
      style="background: linear-gradient(135deg, #0d2137 0%, #1B4F72 50%, #2E86C1 100%);">

      <!-- Background texture -->
      <div class="absolute inset-0 opacity-5" :style="{ backgroundImage: bgTexture }"></div>

      <div class="max-w-6xl mx-auto px-4 sm:px-6 py-20 lg:py-28 w-full">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">

          <!-- Left: copy -->
          <div class="text-white text-center lg:text-left">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold bg-white/10 border border-white/20 text-white/90 mb-6">
              <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
              Essai gratuit 14 jours — Sans carte bancaire
            </div>

            <h1 class="text-4xl sm:text-5xl lg:text-5xl xl:text-6xl font-extrabold leading-tight mb-6"
              style="font-family: 'Plus Jakarta Sans', sans-serif; letter-spacing: -0.02em;">
              La RH de votre équipe,<br>
              <span style="background: linear-gradient(90deg, #7DD3FC, #38BDF8); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">enfin centralisée.</span>
            </h1>

            <p class="text-white/70 text-lg leading-relaxed mb-8 max-w-lg mx-auto lg:mx-0">
              Congés, pointage, bulletins de paie, heures sup et signatures — tout dans un logiciel RH simple et complet, pensé pour les TPE et PME françaises.
            </p>

            <!-- CTAs -->
            <div class="flex flex-col sm:flex-row gap-3 justify-center lg:justify-start mb-8">
              <a href="/register"
                class="group inline-flex items-center justify-center gap-2 px-7 py-4 bg-white text-[#1B4F72] font-bold text-base rounded-2xl shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all duration-200 active:translate-y-0"
              >
                Démarrer gratuitement
                <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
              </a>
              <a href="#demo"
                class="inline-flex items-center justify-center gap-2 px-7 py-4 bg-white/10 border border-white/20 text-white font-semibold text-base rounded-2xl hover:bg-white/20 transition-all duration-200"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Voir le produit
              </a>
            </div>

            <!-- Social proof -->
            <div class="flex items-center gap-4 justify-center lg:justify-start">
              <div class="flex -space-x-2">
                <div v-for="(c, i) in avatarColors" :key="i"
                  class="w-8 h-8 rounded-full border-2 border-[#1B4F72] flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                  :style="{ backgroundColor: c }">{{ avatarInitials[i] }}</div>
              </div>
              <div>
                <div class="flex items-center gap-1">
                  <span v-for="i in 5" :key="i" class="text-amber-400 text-sm">★</span>
                </div>
                <p class="text-white/60 text-xs mt-0.5">+200 entreprises françaises nous font confiance</p>
              </div>
            </div>
          </div>

          <!-- Right: product mockup -->
          <div class="hidden lg:block relative">
            <div class="relative mx-auto" style="max-width: 520px;">
              <!-- Glow -->
              <div class="absolute -inset-8 rounded-3xl opacity-20 blur-2xl" style="background: radial-gradient(circle, #38BDF8, transparent 70%);"></div>

              <!-- Browser chrome -->
              <div class="relative bg-white rounded-2xl shadow-2xl overflow-hidden border border-white/20">
                <!-- Browser bar -->
                <div class="bg-slate-100 px-4 py-2.5 flex items-center gap-2">
                  <div class="flex gap-1.5">
                    <div class="w-3 h-3 rounded-full bg-red-400"></div>
                    <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                    <div class="w-3 h-3 rounded-full bg-green-400"></div>
                  </div>
                  <div class="flex-1 mx-3 bg-white rounded-md px-3 py-1 text-xs text-slate-400 font-mono">
                    app.simpli-rh.com/dashboard
                  </div>
                </div>

                <!-- App UI mockup -->
                <div class="flex" style="height: 360px;">
                  <!-- Sidebar mini -->
                  <div class="w-14 flex-shrink-0 flex flex-col items-center py-3 gap-2" style="background: #1B4F72;">
                    <div class="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center mb-2">
                      <span class="text-white text-xs font-bold">S</span>
                    </div>
                    <div v-for="i in 6" :key="i" class="w-8 h-8 rounded-xl flex items-center justify-center"
                      :class="i === 1 ? 'bg-white/20' : 'opacity-40'">
                      <div class="w-4 h-4 rounded bg-white/60"></div>
                    </div>
                  </div>

                  <!-- Main content -->
                  <div class="flex-1 bg-slate-50 p-4 overflow-hidden">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-4">
                      <div>
                        <div class="h-4 w-32 bg-slate-200 rounded mb-1.5"></div>
                        <div class="h-2.5 w-20 bg-slate-100 rounded"></div>
                      </div>
                      <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-slate-200"></div>
                        <div class="w-8 h-8 rounded-full bg-[#1B4F72]/20"></div>
                      </div>
                    </div>

                    <!-- Stats row -->
                    <div class="grid grid-cols-3 gap-2 mb-4">
                      <div v-for="stat in mockStats" :key="stat.label"
                        class="bg-white rounded-xl p-2.5 border border-slate-100 shadow-sm">
                        <div class="text-lg font-bold" :style="{ color: stat.color }">{{ stat.value }}</div>
                        <div class="text-[10px] text-slate-400 mt-0.5">{{ stat.label }}</div>
                      </div>
                    </div>

                    <!-- Leave requests list -->
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                      <div class="px-3 py-2 border-b border-slate-100 text-xs font-semibold text-slate-500">Demandes récentes</div>
                      <div v-for="req in mockRequests" :key="req.name" class="flex items-center gap-2 px-3 py-2 border-b border-slate-50 last:border-0">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-[9px] font-bold text-white flex-shrink-0" :style="{ backgroundColor: req.color }">
                          {{ req.initials }}
                        </div>
                        <div class="flex-1 min-w-0">
                          <div class="text-[10px] font-medium text-slate-700 truncate">{{ req.name }}</div>
                          <div class="text-[9px] text-slate-400">{{ req.type }}</div>
                        </div>
                        <span class="px-1.5 py-0.5 rounded-full text-[9px] font-semibold" :class="req.statusCls">{{ req.status }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Floating notification -->
              <div class="absolute -bottom-4 -left-6 bg-white rounded-2xl shadow-xl border border-slate-100 p-3 flex items-center gap-2.5" style="max-width: 200px;">
                <div class="w-8 h-8 rounded-xl bg-green-100 flex items-center justify-center flex-shrink-0">
                  <span class="text-sm">✓</span>
                </div>
                <div>
                  <p class="text-xs font-semibold text-slate-800">Bulletin envoyé</p>
                  <p class="text-[10px] text-slate-400">Marie D. — il y a 2 min</p>
                </div>
              </div>

              <!-- Floating time saved -->
              <div class="absolute -top-4 -right-4 bg-white rounded-2xl shadow-xl border border-slate-100 p-3 text-center" style="min-width: 100px;">
                <p class="text-2xl font-extrabold text-[#1B4F72]">−5h</p>
                <p class="text-[10px] text-slate-400 font-medium">par semaine</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Wave bottom -->
      <div class="absolute bottom-0 inset-x-0">
        <svg viewBox="0 0 1440 60" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" style="display:block; height:60px; width:100%;">
          <path d="M0,60 L0,30 Q360,0 720,30 Q1080,60 1440,30 L1440,60 Z" fill="white"/>
        </svg>
      </div>
    </section>

    <!-- ══ LOGOS ═══════════════════════════════════════════════════════════════ -->
    <section class="py-12 bg-white border-b border-slate-100">
      <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <p class="text-center text-xs font-semibold text-slate-400 uppercase tracking-widest mb-8">
          Ils ont dit adieu aux tableurs Excel
        </p>
        <div class="flex flex-wrap items-center justify-center gap-x-12 gap-y-6">
          <div v-for="logo in clientLogos" :key="logo" class="text-slate-300 font-bold text-lg tracking-tight">{{ logo }}</div>
        </div>
      </div>
    </section>

    <!-- ══ PROBLÈMES ══════════════════════════════════════════════════════════ -->
    <section class="py-20 sm:py-24 bg-white">
      <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-16">
          <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-600 mb-4 inline-block">Ça vous ressemble ?</span>
          <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-4" style="font-family: 'Plus Jakarta Sans', sans-serif;">
            Gérer les RH ne devrait pas<br>vous prendre des heures
          </h2>
          <p class="text-slate-500 text-lg max-w-xl mx-auto">
            Pourtant, la réalité dans beaucoup d'entreprises ressemble souvent à ça…
          </p>
        </div>

        <div class="grid sm:grid-cols-3 gap-6">
          <div v-for="pain in painPoints" :key="pain.title"
            class="bg-red-50 border border-red-100 rounded-2xl p-6 relative overflow-hidden">
            <div class="text-3xl mb-4">{{ pain.emoji }}</div>
            <h3 class="text-base font-bold text-red-800 mb-2">{{ pain.title }}</h3>
            <p class="text-sm text-red-600 leading-relaxed">{{ pain.desc }}</p>
          </div>
        </div>

        <!-- Arrow down to solution -->
        <div class="text-center mt-10">
          <div class="inline-flex items-center gap-2 text-sm text-slate-400">
            <span>Il existe une meilleure façon</span>
            <svg class="w-4 h-4 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </div>
        </div>
      </div>
    </section>

    <!-- ══ SOLUTION / FEATURES ════════════════════════════════════════════════ -->
    <section id="demo" class="py-20 sm:py-28" style="background: #F8FAFC;">
      <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-16">
          <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 mb-4 inline-block">Tout en un seul endroit</span>
          <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-4" style="font-family: 'Plus Jakarta Sans', sans-serif;">
            Chaque module vous fait<br>gagner du temps, vraiment.
          </h2>
          <p class="text-slate-500 text-lg max-w-xl mx-auto">
            SimpliRH couvre l'intégralité du cycle RH — de l'embauche au bulletin de paie.
          </p>
        </div>

        <!-- Feature tabs -->
        <div class="flex flex-wrap justify-center gap-2 mb-10">
          <button
            v-for="(feat, i) in features"
            :key="feat.key"
            @click="activeFeature = i"
            class="px-4 py-2 rounded-xl text-sm font-semibold transition-all"
            :class="activeFeature === i
              ? 'bg-[#1B4F72] text-white shadow-md'
              : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
          >
            {{ feat.tab }}
          </button>
        </div>

        <Transition name="fade-feature" mode="out-in">
          <div :key="activeFeature" class="grid lg:grid-cols-2 gap-10 items-center">
            <!-- Text -->
            <div>
              <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-5 text-2xl shadow-sm"
                :style="{ backgroundColor: features[activeFeature].bgColor }">
                {{ features[activeFeature].emoji }}
              </div>
              <h3 class="text-2xl font-extrabold text-slate-900 mb-3" style="font-family: 'Plus Jakarta Sans', sans-serif;">
                {{ features[activeFeature].title }}
              </h3>
              <p class="text-slate-500 leading-relaxed mb-6">{{ features[activeFeature].desc }}</p>
              <ul class="space-y-3">
                <li v-for="point in features[activeFeature].points" :key="point"
                  class="flex items-start gap-3 text-sm text-slate-700">
                  <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                  </svg>
                  {{ point }}
                </li>
              </ul>
            </div>

            <!-- Visual mockup -->
            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden">
              <div class="bg-slate-50 border-b border-slate-100 px-4 py-2.5 flex items-center gap-2">
                <div class="flex gap-1.5">
                  <div class="w-2.5 h-2.5 rounded-full bg-red-300"></div>
                  <div class="w-2.5 h-2.5 rounded-full bg-amber-300"></div>
                  <div class="w-2.5 h-2.5 rounded-full bg-green-300"></div>
                </div>
                <div class="text-xs text-slate-400 font-mono">{{ features[activeFeature].url }}</div>
              </div>
              <div class="p-5" v-html="features[activeFeature].mockup"></div>
            </div>
          </div>
        </Transition>
      </div>
    </section>

    <!-- ══ COMMENT ÇA MARCHE ══════════════════════════════════════════════════ -->
    <section class="py-20 sm:py-24 bg-white">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center">
        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-700 mb-4 inline-block">Démarrage en 15 minutes</span>
        <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-4" style="font-family: 'Plus Jakarta Sans', sans-serif;">
          Opérationnel avant même la pause café
        </h2>
        <p class="text-slate-500 text-lg mb-16 max-w-lg mx-auto">Pas d'installation, pas de DSI, pas de formation. Juste un navigateur.</p>

        <div class="grid sm:grid-cols-3 gap-8 relative">
          <!-- Connecting line -->
          <div class="hidden sm:block absolute top-8 left-1/4 right-1/4 h-px bg-slate-100"></div>

          <div v-for="(step, i) in howItWorks" :key="step.title" class="relative">
            <div class="w-16 h-16 rounded-2xl mx-auto mb-5 flex items-center justify-center text-2xl shadow-sm"
              :style="{ background: 'linear-gradient(135deg, #1B4F72, #2E86C1)' }">
              <span class="text-white font-bold text-lg">{{ i + 1 }}</span>
            </div>
            <h3 class="text-base font-bold text-slate-900 mb-2">{{ step.title }}</h3>
            <p class="text-sm text-slate-500 leading-relaxed">{{ step.desc }}</p>
          </div>
        </div>

        <div class="mt-12">
          <a href="/register"
            class="inline-flex items-center gap-2 px-8 py-4 bg-[#1B4F72] hover:bg-[#154060] text-white font-bold text-base rounded-2xl shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all duration-200">
            Créer mon espace gratuitement
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
          </a>
        </div>
      </div>
    </section>

    <!-- ══ STATS ══════════════════════════════════════════════════════════════ -->
    <section class="py-16 sm:py-20" style="background: linear-gradient(135deg, #0d2137 0%, #1B4F72 100%);">
      <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-8 text-center">
          <div v-for="stat in impactStats" :key="stat.value">
            <div class="text-4xl sm:text-5xl font-extrabold text-white mb-2" style="font-family: 'Plus Jakarta Sans', sans-serif;">{{ stat.value }}</div>
            <div class="text-blue-200 text-sm font-medium">{{ stat.label }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- ══ TÉMOIGNAGES ════════════════════════════════════════════════════════ -->
    <section class="py-20 sm:py-28 bg-white">
      <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-14">
          <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 mb-4 inline-block">Ils témoignent</span>
          <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900" style="font-family: 'Plus Jakarta Sans', sans-serif;">
            Ce que disent nos clients
          </h2>
        </div>

        <div class="grid sm:grid-cols-3 gap-6">
          <div v-for="t in testimonials" :key="t.name"
            class="bg-slate-50 rounded-2xl p-6 border border-slate-100 flex flex-col hover:shadow-md transition-shadow">
            <div class="flex mb-3">
              <span v-for="i in 5" :key="i" class="text-amber-400 text-sm">★</span>
            </div>
            <blockquote class="text-slate-700 text-sm leading-relaxed flex-1 mb-5">"{{ t.quote }}"</blockquote>
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white flex-shrink-0"
                :style="{ backgroundColor: t.color }">{{ t.initials }}</div>
              <div>
                <p class="text-sm font-semibold text-slate-900">{{ t.name }}</p>
                <p class="text-xs text-slate-400">{{ t.role }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ══ PRICING ════════════════════════════════════════════════════════════ -->
    <section id="tarifs" class="py-20 sm:py-28" style="background: #F8FAFC;">
      <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-14">
          <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 mb-4 inline-block">Tarification simple</span>
          <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-4" style="font-family: 'Plus Jakarta Sans', sans-serif;">
            Choisissez votre plan
          </h2>
          <p class="text-slate-500 text-lg">14 jours d'essai gratuit sur tous les plans — sans engagement.</p>
        </div>

        <div class="grid sm:grid-cols-3 gap-6 items-start">
          <div v-for="plan in pricingPlans" :key="plan.name"
            class="relative bg-white rounded-2xl border-2 p-6 flex flex-col transition-all"
            :class="plan.popular ? 'border-[#1B4F72] shadow-xl shadow-blue-100' : 'border-slate-200 hover:border-slate-300'"
          >
            <div v-if="plan.popular" class="absolute -top-4 left-1/2 -translate-x-1/2 px-4 py-1.5 rounded-full text-xs font-bold shadow-md"
              style="background: linear-gradient(135deg, #1B4F72, #2E86C1); color: white;">
              ⭐ Populaire
            </div>

            <h3 class="text-lg font-bold text-slate-900 mb-1">{{ plan.name }}</h3>
            <p class="text-xs text-slate-400 mb-4">{{ plan.desc }}</p>
            <div class="flex items-baseline gap-1 mb-6">
              <span class="text-4xl font-extrabold text-slate-900">{{ plan.price }}</span>
              <span class="text-sm text-slate-400">/mois HT</span>
            </div>

            <a href="/register"
              class="w-full py-3 text-sm font-bold rounded-xl text-center transition-all mb-6 block"
              :class="plan.popular
                ? 'bg-[#1B4F72] hover:bg-[#154060] text-white shadow-md hover:shadow-lg'
                : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
            >
              Commencer l'essai gratuit
            </a>

            <ul class="space-y-2.5 text-sm">
              <li v-for="f in plan.features" :key="f" class="flex items-start gap-2 text-slate-600">
                <svg class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                {{ f }}
              </li>
            </ul>
          </div>
        </div>

        <p class="text-center text-xs text-slate-400 mt-6">
          Tous les prix sont HT · TVA 20% applicable · Résiliable à tout moment · Données hébergées en France
        </p>
      </div>
    </section>

    <!-- ══ FAQ ════════════════════════════════════════════════════════════════ -->
    <section class="py-20 sm:py-24 bg-white">
      <div class="max-w-2xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-extrabold text-slate-900 mb-3" style="font-family: 'Plus Jakarta Sans', sans-serif;">Questions fréquentes</h2>
          <p class="text-slate-500">Tout ce que vous voulez savoir avant de vous lancer.</p>
        </div>

        <div class="space-y-3">
          <div
            v-for="(faq, i) in faqs"
            :key="faq.q"
            class="border border-slate-200 rounded-2xl overflow-hidden"
          >
            <button
              @click="activeFaq = activeFaq === i ? null : i"
              class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 transition-colors"
            >
              <span class="text-sm font-semibold text-slate-800 pr-4">{{ faq.q }}</span>
              <svg class="w-4 h-4 text-slate-400 flex-shrink-0 transition-transform duration-200"
                :class="activeFaq === i ? 'rotate-180' : ''"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>
            <Transition
              enter-active-class="transition-all duration-200 ease-out"
              enter-from-class="opacity-0 max-h-0"
              enter-to-class="opacity-100 max-h-48"
              leave-active-class="transition-all duration-150 ease-in"
              leave-from-class="opacity-100 max-h-48"
              leave-to-class="opacity-0 max-h-0"
            >
              <div v-if="activeFaq === i" class="px-5 pb-4 overflow-hidden">
                <p class="text-sm text-slate-500 leading-relaxed">{{ faq.a }}</p>
              </div>
            </Transition>
          </div>
        </div>
      </div>
    </section>

    <!-- ══ FINAL CTA ══════════════════════════════════════════════════════════ -->
    <section class="py-20 sm:py-28 relative overflow-hidden"
      style="background: linear-gradient(135deg, #0d2137 0%, #1B4F72 60%, #2E86C1 100%);">
      <div class="max-w-3xl mx-auto px-4 sm:px-6 text-center text-white relative z-10">
        <div class="w-16 h-16 bg-white/15 rounded-2xl flex items-center justify-center mx-auto mb-6">
          <span class="text-3xl font-bold text-white">S</span>
        </div>
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold mb-5 leading-tight" style="font-family: 'Plus Jakarta Sans', sans-serif;">
          Prêt à récupérer<br>5 heures par semaine ?
        </h2>
        <p class="text-white/70 text-lg mb-8 max-w-xl mx-auto">
          Rejoignez plus de 200 entreprises françaises qui ont simplifié leur gestion RH avec SimpliRH.
          Essai gratuit 14 jours, sans carte bancaire.
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center mb-6">
          <a href="/register"
            class="group inline-flex items-center justify-center gap-2 px-8 py-4 bg-white text-[#1B4F72] font-bold text-base rounded-2xl shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all duration-200">
            Démarrer l'essai gratuit
            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
          </a>
          <a href="mailto:bonjour@simpli-rh.com"
            class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white/10 border border-white/20 text-white font-semibold text-base rounded-2xl hover:bg-white/20 transition-all">
            Nous contacter
          </a>
        </div>
        <p class="text-white/50 text-xs">Aucune carte bancaire requise · Résiliable à tout moment · Support inclus</p>
      </div>
    </section>

    <!-- ══ FOOTER ═════════════════════════════════════════════════════════════ -->
    <footer class="bg-slate-900 text-slate-400 py-12">
      <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="grid sm:grid-cols-4 gap-8 mb-10">
          <!-- Brand -->
          <div class="sm:col-span-1">
            <div class="flex items-center gap-2 mb-3">
              <div class="w-7 h-7 bg-[#2E86C1] rounded-lg flex items-center justify-center">
                <span class="text-white font-bold text-xs">S</span>
              </div>
              <span class="text-white font-bold text-sm">SimpliRH</span>
            </div>
            <p class="text-xs leading-relaxed text-slate-500">La solution RH pensée pour les TPE et PME françaises.</p>
            <div class="flex gap-3 mt-4">
              <span class="px-2 py-0.5 bg-slate-800 text-slate-400 text-[10px] rounded font-medium">🇫🇷 Made in France</span>
              <span class="px-2 py-0.5 bg-slate-800 text-slate-400 text-[10px] rounded font-medium">RGPD ✓</span>
            </div>
          </div>

          <div v-for="col in footerLinks" :key="col.title">
            <h4 class="text-white text-xs font-semibold uppercase tracking-wider mb-4">{{ col.title }}</h4>
            <ul class="space-y-2.5">
              <li v-for="link in col.links" :key="link.label">
                <a :href="link.href" class="text-xs text-slate-500 hover:text-slate-300 transition-colors">{{ link.label }}</a>
              </li>
            </ul>
          </div>
        </div>

        <div class="border-t border-slate-800 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3">
          <p class="text-xs text-slate-600">© {{ new Date().getFullYear() }} SimpliRH. Tous droits réservés.</p>
          <div class="flex items-center gap-4">
            <a href="#" class="text-xs text-slate-600 hover:text-slate-400 transition-colors">Mentions légales</a>
            <a href="#" class="text-xs text-slate-600 hover:text-slate-400 transition-colors">Politique de confidentialité</a>
            <a href="#" class="text-xs text-slate-600 hover:text-slate-400 transition-colors">CGV</a>
          </div>
        </div>
      </div>
    </footer>

    <!-- ══ MOBILE STICKY CTA ══════════════════════════════════════════════════ -->
    <Transition enter-active-class="transition duration-300" enter-from-class="translate-y-full opacity-0" enter-to-class="translate-y-0 opacity-100">
      <div v-if="showMobileCta" class="sm:hidden fixed bottom-0 inset-x-0 z-50 p-3 bg-white border-t border-slate-200 shadow-lg">
        <a href="/register"
          class="flex items-center justify-center gap-2 w-full py-3.5 bg-[#1B4F72] text-white text-sm font-bold rounded-xl shadow-lg transition-all active:scale-95">
          Essai gratuit 14 jours — sans carte bancaire
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
          </svg>
        </a>
      </div>
    </Transition>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head } from '@inertiajs/vue3'

// ── Background texture (data-URI with single quotes escaped) ───────────────
const bgTexture = computed(() => {
  const svg = `%3Csvg width=%2260%22 height=%2260%22 viewBox=%220 0 60 60%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cg fill=%22none%22 fill-rule=%22evenodd%22%3E%3Cg fill=%22%23ffffff%22 fill-opacity=%221%22%3E%3Cpath d=%22M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z%22%2F%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E`
  return `url("data:image/svg+xml,${svg}")`
})

// ── Nav scroll effect ──────────────────────────────────────────────────────
const scrolled = ref(false)
const showMobileCta = ref(false)

function handleScroll() {
  scrolled.value = window.scrollY > 60
  showMobileCta.value = window.scrollY > 400
}

onMounted(() => window.addEventListener('scroll', handleScroll, { passive: true }))
onUnmounted(() => window.removeEventListener('scroll', handleScroll))

// ── Nav links ──────────────────────────────────────────────────────────────
const navLinks = [
  { label: 'Fonctionnalités', href: '#demo' },
  { label: 'Tarifs',          href: '#tarifs' },
  { label: 'FAQ',             href: '#faq' },
]

// ── Hero mockup data ───────────────────────────────────────────────────────
const avatarColors   = ['#2E86C1', '#27AE60', '#9B59B6', '#E74C3C', '#F39C12']
const avatarInitials = ['ML', 'TR', 'IM', 'PB', 'SC']

const mockStats = [
  { value: '12',  label: 'Actifs',   color: '#27AE60' },
  { value: '3',   label: 'Congés',   color: '#F39C12' },
  { value: '98%', label: 'Payés',    color: '#2E86C1' },
]

const mockRequests = [
  { name: 'Marie Dupont',   initials: 'MD', color: '#2E86C1', type: 'Congés annuels · 5j', status: 'Validé',   statusCls: 'bg-green-100 text-green-700'  },
  { name: 'Thomas Renard',  initials: 'TR', color: '#9B59B6', type: 'RTT · 1j',             status: 'En attente',statusCls: 'bg-amber-100 text-amber-700' },
  { name: 'Isabelle Martin', initials: 'IM', color: '#27AE60', type: 'Maladie · 3j',         status: 'Validé',   statusCls: 'bg-green-100 text-green-700'  },
]

// ── Logos ──────────────────────────────────────────────────────────────────
const clientLogos = ['Artisans & Co', 'TechPME', 'Bâtisseurs 35', 'MediCab Lyon', 'Studio Créa', 'BTP Sud', 'Formaction']

// ── Pain points ────────────────────────────────────────────────────────────
const painPoints = [
  {
    emoji: '📊',
    title: 'Des heures perdues sur Excel',
    desc: 'Calcul manuel des congés, des heures sup, des compteurs... Chaque demande prend 15 minutes au lieu de 15 secondes.',
  },
  {
    emoji: '📮',
    title: 'Des bulletins qui n\'arrivent jamais',
    desc: 'Impression, mise sous pli, envoi postal... ou pire, des fichiers PDF perdus dans des chaînes de mails.',
  },
  {
    emoji: '✍️',
    title: 'Des signatures qui traînent des semaines',
    desc: 'Contrats qui circulent de bureau en bureau, DRH qui court après les paraphes, collaborateurs qui "l\'ont mis quelque part".',
  },
]

// ── Features ───────────────────────────────────────────────────────────────
const activeFeature = ref(0)

const features = [
  {
    key:      'conges',
    tab:      '🌴 Congés',
    emoji:    '🌴',
    bgColor:  '#EFF6FF',
    title:    'Congés & absences en temps réel',
    desc:     'Vos collaborateurs demandent, vous validez en un clic. Les compteurs se mettent à jour instantanément, sans aucun calcul manuel.',
    points:   [
      'Demande en ligne depuis mobile ou ordinateur',
      'Validation ou refus en un clic avec notification automatique',
      'Compteurs mis à jour en temps réel (CP, RTT, ancienneté)',
      'Calendrier de l\'équipe pour visualiser les absences',
    ],
    url:     'app.simpli-rh.com/conges',
    mockup: `<div class="space-y-3">
      <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-100">
        <div class="flex items-center gap-3"><div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-xs font-bold text-blue-700">MD</div><div><p class="text-xs font-semibold text-slate-800">Marie Dupont</p><p class="text-xs text-slate-400">Congés annuels · 5 jours · 15-19 juil.</p></div></div>
        <div class="flex gap-2"><button class="px-3 py-1.5 bg-green-500 text-white text-xs font-bold rounded-lg">✓ Valider</button><button class="px-3 py-1.5 bg-red-100 text-red-600 text-xs font-bold rounded-lg">✗</button></div>
      </div>
      <div class="grid grid-cols-3 gap-2">
        <div class="bg-blue-50 rounded-xl p-3 text-center"><p class="text-xl font-extrabold text-blue-700">18</p><p class="text-[10px] text-slate-400">Jours CP</p></div>
        <div class="bg-purple-50 rounded-xl p-3 text-center"><p class="text-xl font-extrabold text-purple-700">5</p><p class="text-[10px] text-slate-400">RTT</p></div>
        <div class="bg-green-50 rounded-xl p-3 text-center"><p class="text-xl font-extrabold text-green-700">2</p><p class="text-[10px] text-slate-400">Ancienneté</p></div>
      </div>
    </div>`,
  },
  {
    key:     'bulletins',
    tab:     '💰 Bulletins',
    emoji:   '💰',
    bgColor: '#F0FDF4',
    title:   'Bulletins de paie distribués automatiquement',
    desc:    'Importez vos bulletins en lot, SimpliRH reconnaît chaque fichier et les distribue au bon collaborateur en un clic.',
    points:  [
      'Import en masse par glisser-déposer',
      'Reconnaissance automatique par nom ou matricule',
      'Distribution individuelle avec email de notification',
      'Historique complet accessible 24h/24 par chaque collaborateur',
    ],
    url:    'app.simpli-rh.com/bulletins',
    mockup: `<div class="space-y-3">
      <div class="border-2 border-dashed border-green-200 rounded-xl p-4 text-center bg-green-50"><p class="text-sm font-semibold text-green-700">Glissez 47 bulletins ici</p><p class="text-xs text-green-500 mt-1">PDF reconnus automatiquement</p></div>
      <div class="space-y-2">
        <div class="flex items-center justify-between p-2.5 bg-green-50 rounded-xl border border-green-100"><span class="text-xs text-slate-700 font-medium">bulletin_dupont_marie_2025-06.pdf</span><span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-bold rounded-full">✓ Envoyé</span></div>
        <div class="flex items-center justify-between p-2.5 bg-green-50 rounded-xl border border-green-100"><span class="text-xs text-slate-700 font-medium">bulletin_renard_thomas_2025-06.pdf</span><span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-bold rounded-full">✓ Envoyé</span></div>
        <div class="flex items-center justify-between p-2.5 bg-amber-50 rounded-xl border border-amber-100"><span class="text-xs text-slate-700 font-medium">bulletin_martin_xxx_2025-06.pdf</span><span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-xs font-bold rounded-full">⚠ À vérifier</span></div>
      </div>
    </div>`,
  },
  {
    key:     'pointage',
    tab:     '⏱ Pointage',
    emoji:   '⏱',
    bgColor: '#FFF7ED',
    title:   'Pointage et heures supplémentaires sans prise de tête',
    desc:    'Chaque collaborateur pointe depuis son téléphone. Les heures supplémentaires sont calculées et soumises à validation automatiquement.',
    points:  [
      'Pointage mobile en un tap (arrivée, pause, départ)',
      'Calcul automatique des heures sup au-delà du seuil légal',
      'Workflow de validation manager intégré',
      'Rapport mensuel exportable pour la paie',
    ],
    url:    'app.simpli-rh.com/pointage',
    mockup: `<div class="text-center space-y-4">
      <div class="w-20 h-20 rounded-full bg-green-100 border-4 border-green-300 flex items-center justify-center mx-auto"><p class="text-xs font-bold text-green-700">Pointé<br/>09:02</p></div>
      <div class="grid grid-cols-2 gap-3">
        <button class="py-3 bg-amber-100 text-amber-700 text-sm font-bold rounded-xl border border-amber-200">⏸ Pause</button>
        <button class="py-3 bg-red-100 text-red-600 text-sm font-bold rounded-xl border border-red-200">⏹ Départ</button>
      </div>
      <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
        <p class="text-xs text-slate-500 mb-2">Aujourd'hui</p>
        <div class="flex justify-between text-xs"><span class="text-slate-600">Arrivée</span><span class="font-semibold">09:02</span></div>
        <div class="flex justify-between text-xs mt-1"><span class="text-slate-600">Temps écoulé</span><span class="font-semibold text-green-700">3h 45min</span></div>
      </div>
    </div>`,
  },
  {
    key:     'documents',
    tab:     '✍️ Signatures',
    emoji:   '✍️',
    bgColor: '#F5F3FF',
    title:   'Signatures électroniques simples et légales',
    desc:    'Envoyez un contrat, une charte ou tout document à signer. Votre collaborateur signe depuis son téléphone. Le document est archivé avec un certificat.',
    points:  [
      'Envoi du lien de signature par email en 30 secondes',
      'Signature depuis n\'importe quel appareil, sans compte',
      'Certificat d\'horodatage et traçabilité complète',
      'Archivage sécurisé chiffré AES-256',
    ],
    url:    'app.simpli-rh.com/documents',
    mockup: `<div class="space-y-3">
      <div class="bg-purple-50 rounded-xl p-4 border border-purple-100">
        <div class="flex items-center gap-3 mb-3"><div class="w-10 h-12 bg-white rounded border border-purple-200 flex items-center justify-center text-xl">📄</div><div><p class="text-xs font-semibold text-slate-800">Contrat CDI — Marie Dupont.pdf</p><p class="text-xs text-slate-400">Envoyé le 12 mars 2025</p></div></div>
        <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span><span class="text-xs text-green-700 font-semibold">Signé le 12 mars à 14h33</span></div>
      </div>
      <div class="bg-white rounded-xl p-3 border border-slate-100">
        <p class="text-xs font-semibold text-slate-700 mb-2">Certificat de signature</p>
        <div class="space-y-1.5 text-[10px] text-slate-500"><div class="flex gap-2"><span class="font-mono bg-slate-100 px-1 rounded">SHA-256</span><span>a3f9b2c8e1d5...</span></div><div>Signataire vérifié par email · 12/03/2025 14:33:07</div></div>
      </div>
    </div>`,
  },
]

// ── How it works ───────────────────────────────────────────────────────────
const howItWorks = [
  { title: 'Créez votre espace',   desc: 'Inscrivez-vous en 2 minutes. Renseignez le nom de votre entreprise, votre département, c\'est tout.' },
  { title: 'Invitez votre équipe', desc: 'Importez vos collaborateurs par CSV ou invitez-les un par un. Ils reçoivent un email d\'activation.' },
  { title: 'Gérez tout simplement', desc: 'Congés, bulletins, pointage — tout est centralisé. Vous gagnez du temps dès le premier jour.' },
]

// ── Impact stats ───────────────────────────────────────────────────────────
const impactStats = [
  { value: '5h',   label: 'économisées par semaine en moyenne' },
  { value: '2min', label: 'pour traiter une demande de congés' },
  { value: '100%', label: 'des bulletins distribués automatiquement' },
  { value: '0€',   label: 'd\'installation ni de maintenance' },
]

// ── Testimonials ───────────────────────────────────────────────────────────
const testimonials = [
  {
    name:     'Marie Lefebvre',
    initials: 'ML',
    color:    '#2E86C1',
    role:     'DRH — Agence de communication, 45 salariés',
    quote:    "J'ai enfin le temps de faire mon vrai travail. Avant, je passais 6 heures par semaine sur les congés et les bulletins. Maintenant c'est 30 minutes. SimpliRH a littéralement changé mon quotidien.",
  },
  {
    name:     'Thomas Renard',
    initials: 'TR',
    color:    '#27AE60',
    role:     'Gérant — BTP Provence, 18 salariés',
    quote:    "L'installation a pris 15 minutes, sérieusement. J'importé ma liste d'employés depuis Excel, tout le monde a reçu son invitation automatiquement. Mes gars pointent depuis leur téléphone sur le chantier.",
  },
  {
    name:     'Isabelle Martin',
    initials: 'IM',
    color:    '#9B59B6',
    role:     'Office Manager — Cabinet médical, 12 personnes',
    quote:    "Ce qui m'a le plus surpris : mes collègues ont adopté l'outil sans aucune résistance. L'interface est tellement intuitive que personne n'a eu besoin de formation. Et les signatures électroniques, c'est magique.",
  },
]

// ── Pricing ────────────────────────────────────────────────────────────────
const pricingPlans = [
  {
    name:     'Starter',
    desc:     'Idéal pour les petites équipes',
    price:    '29 €',
    popular:  false,
    features: [
      'Jusqu\'à 20 collaborateurs',
      'Gestion des congés & absences',
      'Bulletins de paie',
      'Pointage & heures sup.',
      'Documents & signatures',
      'Export paie CSV/Excel',
      'Support par email',
    ],
  },
  {
    name:     'Business',
    desc:     'Pour les entreprises en croissance',
    price:    '69 €',
    popular:  true,
    features: [
      'Jusqu\'à 100 collaborateurs',
      'Tout Starter inclus',
      'Planning mensuel',
      'Recrutement & pipeline',
      'Export paie PDF complet',
      'Personnalisation (logo, couleurs)',
      'Support prioritaire',
    ],
  },
  {
    name:     'Enterprise',
    desc:     'Pour les grandes structures',
    price:    '149 €',
    popular:  false,
    features: [
      'Collaborateurs illimités',
      'Tout Business inclus',
      'Import CSV en masse',
      'Manager dédié',
      'SLA 99,9 %',
      'Onboarding personnalisé',
    ],
  },
]

// ── FAQ ────────────────────────────────────────────────────────────────────
const activeFaq = ref(null)

const faqs = [
  {
    q: 'Faut-il une carte bancaire pour démarrer l\'essai ?',
    a: 'Non, absolument pas. Vous pouvez utiliser SimpliRH pendant 14 jours avec toutes les fonctionnalités, sans renseigner aucune information de paiement. Vous décidez à la fin de l\'essai si vous souhaitez continuer.',
  },
  {
    q: 'Mes données sont-elles sécurisées et conformes RGPD ?',
    a: 'Oui. Toutes les données sont hébergées en France, chiffrées en transit (TLS) et au repos (AES-256). SimpliRH est conçu pour être conforme au RGPD : vous restez propriétaire de vos données et pouvez les exporter ou les supprimer à tout moment.',
  },
  {
    q: 'Puis-je importer mes données existantes depuis Excel ?',
    a: 'Oui, vous pouvez importer vos collaborateurs via un fichier CSV en quelques clics. Un modèle vous est fourni. Pour les bulletins de paie et documents existants, vous pouvez les téléverser en lot.',
  },
  {
    q: 'Quelle est la durée d\'engagement ?',
    a: 'Aucun engagement. SimpliRH est sans abonnement annuel obligatoire — vous payez mois par mois et pouvez résilier à tout moment depuis votre espace paramètres, sans pénalité.',
  },
  {
    q: 'Combien de temps faut-il pour démarrer ?',
    a: 'La plupart de nos clients sont opérationnels en moins de 30 minutes. L\'inscription prend 2 minutes, l\'import de votre équipe 5 minutes, et la configuration des modules quelques minutes supplémentaires. Pas de formation, pas d\'installation.',
  },
]

// ── Footer ─────────────────────────────────────────────────────────────────
const footerLinks = [
  {
    title: 'Produit',
    links: [
      { label: 'Fonctionnalités',   href: '#demo' },
      { label: 'Tarifs',            href: '#tarifs' },
      { label: 'Nouveautés',        href: '#' },
    ],
  },
  {
    title: 'Entreprise',
    links: [
      { label: 'À propos',          href: '#' },
      { label: 'Blog RH',           href: '#' },
      { label: 'Nous contacter',    href: 'mailto:bonjour@simpli-rh.com' },
    ],
  },
  {
    title: 'Compte',
    links: [
      { label: 'Se connecter',      href: '/login' },
      { label: 'Créer un compte',   href: '/register' },
      { label: 'Mot de passe oublié', href: '/forgot-password' },
    ],
  },
]
</script>

<style scoped>
.fade-feature-enter-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.fade-feature-leave-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.fade-feature-enter-from   { opacity: 0; transform: translateY(8px); }
.fade-feature-leave-to     { opacity: 0; transform: translateY(-8px); }
</style>
