<script setup lang="ts">
import { useToastStore } from '@/Stores/useToastStore';
import { PhCheckCircle, PhWarningCircle, PhInfo, PhXCircle, PhX } from '@phosphor-icons/vue';

const toastStore = useToastStore();

const getIcon = (type: string) => {
    switch (type) {
        case 'success': return PhCheckCircle;
        case 'error': return PhXCircle;
        case 'warning': return PhWarningCircle;
        case 'info': return PhInfo;
        default: return PhInfo;
    }
};

const getColorClass = (type: string) => {
    switch (type) {
        case 'success': return 'bg-green-50 border-green-200 text-green-800';
        case 'error': return 'bg-red-50 border-red-200 text-red-800';
        case 'warning': return 'bg-yellow-50 border-yellow-200 text-yellow-800';
        case 'info': return 'bg-blue-50 border-blue-200 text-blue-800';
        default: return 'bg-slate-50 border-slate-200 text-slate-800';
    }
};

const getIconColorClass = (type: string) => {
    switch (type) {
        case 'success': return 'text-green-500';
        case 'error': return 'text-red-500';
        case 'warning': return 'text-yellow-500';
        case 'info': return 'text-blue-500';
        default: return 'text-slate-500';
    }
};
</script>

<template>
    <div class="fixed bottom-6 right-6 z-[6000] flex flex-col gap-3 pointer-events-none">
        <transition-group 
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="transform translate-y-8 opacity-0"
            enter-to-class="transform translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="transform translate-y-0 opacity-100"
            leave-to-class="transform translate-y-4 opacity-0"
        >
            <div 
                v-for="toast in toastStore.toasts" 
                :key="toast.id"
                class="pointer-events-auto flex items-start gap-3 p-4 rounded-xl shadow-lg border min-w-[300px] max-w-[400px]"
                :class="getColorClass(toast.type)"
            >
                <component 
                    :is="getIcon(toast.type)" 
                    :size="20" 
                    weight="fill" 
                    class="shrink-0 mt-0.5"
                    :class="getIconColorClass(toast.type)"
                />
                <div class="flex-1 text-[14px] font-medium leading-tight">
                    {{ toast.message }}
                </div>
                <button 
                    @click="toastStore.removeToast(toast.id)"
                    class="shrink-0 p-0.5 hover:bg-black/5 rounded-md transition-colors opacity-70 hover:opacity-100"
                >
                    <PhX :size="16" />
                </button>
            </div>
        </transition-group>
    </div>
</template>
