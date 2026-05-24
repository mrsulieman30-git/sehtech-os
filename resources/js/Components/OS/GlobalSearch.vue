<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { PhMagnifyingGlass, PhRobot, PhX } from '@phosphor-icons/vue';
import { useWindowManagerStore } from '@/Stores/useWindowManagerStore';

const windowManager = useWindowManagerStore();
const isOpen = ref(false);
const searchInput = ref<HTMLInputElement | null>(null);
const query = ref('');

// Listen for Cmd+K or Ctrl+K
const handleKeydown = (e: KeyboardEvent) => {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        openSearch();
    }
    if (e.key === 'Escape' && isOpen.value) {
        closeSearch();
    }
};

const openSearch = () => {
    isOpen.value = true;
    nextTick(() => {
        searchInput.value?.focus();
    });
};

const closeSearch = () => {
    isOpen.value = false;
    query.value = '';
};

// Launch the AI Master Agent from search
const askMasterAgent = () => {
    closeSearch();
    windowManager.openWindow({
        id: 'agents',
        title: 'AI Master Agent',
        department: 'ai',
        color: '#0D9488'
    });
};

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <div 
        v-if="isOpen"
        class="fixed inset-0 z-[10000] flex items-start justify-center pt-[15vh] bg-shell-bg/80 backdrop-blur-md transition-opacity duration-200"
        @click.self="closeSearch"
    >
        <div class="w-full max-w-[600px] bg-white rounded-modal shadow-modal border border-shell-border overflow-hidden flex flex-col">
            
            <div class="flex items-center px-4 py-3 border-b border-shell-border">
                <PhMagnifyingGlass :size="24" class="text-text-secondary" />
                <input 
                    ref="searchInput"
                    v-model="query"
                    type="text" 
                    placeholder="Search clients, projects, files, or ask AI..."
                    class="flex-1 bg-transparent border-none outline-none focus:ring-0 px-4 text-[16px] text-text-primary placeholder:text-text-disabled"
                />
                <button @click="closeSearch" class="p-1 text-text-disabled hover:text-text-primary rounded-btn transition-colors">
                    <PhX :size="20" />
                </button>
            </div>

            <div class="p-4 bg-shell-panel flex flex-col items-center justify-center py-12">
                <p v-if="query.length === 0" class="text-text-secondary text-[14px]">
                    Type to search Meilisearch index...
                </p>
                <p v-else class="text-text-secondary text-[14px]">
                    Searching for "<strong>{{ query }}</strong>"...
                </p>

                <button 
                    @click="askMasterAgent"
                    class="mt-6 flex items-center gap-2 px-4 py-2 bg-dept-ai-sec text-dept-ai-main border border-dept-ai-main/20 rounded-btn hover:bg-dept-ai-main hover:text-white transition-colors duration-150"
                >
                    <PhRobot :size="18" weight="fill" />
                    <span class="text-[13px] font-medium">Ask Master Agent NEXAR</span>
                </button>
            </div>
            
            <div class="px-4 py-2 bg-white border-t border-shell-border flex items-center justify-between text-[11px] text-text-disabled">
                <span>Navigate with <kbd class="px-1 py-0.5 rounded bg-shell-panel border border-shell-border">↑</kbd> <kbd class="px-1 py-0.5 rounded bg-shell-panel border border-shell-border">↓</kbd></span>
                <span>Press <kbd class="px-1 py-0.5 rounded bg-shell-panel border border-shell-border">Esc</kbd> to close</span>
            </div>
        </div>
    </div>
</template>
