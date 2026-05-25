<script setup lang="ts">
import { ref } from 'vue';
import { gsap } from 'gsap';
import { router } from '@inertiajs/vue3';
import { useWindowManagerStore } from '@/Stores/useWindowManagerStore';
import { 
    PhBuildings, PhLightbulb, PhCode, PhMegaphone, 
    PhHandshake, PhScales, PhBank, PhUsers, 
    PhLifebuoy, PhCpu, PhRobot, PhPlus, PhX, PhCaretUp,
    PhCaretLeft, PhCaretRight, PhArrowsClockwise, PhCornersOut, PhCornersIn
} from '@phosphor-icons/vue';

const windowManager = useWindowManagerStore();
const dockContainer = ref<HTMLElement | null>(null);
const iconsRefs = ref<HTMLElement[]>([]);

// Fullscreen & OS Navigation Controls
const isFullscreen = ref(false);

const goBack = () => {
    window.history.back();
};

const goForward = () => {
    window.history.forward();
};

const refreshPage = () => {
    window.dispatchEvent(new CustomEvent('app-refresh'));
    // Also dispatch specific dashboard events for backward compatibility
    window.dispatchEvent(new CustomEvent('refresh-support-dashboard'));
    window.dispatchEvent(new CustomEvent('refresh-hr-dashboard'));
    window.dispatchEvent(new CustomEvent('refresh-finance-dashboard'));
};

const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().then(() => {
            isFullscreen.value = true;
        }).catch((err) => {
            console.error(`Error attempting to enable full-screen mode: ${err.message}`);
        });
    } else {
        document.exitFullscreen().then(() => {
            isFullscreen.value = false;
        });
    }
};

if (typeof document !== 'undefined') {
    document.addEventListener('fullscreenchange', () => {
        isFullscreen.value = !!document.fullscreenElement;
    });
}

const isDockVisible = ref(false);
let hideTimeout: ReturnType<typeof setTimeout> | null = null;
let showTimeout: ReturnType<typeof setTimeout> | null = null;

const startShowTimer = () => {
    if (hideTimeout) clearTimeout(hideTimeout);
    if (!showTimeout && !isDockVisible.value) {
        showTimeout = setTimeout(() => {
            isDockVisible.value = true;
            showTimeout = null;
        }, 1000);
    }
};

const cancelShowTimer = () => {
    if (showTimeout) {
        clearTimeout(showTimeout);
        showTimeout = null;
    }
};

const showDockNow = () => {
    cancelShowTimer();
    if (hideTimeout) clearTimeout(hideTimeout);
    isDockVisible.value = true;
};

const hideDock = () => {
    cancelShowTimer();
    hideTimeout = setTimeout(() => {
        isDockVisible.value = false;
    }, 400);
};

const dockApps = [
    { appId: 'admin', title: 'Administration', dept: 'admin', color: '#1B2A4A', icon: PhBuildings },
    { appId: 'research', title: 'Research & Innovation', dept: 'research', color: '#4338CA', icon: PhLightbulb },
    { appId: 'dev', title: 'Development', dept: 'dev', color: '#0F172A', icon: PhCode },
    { appId: 'marketing', title: 'Marketing', dept: 'marketing', color: '#DB2777', icon: PhMegaphone },
    { appId: 'sales', title: 'Sales & BD', dept: 'sales', color: '#059669', icon: PhHandshake },
    { appId: 'legal', title: 'Legal & Compliance', dept: 'legal', color: '#7C3AED', icon: PhScales },
    { appId: 'finance', title: 'Finance', dept: 'finance', color: '#CA8A04', icon: PhBank },
    { appId: 'hr', title: 'HR & People', dept: 'hr', color: '#0891B2', icon: PhUsers },
    { appId: 'support', title: 'Technical Support', dept: 'support', color: '#EA580C', icon: PhLifebuoy },
    { appId: 'operations', title: 'Operations', dept: 'ops', color: '#64748B', icon: PhCpu },
    { appId: 'agents', title: 'AI Master Agent', dept: 'ai', color: '#0D9488', icon: PhRobot },
];

