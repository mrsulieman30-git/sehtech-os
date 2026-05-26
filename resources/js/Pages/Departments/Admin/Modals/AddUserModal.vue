<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { PhX, PhUserPlus, PhIdentificationBadge, PhCheckCircle, PhCopy } from '@phosphor-icons/vue';
import { useToastStore } from '@/Stores/useToastStore';

const props = defineProps<{
    roles: any[];
    departments: any[];
}>();

const emit = defineEmits(['close']);
const toastStore = useToastStore();

const step = ref(1); // 1=Personal, 2=Job, 3=Success
const isSubmitting = ref(false);
const createdCredentials = ref<{ email: string; default_password: string } | null>(null);

const form = ref({
    name: '',
    email: '',
    role_id: '',
    department_id: '',
    status: 'active',
    job_title: '',
    employment_type: 'full_time',
    hire_date: new Date().toISOString().split('T')[0],
    salary: ''
});

const nextStep = () => {
    if (step.value < 2) step.value++;
};
const prevStep = () => {
    if (step.value > 1) step.value--;
};

const submitUser = async () => {
    if (isSubmitting.value) return;
    isSubmitting.value = true;
    
    try {
        const response = await axios.post('/api/admin/users', form.value);
        createdCredentials.value = response.data.credentials;
        step.value = 3; // Success step
        window.dispatchEvent(new CustomEvent('refresh-admin-dashboard'));
        window.dispatchEvent(new CustomEvent('refresh-hr-dashboard'));
        window.dispatchEvent(new CustomEvent('refresh-finance-dashboard'));
        toastStore.showToast(`${form.value.name} has been added successfully!`, 'success');
    } catch (err: any) {
        const msg = err.response?.data?.message || 'Failed to add user';
        toastStore.showToast(msg, 'error');
    } finally {
        isSubmitting.value = false;
    }
};

const copyToClipboard = (text: string) => {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text);
    } else {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
    }
    toastStore.showToast('Copied to clipboard!', 'success');
};

const stepLabels = ['Personal Info', 'Job Details'];
</script>

