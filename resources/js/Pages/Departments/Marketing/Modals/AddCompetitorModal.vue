<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { PhX } from '@phosphor-icons/vue';

const emit = defineEmits(['close']);

const form = ref({
    name: '',
    product_category: '',
    pricing_tier: '',
    strengths: '',
    weaknesses: ''
});

const isSubmitting = ref(false);
const error = ref('');

const submitCompetitor = async () => {
    isSubmitting.value = true;
    error.value = '';
    
    try {
        await axios.post('/api/marketing/competitors', form.value);
        window.dispatchEvent(new CustomEvent('refresh-marketing-dashboard'));
        emit('close');
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Failed to add competitor';
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div class="w-[500px] bg-white rounded-modal shadow-modal border border-shell-border flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <div class="h-[56px] flex items-center justify-between px-6 border-b border-shell-border bg-shell-panel shrink-0">
            <h2 class="text-[15px] font-bold text-text-primary">Add Competitor Profile</h2>
            <button @click="$emit('close')" class="p-1.5 text-text-disabled hover:text-state-error rounded transition-colors">
                <PhX :size="18" weight="bold" />
            </button>
        </div>

        <form @submit.prevent="submitCompetitor" class="p-6 flex flex-col gap-4">
            <div v-if="error" class="p-3 bg-state-error/10 border border-state-error/20 text-state-error rounded-input text-[13px] font-medium">{{ error }}</div>

            <div class="flex flex-col gap-1">
                <label class="text-[13px] font-semibold text-text-primary">Company / Product Name</label>
                <input v-model="form.name" type="text" required class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-marketing-main outline-none" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1">
                    <label class="text-[13px] font-semibold text-text-primary">Product Category</label>
                    <input v-model="form.product_category" type="text" required placeholder="e.g. Telehealth" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-marketing-main outline-none" />
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-[13px] font-semibold text-text-primary">Pricing Tier</label>
                    <input v-model="form.pricing_tier" type="text" placeholder="e.g. Enterprise ($5k/mo)" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-marketing-main outline-none" />
                </div>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[13px] font-semibold text-text-primary">Key Strengths</label>
                <textarea v-model="form.strengths" rows="2" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-marketing-main outline-none resize-none"></textarea>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[13px] font-semibold text-text-primary">Key Weaknesses</label>
                <textarea v-model="form.weaknesses" rows="2" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-marketing-main outline-none resize-none"></textarea>
            </div>

            <div class="mt-4 flex items-center justify-end gap-3 pt-4 border-t border-shell-border">
                <button type="button" @click="$emit('close')" class="px-4 py-2 text-[13px] font-medium text-text-secondary hover:text-text-primary transition-colors">Cancel</button>
                <button type="submit" :disabled="isSubmitting" class="px-5 py-2 bg-dept-marketing-main text-white text-[13px] font-medium rounded-btn hover:bg-[#BE185D] transition-colors disabled:opacity-50">
                    {{ isSubmitting ? 'Saving...' : 'Add Competitor' }}
                </button>
            </div>
        </form>
    </div>
</template>
