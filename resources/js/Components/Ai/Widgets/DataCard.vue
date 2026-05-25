<script setup lang="ts">
import { computed } from 'vue';
import { 
    PhCurrencyDollar, PhUsers, PhBuildings, PhBriefcase, 
    PhChartBar, PhChartLineUp, PhHandshake, PhWarningCircle 
} from '@phosphor-icons/vue';

const props = defineProps({
    title: { type: String, required: true },
    value: { type: String, required: true },
    icon: { type: String, default: 'PhBriefcase' },
    color: { type: String, default: 'blue' }
});

const iconMap: Record<string, any> = {
    PhCurrencyDollar, PhUsers, PhBuildings, PhBriefcase, 
    PhChartBar, PhChartLineUp, PhHandshake, PhWarningCircle
};

const theme = computed(() => {
    const themes: Record<string, any> = {
        emerald: { bg: 'bg-emerald-50', border: 'border-emerald-200', iconBg: 'bg-emerald-100', iconText: 'text-emerald-600', titleText: 'text-emerald-800', valueText: 'text-emerald-950' },
        blue: { bg: 'bg-blue-50', border: 'border-blue-200', iconBg: 'bg-blue-100', iconText: 'text-blue-600', titleText: 'text-blue-800', valueText: 'text-blue-950' },
        amber: { bg: 'bg-amber-50', border: 'border-amber-200', iconBg: 'bg-amber-100', iconText: 'text-amber-600', titleText: 'text-amber-800', valueText: 'text-amber-950' },
        purple: { bg: 'bg-purple-50', border: 'border-purple-200', iconBg: 'bg-purple-100', iconText: 'text-purple-600', titleText: 'text-purple-800', valueText: 'text-purple-950' },
        rose: { bg: 'bg-rose-50', border: 'border-rose-200', iconBg: 'bg-rose-100', iconText: 'text-rose-600', titleText: 'text-rose-800', valueText: 'text-rose-950' },
    };
    return themes[props.color] || themes.blue;
});
</script>

<template>
    <div :class="[theme.bg, theme.border, 'border rounded-xl p-4 flex items-center gap-4 my-2 shadow-sm w-max min-w-[200px]']">
        <div :class="[theme.iconBg, theme.iconText, 'p-3 rounded-lg flex items-center justify-center']">
            <component :is="iconMap[icon] || iconMap.PhBriefcase" :size="24" weight="duotone" />
        </div>
        <div>
            <h4 :class="[theme.titleText, 'text-[11px] font-bold uppercase tracking-wider opacity-80']">{{ title }}</h4>
            <div :class="[theme.valueText, 'text-xl font-black mt-0.5']">{{ value }}</div>
        </div>
    </div>
</template>
