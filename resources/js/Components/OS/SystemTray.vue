<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import axios from 'axios';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import { PhBell, PhGear, PhUserCircle, PhSignOut, PhCheckCircle } from '@phosphor-icons/vue';
import FloatingToolbar from '@/Components/OS/FloatingToolbar.vue';
import { useWindowManagerStore } from '@/Stores/useWindowManagerStore';

dayjs.extend(relativeTime);

const page = usePage();
const user = computed(() => page.props.auth.user);
const windowManager = useWindowManagerStore();

const currentTime = ref(dayjs().format('HH:mm'));
const currentDate = ref(dayjs().format('ddd, MMM D'));
let timer: ReturnType<typeof setInterval>;
let notificationTimer: ReturnType<typeof setInterval>;

const showProfileMenu = ref(false);
const showNotificationsMenu = ref(false);

const notifications = ref<any[]>([]);
const unreadCount = ref(0);

const toggleMenu = () => {
    showProfileMenu.value = !showProfileMenu.value;
    if (showProfileMenu.value) showNotificationsMenu.value = false;
};

const toggleNotifications = () => {
    showNotificationsMenu.value = !showNotificationsMenu.value;
    if (showNotificationsMenu.value) showProfileMenu.value = false;
};

const openSettings = () => {
    windowManager.openWindow({ 
        appId: 'settings', 
        title: 'Universal Settings', 
        department: 'admin', 
        color: '#1B2A4A' 
    });
};

const fetchNotifications = async () => {
    try {
        const response = await axios.get('/api/notifications');
        notifications.value = response.data.all;
        unreadCount.value = response.data.unread.length;
    } catch (error) {
        console.error('Failed to fetch notifications', error);
    }
};

const markAsRead = async (id: string) => {
    try {
        await axios.post(`/api/notifications/${id}/read`);
        const notification = notifications.value.find(n => n.id === id);
        if (notification && !notification.read_at) {
            notification.read_at = new Date().toISOString();
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        }
    } catch (error) {
        console.error('Failed to mark as read', error);
    }
};

const markAllAsRead = async () => {
    try {
        await axios.post('/api/notifications/read-all');
        notifications.value.forEach(n => n.read_at = n.read_at || new Date().toISOString());
        unreadCount.value = 0;
    } catch (error) {
        console.error('Failed to mark all as read', error);
    }
};

onMounted(() => {
    timer = setInterval(() => {
        currentTime.value = dayjs().format('HH:mm');
        currentDate.value = dayjs().format('ddd, MMM D');
    }, 10000);

    fetchNotifications();
    notificationTimer = setInterval(fetchNotifications, 30000); // Check every 30s
});

onUnmounted(() => {
    clearInterval(timer);
    clearInterval(notificationTimer);
});
</script>

<template>
    <div class="fixed top-0 left-1/2 -translate-x-1/2 z-[9999] h-[44px] flex items-center gap-6 px-6 bg-shell-window/90 backdrop-blur-md border border-t-0 border-shell-border rounded-b-[16px] shadow-sm select-none">
        
        <div class="flex items-center gap-4 text-white text-[13px] font-bold tracking-wider mr-2">
            <span class="text-white text-[12px]">SEHTECH OS</span>
        </div>

        <div class="flex items-center gap-5">
            <div class="relative flex items-center">
                <button @click="toggleNotifications" class="relative text-text-disabled hover:text-white transition-colors">
                    <PhBell :size="16" weight="bold" />
                    <span v-if="unreadCount > 0" class="absolute -top-1.5 -right-1.5 min-w-[14px] h-[14px] px-1 bg-state-error text-white text-[9px] font-bold rounded-full border border-shell-window flex items-center justify-center">
                        {{ unreadCount > 99 ? '99+' : unreadCount }}
                    </span>
                </button>

                <div v-if="showNotificationsMenu" class="absolute left-1/2 -translate-x-1/2 top-10 mt-1 w-80 bg-white rounded-modal shadow-modal border border-shell-border overflow-hidden origin-top">
                    <div class="px-4 py-3 border-b border-shell-border flex items-center justify-between bg-slate-50">
                        <p class="text-[14px] font-bold text-text-primary">Notifications</p>
                        <button v-if="unreadCount > 0" @click="markAllAsRead" class="text-[12px] text-blue-600 hover:text-blue-700 font-medium transition-colors">
                            Mark all as read
                        </button>
                    </div>
                    
                    <div class="max-h-[350px] overflow-y-auto">
                        <div v-if="notifications.length === 0" class="p-6 text-center text-text-secondary text-[13px]">
                            <PhCheckCircle :size="32" class="mx-auto mb-2 text-slate-300" />
                            You're all caught up!
                        </div>
                        
                        <div v-for="notification in notifications" :key="notification.id" 
                            class="p-3 border-b border-shell-border last:border-0 hover:bg-slate-50 transition-colors relative group cursor-pointer"
                            :class="{'bg-blue-50/30': !notification.read_at}"
                            @click="markAsRead(notification.id)">
                            
                            <!-- Unread Indicator -->
                            <div v-if="!notification.read_at" class="absolute left-2 top-1/2 -translate-y-1/2 w-1.5 h-1.5 rounded-full bg-blue-500"></div>
                            
                            <div class="pl-3">
                                <p class="text-[13px] font-semibold text-text-primary" :class="{'text-blue-900': !notification.read_at}">
                                    {{ notification.data.title || 'System Alert' }}
                                </p>
                                <p class="text-[12px] text-text-secondary mt-0.5 line-clamp-2">
                                    {{ notification.data.message || 'You have a new notification.' }}
                                </p>
                                <p class="text-[10px] text-slate-400 mt-1">
                                    {{ dayjs(notification.created_at).fromNow() }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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
