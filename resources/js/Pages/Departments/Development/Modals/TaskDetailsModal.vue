<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { PhX, PhCheck, PhPencilSimple, PhPaperclip, PhChatCircle } from '@phosphor-icons/vue';
import dayjs from 'dayjs';
import RichTextEditor from '@/Components/OS/RichTextEditor.vue';

const props = defineProps<{
    task: any;
}>();

const emit = defineEmits(['close']);

const isEditing = ref(false);
const isSavingTask = ref(false);

const form = ref({
    ...props.task,
    assignees: props.task.assignees ? props.task.assignees.map((a: any) => a.id) : []
});

const users = ref<any[]>([]);

const newComment = ref('');
const isSubmittingComment = ref(false);

onMounted(async () => {
    try {
        const res = await axios.get('/api/admin/users');
        users.value = res.data.users;
    } catch (e) {
        console.error('Failed to load users', e);
    }
});

const saveTaskDetails = async () => {
    isSavingTask.value = true;
    try {
        const response = await axios.put(`/api/development/tasks/${props.task.id}`, {
            title: form.value.title,
            description: form.value.description,
            priority: form.value.priority,
            status: form.value.status,
            story_points: form.value.story_points,
            due_date: form.value.due_date,
            assignees: form.value.assignees
        });
        
        // Update local task with saved data
        Object.assign(props.task, response.data.task);
        isEditing.value = false;
        
        window.dispatchEvent(new CustomEvent('refresh-dev-board'));
    } catch (error) {
        console.error('Failed to save task details', error);
    } finally {
        isSavingTask.value = false;
    }
};

const submitComment = async () => {
    if (!newComment.value.trim()) return;
    isSubmittingComment.value = true;
    try {
        const response = await axios.post(`/api/development/tasks/${props.task.id}/comments`, {
            body: newComment.value
        });
        
        if (!props.task.comments) {
            props.task.comments = [];
        }
        props.task.comments.push(response.data.comment);
        newComment.value = '';
    } catch (error) {
        console.error('Failed to add comment', error);
    } finally {
        isSubmittingComment.value = false;
    }
};

const getPriorityColor = (priority: string) => {
    const map: Record<string, string> = {
        'p0': 'text-red-600',
        'p1': 'text-orange-600',
        'p2': 'text-blue-600',
        'p3': 'text-slate-600'
    };
    return map[priority] || map['p2'];
};

const getPriorityName = (priority: string) => {
    const map: Record<string, string> = {
        'p0': 'P0 - Critical',
        'p1': 'P1 - High',
        'p2': 'P2 - Medium',
        'p3': 'P3 - Low'
    };
    return map[priority] || 'P2 - Medium';
};

const getStatusName = (status: string) => {
    const map: Record<string, string> = {
        'backlog': 'Backlog',
        'todo': 'To Do',
        'in_progress': 'In Progress',
        'review': 'Code Review',
        'qa': 'QA Testing',
        'done': 'Done',
        'deployed': 'Deployed'
    };
    return map[status] || status;
};
</script>

