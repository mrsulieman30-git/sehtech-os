<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { 
    PhFolder, PhFile, PhUploadSimple, PhDownloadSimple, 
    PhDotsThree, PhFilePdf, PhFileImage, PhFileText, PhHardDrives, PhFolderPlus,
    PhTrash, PhCopy, PhArrowRight, PhClockCounterClockwise, PhCheckSquare,
    PhWarningCircle
} from '@phosphor-icons/vue';
import dayjs from 'dayjs';

interface FileNode {
    id: string; // The physical path
    original_name: string;
    mime_type: string;
    size: number;
    created_at: string;
}

interface Directory {
    id: string; // The physical path
    name: string;
}

const currentPath = ref<string>('');
const directories = ref<Directory[]>([]);
const files = ref<FileNode[]>([]);
const trashedFiles = ref<any[]>([]);
const isTrashMode = ref(false);

const isDragging = ref(false);
const isUploading = ref(false);

// Modals
const showNewFolderModal = ref(false);
const newFolderName = ref('');
const showDestModal = ref(false);
const destAction = ref<'copy'|'move'>('copy');
const destTargetPath = ref('');
const destError = ref('');

// Selection & Bulk
const isSelectMode = ref(false);
const selectedItems = ref<string[]>([]);

// Delete Modal
const showDeleteModal = ref(false);
const itemsToDelete = ref<string[]>([]);
const deleteError = ref('');

// RBAC
const auth = (window as any).$page?.props?.auth || { user: { role: { name: 'admin' } } };
const isAdmin = ref(true); // Default to true or derive from auth
const showAccessModal = ref(false);
const selectedPathForAccess = ref('');
const allUsers = ref<any[]>([]);
const selectedUserIds = ref<string[]>([]);
const isSavingAccess = ref(false);

// Context Menu
const showContextMenu = ref(false);
const contextMenuX = ref(0);
const contextMenuY = ref(0);
const contextMenuTarget = ref<{ id: string, type: 'dir' | 'file', name: string } | null>(null);

const closeContextMenu = () => {
    showContextMenu.value = false;
};

onMounted(() => {
    fetchContents();
    window.addEventListener('click', closeContextMenu);
    window.addEventListener('scroll', closeContextMenu, true);
});

onUnmounted(() => {
    window.removeEventListener('click', closeContextMenu);
    window.removeEventListener('scroll', closeContextMenu, true);
});

const openContextMenu = (e: MouseEvent, item: { id: string, type: 'dir' | 'file', name: string }) => {
    e.preventDefault();
    contextMenuTarget.value = item;
    contextMenuX.value = e.clientX;
    contextMenuY.value = e.clientY;
    showContextMenu.value = true;
};

const toggleSelectMode = () => {
    isSelectMode.value = !isSelectMode.value;
    if (!isSelectMode.value) {
        selectedItems.value = [];
    }
};

const toggleSelection = (id: string) => {
    const index = selectedItems.value.indexOf(id);
    if (index > -1) {
        selectedItems.value.splice(index, 1);
    } else {
        selectedItems.value.push(id);
    }
};

const openAccessModal = async (path: string) => {
    selectedPathForAccess.value = path;
    showAccessModal.value = true;
    try {
        const res = await axios.get('/api/files/access', { params: { path } });
        allUsers.value = res.data.allUsers;
        selectedUserIds.value = res.data.grants.map((g: any) => g.user_id);
    } catch (e) {
        console.error(e);
    }
};

const saveAccess = async () => {
    isSavingAccess.value = true;
    try {
        await axios.post('/api/files/access', {
            path: selectedPathForAccess.value,
            user_ids: selectedUserIds.value
        });
        showAccessModal.value = false;
    } catch (e) {
        console.error(e);
    }
    isSavingAccess.value = false;
};

const breadcrumbs = ref<{path: string, name: string}[]>([
    { path: '', name: 'Central Storage' }
]);

