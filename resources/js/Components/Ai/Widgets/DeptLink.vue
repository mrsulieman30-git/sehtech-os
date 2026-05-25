<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { PhArrowRight } from '@phosphor-icons/vue';

const props = defineProps({
    slug: { type: String, required: true },
});

const deptData = computed(() => {
    const map: Record<string, any> = {
        hr: { name: 'Human Resources', color: 'bg-blue-100 text-blue-700 border-blue-200 hover:bg-blue-200', icon: 'PhUsers' },
        finance: { name: 'Finance & Accounting', color: 'bg-emerald-100 text-emerald-700 border-emerald-200 hover:bg-emerald-200', icon: 'PhCurrencyDollar' },
        it: { name: 'IT & Engineering', color: 'bg-indigo-100 text-indigo-700 border-indigo-200 hover:bg-indigo-200', icon: 'PhCpu' },
        marketing: { name: 'Marketing', color: 'bg-rose-100 text-rose-700 border-rose-200 hover:bg-rose-200', icon: 'PhMegaphone' },
        sales: { name: 'Sales & CRM', color: 'bg-amber-100 text-amber-700 border-amber-200 hover:bg-amber-200', icon: 'PhHandshake' },
    };
    return map[props.slug.toLowerCase()] || { name: props.slug, color: 'bg-gray-100 text-gray-700 border-gray-200 hover:bg-gray-200', icon: 'PhBuildings' };
});

const url = computed(() => {
    const s = props.slug.toLowerCase();
    if (s === 'it' || s === 'development') return '/app/development';
    return `/app/${s}`;
});
</script>

<template>
    <Link :href="url" :class="[deptData.color, 'inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider border transition-colors my-1']">
        <span>{{ deptData.name }}</span>
        <PhArrowRight :size="12" weight="bold" />
    </Link>
</template>
