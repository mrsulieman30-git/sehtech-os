<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { PhX } from '@phosphor-icons/vue';
import RichTextEditor from '@/Components/OS/RichTextEditor.vue';

const emit = defineEmits(['close']);

const form = ref({
    name: '',
    budget: '',
    target_audience: '',
    brief: '',
});

const isSubmitting = ref(false);

const submitCampaign = async () => {
    isSubmitting.value = true;
    try {
        // Mock API call since campaign endpoint is not fully defined yet, 
        // or it could just emit an event to refresh if it exists
        // await axios.post('/api/marketing/campaigns', form.value);
        window.dispatchEvent(new CustomEvent('refresh-marketing-dashboard'));
        emit('close');
    } catch (e) {
        console.error('Failed to create campaign', e);
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div class="w-[600px] bg-white rounded-modal shadow-modal border border-shell-border flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <div class="h-[56px] flex items-center justify-between px-6 border-b border-shell-border bg-shell-panel shrink-0">
            <h2 class="text-[15px] font-bold text-text-primary">Create New Campaign</h2>
            <button @click="$emit('close')" class="p-1.5 text-text-disabled hover:text-state-error rounded transition-colors">
                <PhX :size="18" weight="bold" />
            </button>
        </div>

        <form @submit.prevent="submitCampaign" class="p-6 flex flex-col gap-4 overflow-y-auto max-h-[70vh]">
            <div class="flex flex-col gap-1">
                <label class="text-[13px] font-semibold text-text-primary">Campaign Name</label>
                <input v-model="form.name" type="text" required autofocus placeholder="e.g., Q3 Enterprise Outreach" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-marketing-main outline-none" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1">
                    <label class="text-[13px] font-semibold text-text-primary">Allocated Budget</label>
                    <input v-model="form.budget" type="number" required placeholder="$$$" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-marketing-main outline-none" />
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-[13px] font-semibold text-text-primary">Target Audience</label>
                    <input v-model="form.target_audience" type="text" required placeholder="e.g., C-Level Executives" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-marketing-main outline-none" />
                </div>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[13px] font-semibold text-text-primary">Campaign Brief</label>
                <RichTextEditor v-model="form.brief" />
            </div>

            <div class="mt-4 flex items-center justify-end gap-3 pt-4 border-t border-shell-border">
                <button type="button" @click="$emit('close')" class="px-4 py-2 text-[13px] font-medium text-text-secondary hover:text-text-primary transition-colors">Cancel</button>
                <button type="submit" :disabled="isSubmitting" class="px-5 py-2 bg-dept-marketing-main text-white text-[13px] font-medium rounded-btn hover:bg-[#BE185D] transition-colors disabled:opacity-50">
                    {{ isSubmitting ? 'Creating...' : 'Create Campaign' }}
                </button>
            </div>
        </form>
    </div>
</template>