const fetchContents = async () => {
    if (isTrashMode.value) {
        try {
            const response = await axios.get('/api/files/trash');
            trashedFiles.value = response.data.trashed;
        } catch (error) {
            console.error('Failed to fetch trash', error);
        }
        return;
    }

    try {
        const response = await axios.get('/api/files', {
            params: { path: currentPath.value }
        });
        directories.value = response.data.directories;
        files.value = response.data.files;
        // Clean up selections that might not exist anymore
        selectedItems.value = [];
    } catch (error) {
        console.error('Failed to fetch file system contents', error);
    }
};

const toggleTrashMode = () => {
    isTrashMode.value = !isTrashMode.value;
    if (!isTrashMode.value) {
        currentPath.value = '';
        breadcrumbs.value = [{ path: '', name: 'Central Storage' }];
    }
    selectedItems.value = [];
    isSelectMode.value = false;
    fetchContents();
};

const restoreFile = async (id: number) => {
    try {
        await axios.post(`/api/files/trash/${id}/restore`);
        fetchContents();
    } catch (error) {
        console.error('Failed to restore file', error);
    }
};

const initiateDelete = (paths: string[]) => {
    itemsToDelete.value = paths;
    deleteError.value = '';
    showDeleteModal.value = true;
};

const confirmDelete = async () => {
    deleteError.value = '';
    try {
        await Promise.all(itemsToDelete.value.map(path => 
            axios.delete('/api/files/delete', { data: { path } })
        ));
        showDeleteModal.value = false;
        itemsToDelete.value = [];
        selectedItems.value = [];
        fetchContents();
    } catch (error: any) {
        console.error('Failed to delete item(s)', error);
        deleteError.value = error.response?.data?.message || 'Failed to move to recycle bin. Items may be in use.';
    }
};

const openDestModal = (paths: string[], action: 'copy'|'move') => {
    // For bulk actions we store the paths in a ref, we can reuse selectedItems or a new ref
    // To keep it simple, we'll execute it based on what is selected if paths length > 1
    // Wait, destTargetPath is common, we need a sourcePaths array
    sourcePathsForDest.value = paths;
    destAction.value = action;
    destTargetPath.value = '';
    destError.value = '';
    showDestModal.value = true;
};
const sourcePathsForDest = ref<string[]>([]);

const submitDestAction = async () => {
    destError.value = '';
    try {
        const endpoint = destAction.value === 'copy' ? '/api/files/copy' : '/api/files/move';
        await Promise.all(sourcePathsForDest.value.map(source => 
            axios.post(endpoint, { source, destination: destTargetPath.value })
        ));
        
        showDestModal.value = false;
        selectedItems.value = [];
        sourcePathsForDest.value = [];
        fetchContents();
    } catch (error: any) {
        console.error(`Failed to ${destAction.value} item(s)`, error);
        destError.value = error.response?.data?.message || 'Operation failed';
    }
};

const handleFileUpload = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (!target.files?.length) return;
    await uploadFiles(target.files);
    target.value = '';
};

const handleDrop = async (event: DragEvent) => {
    isDragging.value = false;
    if (!event.dataTransfer?.files?.length) return;
    await uploadFiles(event.dataTransfer.files);
};

const uploadFiles = async (fileList: FileList) => {
    isUploading.value = true;
    const CHUNK_SIZE = 5 * 1024 * 1024; // 5MB

    for (let i = 0; i < fileList.length; i++) {
        const file = fileList[i];
        const identifier = `${file.name}-${file.size}-${file.lastModified}`.replace(/[^a-zA-Z0-9-]/g, '');
        const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
        
        try {
            const statusRes = await axios.get('/api/files/upload/status', { params: { identifier } });
            const uploadedChunks: number[] = statusRes.data.uploaded_chunks || [];
            
            for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
                if (uploadedChunks.includes(chunkIndex)) continue;
                
                const start = chunkIndex * CHUNK_SIZE;
                const end = Math.min(start + CHUNK_SIZE, file.size);
                const chunk = file.slice(start, end);
                
                const formData = new FormData();
                formData.append('file', chunk);
                formData.append('identifier', identifier);
                formData.append('chunk_index', chunkIndex.toString());
                
                await axios.post('/api/files/upload/chunk', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
            }
            
            await axios.post('/api/files/upload/complete', {
                identifier,
                original_name: file.name,
                total_chunks: totalChunks,
                path: currentPath.value,
                relative_path: file.webkitRelativePath || ''
            });

        } catch (error) {
            console.error('Chunk upload failed for ' + file.name, error);
        }
    }
    
    isUploading.value = false;
    fetchContents();
};

