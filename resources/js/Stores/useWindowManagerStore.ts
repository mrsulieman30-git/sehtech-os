import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export interface AppWindow {
    windowId: string;
    appId: string;
    title: string;
    department: string;
    color: string;
    isOpen: boolean;
    isMinimized: boolean;
    isMaximized: boolean;
    zIndex: number;
}

export const useWindowManagerStore = defineStore('windowManager', () => {
    const windows = ref<AppWindow[]>([]);
    let baseZIndex = 1000;

    const activeWindows = computed(() => windows.value.filter(w => w.isOpen));

    function openWindow(appData: Omit<AppWindow, 'windowId' | 'isOpen' | 'isMinimized' | 'isMaximized' | 'zIndex'>, forceNewTab = false) {
        // If not forcing a new tab, check if one exists and focus it
        if (!forceNewTab) {
            const existing = windows.value.find(w => w.appId === appData.appId && w.isOpen);
            if (existing) {
                existing.isMinimized = false;
                focusWindow(existing.windowId);
                return;
            }
        }

        // Generate a unique ID for this specific tab instance
        const newWindowId = `${appData.appId}-${Date.now()}`;
        
        // Count existing instances to append a tab number to the title if needed
        const existingCount = windows.value.filter(w => w.appId === appData.appId && w.isOpen).length;
        const displayTitle = existingCount > 0 ? `${appData.title} (${existingCount + 1})` : appData.title;

        windows.value.push({
            ...appData,
            title: displayTitle,
            windowId: newWindowId,
            isOpen: true,
            isMinimized: false,
            isMaximized: true, 
            zIndex: ++baseZIndex,
        });
    }

    function closeWindow(windowId: string) {
        const win = windows.value.find(w => w.windowId === windowId);
        if (win) win.isOpen = false;
    }

    function minimizeWindow(windowId: string) {
        const win = windows.value.find(w => w.windowId === windowId);
        if (win) win.isMinimized = true;
    }

    function maximizeWindow(windowId: string) {
        const win = windows.value.find(w => w.windowId === windowId);
        if (win) {
            win.isMaximized = !win.isMaximized;
            focusWindow(windowId);
        }
    }

    function focusWindow(windowId: string) {
        const win = windows.value.find(w => w.windowId === windowId);
        if (win && !win.isMinimized) {
            win.zIndex = ++baseZIndex;
        }
    }

    function toggleMinimize(windowId: string) {
        const win = windows.value.find(w => w.windowId === windowId);
        if (win) {
            if (win.isMinimized) {
                win.isMinimized = false;
                focusWindow(windowId);
            } else {
                const topWindow = activeWindows.value.reduce((prev, current) => (prev.zIndex > current.zIndex) ? prev : current, activeWindows.value[0]);
                if (topWindow && topWindow.windowId === windowId) {
                    win.isMinimized = true;
                } else {
                    focusWindow(windowId);
                }
            }
        }
    }

    function cycleWindows() {
        if (activeWindows.value.length < 2) return;
        
        // Sort windows by zIndex ascending
        const sorted = [...activeWindows.value].sort((a, b) => a.zIndex - b.zIndex);
        
        // The currently focused window is the last one in the sorted array.
        // To cycle, we take the one just below it (second to last) and bring it to front.
        // Actually, a standard cycle brings the bottom-most window to the top.
        const windowToFocus = sorted[0];
        
        if (windowToFocus) {
            windowToFocus.isMinimized = false;
            focusWindow(windowToFocus.windowId);
        }
    }

    return {
        windows,
        activeWindows,
        openWindow,
        closeWindow,
        minimizeWindow,
        maximizeWindow,
        focusWindow,
        toggleMinimize,
        cycleWindows
    };
});
