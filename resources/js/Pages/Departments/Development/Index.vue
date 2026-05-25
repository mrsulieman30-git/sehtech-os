<script setup lang="ts">
import { ref, reactive, computed, onMounted, onUnmounted, nextTick } from 'vue';
import axios from 'axios';
import { VueDraggable } from 'vue-draggable-plus';
import { 
    PhFolder, PhCaretRight, PhCaretDown, PhClock, 
    PhChatCircle, PhPaperclip, PhRobot, PhPlus, PhKanban, PhTrash
} from '@phosphor-icons/vue';
import dayjs from 'dayjs';
import { useModalStore } from '@/Stores/useModalStore';
import { useToastStore } from '@/Stores/useToastStore';
import { usePage } from '@inertiajs/vue3';
import ExplorerNode from './Components/ExplorerNode.vue';
import RichMessageRenderer from '@/Components/Ai/RichMessageRenderer.vue';
import { PhArrowRight } from '@phosphor-icons/vue';

const modalStore = useModalStore();
const toastStore = useToastStore();
const auth = usePage().props.auth as any;

const canManage = computed(() => {
    return auth.user?.role?.name === 'admin' || auth.user?.role?.name === 'manager' || auth.user?.role?.is_super_admin;
});

const canEditTask = (task: Task) => {
    if (canManage.value) return true;
    return task.assignees && task.assignees.some(u => u.id === auth.user.id);
};

// Types
interface Assignee { id: string; name: string; avatar: string | null; }
interface Task {
    id: string; title: string; status: string; priority: string; 
    due_date: string | null; assignees: Assignee[]; 
    attachments_count: number; story_points: number;
    description: string;
    comments: any[];
    node_id: string | null;
}
interface Project {
    id: string; name: string; status: string; tasks: Task[]; meta?: any; nodes?: any[];
}

const projects = ref<Project[]>([]);
const activeProjectId = ref<string | null>(null);
const expandedFolders = ref<Record<string, boolean>>({});
const activeNodeId = ref<string | null>(null);

const columns = [
    { id: 'backlog', title: 'Backlog', border: 'border-slate-300', bg: 'bg-slate-50' },
    { id: 'todo', title: 'To Do', border: 'border-red-300', bg: 'bg-red-50' },
    { id: 'in_progress', title: 'In Progress', border: 'border-yellow-300', bg: 'bg-yellow-50' },
    { id: 'review', title: 'Code Review', border: 'border-purple-300', bg: 'bg-purple-50' },
    { id: 'qa', title: 'QA Testing', border: 'border-orange-300', bg: 'bg-orange-50' },
    { id: 'done', title: 'Done', border: 'border-green-300', bg: 'bg-green-50' },
    { id: 'deployed', title: 'Deployed', border: 'border-teal-300', bg: 'bg-teal-50' }
];

// Pre-initialize all column arrays so v-model never sees undefined
const boardTasks = reactive<Record<string, Task[]>>(
    Object.fromEntries(columns.map(col => [col.id, []]))
);

const populateBoard = () => {
    const proj = projects.value.find(p => p.id === activeProjectId.value);
    let tasks = proj ? proj.tasks : [];
    if (activeNodeId.value) {
        tasks = tasks.filter(t => t.node_id === activeNodeId.value);
    } else {
        // If no node is selected, show no tasks (or all tasks, depending on preference. Let's show empty if they haven't selected a board)
        tasks = [];
    }
    columns.forEach(col => {
        boardTasks[col.id] = tasks.filter(t => t.status === col.id);
    });
};

const getBreadcrumbs = (projectId: string, nodeId: string | null) => {
    const proj = projects.value.find(p => p.id === projectId);
    if (!proj) return [];
    
    const crumbs = [proj.name];
    if (!nodeId) return crumbs;

    const buildPath = (currentId: string, path: string[]) => {
        const node = proj.nodes?.find((n: any) => n.id === currentId);
        if (!node) return path;
        path.unshift(node.name);
        if (node.parent_id) {
            buildPath(node.parent_id, path);
        }
        return path;
    };
    
    const nodePath = buildPath(nodeId, []);
    return [...crumbs, ...nodePath];
};

const addRootNode = (projectId: string, type: 'folder' | 'board') => {
    modalStore.openModal('create-node', {
        projectId,
        parentId: null,
        type
    });
};