const createFolder = async () => {
    if (!newFolderName.value.trim()) return;
    try {
        await axios.post('/api/files/folders', {
            name: newFolderName.value,
            path: currentPath.value
        });
        showNewFolderModal.value = false;
        newFolderName.value = '';
        fetchContents();
    } catch (error) {
        console.error('Failed to create folder', error);
    }
};

const navigateToDir = (path: string, dirName: string) => {
    currentPath.value = path;
    selectedItems.value = []; // clear selection on navigate
    if (path === '') {
        breadcrumbs.value = [{ path: '', name: 'Central Storage' }];
    } else {
        const parts = path.split('/');
        const newCrumbs = [{ path: '', name: 'Central Storage' }];
        let currentBuildPath = '';
        parts.forEach(part => {
            if (!part) return;
            currentBuildPath = currentBuildPath ? currentBuildPath + '/' + part : part;
            newCrumbs.push({ path: currentBuildPath, name: part });
        });
        breadcrumbs.value = newCrumbs;
    }
    fetchContents();
};

const formatSize = (bytes: number) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const getFileIcon = (mimeType: string) => {
    if (!mimeType) return PhFile;
    if (mimeType.includes('pdf')) return PhFilePdf;
    if (mimeType.includes('image')) return PhFileImage;
    if (mimeType.includes('text') || mimeType.includes('json') || mimeType.includes('javascript') || mimeType.includes('php')) return PhFileText;
    return PhFile;
};

const downloadFile = (filePath: string) => {
    window.open(`/api/files/preview?path=${encodeURIComponent(filePath)}`, '_blank');
};
</script>

