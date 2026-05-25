<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { PhX } from '@phosphor-icons/vue';

const props = defineProps<{
    user: any;
    roles: any[];
    departments: any[];
}>();

const emit = defineEmits(['close']);

const form = ref({
    name: props.user.name,
    email: props.user.email,
    password: '', // Optional - only changed if provided
    role_id: props.user.role_id || '',
    department_id: props.user.department_id || '',
    status: props.user.status || 'active',
    job_title: props.user.employee_profile?.job_title || '',
    employment_type: props.user.employee_profile?.employment_type || 'full_time',
    hire_date: props.user.employee_profile?.hire_date ? new Date(props.user.employee_profile.hire_date).toISOString().split('T')[0] : new Date().toISOString().split('T')[0],
    salary: props.user.employee_profile?.salary || ''
});

const isSubmitting = ref(false);
const error = ref('');

const submitUser = async () => {
    isSubmitting.value = true;
    error.value = '';
    
    try {
        await axios.put(`/api/admin/users/${props.user.id}`, form.value);
        window.dispatchEvent(new CustomEvent('refresh-admin-dashboard'));
        window.dispatchEvent(new CustomEvent('refresh-hr-dashboard'));
        window.dispatchEvent(new CustomEvent('refresh-finance-dashboard'));
        emit('close');
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Failed to update user';
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div class="w-[500px] bg-white rounded-modal shadow-modal border border-shell-border flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        
        <div class="h-[56px] flex items-center justify-between px-6 border-b border-shell-border bg-shell-panel shrink-0">
            <h2 class="text-[15px] font-bold text-text-primary">Edit User: {{ user.name }}</h2>
            <button @click="$emit('close')" class="p-1.5 text-text-disabled hover:text-state-error rounded transition-colors bg-transparent border-0 cursor-pointer">
                <PhX :size="18" weight="bold" />
            </button>
        </div>

        <form @submit.prevent="submitUser" class="p-6 flex flex-col gap-4">
            <div v-if="error" class="p-3 bg-state-error/10 border border-state-error/20 text-state-error rounded-input text-[13px] font-medium">
                {{ error }}
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[13px] font-semibold text-text-primary">Full Name</label>
                <input v-model="form.name" type="text" required class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-admin-main outline-none" />
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[13px] font-semibold text-text-primary">Email Address</label>
                <input v-model="form.email" type="email" required class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-admin-main outline-none" />
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[13px] font-semibold text-text-primary">New Password <span class="text-text-disabled font-normal">(Leave blank to keep current)</span></label>
                <input v-model="form.password" type="password" minlength="8" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-admin-main outline-none" placeholder="••••••••" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1">
                    <label class="text-[13px] font-semibold text-text-primary">System Role</label>
                    <select v-model="form.role_id" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-admin-main outline-none bg-white">
                        <option value="">— Select Role —</option>
                        <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-[13px] font-semibold text-text-primary">Department</label>
                    <select v-model="form.department_id" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-admin-main outline-none bg-white">
                        <option value="">— Select Dept —</option>
                        <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                </div>
            </div>

            <!-- HR Fields (Shown if Department is selected) -->
            <div v-if="form.department_id" class="grid grid-cols-2 gap-4 animate-in fade-in zoom-in-95 duration-200">
                <div class="flex flex-col gap-1">
                    <label class="text-[13px] font-semibold text-text-primary">Job Title *</label>
                    <input v-model="form.job_title" type="text" required class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-admin-main outline-none" placeholder="e.g. Software Engineer" />
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-[13px] font-semibold text-text-primary">Employment Type *</label>
                    <select v-model="form.employment_type" required class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-admin-main outline-none">
                        <option value="full_time">Full-Time</option>
                        <option value="part_time">Part-Time</option>
                        <option value="contract">Contract</option>
                        <option value="intern">Intern</option>
                    </select>
                </div>
            </div>
            
            <div v-if="form.department_id" class="grid grid-cols-2 gap-4 animate-in fade-in zoom-in-95 duration-200">
                <div class="flex flex-col gap-1">
                    <label class="text-[13px] font-semibold text-text-primary">Hire Date *</label>
                    <input v-model="form.hire_date" type="date" required class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-admin-main outline-none" />
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-[13px] font-semibold text-text-primary">Annual Salary (USD) *</label>
                    <input v-model="form.salary" type="number" step="0.01" required class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-admin-main outline-none" placeholder="e.g. 50000" />
                </div>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[13px] font-semibold text-text-primary">Account Status</label>
                <select v-model="form.status" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-admin-main outline-none bg-white">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>

            <div class="mt-4 flex items-center justify-end gap-3 pt-4 border-t border-shell-border">
                <button type="button" @click="$emit('close')" class="px-4 py-2 text-[13px] font-medium text-text-secondary hover:text-text-primary transition-colors bg-transparent border-0 cursor-pointer">Cancel</button>
                <button type="submit" :disabled="isSubmitting" class="px-5 py-2 bg-dept-admin-main text-white text-[13px] font-medium rounded-btn hover:bg-[#0F172A] transition-colors disabled:opacity-50 border-0 cursor-pointer">
                    {{ isSubmitting ? 'Saving...' : 'Save Changes' }}
                </button>
            </div>
        </form>
    </div>
</template>
