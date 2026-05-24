<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { PhTimer, PhCalculator, PhMagnifyingGlass, PhNotepad, PhWrench } from '@phosphor-icons/vue';
import PomodoroTimer from './Tools/PomodoroTimer.vue';
import FloatingCalculator from './Tools/FloatingCalculator.vue';
import QuickSearch from './Tools/QuickSearch.vue';
import FloatingNotes from './Tools/FloatingNotes.vue';

const isExpanded = ref(false);
const openedByClick = ref(false);

const showPomodoro = ref(false);
const showCalculator = ref(false);
const showSearch = ref(false);
const showNotes = ref(false);

// minimized state (tool stays alive in background but panel is collapsed)
const minPomodoro = ref(false);
const minCalculator = ref(false);
const minSearch = ref(false);
const minNotes = ref(false);

const tools = [
    { id: 'pomodoro', icon: PhTimer, label: 'Pomodoro', color: '#854D0E', glow: 'rgba(133,77,14,0.5)', show: showPomodoro, min: minPomodoro },
    { id: 'calc', icon: PhCalculator, label: 'Calculator', color: '#059669', glow: 'rgba(5,150,105,0.5)', show: showCalculator, min: minCalculator },
    { id: 'search', icon: PhMagnifyingGlass, label: 'Search', color: '#3B82F6', glow: 'rgba(59,130,246,0.5)', show: showSearch, min: minSearch },
    { id: 'notes', icon: PhNotepad, label: 'Notes', color: '#8B5CF6', glow: 'rgba(139,92,246,0.5)', show: showNotes, min: minNotes },
];

let hoverTimer: ReturnType<typeof setTimeout> | null = null;

const handleMouseEnter = () => {
    if (!isExpanded.value) {
        hoverTimer = setTimeout(() => {
            isExpanded.value = true;
            openedByClick.value = false;
        }, 1000);
    }
};

const handleMouseLeave = () => {
    if (hoverTimer) clearTimeout(hoverTimer);
    if (!openedByClick.value) {
        isExpanded.value = false;
    }
};

const toggleMenuClick = () => {
    if (hoverTimer) clearTimeout(hoverTimer);
    isExpanded.value = !isExpanded.value;
    if (isExpanded.value) openedByClick.value = true;
};

const closeOverlay = () => {
    isExpanded.value = false;
    openedByClick.value = false;
};

const toggle = (tool: typeof tools[0]) => {
    if (tool.show.value && !tool.min.value) {
        tool.min.value = true;
    } else if (tool.show.value && tool.min.value) {
        tool.min.value = false;
    } else {
        tool.show.value = true;
        tool.min.value = false;
    }
};

const STORAGE_KEY = 'sehtech_toolbar_state';

const handleToolClick = (tool: typeof tools[0]) => {
    toggle(tool);
    closeOverlay();
};

const closeTool = (tool: typeof tools[0]) => {
    tool.show.value = false;
    tool.min.value = false;
};

const minimizeTool = (tool: typeof tools[0]) => {
    tool.min.value = true;
};

// Persist open/minimized state
const saveState = () => {
    const state: Record<string, { open: boolean; minimized: boolean }> = {};
    tools.forEach(t => {
        state[t.id] = { open: t.show.value, minimized: t.min.value };
    });
    localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
};

const loadState = () => {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return;
        const state = JSON.parse(raw);
        tools.forEach(t => {
            if (state[t.id]) {
                t.show.value = state[t.id].open;
                t.min.value = state[t.id].minimized;
            }
        });
    } catch (_) {}
};

onMounted(() => loadState());

// Watch all state changes
watch([showPomodoro, showCalculator, showSearch, showNotes, minPomodoro, minCalculator, minSearch, minNotes], () => {
    saveState();
});
</script>

<template>
    <!-- Expandable Topbar Tools -->
    <div class="relative flex items-center justify-center" @mouseleave="handleMouseLeave">
        <!-- Trigger Icon -->
        <button @click="toggleMenuClick" @mouseenter="handleMouseEnter" class="text-text-disabled hover:text-white transition-colors relative z-10 flex items-center justify-center">
            <PhWrench :size="16" weight="bold" />
        </button>

        <!-- Expanded Tools Dropdown -->
        <div 
            class="absolute top-[120%] right-1/2 translate-x-1/2 flex flex-col gap-2 items-center bg-[#1B2A4A]/90 backdrop-blur-xl rounded-[20px] p-2.5 shadow-2xl border border-shell-border transition-all duration-300 origin-top overflow-hidden z-[9999]"
            :class="isExpanded ? 'opacity-100 scale-100 translate-y-0 h-auto' : 'opacity-0 scale-95 -translate-y-4 h-0 p-0 border-transparent pointer-events-none'"
        >
            <div 
                v-for="tool in tools" 
                :key="tool.id"
                @click="handleToolClick(tool)"
                class="w-10 h-10 rounded-full flex items-center justify-center cursor-pointer transition-all duration-200 group relative shrink-0"
                :class="tool.show.value ? 'scale-110' : 'hover:scale-110 hover:bg-white/10'"
                :style="{ 
                    background: tool.show.value ? tool.color : 'transparent',
                    boxShadow: tool.show.value ? '0 0 10px ' + tool.glow : 'none'
                }"
                :title="tool.label"
            >
                <component 
                    :is="tool.icon" 
                    :size="20" 
                    weight="fill"
                    class="transition-colors"
                    :class="tool.show.value ? 'text-white' : 'text-white/60 group-hover:text-white'"
                />
                
                <!-- Minimized dot -->
                <span 
                    v-if="tool.show.value && tool.min.value"
                    class="absolute -bottom-0.5 right-1 w-2.5 h-2.5 rounded-full border-2 border-shell-border opacity-80"
                    :style="{ background: tool.color }"
                ></span>
            </div>
        </div>
    </div>

    <!-- Frosted Glass Background Blur when Expanded -->
    <Teleport to="body">
        <div 
            class="fixed inset-0 z-[9990] bg-slate-900/50 backdrop-blur-sm transition-all duration-300"
            :class="isExpanded ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'"
            @click="closeOverlay"
        ></div>
    </Teleport>

    <!-- Tool Panels -->
    <Teleport to="body">
        <PomodoroTimer 
            v-if="showPomodoro" 
            :minimized="minPomodoro"
            @close="closeTool(tools[0])" 
            @minimize="minimizeTool(tools[0])"
            @restore="minPomodoro = false"
        />
        <FloatingCalculator 
            v-if="showCalculator" 
            :minimized="minCalculator"
            @close="closeTool(tools[1])" 
            @minimize="minimizeTool(tools[1])"
            @restore="minCalculator = false"
        />
        <QuickSearch 
            v-if="showSearch" 
            :minimized="minSearch"
            @close="closeTool(tools[2])" 
            @minimize="minimizeTool(tools[2])"
            @restore="minSearch = false"
        />
        <FloatingNotes 
            v-if="showNotes" 
            :minimized="minNotes"
            @close="closeTool(tools[3])" 
            @minimize="minimizeTool(tools[3])"
            @restore="minNotes = false"
        />
    </Teleport>
</template>
