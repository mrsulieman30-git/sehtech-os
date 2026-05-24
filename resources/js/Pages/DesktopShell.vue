<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';
import SystemTray from '@/Components/OS/SystemTray.vue';
import DesktopDock from '@/Components/OS/DesktopDock.vue';
import WindowFrame from '@/Components/OS/WindowFrame.vue';
import GlobalSearch from '@/Components/OS/GlobalSearch.vue';
import ModalManager from '@/Components/OS/ModalManager.vue';
import ToastManager from '@/Components/OS/ToastManager.vue';
import { useWindowManagerStore } from '@/Stores/useWindowManagerStore';

const windowManager = useWindowManagerStore();

const handleGlobalKeydown = (e: KeyboardEvent) => {
    if (e.ctrlKey && e.key === 'Backspace') {
        e.preventDefault();
        windowManager.cycleWindows();
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleGlobalKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleGlobalKeydown);
});
</script>

<template>
    <Head title="Workspace" />

    <div class="relative w-screen h-screen overflow-hidden bg-shell-bg text-text-primary selection:bg-state-focus/30">
        
        <SystemTray />
        
        <ModalManager />

        <GlobalSearch />

        <div class="absolute inset-0 z-[100] pointer-events-none">
            <div class="pointer-events-auto h-full w-full">
                <WindowFrame 
                    v-for="win in windowManager.activeWindows" 
                    :key="win.windowId" 
                    :window-data="win"
                />
            </div>
        </div>

        <DesktopDock />

        <ToastManager />
    </div>
</template>