const isDragOverProject = ref<string | null>(null);

const onProjectDragStart = (e: DragEvent, projectId: string) => {
    if (!canManage.value) return e.preventDefault();
    if (e.dataTransfer) {
        e.dataTransfer.setData('text/plain', JSON.stringify({ type: 'project', id: projectId }));
        e.dataTransfer.effectAllowed = 'move';
    }
};

const onProjectDragOver = (e: DragEvent, projectId: string) => {
    if (!canManage.value) return;
    e.preventDefault();
    isDragOverProject.value = projectId;
};

const onProjectDragLeave = () => {
    isDragOverProject.value = null;
};

const onProjectDrop = async (e: DragEvent, targetProjectId: string) => {
    isDragOverProject.value = null;
    if (!canManage.value) return;
    
    const dataStr = e.dataTransfer?.getData('text/plain');
    if (!dataStr) return;
    
    try {
        const data = JSON.parse(dataStr);
        if (data.type === 'node') {
            await axios.put(`/api/development/nodes/${data.id}/move`, {
                parent_id: null,
                project_id: targetProjectId
            });
            fetchBoardData();
        } else if (data.type === 'project' && data.id !== targetProjectId) {
            await axios.post('/api/development/projects/merge', {
                source_project_id: data.id,
                target_project_id: targetProjectId,
                target_parent_id: null
            });
            toastStore.addToast('success', 'Project merged successfully');
            fetchBoardData();
        }
    } catch (err) {
        toastStore.addToast('error', 'Failed to move item to project');
    }
};

const fetchBoardData = async () => {
    try {
        const response = await axios.get('/api/development/board');
        projects.value = response.data.projects;
        if (projects.value.length > 0 && !activeProjectId.value) {
            activeProjectId.value = projects.value[0].id;
        }
        populateBoard();
    } catch (error) {
        console.error('Failed to fetch board data', error);
    }
};

const activeProjectTasks = computed(() => {
    const proj = projects.value.find(p => p.id === activeProjectId.value);
    return proj ? proj.tasks : [];
});

const getTasksByStatus = (status: string) => {
    return activeProjectTasks.value.filter(t => t.status === status);
};

// --- Drag and Drop ---
// Track dragging state so we can suppress @click during a drag
const isDragging = ref(false);

const onDragStart = () => {
    isDragging.value = true;
};

const onDragEnd = (evt: any) => {
    // The VueDraggable already moved the item in the arrays via v-model.
    // We just need to figure out which column the item landed in and update the API.
    const taskId = evt.item?.dataset?.taskId;
    if (!taskId) {
        isDragging.value = false;
        return;
    }

    // Find which column now contains this task
    let newStatus = '';
    for (const col of columns) {
        if (boardTasks[col.id].some(t => t.id === taskId)) {
            newStatus = col.id;
            break;
        }
    }

    if (newStatus) {
        // Find the task and update its status locally
        const task = boardTasks[newStatus].find(t => t.id === taskId);
        if (task && task.status !== newStatus) {
            task.status = newStatus;

            // Also sync back to the projects source array
            const proj = projects.value.find(p => p.id === activeProjectId.value);
            if (proj) {
                const sourceTask = proj.tasks.find(t => t.id === taskId);
                if (sourceTask) sourceTask.status = newStatus;
            }

            // Persist to backend
            axios.put(`/api/development/tasks/${taskId}/status`, { status: newStatus }).catch(err => {
                console.error('Failed to update task status', err);
            });
        }
    }

    // Reset dragging flag after a short delay so the click handler can check it
    setTimeout(() => {
        isDragging.value = false;
    }, 100);
};

const openTask = (task: Task) => {
    // Only open modal if we weren't just dragging
    if (isDragging.value) return;
    modalStore.openModal('task-details', { task });
};

// --- End Drag and Drop ---

const toggleFolder = (id: string) => {
    expandedFolders.value[id] = !expandedFolders.value[id];
};

const selectProject = (id: string) => {
    activeProjectId.value = id;
    activeNodeId.value = null;
    populateBoard();
};

const selectBoard = (nodeId: string) => {
    activeNodeId.value = nodeId;
    populateBoard();
};