const launchApp = (app: any, forceNew = false) => {
    windowManager.openWindow({
        appId: app.appId,
        title: app.title,
        department: app.dept,
        color: app.color,
    }, forceNew);
};

const getAppWindows = (appId: string) => {
    return windowManager.activeWindows.filter(w => w.appId === appId);
};

const handleMouseMove = (e: MouseEvent) => {
    if (!dockContainer.value) return;
    
    iconsRefs.value.forEach((icon) => {
        if (!icon) return;
        const rect = icon.getBoundingClientRect();
        const iconCenterX = rect.left + rect.width / 2;
        const distance = Math.abs(e.clientX - iconCenterX);
        
        const maxDist = 150; 
        
        let scale = 1;
        if (distance < maxDist) {
            const scaleFactor = 1 + (0.3 * Math.cos((distance / maxDist) * (Math.PI / 2)));
            scale = scaleFactor;
        }

        gsap.to(icon, {
            scale: scale,
            duration: 0.1,
            ease: 'power1.out',
            transformOrigin: 'bottom center'
        });
    });
};

const handleMouseLeave = () => {
    iconsRefs.value.forEach((icon) => {
        if (!icon) return;
        gsap.to(icon, {
            scale: 1,
            duration: 0.3,
            ease: 'back.out(1.5)',
        });
    });
};

const setIconRef = (el: any, index: number) => {
    if (el) iconsRefs.value[index] = el;
};
</script>

