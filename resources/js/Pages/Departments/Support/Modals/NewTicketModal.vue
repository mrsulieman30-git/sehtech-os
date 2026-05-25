<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { useToastStore } from '@/Stores/useToastStore';
import { PhX } from '@phosphor-icons/vue';

const emit = defineEmits(['close']);
const toastStore = useToastStore();

const form = ref({
    title: '',
    description: '',
    priority: 'p2',
    category: 'technical',
});

const isSubmitting = ref(false);

const submit = async () => {
    if (!form.value.title || !form.value.description) {
        toastStore.showToast('Please fill in all required fields', 'error');
        return;
    }
    
    isSubmitting.value = true;
    try {
        await axios.post('/api/support/tickets', form.value);
        toastStore.showToast('Ticket created successfully', 'success');
        window.dispatchEvent(new CustomEvent('refresh-support-dashboard'));
        emit('close');
    } catch (e) {
        toastStore.showToast('Failed to create ticket', 'error');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div class="bg-white w-full max-w-md rounded-xl shadow-2xl overflow-hidden animate-fade-in-up border border-shell-border flex flex-col max-h-[90vh]">
        <div class="h-12 bg-shell-panel border-b border-shell-border flex items-center justify-between px-4 shrink-0">
            <h3 class="font-bold text-[14px] text-text-primary">Create New Ticket</h3>
            <button @click="$emit('close')" class="p-1.5 hover:bg-shell-border/50 rounded-btn transition-colors text-text-secondary cursor-pointer border-0 bg-transparent">
                <PhX :size="16" />
            </button>
        </div>
        
        <div class="p-5 flex-1 overflow-y-auto space-y-4">
            <div>
                <label class="block text-[11px] font-bold text-text-secondary uppercase mb-1.5">Ticket Title</label>
                <input v-model="form.title" type="text" placeholder="Brief summary of the issue..." class="w-full px-3 py-2 bg-slate-50 border border-shell-border rounded-input text-[13px] text-text-primary focus:ring-1 focus:ring-dept-support-main outline-none" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-text-secondary uppercase mb-1.5">Priority</label>
                    <select v-model="form.priority" class="w-full px-3 py-2 bg-slate-50 border border-shell-border rounded-input text-[13px] text-text-primary focus:ring-1 focus:ring-dept-support-main outline-none appearance-none">
                        <option value="p0">P0 - Critical Outage</option>
                        <option value="p1">P1 - High Priority</option>
                        <option value="p2">P2 - Normal</option>
                        <option value="p3">P3 - Low Priority</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-text-secondary uppercase mb-1.5">Category</label>
                    <select v-model="form.category" class="w-full px-3 py-2 bg-slate-50 border border-shell-border rounded-input text-[13px] text-text-primary focus:ring-1 focus:ring-dept-support-main outline-none appearance-none">
                        <option value="technical">Technical Support</option>
                        <option value="billing">Billing</option>
                        <option value="feature_request">Feature Request</option>
                        <option value="bug">Bug Report</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-text-secondary uppercase mb-1.5">Description</label>
                <textarea v-model="form.description" rows="5" placeholder="Detailed description of the problem..." class="w-full px-3 py-2 bg-slate-50 border border-shell-border rounded-input text-[13px] text-text-primary focus:ring-1 focus:ring-dept-support-main outline-none resize-none"></textarea>
            </div>
        </div>
        
        <div class="p-4 bg-shell-panel border-t border-shell-border flex justify-end gap-2 shrink-0">
            <button @click="$emit('close')" type="button" class="px-4 py-2 text-[13px] font-medium text-text-secondary hover:text-text-primary cursor-pointer border-0 bg-transparent transition-colors">
                Cancel
            </button>
            <button @click="submit" :disabled="isSubmitting" class="px-4 py-2 bg-dept-support-main hover:bg-opacity-90 text-white text-[13px] font-bold rounded-btn transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-sm border-0 cursor-pointer">
                {{ isSubmitting ? 'Creating...' : 'Create Ticket' }}
            </button>
        </div>
    </div>
</template>
