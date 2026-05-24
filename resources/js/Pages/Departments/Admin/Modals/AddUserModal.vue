<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { PhX } from '@phosphor-icons/vue';

const emit = defineEmits(['close']);

const form = ref({
    name: '',
    email: '',
    password: '',
    status: 'active'
});

const isSubmitting = ref(false);
const error = ref('');

const submitUser = async () => {
    isSubmitting.value = true;
    error.value = '';
    
    try {
        await axios.post('/api/admin/users', form.value);
        window.dispatchEvent(new CustomEvent('refresh-admin-dashboard'));
        emit('close');
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Failed to add user';
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div class="w-[500px] bg-white rounded-modal shadow-modal border border-shell-border flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        
        <div class="h-[56px] flex items-center justify-between px-6 border-b border-shell-border bg-shell-panel shrink-0">
            <h2 class="text-[15px] font-bold text-text-primary">Add New User</h2>
            <button @click="$emit('close')" class="p-1.5 text-text-disabled hover:text-state-error rounded transition-colors">
                <PhX :size="18" weight="bold" />
            </button>
        </div>

        <form @submit.prevent="submitUser" class="p-6 flex flex-col gap-4">
            <div v-if="error" class="p-3 bg-state-error/10 border border-state-error/20 text-state-error rounded-input text-[13px] font-medium">
                {{ error }}
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[13px] font-semibold text-text-primary">Full Name</label>
                <input v-model="form.name" type="text" required autofocus class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-admin-main outline-none" />
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[13px] font-semibold text-text-primary">Email Address</label>
                <input v-model="form.email" type="email" required class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-admin-main outline-none" />
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[13px] font-semibold text-text-primary">Temporary Password</label>
                <input v-model="form.password" type="password" required minlength="8" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-admin-main outline-none" />
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[13px] font-semibold text-text-primary">Account Status</label>
                <select v-model="form.status" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-admin-main outline-none">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>

            <div class="mt-4 flex items-center justify-end gap-3 pt-4 border-t border-shell-border">
                <button type="button" @click="$emit('close')" class="px-4 py-2 text-[13px] font-medium text-text-secondary hover:text-text-primary transition-colors">Cancel</button>
                <button type="submit" :disabled="isSubmitting" class="px-5 py-2 bg-dept-admin-main text-white text-[13px] font-medium rounded-btn hover:bg-[#0F172A] transition-colors disabled:opacity-50">
                    {{ isSubmitting ? 'Saving...' : 'Add User' }}
                </button>
            </div>
        </form>
    </div>
</template>