<template>
    <div class="bg-white rounded-xl w-[550px] max-w-[95vw] shadow-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200 max-h-[90vh]">
        <!-- Header -->
        <div class="h-[56px] px-6 border-b border-shell-border flex items-center justify-between shrink-0 bg-gradient-to-r from-dept-admin-main to-slate-800">
            <div class="flex items-center gap-2 text-white">
                <PhUserPlus :size="20" weight="fill" />
                <h3 class="text-[15px] font-bold">Add System User</h3>
            </div>
            <button @click="$emit('close')" class="w-8 h-8 flex items-center justify-center rounded-btn hover:bg-white/20 text-white transition-colors border-0 bg-transparent cursor-pointer">
                <PhX :size="16" />
            </button>
        </div>

        <!-- Step Indicator (steps 1-2) -->
        <div v-if="step <= 2" class="px-6 pt-4 pb-2 flex items-center gap-2">
            <template v-for="(label, i) in stepLabels" :key="i">
                <div class="flex items-center gap-1.5">
                    <div class="w-6 h-6 rounded-full text-[11px] font-bold flex items-center justify-center transition-colors"
                         :class="step > i + 1 ? 'bg-green-500 text-white' : (step === i + 1 ? 'bg-dept-admin-main text-white' : 'bg-slate-100 text-slate-400')">
                        <PhCheckCircle v-if="step > i + 1" :size="14" />
                        <span v-else>{{ i + 1 }}</span>
                    </div>
                    <span class="text-[11px] font-medium" :class="step === i + 1 ? 'text-dept-admin-main' : 'text-text-disabled'">{{ label }}</span>
                </div>
                <div v-if="i < 1" class="flex-1 h-px bg-shell-border mx-2"></div>
            </template>
        </div>

        <!-- Form Content -->
        <div class="flex-1 overflow-y-auto px-6 py-4">

            <!-- Step 1: Personal Info -->
            <div v-if="step === 1" class="space-y-4 animate-in fade-in duration-200">
                <div>
                    <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Full Name *</label>
                    <input v-model="form.name" type="text" required autofocus class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-admin-main focus:ring-1 focus:ring-dept-admin-main" placeholder="e.g. John Doe" />
                </div>
                <div>
                    <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Email Address *</label>
                    <input v-model="form.email" type="email" required class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-admin-main focus:ring-1 focus:ring-dept-admin-main" placeholder="john@sehtech.com" />
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">System Role</label>
                        <select v-model="form.role_id" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] bg-white focus:outline-none focus:border-dept-admin-main focus:ring-1 focus:ring-dept-admin-main">
                            <option value="">— Select Role —</option>
                            <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Account Status</label>
                        <select v-model="form.status" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] bg-white focus:outline-none focus:border-dept-admin-main focus:ring-1 focus:ring-dept-admin-main">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Step 2: Job Details (HR Profile) -->
            <div v-if="step === 2" class="space-y-4 animate-in fade-in duration-200">
                <div class="p-3 bg-blue-50/50 border border-blue-100 rounded-lg text-[12px] text-blue-700 font-medium">
                    Assigning a department will automatically create an Employee Profile for this user in the HR module.
                </div>

                <div>
                    <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Department</label>
                    <select v-model="form.department_id" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] bg-white focus:outline-none focus:border-dept-admin-main focus:ring-1 focus:ring-dept-admin-main">
                        <option value="">— No Department —</option>
                        <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                </div>
                
                <div v-if="form.department_id" class="space-y-4 animate-in fade-in zoom-in-95 duration-200">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Job Title *</label>
                            <input v-model="form.job_title" type="text" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-admin-main focus:ring-1 focus:ring-dept-admin-main" placeholder="e.g. Developer" />
                        </div>
                        <div>
                            <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Employment Type *</label>
                            <select v-model="form.employment_type" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] bg-white focus:outline-none focus:border-dept-admin-main focus:ring-1 focus:ring-dept-admin-main">
                                <option value="full_time">Full-Time</option>
                                <option value="part_time">Part-Time</option>
                                <option value="contract">Contract</option>
                                <option value="intern">Intern</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Hire Date *</label>
                            <input v-model="form.hire_date" type="date" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-admin-main focus:ring-1 focus:ring-dept-admin-main" />
                        </div>
                        <div>
                            <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Annual Salary (USD) *</label>
                            <input v-model="form.salary" type="number" step="0.01" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-admin-main focus:ring-1 focus:ring-dept-admin-main" placeholder="e.g. 50000" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Success & Credentials -->
            <div v-if="step === 3" class="flex flex-col items-center justify-center py-8 text-center animate-in fade-in duration-300">
                <div class="w-16 h-16 rounded-full bg-green-100 text-green-600 flex items-center justify-center mb-4">
                    <PhCheckCircle :size="32" weight="fill" />
                </div>
                <h3 class="text-[18px] font-black text-text-primary mb-1">User Created!</h3>
                <p class="text-[13px] text-text-secondary mb-6">{{ form.name }} has been added to the system. Share these credentials securely.</p>

                <div class="w-full max-w-sm bg-slate-50 border border-shell-border rounded-xl p-4 space-y-3 text-left">
                    <div>
                        <div class="text-[10px] font-bold text-text-disabled uppercase">Login Email</div>
                        <div class="flex items-center justify-between gap-2 mt-1">
                            <span class="text-[14px] font-bold text-text-primary">{{ createdCredentials?.email }}</span>
                            <button @click="copyToClipboard(createdCredentials?.email || '')" class="p-1 hover:bg-slate-200 rounded transition-colors bg-transparent border-0 cursor-pointer text-text-secondary">
                                <PhCopy :size="14" />
                            </button>
                        </div>
                    </div>
                    <div class="border-t border-shell-border pt-3">
                        <div class="text-[10px] font-bold text-text-disabled uppercase">Default Password</div>
                        <div class="flex items-center justify-between gap-2 mt-1">
                            <span class="text-[14px] font-bold text-amber-700 font-mono">{{ createdCredentials?.default_password }}</span>
                            <button @click="copyToClipboard(createdCredentials?.default_password || '')" class="p-1 hover:bg-slate-200 rounded transition-colors bg-transparent border-0 cursor-pointer text-text-secondary">
                                <PhCopy :size="14" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Navigation -->
        <div class="px-6 py-4 border-t border-shell-border flex justify-between items-center shrink-0 bg-slate-50">
            <button v-if="step > 1 && step <= 2" @click="prevStep" class="px-4 py-2 text-[13px] font-medium text-text-secondary hover:bg-slate-200 rounded-btn border-0 bg-transparent cursor-pointer transition-colors">
                ← Back
            </button>
            <div v-else></div>

            <div class="flex gap-2">
                <button v-if="step === 3" @click="$emit('close')" class="px-5 py-2 rounded-btn bg-dept-admin-main text-white text-[13px] font-bold hover:bg-slate-800 transition-colors border-0 cursor-pointer">
                    Done
                </button>
                <button v-else-if="step < 2" @click="nextStep" class="px-5 py-2 rounded-btn bg-dept-admin-main text-white text-[13px] font-bold hover:bg-slate-800 transition-colors border-0 cursor-pointer">
                    Next →
                </button>
                <button v-else @click="submitUser" :disabled="isSubmitting" class="px-5 py-2 rounded-btn bg-green-600 text-white text-[13px] font-bold hover:bg-green-700 transition-colors border-0 cursor-pointer disabled:opacity-50">
                    {{ isSubmitting ? 'Saving...' : 'Complete Setup' }}
                </button>
            </div>
        </div>
    </div>
</template>
