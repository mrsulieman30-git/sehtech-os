<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { PhBell, PhGear, PhUserCircle, PhSignOut } from '@phosphor-icons/vue';
import FloatingToolbar from '@/Components/OS/FloatingToolbar.vue';
import { useWindowManagerStore } from '@/Stores/useWindowManagerStore';

const page = usePage();
const user = computed(() => page.props.auth.user);
const windowManager = useWindowManagerStore();

const currentTime = ref(dayjs().format('HH:mm'));
const currentDate = ref(dayjs().format('ddd, MMM D'));
let timer: ReturnType<typeof setInterval>;

const showProfileMenu = ref(false);

const toggleMenu = () => {
    showProfileMenu.value = !showProfileMenu.value;
};

const openSettings = () => {
    windowManager.openWindow({ 
        appId: 'settings', 
        title: 'Universal Settings', 
        department: 'admin', 
        color: '#1B2A4A' 
    });
};

onMounted(() => {
    timer = setInterval(() => {
        currentTime.value = dayjs().format('HH:mm');
        currentDate.value = dayjs().format('ddd, MMM D');
    }, 10000);
});

onUnmounted(() => {
    clearInterval(timer);
});
</script>

<template>
    <div class="fixed top-0 left-1/2 -translate-x-1/2 z-[9999] h-[44px] flex items-center gap-6 px-6 bg-shell-window/90 backdrop-blur-md border border-t-0 border-shell-border rounded-b-[16px] shadow-sm select-none">
        
        <div class="flex items-center gap-4 text-white text-[13px] font-bold tracking-wider mr-2">
            <span class="text-white text-[12px]">SEHTECH OS</span>
        </div>

        <div class="flex items-center gap-5">
            <button class="relative text-text-disabled hover:text-white transition-colors">
                <PhBell :size="16" weight="bold" />
                <span class="absolute -top-1 -right-1 w-2 h-2 bg-state-error rounded-full border border-shell-window"></span>
            </button>

            <button @click="openSettings" class="text-text-disabled hover:text-white transition-colors">
                <PhGear :size="16" weight="bold" />
            </button>

            <!-- Tools Dropdown Expansion -->
            <FloatingToolbar />

            <div class="relative flex items-center">
                <button @click="toggleMenu" class="group flex items-center gap-3 text-text-disabled hover:text-white transition-colors">
                    <div class="flex flex-col items-end leading-none mr-1">
                        <span class="text-[12px] font-semibold">{{ currentTime }}</span>
                        <span class="text-[10px] text-slate-400 group-hover:text-slate-300 transition-colors">{{ currentDate }}</span>
                    </div>
                    <div class="w-7 h-7 rounded-full bg-dept-dev-main flex items-center justify-center text-white overflow-hidden shadow-inner border border-shell-border">
                        <img v-if="user?.avatar" :src="user.avatar" alt="Avatar" class="w-full h-full object-cover" />
                        <span v-else class="text-[11px] font-bold">{{ user?.name.charAt(0) }}</span>
                    </div>
                </button>

                <div v-if="showProfileMenu" class="absolute right-0 top-10 mt-1 w-56 bg-white rounded-modal shadow-modal border border-shell-border overflow-hidden origin-top-right">
                    <div class="px-4 py-3 border-b border-shell-border">
                        <p class="text-[14px] font-semibold text-text-primary truncate">{{ user?.name }}</p>
                        <p class="text-[12px] text-text-secondary truncate">{{ user?.role?.name || 'Administrator' }}</p>
                    </div>
                    <div class="p-1">
                        <button class="w-full flex items-center gap-2 px-3 py-2 text-[13px] text-text-primary hover:bg-shell-panel rounded-btn transition-colors">
                            <PhUserCircle :size="18" /> My Profile
                        </button>
                        <Link href="/logout" method="post" as="button" class="w-full flex items-center gap-2 px-3 py-2 text-[13px] text-state-error hover:bg-state-error/10 rounded-btn transition-colors">
                            <PhSignOut :size="18" /> Logout
                        </Link>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>
