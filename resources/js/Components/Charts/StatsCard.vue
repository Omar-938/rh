<script setup>
import { ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    label:   { type: String,           required: true },
    value:   { type: [Number, String], default: 0 },
    icon:    { type: String,           required: true },
    color:   { type: String,           default: 'primary' },  // primary | success | warning | danger | slate
    trend:   { type: Number,           default: null },        // % positif/négatif
    href:    { type: String,           default: null },
    loading: { type: Boolean,          default: false },
    suffix:  { type: String,           default: '' },
})

// Animation du compteur
const displayed = ref(0)
onMounted(() => {
    if (typeof props.value !== 'number') { displayed.value = props.value; return }
    const target = props.value
    if (target === 0) return
    const step   = Math.ceil(target / 30)
    const timer  = setInterval(() => {
        displayed.value = Math.min(displayed.value + step, target)
        if (displayed.value >= target) clearInterval(timer)
    }, 20)
})

const colors = {
    primary: { bg: 'bg-primary-50',  icon: 'bg-primary-100  text-primary-700', border: 'border-primary-100' },
    success: { bg: 'bg-success-50',  icon: 'bg-success-100  text-success-700', border: 'border-success-100' },
    warning: { bg: 'bg-warning-50',  icon: 'bg-warning-100  text-warning-700', border: 'border-warning-100' },
    danger:  { bg: 'bg-danger-50',   icon: 'bg-danger-100   text-danger-700',  border: 'border-danger-100'  },
    slate:   { bg: 'bg-slate-50',    icon: 'bg-slate-100    text-slate-600',   border: 'border-slate-200'   },
}
const c = colors[props.color] ?? colors.primary
</script>

<template>
    <component
        :is="href ? Link : 'div'"
        :href="href"
        :class="[
            'group relative bg-white rounded-2xl border p-5 transition-all duration-200',
            c.border,
            href ? 'hover:shadow-md hover:-translate-y-0.5 cursor-pointer' : '',
            loading ? 'animate-pulse' : '',
        ]"
    >
        <!-- Skeleton -->
        <template v-if="loading">
            <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-slate-100" />
                <div class="w-12 h-4 rounded bg-slate-100" />
            </div>
            <div class="w-16 h-7 rounded bg-slate-100 mb-1" />
            <div class="w-24 h-4 rounded bg-slate-100" />
        </template>

        <!-- Contenu réel -->
        <template v-else>
            <div class="flex items-start justify-between mb-4">
                <!-- Icône -->
                <div :class="['w-10 h-10 rounded-xl flex items-center justify-center shrink-0', c.icon]">
                    <span class="w-5 h-5 [&>svg]:w-5 [&>svg]:h-5" v-html="icon" />
                </div>

                <!-- Tendance -->
                <div v-if="trend !== null"
                     :class="[
                         'flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-full',
                         trend >= 0 ? 'bg-success-50 text-success-700' : 'bg-danger-50 text-danger-700'
                     ]"
                >
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path v-if="trend >= 0" fill-rule="evenodd" d="M10 17a.75.75 0 01-.75-.75V5.612L5.29 9.77a.75.75 0 01-1.08-1.04l5.25-5.5a.75.75 0 011.08 0l5.25 5.5a.75.75 0 11-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0110 17z" clip-rule="evenodd" />
                        <path v-else fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.638l3.96-4.158a.75.75 0 111.08 1.04l-5.25 5.5a.75.75 0 01-1.08 0l-5.25-5.5a.75.75 0 111.08-1.04l3.96 4.158V3.75A.75.75 0 0110 3z" clip-rule="evenodd" />
                    </svg>
                    {{ Math.abs(trend) }}%
                </div>

                <!-- Flèche lien -->
                <svg v-else-if="href"
                     class="w-4 h-4 text-slate-300 group-hover:text-slate-500 transition-colors"
                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
            </div>

            <!-- Valeur animée -->
            <p class="text-3xl font-display font-bold text-slate-900 tabular-nums leading-none mb-1">
                {{ typeof value === 'number' ? displayed : value }}{{ suffix }}
            </p>
            <p class="text-sm text-slate-500 font-medium">{{ label }}</p>
        </template>
    </component>
</template>
