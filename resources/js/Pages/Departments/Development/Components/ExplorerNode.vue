<script setup lang="ts">
import { ref, computed } from 'vue';
import axios from 'axios';
import { PhFolder, PhKanban, PhCaretRight, PhCaretDown, PhPlus, PhTrash, PhShieldCheck } from '@phosphor-icons/vue';

import { useModalStore } from '@/Stores/useModalStore';
import { useToastStore } from '@/Stores/useToastStore';
import { usePage } from '@inertiajs/vue3';

const props = defineProps<{
    node: any;
    allNodes: any[];
    projectId: string;
    level: number;
    activeNodeId: string | null;
}>();

const emit = defineEmits(['select-board', 'refresh']);
const modalStore = useModalStore();
const toastStore = useToastStore();
const auth = usePage().props.auth as any;

const canManage = computed(() => {
    return auth.user?.role?.name === 'admin' || auth.user?.role?.name === 'manager' || auth.user?.role?.is_super_admin;
});

const isExpanded = ref(false);

// Context Menu state
const contextMenu = ref({ visible: false, x: 0, y: 0 });

const onContextMenu = (e: MouseEvent) => {
    if (!canManage.value) return;
    e.preventDefault();
    e.stopPropagation();
    contextMenu.value = { visible: true, x: e.clientX, y: e.clientY };
    const closeHandler = () => {
        contextMenu.value.visible = false;
        document.removeEventListener('click', closeHandler);
    };
    setTimeout(() => document.addEventListener('click', closeHandler), 10);
};

const openRbacModal = () => {
    contextMenu.value.visible = false;
    modalStore.openModal('rbac-manage', {
        entityId: props.node.id,
        entityType: 'node',
        entityName: props.node.name
    });
};

const children = computed(() => {
    return props.allNodes.filter(n => n.parent_id === props.node.id).sort((a, b) => {
        // Folders first
        if (a.type !== b.type) return a.type === 'folder' ? -1 : 1;
        return a.name.localeCompare(b.name);
    });
});

const toggleExpand = () => {
    if (props.node.type === 'folder') {
        isExpanded.value = !isExpanded.value;
        emit('select-board', props.node.id);
    } else {
        emit('select-board', props.node.id);
    }
};

const handleAddNode = (type: 'folder' | 'board') => {
    modalStore.openModal('create-node', {
        projectId: props.projectId,
        parentId: props.node.id,
        type: type
    });
};

const handleDelete = async () => {
    modalStore.openModal('confirm-action', {
        title: 'Delete Node',
        message: 'Are you sure you want to delete this? This action cannot be undone and will delete all nested items.',
        confirmText: 'Delete',
        danger: true,
        onConfirm: async () => {
            try {
                await axios.delete(`/api/development/nodes/${props.node.id}`);
                emit('refresh');
                toastStore.addToast('success', 'Deleted successfully');
            } catch (e) {
                toastStore.addToast('error', 'Failed to delete');
            }
        }
    });
};

const onSelectBoard = (id: string) => {
    emit('select-board', id);
};

// DND Handlers
const isDragOver = ref(false);

const onDragStart = (e: DragEvent) => {
    if (!canManage.value) return e.preventDefault();
    if (e.dataTransfer) {
        e.dataTransfer.setData('text/plain', JSON.stringify({ type: 'node', id: props.node.id, project_id: props.projectId }));
        e.dataTransfer.effectAllowed = 'move';
    }
};

const onDragOver = (e: DragEvent) => {
    if (props.node.type !== 'folder' || !canManage.value) return;
    e.preventDefault();
    isDragOver.value = true;
};

const onDragLeave = () => {
    isDragOver.value = false;
};

const onDrop = async (e: DragEvent) => {
    isDragOver.value = false;
    if (props.node.type !== 'folder' || !canManage.value) return;
    
    const dataStr = e.dataTransfer?.getData('text/plain');
    if (!dataStr) return;
    
    try {
        const data = JSON.parse(dataStr);
        if (data.type === 'node' && data.id !== props.node.id) {
            await axios.put(`/api/development/nodes/${data.id}/move`, {
                parent_id: props.node.id,
                project_id: props.projectId
            });
            isExpanded.value = true;
            emit('refresh');
        } else if (data.type === 'project' && data.id !== props.projectId) {
            await axios.post('/api/development/projects/merge', {
                source_project_id: data.id,
                target_project_id: props.projectId,
                target_parent_id: props.node.id
            });
            isExpanded.value = true;
            emit('refresh');
        }
    } catch (e) {
        toastStore.addToast('error', 'Failed to move item');
    }
};
</script>

