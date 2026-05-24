<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { PhX, PhFolder } from '@phosphor-icons/vue';

const emit = defineEmits(['close']);

const form = ref({
    name: '',
    description: ''
});

const isSubmitting = ref(false);
const error = ref('');

const submitProject = async () => {
    isSubmitting.value = true;
    error.value = '';
    
    try {
        await axios.post('/api/development/projects', form.value);
        window.dispatchEvent(new CustomEvent('refresh-dev-board'));
        emit('close');
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Failed to create project';
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div class="w-[460px] bg-white rounded-modal shadow-modal border border-shell-border flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        
        <div class="h-[56px] flex items-center justify-between px-6 border-b border-shell-border bg-shell-panel shrink-0">
            <h2 class="text-[15px] font-bold text-text-primary flex items-center gap-2">
                <PhFolder :size="18" weight="fill" class="text-dept-dev-main"/>
                New Project
            </h2>
            <button @click="$emit('close')" class="p-1.5 text-text-disabled hover:text-state-error rounded transition-colors">
                <PhX :size="18" weight="bold" />
            </button>
        </div>

        <form @submit.prevent="submitProject" class="p-6 flex flex-col gap-4">
            
            <div v-if="error" class="p-3 bg-state-error/10 border border-state-error/20 text-state-error rounded-input text-[13px] font-medium">
                {{ error }}
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[13px] font-semibold text-text-primary">Project Name *</label>
                <input 
                    v-model="form.name" 
                    type="text" 
                    required
                    autofocus
                    placeholder="e.g. Mobile App v2, Backend API..."
                    class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-dev-main outline-none"
                />
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[13px] font-semibold text-text-primary">Description</label>
                <textarea 
                    v-model="form.description" 
                    rows="3"
                    placeholder="Brief description of this project..."
                    class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-dev-main outline-none resize-none"
                ></textarea>
            </div>

            <div class="mt-2 flex items-center justify-end gap-3 pt-4 border-t border-shell-border">
                <button 
                    type="button" 
                    @click="$emit('close')"
                    class="px-4 py-2 text-[13px] font-medium text-text-secondary hover:text-text-primary transition-colors"
                >
                    Cancel
                </button>
                <button 
                    type="submit" 
                    :disabled="isSubmitting"
                    class="px-5 py-2 bg-dept-dev-main text-white text-[13px] font-medium rounded-btn hover:bg-[#1E293B] transition-colors disabled:opacity-50"
                >
                    {{ isSubmitting ? 'Creating...' : 'Create Project' }}
                </button>
            </div>
        </form>
    </div>
</template>
