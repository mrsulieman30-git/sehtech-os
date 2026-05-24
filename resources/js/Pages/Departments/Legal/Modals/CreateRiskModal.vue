<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { PhX, PhWarningCircle } from '@phosphor-icons/vue';
import { useToastStore } from '@/Stores/useToastStore';

const emit = defineEmits(['close']);
const toastStore = useToastStore();

const form = ref({
    title: '',
    description: '',
    likelihood: 'medium',
    impact: 'medium',
    status: 'open',
    mitigation_plan: ''
});

const isSubmitting = ref(false);

const submit = async () => {
    isSubmitting.value = true;
    try {
        await axios.post('/api/legal/risks', form.value);
        window.dispatchEvent(new CustomEvent('refresh-legal'));
        toastStore.addToast('success', 'Risk recorded successfully');
        emit('close');
    } catch (error) {
        console.error(error);
        toastStore.addToast('error', 'Failed to record risk');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div class="fixed inset-0 z-[5000] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4 animate-in fade-in duration-200" @click.self="emit('close')">
        <div class="bg-white rounded-xl shadow-modal w-full max-w-[500px] flex flex-col overflow-hidden animate-in slide-in-from-bottom-4 duration-300">
            <div class="h-14 px-6 border-b border-shell-border flex items-center justify-between bg-shell-panel shrink-0">
                <div class="flex items-center gap-2 text-text-primary font-bold">
                    <PhWarningCircle :size="20" weight="fill" class="text-state-error" /> Log New Risk
                </div>
                <button @click="emit('close')" class="p-1.5 text-text-secondary hover:text-text-primary rounded-md hover:bg-black/5 transition-colors">
                    <PhX :size="18" />
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto max-h-[70vh] flex flex-col gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-[12px] font-bold text-text-secondary uppercase tracking-wider">Risk Title</label>
                    <input v-model="form.title" type="text" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-legal-main outline-none" placeholder="e.g. Data breach from third-party vendor" />
                </div>
                
                <div class="flex flex-col gap-1.5">
                    <label class="text-[12px] font-bold text-text-secondary uppercase tracking-wider">Description</label>
                    <textarea v-model="form.description" rows="3" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-legal-main outline-none"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[12px] font-bold text-text-secondary uppercase tracking-wider">Likelihood</label>
                        <select v-model="form.likelihood" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-legal-main outline-none">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[12px] font-bold text-text-secondary uppercase tracking-wider">Impact</label>
                        <select v-model="form.impact" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-legal-main outline-none">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-[12px] font-bold text-text-secondary uppercase tracking-wider">Mitigation Plan</label>
                    <textarea v-model="form.mitigation_plan" rows="3" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-legal-main outline-none" placeholder="Steps to reduce or manage this risk..."></textarea>
                </div>
            </div>

            <div class="p-6 border-t border-shell-border bg-shell-panel flex justify-end gap-3 shrink-0">
                <button @click="emit('close')" class="px-4 py-2 text-[13px] font-medium text-text-secondary hover:text-text-primary transition-colors">
                    Cancel
                </button>
                <button 
                    @click="submit" 
                    :disabled="isSubmitting || !form.title"
                    class="px-5 py-2 bg-dept-legal-main text-white text-[13px] font-bold rounded-btn hover:bg-[#5B21B6] transition-colors shadow-sm disabled:opacity-50"
                >
                    {{ isSubmitting ? 'Saving...' : 'Save Risk' }}
                </button>
            </div>
        </div>
    </div>
</template>