<template>
    <div>
        <div 
            @click="toggleExpand"
            @contextmenu="onContextMenu"
            :draggable="canManage"
            @dragstart="onDragStart"
            @dragover="onDragOver"
            @dragleave="onDragLeave"
            @drop="onDrop"
            class="flex items-center gap-2 py-1.5 pr-2 rounded cursor-pointer hover:bg-shell-border/50 text-[13px] transition-colors select-none group relative"
            :class="{
                'bg-dept-dev-sec/20': activeNodeId === node.id && !isDragOver,
                'bg-blue-100 border-dashed border border-blue-400': isDragOver
            }"
            :style="{ paddingLeft: `${0.5 + level * 1}rem` }"
        >
            <!-- Caret for Folders -->
            <div class="w-4 h-4 flex items-center justify-center shrink-0">
                <component 
                    v-if="node.type === 'folder'" 
                    :is="isExpanded ? PhCaretDown : PhCaretRight" 
                    :size="14" 
                    class="text-text-disabled" 
                />
            </div>
            
            <!-- Icon -->
            <PhFolder v-if="node.type === 'folder'" :size="16" weight="fill" class="text-dept-dev-main shrink-0" />
            <PhKanban v-else :size="15" weight="fill" class="text-teal-500 shrink-0" />
            
            <span class="font-medium truncate flex-1" :class="{'text-dept-dev-main': activeNodeId === node.id}">{{ node.name }}</span>
            
            <!-- Actions (Hover) -->
            <div v-if="canManage" class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1">
                <button v-if="node.type === 'folder'" @click.stop="handleAddNode('folder')" class="p-0.5 text-text-disabled hover:text-dept-dev-main" title="New Folder">
                    <PhFolder :size="12" weight="bold"/>
                </button>
                <button v-if="node.type === 'folder'" @click.stop="handleAddNode('board')" class="p-0.5 text-text-disabled hover:text-teal-500" title="New Task File (Board)">
                    <PhKanban :size="12" weight="bold"/>
                </button>
                <button @click.stop="handleDelete" class="p-0.5 text-text-disabled hover:text-state-error" title="Delete">
                    <PhTrash :size="12" weight="bold"/>
                </button>
            </div>
        </div>

        <!-- Right-Click Context Menu -->
        <Teleport to="body">
            <div 
                v-if="contextMenu.visible"
                class="fixed z-[9999] min-w-[200px] bg-white border border-shell-border rounded-xl shadow-[0_8px_30px_rgba(0,0,0,0.12)] py-1.5 animate-in fade-in zoom-in-95 duration-150 overflow-hidden"
                :style="{ top: contextMenu.y + 'px', left: contextMenu.x + 'px' }"
            >
                <button 
                    v-if="node.type === 'folder'"
                    @click.stop="() => { contextMenu.visible = false; handleAddNode('folder'); }"
                    class="w-full flex items-center gap-2.5 px-4 py-2 text-[13px] text-text-primary hover:bg-slate-50 transition-colors"
                >
                    <PhFolder :size="16" weight="bold" class="text-dept-dev-main" />
                    New Subfolder
                </button>
                <button 
                    v-if="node.type === 'folder'"
                    @click.stop="() => { contextMenu.visible = false; handleAddNode('board'); }"
                    class="w-full flex items-center gap-2.5 px-4 py-2 text-[13px] text-text-primary hover:bg-slate-50 transition-colors"
                >
                    <PhKanban :size="16" weight="bold" class="text-teal-500" />
                    New Task Board
                </button>
                
                <div class="mx-3 my-1 border-t border-shell-border"></div>
                
                <button 
                    @click.stop="openRbacModal"
                    class="w-full flex items-center gap-2.5 px-4 py-2 text-[13px] text-text-primary hover:bg-violet-50 transition-colors"
                >
                    <PhShieldCheck :size="16" weight="bold" class="text-violet-500" />
                    Manage Access (RBAC)
                </button>

                <div class="mx-3 my-1 border-t border-shell-border"></div>

                <button 
                    @click.stop="() => { contextMenu.visible = false; handleDelete(); }"
                    class="w-full flex items-center gap-2.5 px-4 py-2 text-[13px] text-red-600 hover:bg-red-50 transition-colors"
                >
                    <PhTrash :size="16" weight="bold" />
                    Delete
                </button>
            </div>
        </Teleport>

        <!-- Recursive Children -->
        <div v-if="node.type === 'folder' && isExpanded" class="flex flex-col gap-0.5">
            <ExplorerNode 
                v-for="child in children" 
                :key="child.id" 
                :node="child"
                :all-nodes="allNodes"
                :project-id="projectId"
                :level="level + 1"
                :active-node-id="activeNodeId"
                @select-board="onSelectBoard"
                @refresh="$emit('refresh')"
            />
            <div v-if="children.length === 0" class="py-1 text-[11px] text-text-disabled italic" :style="{ paddingLeft: `${2.5 + level * 1}rem` }">
                Empty folder
            </div>
        </div>
    </div>
</template>
