<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { gsap } from 'gsap';
import { router } from '@inertiajs/vue3';
import { useWindowManagerStore } from '@/Stores/useWindowManagerStore';
import { 
    PhBuildings, PhLightbulb, PhCode, PhMegaphone, PhKanban, PhFolder,
    PhHandshake, PhScales, PhBank, PhUsers, 
    PhLifebuoy, PhCpu, PhRobot, PhPlus, PhX, PhCaretUp,
    PhCaretLeft, PhCaretRight, PhArrowsClockwise, PhCornersOut, PhCornersIn
} from '@phosphor-icons/vue';

const windowManager = useWindowManagerStore();
const dockContainer = ref<HTMLElement | null>(null);
const notchControls = ref<HTMLElement | null>(null);
const dockAppsContainer = ref<HTMLElement | null>(null);
const iconsRefs = ref<HTMLElement[]>([]);

// Fullscreen & OS Navigation Controls
const isFullscreen = ref(false);

const goBack = () => window.history.back();
const goForward = () => window.history.forward();

const refreshPage = () => {
    const events = [
        'app-refresh',
        'refresh-admin-dashboard',
        'refresh-dev-board',
        'refresh-finance-dashboard',
        'refresh-hr-dashboard',
        'refresh-legal',
        'refresh-marketing',
        'refresh-ops-dashboard',
        'refresh-research-dashboard',
        'refresh-sales-crm',
        'refresh-support-dashboard'
    ];
    
    events.forEach(event => window.dispatchEvent(new CustomEvent(event)));
    router.reload({ preserveScroll: true, preserveState: true });
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
        }, 1000); // 1 second delay
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

watch(isDockVisible, (visible) => {
    if (!dockContainer.value || !notchControls.value || !dockAppsContainer.value) return;
    
    if (visible) {
        // Smooth balloon-like expansion
        gsap.to(dockContainer.value, {
            width: dockAppsContainer.value.scrollWidth + 32, // Accommodate all icons + padding
            height: 76,
            duration: 0.6,
            ease: "power3.out",
            onStart: () => { if(dockContainer.value) dockContainer.value.style.overflow = 'hidden'; },
            onComplete: () => { if(dockContainer.value) dockContainer.value.style.overflow = 'visible'; } // Allow tooltips to overflow
        });
        
        // Fade out notch controls
        gsap.to(notchControls.value, {
            opacity: 0,
            scale: 0.9,
            duration: 0.3,
            ease: "power2.out",
            pointerEvents: 'none'
        });
        
        // Fade in dock apps
        gsap.to(dockAppsContainer.value, {
            opacity: 1,
            scale: 1,
            pointerEvents: 'auto',
            duration: 0.5,
            delay: 0.1,
            ease: "power2.out"
        });
    } else {
        // Smooth balloon-like deflation back to notch
        gsap.to(dockContainer.value, {
            width: 260,
            height: 36,
            duration: 0.5,
            ease: "power3.out",
            onStart: () => { if(dockContainer.value) dockContainer.value.style.overflow = 'hidden'; } // Hide overflows instantly while closing
        });
        
        // Fade in notch controls
        gsap.to(notchControls.value, {
            opacity: 1,
            scale: 1,
            duration: 0.4,
            delay: 0.1,
            ease: "power2.out",
            pointerEvents: 'auto'
        });
        
        // Fade out dock apps
        gsap.to(dockAppsContainer.value, {
            opacity: 0,
            scale: 0.95,
            pointerEvents: 'none',
            duration: 0.3,
            ease: "power2.out"
        });
    }
});

const allDockApps = [
    { appId: 'admin', title: 'Administration', dept: 'admin', color: '#1B2A4A', icon: PhBuildings, reqPerm: 'dept.manage' },
    { appId: 'files', title: 'Files', dept: 'files', color: '#64748B', icon: PhFolder, reqPerm: null },
    { appId: 'ide', title: 'Code Editor', dept: 'dev', color: '#0EA5E9', icon: PhCode, reqPerm: 'dev.manage' },
    { appId: 'research', title: 'Research & Innovation', dept: 'research', color: '#4338CA', icon: PhLightbulb, reqPerm: null },
    { appId: 'dev', title: 'Development', dept: 'dev', color: '#0F172A', icon: PhKanban, reqPerm: 'dev.manage' },
    { appId: 'marketing', title: 'Marketing', dept: 'marketing', color: '#DB2777', icon: PhMegaphone, reqPerm: null },
    { appId: 'sales', title: 'Sales & BD', dept: 'sales', color: '#059669', icon: PhHandshake, reqPerm: 'sales.*' },
    { appId: 'legal', title: 'Legal & Compliance', dept: 'legal', color: '#7C3AED', icon: PhScales, reqPerm: 'legal.*' },
    { appId: 'finance', title: 'Finance', dept: 'finance', color: '#CA8A04', icon: PhBank, reqPerm: 'finance.*' },
    { appId: 'hr', title: 'HR & People', dept: 'hr', color: '#0891B2', icon: PhUsers, reqPerm: 'hr.*' },
    { appId: 'support', title: 'Technical Support', dept: 'support', color: '#EA580C', icon: PhLifebuoy, reqPerm: 'support.*' },
    { appId: 'operations', title: 'Operations', dept: 'ops', color: '#64748B', icon: PhCpu, reqPerm: null },
    { appId: 'agents', title: 'AI Master Agent', dept: 'ai', color: '#0D9488', icon: PhRobot, reqPerm: null },
];

import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const authUser = computed(() => page.props.auth?.user);

