<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { 
    PhFolder, PhFile, PhUploadSimple, PhDownloadSimple, 
    PhDotsThree, PhFilePdf, PhFileImage, PhFileText, PhHardDrives
} from '@phosphor-icons/vue';
import dayjs from 'dayjs';

interface FileNode {
    id: string;
    name: string;
    original_name: string;
    mime_type: string;
    size: number;
    created_at: string;
}

interface Directory {
    id: string;
    name: string;
}

const currentDirId = ref<string | null>(null);
const directories = ref<Directory[]>([]);
const files = ref<FileNode[]>([]);
const isDragging = ref(false);
const isUploading = ref(false);
const breadcrumbs = ref<{id: string | null, name: string}[]>([
    { id: null, name: 'Root' }
]);

const fetchContents = async () => {
    try {
        const response = await axios.get('/api/files', {
            params: { directory_id: currentDirId.value }
        });
        directories.value = response.data.directories;
        files.value = response.data.files;
    } catch (error) {
        console.error('Failed to fetch file system contents', error);
    }
};

const handleFileUpload = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (!target.files?.length) return;
    await uploadFiles(target.files);
};

const handleDrop = async (event: DragEvent) => {
    isDragging.value = false;
    if (!event.dataTransfer?.files?.length) return;
    await uploadFiles(event.dataTransfer.files);
};

const uploadFiles = async (fileList: FileList) => {
    isUploading.value = true;
    for (let i = 0; i < fileList.length; i++) {
        const formData = new FormData();
        formData.append('file', fileList[i]);
        if (currentDirId.value) {
            formData.append('directory_id', currentDirId.value);
        }

        try {
            await axios.post('/api/files/upload', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        } catch (error) {
            console.error('Upload failed', error);
        }
    }
    isUploading.value = false;
    fetchContents();
};

const navigateToDir = (dirId: string | null, dirName: string) => {
    currentDirId.value = dirId;
    if (dirId === null) {
        breadcrumbs.value = [{ id: null, name: 'Root' }];
    } else {
        // Simple append for this iteration; in prod, we'd fetch full path from API
        breadcrumbs.value.push({ id: dirId, name: dirName }); 
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
    if (mimeType.includes('pdf')) return PhFilePdf;
    if (mimeType.includes('image')) return PhFileImage;
    if (mimeType.includes('text') || mimeType.includes('word')) return PhFileText;
    return PhFile;
};

onMounted(() => {
    fetchContents();
});
</script>

<template>
    <div class="h-full flex flex-col bg-white">
        
        <div class="h-[56px] border-b border-shell-border flex items-center justify-between px-6 bg-shell-panel shrink-0">
            <div class="flex items-center gap-2 text-[13px] font-medium text-text-secondary">
                <template v-for="(crumb, index) in breadcrumbs" :key="index">
                    <button 
                        @click="navigateToDir(crumb.id, crumb.name)"
                        class="hover:text-state-focus transition-colors"
                    >
                        {{ crumb.name }}
                    </button>
                    <span v-if="index < breadcrumbs.length - 1" class="text-shell-border">/</span>
                </template>
            </div>
            
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 px-4 py-2 bg-dept-dev-main text-white rounded-btn text-[13px] font-medium cursor-pointer hover:bg-[#1E293B] transition-colors shadow-sm">
                    <PhUploadSimple :size="16" weight="bold" />
                    <span>Upload File</span>
                    <input type="file" class="hidden" multiple @change="handleFileUpload" />
                </label>
            </div>
        </div>

        <div class="flex-1 flex overflow-hidden">
            
            <div class="w-[240px] border-r border-shell-border bg-shell-panel overflow-y-auto shrink-0 flex flex-col">
                <div class="p-4 text-[12px] font-bold text-text-disabled uppercase tracking-wider">
                    Locations
                </div>
                <div class="px-2 flex flex-col gap-1">
                    <button 
                        @click="navigateToDir(null, 'Root')"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-btn text-[13px] font-medium transition-colors"
                        :class="currentDirId === null ? 'bg-state-focus/10 text-state-focus' : 'text-text-primary hover:bg-shell-border/50'"
                    >
                        <PhHardDrives :size="18" :weight="currentDirId === null ? 'fill' : 'regular'" />
                        Central Storage
                    </button>
                    <button 
                        v-for="dir in directories" 
                        :key="dir.id"
                        @click="navigateToDir(dir.id, dir.name)"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-btn text-[13px] font-medium text-text-primary hover:bg-shell-border/50 transition-colors"
                    >
                        <PhFolder :size="18" weight="fill" class="text-[#64748B]" />
                        {{ dir.name }}
                    </button>
                </div>
            </div>

            <div 
                class="flex-1 relative bg-white overflow-y-auto p-6"
                @dragenter.prevent="isDragging = true"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop"
            >
                <div 
                    v-if="isDragging"
                    class="absolute inset-0 z-10 bg-state-focus/5 border-2 border-dashed border-state-focus rounded-xl m-4 flex flex-col items-center justify-center text-state-focus pointer-events-none transition-all"
                >
                    <PhUploadSimple :size="48" class="mb-4" />
                    <p class="text-[16px] font-semibold">Drop files here to upload to this directory</p>
                </div>

                <div v-if="directories.length === 0 && files.length === 0" class="h-full flex flex-col items-center justify-center text-text-disabled">
                    <PhFolder :size="64" weight="thin" class="mb-4" />
                    <p class="text-[14px]">This directory is empty.</p>
                </div>

                <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                    
                    <div 
                        v-for="dir in directories" 
                        :key="dir.id"
                        @dblclick="navigateToDir(dir.id, dir.name)"
                        class="group flex flex-col items-center p-4 rounded-card border border-shell-border hover:border-state-focus hover:shadow-card cursor-pointer transition-all bg-shell-panel"
                    >
                        <PhFolder :size="48" weight="fill" class="text-[#64748B] mb-3 group-hover:scale-105 transition-transform" />
                        <span class="text-[13px] font-medium text-text-primary text-center truncate w-full">{{ dir.name }}</span>
                    </div>

                    <div 
                        v-for="file in files" 
                        :key="file.id"
                        class="group relative flex flex-col items-center p-4 rounded-card border border-shell-border hover:border-state-focus hover:shadow-card cursor-pointer transition-all bg-white"
                    >
                        <button class="absolute top-2 right-2 p-1 rounded hover:bg-shell-panel text-text-disabled hover:text-text-primary opacity-0 group-hover:opacity-100 transition-opacity">
                            <PhDotsThree :size="16" weight="bold" />
                        </button>

                        <component :is="getFileIcon(file.mime_type)" :size="48" weight="duotone" class="text-state-focus mb-3 group-hover:scale-105 transition-transform" />
                        
                        <div class="w-full text-center">
                            <p class="text-[13px] font-medium text-text-primary truncate" :title="file.original_name">
                                {{ file.original_name }}
                            </p>
                            <p class="text-[11px] text-text-secondary mt-0.5">
                                {{ formatSize(file.size) }} • {{ dayjs(file.created_at).format('MMM D') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="isUploading" class="absolute bottom-6 right-6 px-4 py-3 bg-shell-window text-white rounded-modal shadow-modal flex items-center gap-3">
                    <div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                    <span class="text-[13px] font-medium">Uploading files...</span>
                </div>
            </div>

        </div>
    </div>
</template>