<template>
    <div class="h-full flex flex-col bg-slate-50 relative" @contextmenu.prevent>
        
        <!-- Header Toolbar -->
        <div class="h-[64px] border-b border-shell-border flex items-center justify-between px-6 bg-white shadow-sm shrink-0 relative z-10">
            <div class="flex items-center gap-2 text-[14px] font-semibold text-slate-700">
                <template v-if="!isTrashMode">
                    <template v-for="(crumb, index) in breadcrumbs" :key="index">
                        <button 
                            @click="navigateToDir(crumb.path, crumb.name)"
                            class="hover:text-blue-600 transition-colors"
                        >
                            {{ crumb.name }}
                        </button>
                        <span v-if="index < breadcrumbs.length - 1" class="text-slate-300">/</span>
                    </template>
                </template>
                <template v-else>
                    <span class="text-red-500 font-bold flex items-center gap-2"><PhTrash weight="fill"/> Recycle Bin</span>
                </template>
            </div>
            
            <div class="flex items-center gap-3">
                <template v-if="!isTrashMode && selectedItems.length > 0">
                    <span class="text-[13px] font-medium text-slate-500 mr-2">{{ selectedItems.length }} selected</span>
                    <button @click="openDestModal(selectedItems, 'copy')" class="px-3 py-1.5 text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg text-[13px] font-medium transition flex items-center gap-1.5">
                        <PhCopy :size="16" /> Copy
                    </button>
                    <button @click="openDestModal(selectedItems, 'move')" class="px-3 py-1.5 text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg text-[13px] font-medium transition flex items-center gap-1.5">
                        <PhArrowRight :size="16" /> Move
                    </button>
                    <button @click="initiateDelete(selectedItems)" class="px-3 py-1.5 text-red-600 bg-red-50 hover:bg-red-100 rounded-lg text-[13px] font-medium transition flex items-center gap-1.5">
                        <PhTrash :size="16" /> Delete
                    </button>
                    <div class="w-px h-6 bg-slate-200 mx-2"></div>
                </template>

                <button @click="toggleSelectMode" class="flex items-center gap-2 px-3 py-2 rounded-lg text-[13px] font-medium transition-colors" :class="isSelectMode ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                    <PhCheckSquare :size="18" :weight="isSelectMode ? 'fill' : 'regular'" />
                    <span>{{ isSelectMode ? 'Done' : 'Select' }}</span>
                </button>

                <button @click="toggleTrashMode" class="flex items-center gap-2 px-3 py-2 rounded-lg text-[13px] font-medium transition-colors" :class="isTrashMode ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                    <PhTrash :size="18" :weight="isTrashMode ? 'fill' : 'regular'" />
                    <span>{{ isTrashMode ? 'Exit Trash' : 'Recycle Bin' }}</span>
                </button>
                
                <template v-if="!isTrashMode">
                    <button @click="showNewFolderModal = true" class="flex items-center gap-2 px-4 py-2 bg-slate-800 text-white rounded-lg text-[13px] font-medium hover:bg-slate-700 transition shadow-sm">
                        <PhFolderPlus :size="18" />
                        <span>New Folder</span>
                    </button>
                    <label class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-[13px] font-medium cursor-pointer hover:bg-blue-700 transition shadow-sm">
                        <PhUploadSimple :size="18" weight="bold" />
                        <span>Upload File</span>
                        <input type="file" class="hidden" multiple @change="handleFileUpload" />
                    </label>
                    <label class="flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg text-[13px] font-medium cursor-pointer hover:bg-emerald-700 transition shadow-sm">
                        <PhUploadSimple :size="18" weight="bold" />
                        <span>Upload Folder</span>
                        <input type="file" class="hidden" webkitdirectory directory multiple @change="handleFileUpload" />
                    </label>
                </template>
            </div>
        </div>

        <!-- Custom Context Menu -->
        <div 
            v-if="showContextMenu && contextMenuTarget" 
            class="fixed bg-white rounded-xl shadow-2xl border border-slate-100 w-48 py-1.5 z-[100] flex flex-col transform"
            :style="{ top: `${contextMenuY}px`, left: `${contextMenuX}px` }"
        >
            <div class="px-3 py-2 border-b border-slate-100 mb-1">
                <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider truncate" :title="contextMenuTarget.name">
                    {{ contextMenuTarget.name }}
                </span>
            </div>
            
            <button v-if="contextMenuTarget.type === 'file'" @click="downloadFile(contextMenuTarget.id)" class="w-full text-left px-4 py-2 text-[13px] font-medium text-slate-700 hover:bg-blue-50 hover:text-blue-700 flex items-center gap-2 transition-colors">
                <PhDownloadSimple :size="16" /> Download
            </button>
            
            <button @click="openDestModal([contextMenuTarget.id], 'copy')" class="w-full text-left px-4 py-2 text-[13px] font-medium text-slate-700 hover:bg-blue-50 hover:text-blue-700 flex items-center gap-2 transition-colors">
                <PhCopy :size="16" /> Copy
            </button>
            <button @click="openDestModal([contextMenuTarget.id], 'move')" class="w-full text-left px-4 py-2 text-[13px] font-medium text-slate-700 hover:bg-blue-50 hover:text-blue-700 flex items-center gap-2 transition-colors">
                <PhArrowRight :size="16" /> Move
            </button>
            <button v-if="isAdmin" @click="openAccessModal(contextMenuTarget.id)" class="w-full text-left px-4 py-2 text-[13px] font-medium text-slate-700 hover:bg-blue-50 hover:text-blue-700 flex items-center gap-2 transition-colors">
                <PhDotsThree :size="16" /> Manage Access
            </button>
            
            <div class="h-px bg-slate-100 my-1"></div>
            
            <button @click="initiateDelete([contextMenuTarget.id])" class="w-full text-left px-4 py-2 text-[13px] font-medium text-red-600 hover:bg-red-50 flex items-center gap-2 transition-colors">
                <PhTrash :size="16" /> Delete
            </button>
        </div>

        <!-- TRASH VIEW -->
        <div v-if="isTrashMode" class="flex-1 p-8 overflow-y-auto">
            <div v-if="trashedFiles.length === 0" class="flex flex-col items-center justify-center h-full text-slate-400">
                <PhTrash :size="64" weight="duotone" class="text-slate-300 mb-6" />
                <p class="text-lg font-medium text-slate-500">The recycle bin is empty.</p>
            </div>
            
            <div v-else class="grid grid-cols-[repeat(auto-fill,minmax(160px,1fr))] gap-6">
                <div 
                    v-for="file in trashedFiles" 
                    :key="file.id"
                    class="group relative flex flex-col items-center p-5 rounded-2xl border border-slate-200 hover:border-red-300 hover:shadow-lg transition-all bg-white"
                >
                    <div class="absolute top-3 right-3 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button @click.stop="restoreFile(file.id)" class="p-1.5 rounded-lg bg-emerald-100 hover:bg-emerald-200 text-emerald-700 shadow-sm" title="Restore">
                            <PhClockCounterClockwise :size="16" weight="bold" />
                        </button>
                    </div>

                    <PhFolder v-if="file.is_directory" :size="56" weight="duotone" class="text-amber-300 mb-4 opacity-50" />
                    <PhFile v-else :size="56" weight="duotone" class="text-slate-300 mb-4 opacity-50" />
                    
                    <span class="text-[14px] font-medium text-slate-700 text-center break-words w-full line-clamp-2" :title="file.original_path">
                        {{ file.original_path.split('/').pop() }}
                    </span>
                    <span class="text-[12px] text-slate-400 mt-1.5 block">Deleted: {{ dayjs(file.created_at).format('MMM D') }}</span>
                </div>
            </div>
        </div>

        <!-- NORMAL FILE MANAGER VIEW -->
        <div v-else class="flex-1 flex overflow-hidden">
            <!-- Sidebar -->
            <div class="w-[260px] border-r border-slate-200 bg-white overflow-y-auto shrink-0 flex flex-col shadow-[2px_0_10px_rgba(0,0,0,0.02)] z-10 relative">
                <div class="p-5 text-[12px] font-bold text-slate-400 uppercase tracking-widest">
                    Quick Access
                </div>
                <div class="px-3 flex flex-col gap-1.5">
                    <button 
                        @click="navigateToDir('', 'Central Storage')"
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-200"
                        :class="currentPath === '' ? 'bg-blue-50 text-blue-700 shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
                    >
                        <PhHardDrives :size="20" :weight="currentPath === '' ? 'fill' : 'regular'" :class="currentPath === '' ? 'text-blue-500' : 'text-slate-400'" />
                        Central Storage
                    </button>
                    <button 
                        v-for="dir in directories" 
                        :key="dir.id"
                        @click="navigateToDir(dir.id, dir.name)"
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-[14px] font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-all duration-200"
                    >
                        <PhFolder :size="20" weight="fill" class="text-amber-400" />
                        <span class="truncate">{{ dir.name }}</span>
                    </button>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1 p-8 overflow-y-auto bg-slate-50 relative" @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false" @drop.prevent="handleDrop">
                
                <div v-if="isDragging" class="absolute inset-0 bg-blue-50/80 border-3 border-dashed border-blue-400 z-50 flex items-center justify-center m-8 rounded-3xl pointer-events-none backdrop-blur-sm transition-all">
                    <div class="flex flex-col items-center text-blue-600">
                        <PhUploadSimple :size="64" weight="duotone" class="mb-4 animate-bounce" />
                        <span class="text-2xl font-bold tracking-tight">Drop files to upload instantly</span>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="directories.length === 0 && files.length === 0 && !isUploading" class="flex flex-col items-center justify-center h-full text-slate-400">
                    <PhHardDrives :size="72" weight="duotone" class="text-slate-300 mb-6" />
                    <h3 class="text-xl font-semibold text-slate-700 mb-2">This folder is empty</h3>
                    <p class="text-[14px] text-slate-500">Drag and drop files here, or use the buttons above.</p>
                </div>

                <div class="grid grid-cols-[repeat(auto-fill,minmax(160px,1fr))] gap-6 relative z-0">
                    <!-- Directories -->
                    <template v-if="directories.length > 0">
                        <div 
                            v-for="dir in directories" 
                            :key="dir.id"
                            class="group relative flex flex-col items-center p-5 rounded-2xl border border-slate-200 hover:border-blue-400 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] cursor-pointer transition-all duration-300 bg-white"
                            :class="{'ring-2 ring-blue-500 border-transparent shadow-[0_8px_30px_rgb(0,0,0,0.08)]': selectedItems.includes(dir.id)}"
                            @dblclick="navigateToDir(dir.id, dir.name)"
                            @contextmenu.prevent="openContextMenu($event, { id: dir.id, type: 'dir', name: dir.name })"
                            @click="isSelectMode ? toggleSelection(dir.id) : null"
                        >
                            <!-- Select Checkbox -->
                            <div v-if="isSelectMode || selectedItems.includes(dir.id)" class="absolute top-3 left-3 z-10" @click.stop="toggleSelection(dir.id)">
                                <div class="w-5 h-5 rounded-md border-2 flex items-center justify-center transition-colors"
                                    :class="selectedItems.includes(dir.id) ? 'bg-blue-500 border-blue-500' : 'border-slate-300 bg-white'">
                                    <PhCheckSquare v-if="selectedItems.includes(dir.id)" weight="fill" class="text-white w-4 h-4" />
                                </div>
                            </div>

                            <div class="w-20 h-20 mb-4 flex items-center justify-center rounded-2xl bg-amber-50 group-hover:bg-amber-100 transition-colors">
                                <PhFolder :size="48" weight="fill" class="text-amber-400 group-hover:scale-110 transition-transform duration-300" />
                            </div>
                            <span class="text-[14px] font-semibold text-slate-700 text-center break-words w-full truncate" :title="dir.name">{{ dir.name }}</span>
                        </div>
                    </template>

                    <!-- Files -->
                    <template v-if="files.length > 0">
                        <div 
                            v-for="file in files" 
                            :key="file.id"
                            class="group relative flex flex-col items-center p-5 rounded-2xl border border-slate-200 hover:border-blue-400 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] cursor-pointer transition-all duration-300 bg-white"
                            :class="{'ring-2 ring-blue-500 border-transparent shadow-[0_8px_30px_rgb(0,0,0,0.08)]': selectedItems.includes(file.id)}"
                            @dblclick="downloadFile(file.id)"
                            @contextmenu.prevent="openContextMenu($event, { id: file.id, type: 'file', name: file.original_name })"
                            @click="isSelectMode ? toggleSelection(file.id) : null"
                        >
                            <!-- Select Checkbox -->
                            <div v-if="isSelectMode || selectedItems.includes(file.id)" class="absolute top-3 left-3 z-10" @click.stop="toggleSelection(file.id)">
                                <div class="w-5 h-5 rounded-md border-2 flex items-center justify-center transition-colors"
                                    :class="selectedItems.includes(file.id) ? 'bg-blue-500 border-blue-500' : 'border-slate-300 bg-white'">
                                    <PhCheckSquare v-if="selectedItems.includes(file.id)" weight="fill" class="text-white w-4 h-4" />
                                </div>
                            </div>

                            <div class="w-20 h-20 mb-4 flex items-center justify-center rounded-2xl bg-blue-50 group-hover:bg-blue-100 transition-colors">
                                <component :is="getFileIcon(file.mime_type)" :size="48" weight="duotone" class="text-blue-500 group-hover:scale-110 transition-transform duration-300" />
                            </div>
                            
                            <span class="text-[14px] font-semibold text-slate-700 text-center break-words w-full truncate" :title="file.original_name">
                                {{ file.original_name }}
                            </span>
                            <span class="text-[12px] font-medium text-slate-400 mt-1.5 block">{{ formatSize(file.size) }}</span>
                        </div>
                    </template>

                    <div v-if="isUploading" class="absolute bottom-8 right-8 px-5 py-4 bg-slate-800 text-white rounded-2xl shadow-2xl flex items-center gap-4 border border-slate-700">
                        <div class="w-5 h-5 border-2 border-white/20 border-t-white rounded-full animate-spin"></div>
                        <span class="text-[14px] font-semibold tracking-wide">Uploading files securely...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Copy/Move Destination Modal -->
        <div v-if="showDestModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col transform transition-all">
                <div class="px-8 py-6 border-b border-slate-100 flex items-center gap-3 bg-white">
                    <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                        <PhCopy v-if="destAction === 'copy'" :size="20" weight="fill" />
                        <PhArrowRight v-else :size="20" weight="fill" />
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 capitalize">{{ destAction }} Item{{ sourcePathsForDest.length > 1 ? 's' : '' }}</h3>
                </div>
                <div class="p-8">
                    <label class="block text-[14px] font-semibold text-slate-700 mb-3">Destination Path</label>
                    <input 
                        type="text" 
                        v-model="destTargetPath" 
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-[14px] text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all placeholder:text-slate-400"
                        placeholder="Leave empty for root, e.g. Folder1/SubFolder"
                        @keyup.enter="submitDestAction"
                    />
                    <p v-if="destError" class="text-red-500 text-[13px] font-medium mt-3 flex items-center gap-1"><PhWarningCircle weight="fill"/> {{ destError }}</p>
                </div>
                <div class="px-8 py-5 bg-slate-50/50 border-t border-slate-100 flex justify-end gap-3">
                    <button @click="showDestModal = false" class="px-5 py-2.5 text-[14px] font-semibold text-slate-600 hover:text-slate-900 bg-white hover:bg-slate-100 border border-slate-200 rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button @click="submitDestAction" class="px-6 py-2.5 bg-blue-600 text-white rounded-xl text-[14px] font-semibold hover:bg-blue-700 transition-all shadow-lg shadow-blue-600/30 capitalize">
                        {{ destAction }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Custom Delete Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col transform transition-all animate-in fade-in zoom-in-95 duration-200">
                <div class="px-8 py-8 flex flex-col items-center text-center">
                    <div class="w-20 h-20 rounded-full bg-red-50 flex items-center justify-center text-red-500 mb-6">
                        <PhTrash :size="40" weight="duotone" />
                    </div>
                    <h3 class="text-2xl font-bold text-slate-800 mb-2">Move to Recycle Bin?</h3>
                    <p class="text-[15px] text-slate-500 leading-relaxed mb-6">
                        Are you sure you want to delete <strong class="text-slate-800">{{ itemsToDelete.length }} item{{ itemsToDelete.length > 1 ? 's' : '' }}</strong>? <br> You can restore them later from the recycle bin.
                    </p>
                    <p v-if="deleteError" class="text-red-500 text-[13px] font-medium p-3 bg-red-50 rounded-xl w-full mb-6 flex items-center justify-center gap-2">
                        <PhWarningCircle weight="fill" :size="16" /> {{ deleteError }}
                    </p>
                    <div class="flex items-center gap-3 w-full">
                        <button @click="showDeleteModal = false" class="flex-1 px-5 py-3 text-[14px] font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                            Cancel
                        </button>
                        <button @click="confirmDelete" class="flex-1 px-5 py-3 bg-red-500 text-white rounded-xl text-[14px] font-bold hover:bg-red-600 transition-all shadow-lg shadow-red-500/30">
                            Delete Items
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- New Folder Modal -->
        <div v-if="showNewFolderModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden transform transition-all">
                <div class="px-8 py-6 border-b border-slate-100 bg-white flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                        <PhFolderPlus :size="24" weight="duotone" />
                    </div>
                    <h3 class="text-xl font-bold text-slate-800">Create New Folder</h3>
                </div>
                <div class="p-8">
                    <label class="block text-[14px] font-semibold text-slate-700 mb-3">Folder Name</label>
                    <input 
                        v-model="newFolderName"
                        @keyup.enter="createFolder"
                        type="text" 
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 outline-none text-[15px] font-medium text-slate-800 transition-all placeholder:text-slate-400 placeholder:font-normal"
                        placeholder="e.g. Marketing Assets"
                        autofocus
                    />
                </div>
                <div class="px-8 py-5 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end gap-3">
                    <button @click="showNewFolderModal = false; newFolderName = ''" class="px-5 py-2.5 text-[14px] font-semibold text-slate-600 hover:text-slate-900 bg-white hover:bg-slate-100 border border-slate-200 rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button @click="createFolder" class="px-6 py-2.5 bg-blue-600 text-white rounded-xl text-[14px] font-semibold hover:bg-blue-700 transition-all shadow-lg shadow-blue-600/30">
                        Create Folder
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Manage Access Modal -->
        <div v-if="showAccessModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
            <div class="bg-white w-full max-w-xl rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh] transform transition-all">
                <div class="px-8 py-6 border-b border-slate-100 bg-white flex items-center gap-4 shrink-0">
                    <div class="w-12 h-12 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600">
                        <PhDotsThree :size="24" weight="bold" />
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-800">Manage Access</h3>
                        <p class="text-[13px] text-slate-500 font-medium mt-0.5 truncate max-w-[300px]" :title="selectedPathForAccess">
                            {{ selectedPathForAccess }}
                        </p>
                    </div>
                </div>
                <div class="p-8 flex-1 overflow-y-auto bg-slate-50/30">
                    <p class="text-[14px] text-slate-600 font-medium mb-5">
                        Select which users can view and edit the contents of this folder. Admins have access by default.
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <label v-for="u in allUsers" :key="u.id" class="flex items-center gap-4 p-4 border-2 border-transparent rounded-2xl cursor-pointer bg-white shadow-sm hover:shadow-md transition-all duration-200" :class="selectedUserIds.includes(u.id) ? 'border-emerald-500 ring-4 ring-emerald-500/10' : 'border-slate-100 hover:border-slate-300'">
                            <input type="checkbox" :value="u.id" v-model="selectedUserIds" class="w-5 h-5 text-emerald-500 rounded-md border-slate-300 focus:ring-emerald-500 focus:ring-offset-0">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-slate-100 overflow-hidden flex items-center justify-center shrink-0 border border-slate-200">
                                    <img v-if="u.avatar" :src="u.avatar" class="w-full h-full object-cover">
                                    <span v-else class="text-[15px] font-bold text-slate-400">{{ u.name.charAt(0) }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[14px] font-bold text-slate-700">{{ u.name }}</span>
                                    <span class="text-[12px] font-medium text-slate-400">{{ u.role?.name || 'User' }}</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                <div class="px-8 py-5 border-t border-slate-100 bg-white flex items-center justify-end gap-3 shrink-0">
                    <button @click="showAccessModal = false" class="px-5 py-2.5 text-[14px] font-semibold text-slate-600 hover:text-slate-900 bg-white hover:bg-slate-100 border border-slate-200 rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button @click="saveAccess" :disabled="isSavingAccess" class="px-6 py-2.5 bg-emerald-600 text-white rounded-xl text-[14px] font-semibold hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-600/30 disabled:opacity-50 disabled:shadow-none">
                        {{ isSavingAccess ? 'Saving Changes...' : 'Save Permissions' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Optional specific overrides if needed */
</style>