const dockApps = computed(() => {
    if (!authUser.value || !authUser.value.role) return allDockApps;
    if (authUser.value.role.is_super_admin) return allDockApps;

    const perms = authUser.value.role.permissions || [];
    if (perms.includes('*')) return allDockApps;

    return allDockApps.filter(app => {
        if (!app.reqPerm) return true; // Allowed by default if no specific permission required

        // Check explicit match
        if (perms.includes(app.reqPerm)) return true;

        // Check wildcard match for the module (e.g., 'hr.*' matches 'hr.manage')
        const module = app.reqPerm.split('.')[0];
        if (perms.includes(`${module}.*`)) return true;

        return false;
    });
});

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
    if (!isDockVisible.value) return; // Only magnify if fully open
    
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
            ease: 'power3.out', // Smooth reset
        });
    });
};

const setIconRef = (el: any, index: number) => {
    if (el) iconsRefs.value[index] = el;
};
</script>

<template>
    <div class="fixed bottom-0 left-0 w-full flex flex-col items-center justify-end z-[9999] pointer-events-none h-[150px]">
        
        <!-- Morphing Container -->
        <div 
            ref="dockContainer"
            @mousemove="handleMouseMove"
            @mouseleave="() => { cancelShowTimer(); handleMouseLeave(); hideDock(); }"
            class="relative flex items-center justify-center bg-shell-window/80 backdrop-blur-xl border border-b-0 border-shell-border shadow-[0_-5px_25px_rgba(0,0,0,0.1)] rounded-t-[16px] pointer-events-auto mx-auto overflow-hidden"
            style="width: 260px; height: 36px; will-change: width, height;"
        >
            
            <!-- Collapsed State: Notch Controls -->
            <div 
                ref="notchControls"
                class="absolute inset-0 flex items-center justify-between px-3 w-[260px] mx-auto z-10"
            >
                <!-- Navigation Left -->
                <div class="flex items-center gap-1">
                    <button @click.stop="goBack" class="p-1.5 rounded-md text-white/50 hover:!text-white hover:bg-white/10 active:scale-90 transition-all flex items-center justify-center" title="Back">
                        <PhCaretLeft :size="16" weight="bold" />
                    </button>
                    <button @click.stop="goForward" class="p-1.5 rounded-md text-white/50 hover:!text-white hover:bg-white/10 active:scale-90 transition-all flex items-center justify-center" title="Forward">
                        <PhCaretRight :size="16" weight="bold" />
                    </button>
                </div>

                <!-- Center Trigger -->
                <div @click="showDockNow" @mouseenter="startShowTimer" class="flex-1 h-full flex items-center justify-center cursor-pointer" title="Show Dock">
                    <div class="flex items-center justify-center text-white/50 hover:!text-white hover:scale-125 transition-all duration-300">
                        <PhCaretUp :size="20" class="animate-pulse" weight="bold" />
                    </div>
                </div>

                <!-- Right: Refresh & Fullscreen -->
                <div class="flex items-center gap-1">
                    <button @click.stop="refreshPage" class="p-1.5 rounded-md text-white/50 hover:!text-white hover:bg-white/10 active:scale-90 transition-all flex items-center justify-center" title="Refresh">
                        <PhArrowsClockwise :size="16" weight="bold" />
                    </button>
                    <button @click.stop="toggleFullscreen" class="p-1.5 rounded-md text-white/50 hover:!text-white hover:bg-white/10 active:scale-90 transition-all flex items-center justify-center" :title="isFullscreen ? 'Exit Fullscreen' : 'Enter Fullscreen'">
                        <component :is="isFullscreen ? PhCornersIn : PhCornersOut" :size="16" weight="bold" />
                    </button>
                </div>
            </div>

            <!-- Expanded State: Dock Apps -->
            <div 
                ref="dockAppsContainer"
                class="absolute inset-0 flex flex-wrap justify-center items-end gap-2 px-3 pt-3 pb-2 w-max max-w-[95vw] mx-auto opacity-0 pointer-events-none z-20"
                style="transform: scale(0.95);"
            >
                <div 
                    v-for="(app, index) in dockApps" 
                    :key="app.appId"
                    :ref="(el) => setIconRef(el, index)"
                    class="relative group flex flex-col items-center justify-end w-[52px]"
                >
                    <!-- Tooltip / Context Menu -->
                    <div class="absolute bottom-[60px] mb-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 min-w-[180px] w-max max-w-[300px] bg-white/90 backdrop-blur-xl border border-shell-border/60 rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] pointer-events-auto flex flex-col overflow-hidden z-[10000]">
                        <div class="px-3 py-2 bg-shell-panel/80 backdrop-blur-md border-b border-shell-border/60 flex justify-between items-center">
                            <span class="text-[12px] font-bold text-text-primary">{{ app.title }}</span>
                        </div>
                        
                        <div class="flex-1 max-h-[350px] overflow-y-auto p-1.5 flex flex-col gap-1 custom-scrollbar">
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

                    <!-- App Icon -->
                    <div 
                        @click="launchApp(app)"
                        class="w-[52px] h-[52px] rounded-[12px] flex items-center justify-center text-white shadow-card transition-colors cursor-pointer relative"
                        :style="{ backgroundColor: app.color }"
                    >
                        <component :is="app.icon" :size="28" weight="fill" />
                        
                        <!-- Open Indicator -->
                        <div 
                            class="absolute -bottom-2 w-1 h-1 rounded-full bg-state-focus transition-opacity duration-200"
                            :class="getAppWindows(app.appId).length > 0 ? 'opacity-100' : 'opacity-0'"
                        ></div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>