<template>
    <div class="fixed bottom-0 left-0 w-full flex flex-col items-center justify-end z-[9999] pointer-events-none h-[120px]">
        
        <!-- Smart Arrow Trigger / OS Navigation Control Notch -->
        <div 
            class="absolute bottom-0 w-[260px] h-[36px] rounded-t-[16px] bg-shell-window/80 backdrop-blur-md border border-b-0 border-shell-border hover:bg-shell-window/90 hover:border-white/20 pointer-events-auto flex items-center justify-between px-3 group transition-all duration-300 z-10 shadow-lg"
            :class="isDockVisible ? 'translate-y-10 opacity-0' : 'translate-y-0 opacity-100'"
        >
            <!-- Navigation Left -->
            <div class="flex items-center gap-1 z-20">
                <button 
                    @click.stop="goBack" 
                    class="p-1.5 rounded-md text-white/50 group-hover:text-white/80 hover:!text-white hover:bg-white/10 active:scale-90 transition-all flex items-center justify-center"
                    title="Back"
                >
                    <PhCaretLeft :size="16" weight="bold" />
                </button>
                <button 
                    @click.stop="goForward" 
                    class="p-1.5 rounded-md text-white/50 group-hover:text-white/80 hover:!text-white hover:bg-white/10 active:scale-90 transition-all flex items-center justify-center"
                    title="Forward"
                >
                    <PhCaretRight :size="16" weight="bold" />
                </button>
            </div>

            <!-- Center: Dock Trigger Area -->
            <div 
                @mouseenter="startShowTimer"
                @mouseleave="cancelShowTimer"
                @click="showDockNow"
                class="flex-1 h-full flex items-center justify-center z-20 cursor-pointer"
                title="Show Dock"
            >
                <div class="flex items-center justify-center text-white/50 group-hover:text-white/80 hover:!text-white hover:scale-125 transition-all duration-300">
                    <PhCaretUp :size="20" class="animate-bounce" weight="bold" />
                </div>
            </div>

            <!-- Right: Refresh & Fullscreen -->
            <div class="flex items-center gap-1 z-20">
                <button 
                    @click.stop="refreshPage" 
                    class="p-1.5 rounded-md text-white/50 group-hover:text-white/80 hover:!text-white hover:bg-white/10 active:scale-90 transition-all flex items-center justify-center"
                    title="Refresh"
                >
                    <PhArrowsClockwise :size="16" weight="bold" />
                </button>
                <button 
                    @click.stop="toggleFullscreen" 
                    class="p-1.5 rounded-md text-white/50 group-hover:text-white/80 hover:!text-white hover:bg-white/10 active:scale-90 transition-all flex items-center justify-center"
                    :title="isFullscreen ? 'Exit Fullscreen' : 'Enter Fullscreen'"
                >
                    <component :is="isFullscreen ? PhCornersIn : PhCornersOut" :size="16" weight="bold" />
                </button>
            </div>
        </div>


        <!-- The Dock -->
        <div 
            ref="dockContainer"
            @mousemove="handleMouseMove"
            @mouseenter="showDockNow"
            @mouseleave="() => { handleMouseLeave(); hideDock(); }"
            class="flex items-end gap-2 px-3 pb-2 pt-2 bg-shell-window/90 backdrop-blur-xl border border-b-0 border-shell-border rounded-t-app shadow-dock pointer-events-auto transition-transform duration-500 ease-[cubic-bezier(0.175,0.885,0.32,1.275)] relative z-20 max-w-full overflow-x-auto overflow-y-visible"
            :class="isDockVisible ? 'translate-y-0' : 'translate-y-full'"
        >
            <div 
                v-for="(app, index) in dockApps" 
                :key="app.appId"
                :ref="(el) => setIconRef(el, index)"
                class="relative group flex flex-col items-center justify-end pb-2 w-[52px]"
            >
                <div class="absolute bottom-16 mb-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 min-w-[200px] bg-white border border-shell-border rounded-card shadow-modal pointer-events-auto flex flex-col overflow-hidden z-[10000]">
                    <div class="px-3 py-2 bg-shell-panel border-b border-shell-border flex justify-between items-center">
                        <span class="text-[12px] font-bold text-text-primary">{{ app.title }}</span>
                    </div>
                    
                    <div class="flex-1 max-h-[200px] overflow-y-auto p-1 flex flex-col gap-1">
                        <div v-if="getAppWindows(app.appId).length === 0" class="px-3 py-4 text-center text-[12px] text-text-disabled">
                            No windows open
                        </div>
                        
                        <div 
                            v-for="win in getAppWindows(app.appId)" 
                            :key="win.windowId"
                            @click.stop="windowManager.focusWindow(win.windowId)"
                            class="flex items-center justify-between px-2 py-1.5 rounded-btn hover:bg-shell-panel cursor-pointer group/tab transition-colors"
                        >
                            <span class="text-[12px] font-medium text-text-primary truncate" :class="{'text-state-focus': !win.isMinimized}">
                                {{ win.title }}
                            </span>
                            <button @click.stop="windowManager.closeWindow(win.windowId)" class="opacity-0 group-hover/tab:opacity-100 p-1 text-text-disabled hover:text-state-error rounded transition-all">
                                <PhX :size="12" weight="bold" />
                            </button>
                        </div>
                    </div>

                    <div class="p-1 border-t border-shell-border bg-shell-panel">
                        <button 
                            @click.stop="launchApp(app, true)"
                            class="w-full flex items-center justify-center gap-2 px-2 py-1.5 text-[12px] font-medium text-text-primary hover:bg-shell-border/50 rounded-btn transition-colors"
                        >
                            <PhPlus :size="14" /> New Window
                        </button>
                    </div>
                </div>

                <div 
                    @click="launchApp(app)"
                    class="w-[52px] h-[52px] rounded-[12px] flex items-center justify-center text-white shadow-card transition-colors cursor-pointer"
                    :style="{ backgroundColor: app.color }"
                >
                    <component :is="app.icon" :size="28" weight="fill" />
                </div>

                <div 
                    class="absolute bottom-0.5 w-1 h-1 rounded-full bg-state-focus transition-opacity duration-200"
                    :class="getAppWindows(app.appId).length > 0 ? 'opacity-100' : 'opacity-0'"
                ></div>
            </div>
        </div>
    </div>
</template>