const getPriorityColor = (priority: string) => {
    const map: Record<string, string> = {
        'p0': 'bg-red-100 text-red-700',
        'p1': 'bg-orange-100 text-orange-700',
        'p2': 'bg-blue-100 text-blue-700',
        'p3': 'bg-slate-100 text-slate-700'
    };
    return map[priority] || map['p2'];
};

const handleBoardRefresh = () => fetchBoardData();

// Development Agent Chat logic
const chatMessage = ref('');
const chatMessages = ref<{ role: string; content: string }>([
    {
        role: 'assistant',
        content: 'I am DEV COPILOT. Need help generating boilerplate, reviewing code, or breaking down epics?'
    }
]);
const isChatTyping = ref(false);
const chatScrollContainer = ref<HTMLElement | null>(null);

const scrollToBottom = () => {
    setTimeout(() => {
        if (chatScrollContainer.value) {
            chatScrollContainer.value.scrollTop = chatScrollContainer.value.scrollHeight;
        }
    }, 50);
};

const sendChatMessage = async () => {
    if (!chatMessage.value.trim() || isChatTyping.value) return;
    
    const userMsg = chatMessage.value;
    chatMessages.value.push({ role: 'user', content: userMsg });
    chatMessage.value = '';
    isChatTyping.value = true;
    scrollToBottom();
    
    try {
        const response = await axios.post('/api/agents/development-agent/chat', {
            message: userMsg
        });
        chatMessages.value.push({ role: 'assistant', content: response.data.response_text });
    } catch (e) {
        chatMessages.value.push({ 
            role: 'assistant', 
            content: 'Agent is currently offline. Please ensure the AI service is running.' 
        });
    } finally {
        isChatTyping.value = false;
        scrollToBottom();
    }
};

onMounted(() => {
    fetchBoardData();
    window.addEventListener('refresh-dev-board', handleBoardRefresh);
});

onUnmounted(() => {
    window.removeEventListener('refresh-dev-board', handleBoardRefresh);
});
</script>

