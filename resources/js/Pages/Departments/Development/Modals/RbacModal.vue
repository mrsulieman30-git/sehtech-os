<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { PhX, PhShieldCheck, PhUser, PhMagnifyingGlass, PhCheck } from '@phosphor-icons/vue';
import { useToastStore } from '@/Stores/useToastStore';

const props = defineProps<{
    entityId: string;
    entityType: 'task' | 'node';
    entityName: string;
}>();

const emit = defineEmits(['close']);
const toastStore = useToastStore();

const allUsers = ref<any[]>([]);
const grantedUserIds = ref<string[]>([]);
const isLoading = ref(true);
const isSaving = ref(false);
const searchQuery = ref('');

const filteredUsers = computed(() => {
    if (!searchQuery.value.trim()) return allUsers.value;
    const q = searchQuery.value.toLowerCase();
    return allUsers.value.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q));
});

const isUserGranted = (userId: string) => grantedUserIds.value.includes(userId);

const toggleUser = (userId: string) => {
    if (isUserGranted(userId)) {
        grantedUserIds.value = grantedUserIds.value.filter(id => id !== userId);
    } else {
        grantedUserIds.value.push(userId);
    }
};

const loadData = async () => {
    isLoading.value = true;
    try {
        const [usersRes, grantsRes] = await Promise.all([
            axios.get('/api/admin/users'),
            axios.get('/api/development/grants', { params: { entity_id: props.entityId, entity_type: props.entityType } })
        ]);
        allUsers.value = usersRes.data.users || usersRes.data || [];
        grantedUserIds.value = (grantsRes.data.grants || []).map((g: any) => g.user_id);
    } catch (e) {
        toastStore.addToast('error', 'Failed to load access data');
    } finally {
        isLoading.value = false;
    }
};

const saveGrants = async () => {
    isSaving.value = true;
    try {
        await axios.post('/api/development/grants/sync', {
            entity_id: props.entityId,
            entity_type: props.entityType,
            user_ids: grantedUserIds.value
        });
        toastStore.addToast('success', 'Access permissions updated');
        window.dispatchEvent(new CustomEvent('refresh-dev-board'));
        emit('close');
    } catch (e) {
        toastStore.addToast('error', 'Failed to save permissions');
    } finally {
        isSaving.value = false;
    }
};

const removeAll = () => {
    grantedUserIds.value = [];
};

onMounted(loadData);
</script>

<template>
    <div class="w-[480px] bg-white rounded-modal shadow-modal border border-shell-border flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200 max-h-[80vh]">
        
        <!-- Header -->
        <div class="h-[56px] flex items-center justify-between px-6 border-b border-shell-border bg-gradient-to-r from-violet-50 to-indigo-50 shrink-0">
            <h2 class="text-[15px] font-bold text-text-primary flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center shadow-sm">
                    <PhShieldCheck :size="18" weight="fill" class="text-white" />
                </div>
                Manage Access
            </h2>
            <button @click="$emit('close')" class="p-1.5 text-text-disabled hover:text-state-error rounded-lg hover:bg-red-50 transition-all">
                <PhX :size="16" weight="bold" />
            </button>
        </div>

        <!-- Info Banner -->
        <div class="px-6 py-3 bg-amber-50 border-b border-amber-100 text-[12px] text-amber-700 font-medium">
            <strong class="text-amber-800">{{ entityName }}</strong> — Select which users can view this {{ entityType === 'task' ? 'task' : 'folder/board' }}. 
            Leave empty to make it visible to everyone.
        </div>

        <!-- Search -->
        <div class="px-6 py-3 border-b border-shell-border">
            <div class="relative">
                <PhMagnifyingGlass :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-text-disabled" />
                <input 
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search users by name or email..."
                    class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-shell-border rounded-lg text-[13px] focus:ring-2 focus:ring-violet-300 focus:bg-white outline-none transition-all"
                />
            </div>
            <div class="flex items-center justify-between mt-2 text-[11px] text-text-disabled">
                <span>{{ grantedUserIds.length }} user{{ grantedUserIds.length !== 1 ? 's' : '' }} selected</span>
                <button v-if="grantedUserIds.length > 0" @click="removeAll" class="text-state-error hover:underline font-medium">
                    Clear all restrictions
                </button>
            </div>
        </div>

        <!-- User List -->
        <div class="flex-1 overflow-y-auto max-h-[350px] custom-scrollbar">
            <div v-if="isLoading" class="flex items-center justify-center py-12">
                <div class="w-6 h-6 border-2 border-violet-300 border-t-violet-600 rounded-full animate-spin"></div>
            </div>
            <div v-else class="p-2 flex flex-col gap-0.5">
                <div 
                    v-for="user in filteredUsers" 
                    :key="user.id"
                    @click="toggleUser(user.id)"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg cursor-pointer transition-all border border-transparent"
                    :class="isUserGranted(user.id) 
                        ? 'bg-violet-50 border-violet-200 shadow-sm' 
                        : 'hover:bg-gray-50'"
                >
                    <!-- Checkbox -->
                    <div 
                        class="w-5 h-5 rounded-md flex items-center justify-center shrink-0 transition-all border-2"
                        :class="isUserGranted(user.id) 
                            ? 'bg-gradient-to-br from-violet-500 to-indigo-600 border-transparent' 
                            : 'border-gray-300 bg-white'"
                    >
                        <PhCheck v-if="isUserGranted(user.id)" :size="12" weight="bold" class="text-white" />
                    </div>

                    <!-- Avatar -->
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-slate-200 to-slate-300 flex items-center justify-center shrink-0 text-[12px] font-bold text-slate-600 overflow-hidden">
                        <img v-if="user.avatar" :src="`/storage/${user.avatar}`" class="w-full h-full object-cover" />
                        <span v-else>{{ user.name?.charAt(0)?.toUpperCase() }}</span>
                    </div>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <div class="text-[13px] font-semibold text-text-primary truncate">{{ user.name }}</div>
                        <div class="text-[11px] text-text-disabled truncate">{{ user.email }}</div>
                    </div>

                    <!-- Role badge -->
                    <span v-if="user.role?.name" class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 shrink-0">
                        {{ user.role.name }}
                    </span>
                </div>

                <div v-if="filteredUsers.length === 0" class="text-center py-8 text-[13px] text-text-disabled">
                    No users found
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-3 flex items-center justify-end gap-3 border-t border-shell-border bg-gray-50/50 shrink-0">
            <button 
                type="button" 
                @click="$emit('close')"
                class="px-4 py-2 text-[13px] font-medium text-text-secondary hover:text-text-primary transition-colors"
            >
                Cancel
            </button>
            <button 
                @click="saveGrants"
                :disabled="isSaving"
                class="px-5 py-2 bg-gradient-to-r from-violet-500 to-indigo-600 text-white text-[13px] font-semibold rounded-lg hover:from-violet-600 hover:to-indigo-700 transition-all shadow-sm disabled:opacity-50"
            >
                {{ isSaving ? 'Saving...' : 'Save Permissions' }}
            </button>
        </div>
    </div>
</template>