<template>
    <div 
        class="bg-white rounded-modal shadow-modal border border-shell-border flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200 transition-all"
        :class="isEditing ? 'w-[90vw] h-[85vh] max-w-[1200px]' : 'w-[600px] max-h-[85vh]'"
    >
        
        <!-- Header -->
        <div class="h-[56px] flex items-center justify-between px-6 border-b border-shell-border bg-shell-panel shrink-0 transition-colors" :class="{'bg-dept-dev-sec/10': isEditing}">
            <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider bg-white border border-shell-border shadow-sm">
                    {{ props.task.id.split('-')[0].toUpperCase() }}
                </span>
                <h2 v-if="!isEditing" class="text-[15px] font-bold text-text-primary truncate max-w-[300px]">{{ props.task.title }}</h2>
                <h2 v-else class="text-[15px] font-bold text-dept-dev-main">Edit Mode Active</h2>
            </div>
            
            <div class="flex items-center gap-2">
                <button v-if="!isEditing" @click="isEditing = true" class="px-3 py-1.5 flex items-center gap-2 bg-dept-dev-main text-white text-[12px] font-medium rounded-btn hover:bg-[#1E293B] transition-colors">
                    <PhPencilSimple :size="14" /> Edit Task
                </button>
                <button v-if="isEditing" @click="saveTaskDetails" :disabled="isSavingTask" class="px-3 py-1.5 flex items-center gap-2 bg-state-success text-white text-[12px] font-medium rounded-btn hover:bg-green-700 transition-colors disabled:opacity-50">
                    <PhCheck :size="14" /> {{ isSavingTask ? 'Saving...' : 'Save Changes' }}
                </button>
                <button v-if="isEditing" @click="isEditing = false; form = {...props.task}" class="px-3 py-1.5 text-[12px] font-medium text-text-secondary hover:text-text-primary transition-colors">
                    Cancel
                </button>
                
                <div class="w-px h-6 bg-shell-border mx-1"></div>
                <button @click="$emit('close')" class="p-1.5 text-text-disabled hover:text-state-error rounded transition-colors">
                    <PhX :size="18" weight="bold" />
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto flex flex-col md:flex-row relative">
            
            <!-- VIEW MODE -->
            <div v-if="!isEditing" class="flex-1 flex flex-col h-full w-full">
                <div class="p-6 flex-1 flex flex-col gap-6">
                    <div class="flex flex-col gap-4 border-b border-shell-border pb-6">
                        <h1 class="text-[20px] font-bold text-text-primary leading-snug">{{ props.task.title }}</h1>
                        
                        <div class="flex flex-wrap gap-4 text-[13px] text-text-secondary">
                            <div class="flex items-center gap-1.5">
                                <span class="font-semibold text-text-primary">Status:</span> 
                                <span class="px-2 py-0.5 rounded bg-shell-border/50 text-text-primary font-medium">{{ getStatusName(props.task.status) }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="font-semibold text-text-primary">Priority:</span> 
                                <span class="font-bold" :class="getPriorityColor(props.task.priority)">{{ getPriorityName(props.task.priority) }}</span>
                            </div>
                            <div v-if="props.task.story_points" class="flex items-center gap-1.5">
                                <span class="font-semibold text-text-primary">Points:</span> {{ props.task.story_points }}
                            </div>
                            <div v-if="props.task.due_date" class="flex items-center gap-1.5">
                                <span class="font-semibold text-text-primary">Due:</span> {{ dayjs(props.task.due_date).format('MMM D, YYYY') }}
                            </div>
                        </div>

                        <div v-if="props.task.assignees?.length > 0" class="flex items-center gap-2">
                            <span class="text-[12px] font-semibold text-text-disabled uppercase">Assignees:</span>
                            <div class="flex gap-2">
                                <div v-for="user in props.task.assignees" :key="user.id" class="flex items-center gap-1.5 px-2 py-1 bg-shell-panel border border-shell-border rounded-full text-[12px]">
                                    <img v-if="user.avatar" :src="user.avatar" class="w-4 h-4 rounded-full object-cover"/>
                                    <div v-else class="w-4 h-4 rounded-full bg-dept-dev-main text-white flex items-center justify-center text-[8px]">{{ user.name.charAt(0) }}</div>
                                    <span>{{ user.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <h3 class="text-[14px] font-bold text-text-primary">Description</h3>
                        <div v-if="props.task.description" class="prose prose-sm max-w-none text-[14px] text-text-secondary bg-shell-panel p-4 rounded-card border border-shell-border" v-html="props.task.description"></div>
                        <div v-else class="text-[13px] text-text-disabled italic">No description provided. Click Edit to add one.</div>
                    </div>

                    <!-- Discussion -->
                    <div class="flex flex-col gap-4 mt-auto border-t border-shell-border pt-6">
                        <h3 class="text-[14px] font-bold text-text-primary flex items-center gap-2">
                            <PhChatCircle :size="18"/> Discussion ({{ props.task.comments?.length || 0 }})
                        </h3>
                        
                        <div class="flex flex-col gap-4 max-h-[300px] overflow-y-auto pr-2">
                            <div v-for="comment in props.task.comments" :key="comment.id" class="flex gap-3">
                                <div class="w-8 h-8 rounded-full border border-shell-border bg-dept-dev-main flex shrink-0 items-center justify-center text-[12px] text-white overflow-hidden">
                                    <img v-if="comment.user?.avatar" :src="comment.user.avatar" class="w-full h-full object-cover"/>
                                    <span v-else>{{ comment.user?.name?.charAt(0) || 'U' }}</span>
                                </div>
                                <div class="flex flex-col flex-1 bg-shell-panel p-3 rounded-card border border-shell-border">
                                    <div class="flex justify-between items-baseline mb-1">
                                        <span class="text-[13px] font-semibold text-text-primary">{{ comment.user?.name || 'User' }}</span>
                                        <span class="text-[11px] text-text-disabled">{{ dayjs(comment.created_at).format('MMM D, h:mm A') }}</span>
                                    </div>
                                    <p class="text-[13px] text-text-secondary whitespace-pre-wrap">{{ comment.body }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-2 mt-2">
                            <textarea 
                                v-model="newComment"
                                placeholder="Write a comment..."
                                class="flex-1 border border-shell-border p-3 rounded-input text-[13px] outline-none focus:ring-1 focus:ring-dept-dev-main resize-none h-[80px]"
                            ></textarea>
                        </div>
                        <div class="flex justify-end">
                            <button 
                                @click="submitComment"
                                :disabled="isSubmittingComment || !newComment.trim()"
                                class="px-4 py-1.5 bg-dept-dev-main text-white text-[13px] font-medium rounded-btn hover:bg-[#1E293B] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ isSubmittingComment ? 'Posting...' : 'Post Comment' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EDIT MODE -->
            <div v-else class="flex-1 flex flex-col md:flex-row h-full w-full overflow-hidden">
                <div class="flex-1 p-6 border-r border-shell-border flex flex-col gap-4 overflow-y-auto h-full">
                    <div class="flex flex-col gap-1">
                        <label class="text-[13px] font-semibold text-text-primary">Task Title</label>
                        <input 
                            v-model="form.title" 
                            type="text" 
                            class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[14px] focus:ring-1 focus:ring-dept-dev-main outline-none font-medium"
                        />
                    </div>
                    
                    <div class="flex flex-col gap-1 flex-1 min-h-[300px]">
                        <label class="text-[13px] font-semibold text-text-primary">Description</label>
                        <div class="border border-shell-border rounded overflow-hidden flex-1 flex flex-col bg-white">
                            <RichTextEditor 
                                v-model="form.description"
                                placeholder="Add a description, checklists, or spec here..."
                            />
                        </div>
                    </div>
                </div>

                <div class="w-[300px] p-6 bg-shell-panel flex flex-col gap-5 overflow-y-auto h-full">
                    <div class="flex flex-col gap-1">
                        <label class="text-[13px] font-semibold text-text-primary">Status</label>
                        <select v-model="form.status" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-dev-main outline-none">
                            <option value="backlog">Backlog</option>
                            <option value="todo">To Do</option>
                            <option value="in_progress">In Progress</option>
                            <option value="review">Code Review</option>
                            <option value="qa">QA Testing</option>
                            <option value="done">Done</option>
                            <option value="deployed">Deployed</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-[13px] font-semibold text-text-primary">Priority</label>
                        <select v-model="form.priority" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-dev-main outline-none">
                            <option value="p0">P0 - Critical</option>
                            <option value="p1">P1 - High</option>
                            <option value="p2">P2 - Medium</option>
                            <option value="p3">P3 - Low</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-[13px] font-semibold text-text-primary">Story Points</label>
                        <input 
                            v-model="form.story_points" 
                            type="number" 
                            class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-dev-main outline-none"
                        />
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-[13px] font-semibold text-text-primary">Due Date</label>
                        <input 
                            v-model="form.due_date" 
                            type="date" 
                            class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-dev-main outline-none"
                        />
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-[13px] font-semibold text-text-primary">Assignees</label>
                        <select 
                            v-model="form.assignees" 
                            multiple 
                            class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-dev-main outline-none h-[120px]"
                        >
                            <option v-for="user in users" :key="user.id" :value="user.id">
                                {{ user.name }}
                            </option>
                        </select>
                        <span class="text-[10px] text-text-disabled">Hold Ctrl/Cmd to select multiple</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>
