<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useWindowManagerStore, type AppWindow } from '@/Stores/useWindowManagerStore';
import { PhMinus, PhCornersOut, PhX } from '@phosphor-icons/vue';
import { AppRegistry } from '@/OS/AppRegistry';

const props = defineProps<{
    windowData: AppWindow;
}>();

const windowManager = useWindowManagerStore();
const windowRef = ref<HTMLElement | null>(null);

const isDragging = ref(false);
const position = ref({ x: 50, y: 50 });

onMounted(() => {
    const vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
    const vh = Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0);
    
    position.value = {
        x: (vw - (vw * 0.8)) / 2,
        y: Math.max(40, (vh - (vh * 0.85)) / 2)
    };

    const checkMobile = () => { isMobile.value = window.innerWidth < 768; };
    checkMobile();
    window.addEventListener('resize', checkMobile);
});

onUnmounted(() => {
    window.removeEventListener('resize', () => { isMobile.value = window.innerWidth < 768; });
});

let startX = 0;
let startY = 0;
let initialX = 0;
let initialY = 0;
const isMobile = ref(false);

const startDrag = (e: MouseEvent) => {
    if (props.windowData.isMaximized || isMobile.value) return; 
    
    isDragging.value = true;
    startX = e.clientX;
    startY = e.clientY;
    initialX = position.value.x;
    initialY = position.value.y;
    
    windowManager.focusWindow(props.windowData.windowId);

    document.addEventListener('mousemove', onDrag);
    document.addEventListener('mouseup', stopDrag);
};

const onDrag = (e: MouseEvent) => {
    if (!isDragging.value) return;
    
    const dx = e.clientX - startX;
    const dy = e.clientY - startY;
    
    const newY = Math.max(0, initialY + dy);
    
    position.value = {
        x: initialX + dx,
        y: newY
    };
};

const stopDrag = () => {
    isDragging.value = false;
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('mouseup', stopDrag);
};

// Map controls to windowId
const handleFocus = () => windowManager.focusWindow(props.windowData.windowId);
const handleMinimize = () => windowManager.minimizeWindow(props.windowData.windowId);
const handleMaximize = () => windowManager.maximizeWindow(props.windowData.windowId);
const handleClose = () => windowManager.closeWindow(props.windowData.windowId);

// Smart scroll: Translate vertical wheel to horizontal if layout requires it
const handleSmartScroll = (e: WheelEvent) => {
    // Only intercept if holding shift is NOT used (native horizontal)
    if (e.shiftKey || Math.abs(e.deltaX) > Math.abs(e.deltaY)) return;

    // We traverse up to find a scrollable container
    let el = e.target as HTMLElement | null;
    while (el && el !== windowRef.value) {
        const style = window.getComputedStyle(el);
        const hasHorizontalScroll = el.scrollWidth > el.clientWidth && style.overflowX !== 'hidden' && style.overflowX !== 'visible';
        const hasVerticalScroll = el.scrollHeight > el.clientHeight && style.overflowY !== 'hidden' && style.overflowY !== 'visible';

        // If the container only needs to scroll horizontally, map the wheel
        if (hasHorizontalScroll && !hasVerticalScroll) {
            e.preventDefault();
            el.scrollLeft += e.deltaY;
            return;
        }
        el = el.parentElement;
    }
};

const windowStyle = computed(() => {
    if (props.windowData.isMinimized) {
        return {
            transform: `translate(${position.value.x}px, calc(100vh + 100px)) scale(0.5)`,
            opacity: 0,
            zIndex: props.windowData.zIndex,
            pointerEvents: 'none' as any
        };
    }
    
    if (props.windowData.isMaximized || isMobile.value) {
        return {
            top: '0px',
            left: '0px',
            width: '100vw',
            height: '100vh', 
            zIndex: props.windowData.zIndex,
            transform: 'none',
            borderRadius: '0px'
        };
    }
    
    return {
        transform: `translate(${position.value.x}px, ${position.value.y}px)`,
        width: '80vw',
        height: '85vh',
        zIndex: props.windowData.zIndex,
    };
});

// Load the component based on appId, not windowId
const activeComponent = computed(() => {
    return AppRegistry[props.windowData.appId] || AppRegistry['fallback'];
});
</script>

<template>
    <div 
        ref="windowRef"
        @mousedown="handleFocus"
        @wheel="handleSmartScroll"
        class="absolute flex flex-col bg-shell-panel rounded-window shadow-modal border border-shell-border overflow-hidden transition-all duration-200 ease-out origin-bottom"
        :class="{ 'duration-0': isDragging }"
        :style="windowStyle"
    >
        <div 
            @mousedown="startDrag"
            class="flex items-center justify-between h-[44px] bg-shell-window border-b border-shell-border select-none"
            :class="{ 'cursor-grab active:cursor-grabbing': !windowData.isMaximized && !isMobile }"
        >
            <div class="flex items-center h-full">
                <div class="h-full w-[6px]" :style="{ backgroundColor: windowData.color }"></div>
                <div class="pl-4 flex items-center gap-2">
                    <span class="text-[13px] font-semibold text-white tracking-wide">{{ windowData.title }}</span>
                </div>
            </div>

            <div class="flex items-center gap-2 pr-4" @mousedown.stop>
                <button v-if="!isMobile" @click="handleMinimize" class="w-[28px] h-[28px] flex items-center justify-center rounded-btn text-text-disabled hover:bg-shell-panel hover:text-text-primary transition-colors">
                    <PhMinus :size="16" weight="bold" />
                </button>
                <button v-if="!isMobile" @click="handleMaximize" class="w-[28px] h-[28px] flex items-center justify-center rounded-btn text-text-disabled hover:bg-shell-panel hover:text-text-primary transition-colors">
                    <PhCornersOut :size="16" weight="bold" />
                </button>
                <button @click="handleClose" class="w-[28px] h-[28px] flex items-center justify-center rounded-btn text-text-disabled hover:bg-state-error hover:text-white transition-colors">
                    <PhX :size="16" weight="bold" />
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-hidden bg-shell-panel relative flex flex-col">
            <component :is="activeComponent" />
        </div>
    </div>
</template>
