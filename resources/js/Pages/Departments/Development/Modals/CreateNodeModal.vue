<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { PhX, PhFolder, PhKanban } from '@phosphor-icons/vue';

const props = defineProps<{
    projectId: string;
    parentId: string | null;
    type: 'folder' | 'board';
}>();

const emit = defineEmits(['close']);

const form = ref({
    name: '',
    type: props.type,
    parent_id: props.parentId
});

const isSubmitting = ref(false);
const error = ref('');

const submitNode = async () => {
    isSubmitting.value = true;
    error.value = '';
    
    try {
        await axios.post(`/api/development/projects/${props.projectId}/nodes`, form.value);
        window.dispatchEvent(new CustomEvent('refresh-dev-board'));
        emit('close');
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Failed to create item';
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div class="w-[400px] bg-white rounded-modal shadow-modal border border-shell-border flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        
        <div class="h-[52px] flex items-center justify-between px-6 border-b border-shell-border bg-shell-panel shrink-0">
            <h2 class="text-[15px] font-bold text-text-primary flex items-center gap-2">
                <PhFolder v-if="props.type === 'folder'" :size="18" weight="fill" class="text-dept-dev-main"/>
                <PhKanban v-else :size="18" weight="fill" class="text-teal-500"/>
                New {{ props.type === 'folder' ? 'Folder' : 'Task File' }}
            </h2>
            <button @click="$emit('close')" class="p-1.5 text-text-disabled hover:text-state-error rounded transition-colors">
                <PhX :size="16" weight="bold" />
            </button>
        </div>

        <form @submit.prevent="submitNode" class="p-6 flex flex-col gap-4">
            
            <div v-if="error" class="p-3 bg-state-error/10 border border-state-error/20 text-state-error rounded-input text-[13px] font-medium">
                {{ error }}
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[13px] font-semibold text-text-primary">Name *</label>
                <input 
                    v-model="form.name" 
                    type="text" 
                    required
                    autofocus
                    :placeholder="props.type === 'folder' ? 'e.g. Frontend, API...' : 'e.g. Bug Fixes, Sprint 1...'"
                    class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-dev-main outline-none"
                />
            </div>

            <div class="mt-4 flex items-center justify-end gap-3 pt-4 border-t border-shell-border">
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
                    {{ isSubmitting ? 'Creating...' : 'Create' }}
                </button>
            </div>
        </form>
    </div>
</template>
