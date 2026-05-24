<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { PhX } from '@phosphor-icons/vue';

const emit = defineEmits(['close']);

const form = ref({
    name: '',
    type: 'server',
    provider: '',
    expiry_date: '',
    cost: null
});

const isSubmitting = ref(false);
const error = ref('');

const submitAsset = async () => {
    isSubmitting.value = true;
    error.value = '';
    
    try {
        await axios.post('/api/operations/assets', form.value);
        window.dispatchEvent(new CustomEvent('refresh-ops-dashboard'));
        emit('close');
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Failed to track asset';
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div class="w-[500px] bg-white rounded-modal shadow-modal border border-shell-border flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <div class="h-[56px] flex items-center justify-between px-6 border-b border-shell-border bg-shell-panel shrink-0">
            <h2 class="text-[15px] font-bold text-text-primary">Track New Infrastructure Asset</h2>
            <button @click="$emit('close')" class="p-1.5 text-text-disabled hover:text-state-error rounded transition-colors">
                <PhX :size="18" weight="bold" />
            </button>
        </div>

        <form @submit.prevent="submitAsset" class="p-6 flex flex-col gap-4">
            <div v-if="error" class="p-3 bg-state-error/10 border border-state-error/20 text-state-error rounded-input text-[13px] font-medium">{{ error }}</div>

            <div class="flex flex-col gap-1">
                <label class="text-[13px] font-semibold text-text-primary">Asset Name</label>
                <input v-model="form.name" type="text" required placeholder="e.g. Main Database Cluster" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-ops-main outline-none" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1">
                    <label class="text-[13px] font-semibold text-text-primary">Asset Type</label>
                    <select v-model="form.type" required class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-ops-main outline-none">
                        <option value="server">Server / VPS</option>
                        <option value="domain">Domain Name</option>
                        <option value="ssl">SSL Certificate</option>
                        <option value="license">Software License</option>
                        <option value="saas">SaaS Subscription</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-[13px] font-semibold text-text-primary">Provider / Vendor</label>
                    <input v-model="form.provider" type="text" required placeholder="e.g. AWS, Namecheap" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-ops-main outline-none" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1">
                    <label class="text-[13px] font-semibold text-text-primary">Renewal / Expiry Date</label>
                    <input v-model="form.expiry_date" type="date" required class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-ops-main outline-none" />
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-[13px] font-semibold text-text-primary">Cost (USD)</label>
                    <input v-model="form.cost" type="number" step="0.01" placeholder="e.g. 50.00" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-ops-main outline-none" />
                </div>
            </div>

            <div class="mt-4 flex items-center justify-end gap-3 pt-4 border-t border-shell-border">
                <button type="button" @click="$emit('close')" class="px-4 py-2 text-[13px] font-medium text-text-secondary hover:text-text-primary transition-colors">Cancel</button>
                <button type="submit" :disabled="isSubmitting" class="px-5 py-2 bg-dept-ops-main text-white text-[13px] font-medium rounded-btn hover:bg-[#475569] transition-colors disabled:opacity-50">
                    {{ isSubmitting ? 'Tracking...' : 'Track Asset' }}
                </button>
            </div>
        </form>
    </div>
</template>
