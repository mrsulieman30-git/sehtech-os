<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { PhX } from '@phosphor-icons/vue';

const props = defineProps<{
    projectId: string;
    nodeId: string;
}>();

const emit = defineEmits(['close', 'task-created']);

const form = ref({
    title: '',
    description: '',
    priority: 'p2',
    status: 'backlog',
    story_points: null as number | null,
    due_date: '',
    assignees: [] as string[],
    node_id: props.nodeId
});

const isSubmitting = ref(false);
const error = ref('');
const users = ref<any[]>([]);

import { onMounted } from 'vue';
onMounted(async () => {
    try {
        const res = await axios.get('/api/admin/users');
        users.value = res.data.users;
    } catch (e) {
        console.error('Failed to load users', e);
    }
});

const submitTask = async () => {
    isSubmitting.value = true;
    error.value = '';
    
    try {
        const response = await axios.post('/api/development/tasks', {
            ...form.value,
            project_id: props.projectId
        });
        
        // Let the parent window know to refresh the board
        // In a real app, we might use an EventBus or Pinia to trigger the refresh
        window.dispatchEvent(new CustomEvent('refresh-dev-board'));
        emit('close');
        
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Failed to create task';
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div class="w-[460px] max-h-[85vh] bg-white rounded-modal shadow-modal border border-shell-border flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        
        <div class="h-[48px] flex items-center justify-between px-5 border-b border-shell-border bg-shell-panel shrink-0">
            <h2 class="text-[14px] font-bold text-text-primary">Create New Task</h2>
            <button @click="$emit('close')" class="p-1.5 text-text-disabled hover:text-state-error rounded transition-colors">
                <PhX :size="16" weight="bold" />
            </button>
        </div>

        <form @submit.prevent="submitTask" class="flex-1 overflow-y-auto custom-scrollbar">
            <div class="p-5 flex flex-col gap-3">
                
                <div v-if="error" class="p-2.5 bg-state-error/10 border border-state-error/20 text-state-error rounded-input text-[12px] font-medium">
                    {{ error }}
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-[12px] font-semibold text-text-primary">Task Title *</label>
                    <input 
                        v-model="form.title" 
                        type="text" 
                        required
                        autofocus
                        placeholder="e.g. Implement user authentication..."
                        class="w-full px-3 py-1.5 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-dev-main outline-none"
                    />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="flex flex-col gap-1">
                        <label class="text-[12px] font-semibold text-text-primary">Status</label>
                        <select v-model="form.status" class="w-full px-3 py-1.5 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-dev-main outline-none">
                            <option value="backlog">Backlog</option>
                            <option value="todo">To Do</option>
                            <option value="in_progress">In Progress</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-[12px] font-semibold text-text-primary">Priority</label>
                        <select v-model="form.priority" class="w-full px-3 py-1.5 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-dev-main outline-none">
                            <option value="p0">P0 - Critical</option>
                            <option value="p1">P1 - High</option>
                            <option value="p2">P2 - Medium</option>
                            <option value="p3">P3 - Low</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="flex flex-col gap-1">
                        <label class="text-[12px] font-semibold text-text-primary">Story Points</label>
                        <input 
                            v-model="form.story_points" 
                            type="number" 
                            min="1" max="100"
                            placeholder="e.g. 5"
                            class="w-full px-3 py-1.5 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-dev-main outline-none"
                        />
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-[12px] font-semibold text-text-primary">Due Date</label>
                        <input 
                            v-model="form.due_date" 
                            type="date" 
                            class="w-full px-3 py-1.5 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-dev-main outline-none"
                        />
                    </div>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-[12px] font-semibold text-text-primary">Assignees</label>
                    <select 
                        v-model="form.assignees" 
                        multiple 
                        class="w-full px-3 py-1.5 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-dev-main outline-none h-[60px]"
                    >
                        <option v-for="user in users" :key="user.id" :value="user.id">
                            {{ user.name }}
                        </option>
                    </select>
                    <span class="text-[10px] text-text-disabled">Hold Ctrl/Cmd to select multiple</span>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-[12px] font-semibold text-text-primary">Description</label>
                    <textarea 
                        v-model="form.description" 
                        rows="2"
                        placeholder="Add more details about this task..."
                        class="w-full px-3 py-1.5 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-dev-main outline-none resize-none"
                    ></textarea>
                </div>
            </div>

            <div class="px-5 py-3 flex items-center justify-end gap-3 border-t border-shell-border bg-gray-50/50 shrink-0 sticky bottom-0">
                <button 
                    type="button" 
                    @click="$emit('close')"
                    class="px-4 py-1.5 text-[13px] font-medium text-text-secondary hover:text-text-primary transition-colors"
                >
                    Cancel
                </button>
                <button 
                    type="submit" 
                    :disabled="isSubmitting"
                    class="px-5 py-1.5 bg-dept-dev-main text-white text-[13px] font-medium rounded-btn hover:bg-[#1E293B] transition-colors disabled:opacity-50"
                >
                    {{ isSubmitting ? 'Creating...' : 'Create Task' }}
                </button>
            </div>
        </form>
    </div>
</template>