<template>
    <div class="h-full flex flex-col md:flex-row bg-white text-text-primary overflow-hidden font-sans">
        
        <div class="w-[240px] flex-shrink-0 border-r border-shell-border bg-shell-panel flex flex-col h-full">
            <div class="h-[44px] flex items-center px-4 border-b border-shell-border bg-shell-panel shrink-0">
                <span class="text-[12px] font-bold text-text-secondary uppercase tracking-wider">Explorer</span>
                <button @click="modalStore.openModal('create-project')" class="ml-auto text-text-disabled hover:text-dept-dev-main" title="New Project"><PhPlus :size="16" weight="bold"/></button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-2">
                <div v-for="project in projects" :key="project.id" class="mb-1">
                    <div 
                        @click="selectProject(project.id); expandedFolders[project.id] = true"
                        :draggable="canManage"
                        @dragstart="onProjectDragStart($event, project.id)"
                        @dragover="onProjectDragOver($event, project.id)"
                        @dragleave="onProjectDragLeave"
                        @drop="onProjectDrop($event, project.id)"
                        class="flex items-center gap-2 px-2 py-1.5 rounded cursor-pointer hover:bg-shell-border/50 text-[13px] transition-colors select-none group"
                        :class="{
                            'bg-dept-dev-sec/20': activeProjectId === project.id && !activeNodeId && isDragOverProject !== project.id,
                            'bg-blue-100 border-dashed border border-blue-400': isDragOverProject === project.id
                        }"
                    >
                        <component :is="expandedFolders[project.id] ? PhCaretDown : PhCaretRight" :size="14" class="text-text-disabled" @click.stop="toggleFolder(project.id)"/>
                        <PhFolder :size="16" weight="fill" class="text-dept-dev-main shrink-0" />
                        <span class="font-medium truncate flex-1" :class="{'text-dept-dev-main': activeProjectId === project.id && !activeNodeId}">{{ project.name }}</span>
                        
                        <div v-if="canManage" class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1">
                            <button @click.stop="addRootNode(project.id, 'folder')" class="p-0.5 text-text-disabled hover:text-dept-dev-main" title="New Folder">
                                <PhFolder :size="12" weight="bold"/>
                            </button>
                            <button @click.stop="addRootNode(project.id, 'board')" class="p-0.5 text-text-disabled hover:text-teal-500" title="New Task File (Board)">
                                <PhKanban :size="12" weight="bold"/>
                            </button>
                        </div>
                    </div>

                    <div v-if="expandedFolders[project.id]" class="flex flex-col gap-0.5">
                        <ExplorerNode 
                            v-for="node in (project.nodes || []).filter(n => !n.parent_id).sort((a, b) => {
                                if (a.type !== b.type) return a.type === 'folder' ? -1 : 1;
                                return a.name.localeCompare(b.name);
                            })" 
                            :key="node.id" 
                            :node="node"
                            :all-nodes="project.nodes || []"
                            :project-id="project.id"
                            :level="1"
                            :active-node-id="activeNodeId"
                            @select-board="(id) => { selectProject(project.id); selectBoard(id); }"
                            @refresh="fetchBoardData"
                        />
                        <div v-if="!(project.nodes || []).some(n => !n.parent_id)" class="py-1 text-[11px] text-text-disabled italic pl-8">
                            No folders or task files.
                        </div>
                    </div>
                </div>

                <div v-if="projects.length === 0" class="px-3 py-6 text-center text-[12px] text-text-disabled">
                    No projects yet. Click + to create one.
                </div>
            </div>
        </div>

        <div class="flex-1 flex flex-col h-full bg-[#F8FAFC] overflow-hidden">
            <div class="h-[44px] bg-white border-b border-shell-border flex items-center px-6 shrink-0 justify-between">
                <div class="flex items-center gap-4">
                    <h2 class="text-[15px] font-semibold text-dept-dev-main flex items-center gap-2">
                        <template v-if="activeProjectId">
                            <span v-for="(crumb, idx) in getBreadcrumbs(activeProjectId, activeNodeId)" :key="idx" class="flex items-center gap-2">
                                <span :class="idx === getBreadcrumbs(activeProjectId, activeNodeId).length - 1 ? 'text-dept-dev-main' : 'text-text-disabled'">{{ crumb }}</span>
                                <PhCaretRight v-if="idx < getBreadcrumbs(activeProjectId, activeNodeId).length - 1" :size="12" class="text-text-disabled" />
                            </span>
                        </template>
                        <span v-else>Sprint Board</span>
                    </h2>
                </div>
                <div class="flex gap-2">
                    <button 
                        @click="modalStore.openModal('create-task', { projectId: activeProjectId, nodeId: activeNodeId })"
                        :disabled="!activeProjectId || !activeNodeId"
                        class="px-3 py-1.5 bg-dept-dev-main text-white text-[13px] font-medium rounded-btn hover:bg-[#1E293B] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Create Task
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-x-auto overflow-y-hidden p-6">
                <div class="flex gap-6 h-full items-start min-w-max pb-4">
                    <div 
                        v-for="col in columns" 
                        :key="col.id" 
                        class="w-[280px] h-full flex flex-col bg-shell-panel rounded-lg border border-shell-border shrink-0"
                    >
                        <div class="px-4 py-3 border-b border-shell-border bg-white rounded-t-lg flex justify-between items-center">
                            <h3 class="text-[13px] font-semibold text-text-primary uppercase tracking-wide flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full" :class="col.border.replace('border-', 'bg-')"></div>
                                {{ col.title }}
                            </h3>
                            <span class="text-[12px] font-medium text-text-disabled bg-shell-border/30 px-2 py-0.5 rounded-full">
                                {{ boardTasks[col.id]?.length || 0 }}
                            </span>
                        </div>

                        <div class="flex-1 overflow-y-auto p-3">
                            <VueDraggable
                                v-model="boardTasks[col.id]"
                                :animation="150"
                                group="tasks"
                                ghost-class="opacity-50"
                                filter=".undraggable"
                                class="min-h-[100px] h-full flex flex-col gap-3"
                                item-key="id"
                                @start="onDragStart"
                                @end="onDragEnd"
                            >
                                <div 
                                    v-for="task in boardTasks[col.id]" 
                                    :key="task.id"
                                    :data-task-id="task.id"
                                    @click="canEditTask(task) ? openTask(task) : null"
                                    class="p-3 rounded-card shadow-sm transition-all relative overflow-hidden flex flex-col gap-2"
                                    :class="[
                                        col.bg,
                                        getPriorityColor(task.priority).replace('bg-', 'border-l-').replace('100', '500'),
                                        canEditTask(task) ? 'cursor-pointer hover:shadow-card hover:border-r-dept-dev-main hover:border-y-dept-dev-main border-l-4 border-y border-r border-shell-border' : 'opacity-60 cursor-not-allowed border-l-4 border-transparent undraggable'
                                    ]"
                                >
                                <!-- Task Card Header -->
                                <div class="flex justify-between items-start mb-2 group">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-sm" :class="getPriorityColor(task.priority)">
                                            {{ task.priority.toUpperCase() }}
                                        </span>
                                        <span v-if="task.story_points" class="text-[11px] font-medium bg-slate-100 px-1.5 rounded text-text-secondary">
                                            {{ task.story_points }} pts
                                        </span>
                                    </div>
                                    
                                    <div class="flex gap-1 items-center opacity-0 group-hover:opacity-100 transition-opacity">
                                        <!-- Edit button triggers TaskDetailsModal which allows assigning/modifying if admin or creator, etc. -->
                                    </div>
                                </div>
                                    
                                    <p class="text-[14px] font-medium text-text-primary leading-snug">
                                        {{ task.title }}
                                    </p>

                                    <div class="flex items-center justify-between text-text-secondary mt-1">
                                        <div class="flex items-center gap-3">
                                            <div v-if="task.attachments_count" class="flex items-center gap-1 text-[12px]">
                                                <PhPaperclip :size="14" /> {{ task.attachments_count }}
                                            </div>
                                            <div class="flex items-center gap-1 text-[12px]" :class="{'text-dept-dev-main': task.comments?.length > 0}">
                                                <PhChatCircle :size="14" :weight="task.comments?.length > 0 ? 'fill' : 'regular'" /> {{ task.comments?.length || 0 }}
                                            </div>
                                        </div>
                                        
                                        <div class="flex -space-x-2">
                                            <div v-for="user in (task.assignees || []).slice(0,3)" :key="user.id" class="w-6 h-6 rounded-full border-2 border-white bg-dept-dev-main flex items-center justify-center text-[10px] text-white overflow-hidden">
                                                <img v-if="user.avatar" :src="user.avatar" class="w-full h-full object-cover"/>
                                                <span v-else>{{ user.name.charAt(0) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </VueDraggable>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right AI Sidebar: DEV COPILOT -->
        <div class="w-[300px] flex-shrink-0 border-l border-shell-border bg-shell-panel flex flex-col h-full z-10">
            <div class="h-[44px] flex items-center px-4 border-b border-shell-border bg-dept-dev-main text-white shrink-0 gap-2">
                <PhRobot :size="18" weight="fill" />
                <div class="flex flex-col">
                    <span class="text-[13px] font-bold tracking-wide leading-tight">DEV COPILOT</span>
                </div>
            </div>
            
            <div class="flex-1 p-4 overflow-y-auto text-[12px] flex flex-col gap-3 bg-white" ref="chatScrollContainer">
                <div v-for="(m, idx) in chatMessages" :key="idx" :class="m.role === 'user' ? 'text-right' : 'text-left'">
                    <div class="inline-block p-3 rounded-card shadow-sm text-text-secondary max-w-[95%] leading-relaxed text-left" 
                         :class="m.role === 'user' ? 'bg-dept-dev-main/10 text-dept-dev-main border border-dept-dev-main/20 rounded-br-none' : 'bg-shell-panel border border-shell-border rounded-bl-none'">
                        <RichMessageRenderer :content="m.content" />
                    </div>
                </div>
                <div v-if="isChatTyping" class="text-[11px] text-text-disabled animate-pulse flex items-center gap-1.5 pl-1">
                    <PhRobot :size="14" class="animate-bounce text-dept-dev-main" /> COPILOT is typing...
                </div>
            </div>

            <div class="p-3 border-t border-shell-border bg-shell-panel shrink-0">
                <div class="relative">
                    <input 
                        v-model="chatMessage"
                        @keyup.enter="sendChatMessage"
                        type="text" 
                        placeholder="Ask Dev Copilot..." 
                        class="w-full pl-3 pr-10 py-2 bg-white border border-shell-border rounded-input text-[12px] focus:ring-1 focus:ring-dept-dev-main outline-none" 
                    />
                    <button @click="sendChatMessage" class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-dept-dev-main hover:text-[#0F172A] transition-colors cursor-pointer bg-transparent border-0 flex items-center justify-center">
                        <PhArrowRight :size="14" weight="bold" />
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>
