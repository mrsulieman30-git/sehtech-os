<script setup lang="ts">
import { PhWarning, PhTrash } from '@phosphor-icons/vue';

const props = defineProps<{
    title?: string;
    message?: string;
    confirmText?: string;
    cancelText?: string;
    danger?: boolean;
    onConfirm: () => void;
}>();

const emit = defineEmits(['close']);

const handleConfirm = () => {
    if (typeof props.onConfirm === 'function') {
        props.onConfirm();
    }
    emit('close');
};
</script>

<template>
    <div class="w-[380px] bg-white rounded-modal shadow-modal border border-shell-border flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <div class="p-6 flex flex-col items-center text-center gap-3">
            <div 
                class="w-12 h-12 rounded-full flex items-center justify-center mb-2"
                :class="props.danger !== false ? 'bg-state-error/10 text-state-error' : 'bg-dept-dev-main/10 text-dept-dev-main'"
            >
                <PhTrash v-if="props.danger !== false" :size="24" weight="fill" />
                <PhWarning v-else :size="24" weight="fill" />
            </div>
            
            <h3 class="text-[16px] font-bold text-text-primary">{{ props.title || 'Confirm Action' }}</h3>
            <p class="text-[13px] text-text-secondary leading-relaxed">
                {{ props.message || 'Are you sure you want to proceed?' }}
            </p>
        </div>

        <div class="px-6 py-4 bg-shell-panel border-t border-shell-border flex justify-end gap-3">
            <button 
                @click="$emit('close')"
                class="px-4 py-2 text-[13px] font-medium text-text-secondary hover:bg-slate-200 rounded-btn transition-colors border-0 cursor-pointer bg-transparent"
            >
                {{ props.cancelText || 'Cancel' }}
            </button>
            <button 
                @click="handleConfirm"
                class="px-5 py-2 text-white text-[13px] font-medium rounded-btn transition-colors border-0 cursor-pointer shadow-sm"
                :class="props.danger !== false ? 'bg-state-error hover:bg-red-700' : 'bg-dept-dev-main hover:bg-[#0F172A]'"
            >
                {{ props.confirmText || 'Confirm' }}
            </button>
        </div>
    </div>
</template>
