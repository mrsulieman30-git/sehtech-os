<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { PhX, PhPencilSimple, PhIdentificationBadge, PhBank, PhFirstAid, PhCheckCircle } from '@phosphor-icons/vue';
import { useToastStore } from '@/Stores/useToastStore';

const props = defineProps<{ userId: string }>();
const emit = defineEmits(['close']);
const toastStore = useToastStore();

const step = ref(1); // 1=Personal, 2=Job, 3=Financial, 4=Emergency
const isSubmitting = ref(false);
const loading = ref(true);

const avatarFile = ref<File | null>(null);
const avatarPreview = ref<string | null>(null);

const handleAvatarChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        avatarFile.value = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            avatarPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const departments = ref<any[]>([]);
const roles = ref<any[]>([]);
const managers = ref<any[]>([]);

const form = ref({
    // Personal
    name: '',
    email: '',
    // Job
    job_title: '',
    department_id: '',
    role_id: '',
    manager_id: '',
    employment_type: 'full_time',
    hire_date: '',
    salary: '',
    // Financial
    national_id: '',
    tax_id: '',
    bank_name: '',
    bank_account: '',
    bank_routing: '',
    // Emergency
    emergency_name: '',
    emergency_phone: '',
    emergency_relationship: '',
});

onMounted(async () => {
    try {
        // Fetch metadata first
        const metaResp = await axios.get('/api/hr/metadata');
        departments.value = metaResp.data.departments || [];
        roles.value = metaResp.data.roles || [];
        managers.value = metaResp.data.managers || [];

        // Fetch employee data
        const resp = await axios.get(`/api/hr/employees/${props.userId}`);
        const data = resp.data;
        const user = data.user;
        const profile = user.employee_profile || {};
        const bank = profile.bank_details || {};
        const emergency = profile.emergency_contact || {};

        if (user.avatar) {
            avatarPreview.value = user.avatar;
        }

        form.value = {
            name: user.name,
            email: user.email,
            job_title: profile.job_title || '',
            department_id: user.department_id || '',
            role_id: user.role_id || '',
            manager_id: profile.manager_id || '',
            employment_type: profile.employment_type || 'full_time',
            hire_date: profile.hire_date ? profile.hire_date.substring(0, 10) : '',
            salary: profile.salary || '',
            national_id: profile.national_id || '',
            tax_id: profile.tax_id || '',
            bank_name: bank.bank_name || '',
            bank_account: bank.account_number || '',
            bank_routing: bank.routing_number || '',
            emergency_name: emergency.name || '',
            emergency_phone: emergency.phone || '',
            emergency_relationship: emergency.relationship || '',
        };

    } catch (e) {
        console.error('Failed to load employee data', e);
        toastStore.showToast('Failed to load employee data.', 'error');
    } finally {
        loading.value = false;
    }
});

const nextStep = () => {
    if (step.value < 4) step.value++;
};
const prevStep = () => {
    if (step.value > 1) step.value--;
};

const submit = async () => {
    if (isSubmitting.value) return;
    isSubmitting.value = true;
    try {
        const formData = new FormData();
        formData.append('_method', 'PUT'); // Need to use POST with _method=PUT for file uploads in Laravel
        Object.keys(form.value).forEach(key => {
            const val = (form.value as any)[key];
            if (val === true) formData.append(key, '1');
            else if (val === false) formData.append(key, '0');
            else if (val !== null && val !== '') formData.append(key, val);
        });

        if (avatarFile.value) {
            formData.append('avatar', avatarFile.value);
        }

        await axios.post(`/api/hr/employees/${props.userId}`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        window.dispatchEvent(new CustomEvent('refresh-hr-dashboard'));
        window.dispatchEvent(new CustomEvent('refresh-finance-dashboard'));
        toastStore.showToast(`${form.value.name} has been updated successfully!`, 'success');
        emit('close');
    } catch (e: any) {
        const msg = e.response?.data?.message || 'Failed to update employee.';
        toastStore.showToast(msg, 'error');
    } finally {
        isSubmitting.value = false;
    }
};

const stepLabels = ['Personal Info', 'Job Details', 'Financial & IDs', 'Emergency Contact'];
</script>

<template>
    <div class="bg-white rounded-xl w-[620px] max-w-[95vw] shadow-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200 max-h-[90vh]">
        <!-- Header -->
        <div class="h-[56px] px-6 border-b border-shell-border flex items-center justify-between shrink-0 bg-gradient-to-r from-dept-hr-main to-cyan-500">
            <div class="flex items-center gap-2 text-white">
                <PhPencilSimple :size="20" weight="fill" />
                <h3 class="text-[15px] font-bold">Edit Employee</h3>
            </div>
            <button @click="$emit('close')" class="w-8 h-8 flex items-center justify-center rounded-btn hover:bg-white/20 text-white transition-colors border-0 bg-transparent cursor-pointer">
                <PhX :size="16" />
            </button>
        </div>

        <div v-if="loading" class="flex items-center justify-center py-16 text-[13px] text-text-secondary">Loading profile...</div>

        <template v-else>
            <!-- Step Indicator -->
            <div class="px-6 pt-4 pb-2 flex items-center gap-2">
                <template v-for="(label, i) in stepLabels" :key="i">
                    <div class="flex items-center gap-1.5">
                        <div class="w-6 h-6 rounded-full text-[11px] font-bold flex items-center justify-center transition-colors"
                             :class="step > i + 1 ? 'bg-green-500 text-white' : (step === i + 1 ? 'bg-dept-hr-main text-white' : 'bg-slate-100 text-slate-400')">
                            <PhCheckCircle v-if="step > i + 1" :size="14" />
                            <span v-else>{{ i + 1 }}</span>
                        </div>
                        <span class="text-[11px] font-medium" :class="step === i + 1 ? 'text-dept-hr-main' : 'text-text-disabled'">{{ label }}</span>
                    </div>
                    <div v-if="i < 3" class="flex-1 h-px bg-shell-border"></div>
                </template>
            </div>

            <!-- Form Content -->
            <div class="flex-1 overflow-y-auto px-6 py-4">

                <!-- Step 1: Personal Info -->
                <div v-if="step === 1" class="space-y-4 animate-in fade-in duration-200">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 rounded-full bg-shell-panel border border-shell-border overflow-hidden flex items-center justify-center shrink-0">
                            <img v-if="avatarPreview" :src="avatarPreview" class="w-full h-full object-cover" />
                            <PhIdentificationBadge v-else :size="24" class="text-text-disabled" />
                        </div>
                        <div>
                            <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Profile Picture</label>
                            <input type="file" accept="image/*" @change="handleAvatarChange" class="block w-full text-[12px] text-text-secondary file:mr-3 file:py-1.5 file:px-3 file:rounded-btn file:border-0 file:text-[11px] file:font-semibold file:bg-dept-hr-main/10 file:text-dept-hr-main hover:file:bg-dept-hr-main/20 transition-colors cursor-pointer" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Full Name *</label>
                        <input v-model="form.name" type="text" required class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main" />
                    </div>
                    <div>
                        <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Work Email *</label>
                        <input v-model="form.email" type="email" required class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main" />
                    </div>
                </div>

                <!-- Step 2: Job Details -->
                <div v-if="step === 2" class="space-y-4 animate-in fade-in duration-200">
                    <div>
                        <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Job Title *</label>
                        <input v-model="form.job_title" type="text" required class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Department</label>
                            <select v-model="form.department_id" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] bg-white focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main">
                                <option value="">— Select —</option>
                                <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">System Role</label>
                            <select v-model="form.role_id" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] bg-white focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main">
                                <option value="">— Select —</option>
                                <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Reports To (Manager)</label>
                            <select v-model="form.manager_id" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] bg-white focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main">
                                <option value="">— None —</option>
                                <option v-for="m in managers" :key="m.id" :value="m.id">{{ m.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Employment Type</label>
                            <select v-model="form.employment_type" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] bg-white focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main">
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
                            <input v-model="form.hire_date" type="date" required class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main" />
                        </div>
                        <div>
                            <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Annual Salary (USD) *</label>
                            <input v-model="form.salary" type="number" step="0.01" required class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main" />
                        </div>
                    </div>
                </div>

                <!-- Step 3: Financial & IDs -->
                <div v-if="step === 3" class="space-y-4 animate-in fade-in duration-200">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">National ID</label>
                            <input v-model="form.national_id" type="text" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main" />
                        </div>
                        <div>
                            <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Tax ID / TIN</label>
                            <input v-model="form.tax_id" type="text" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main" />
                        </div>
                    </div>

                    <div class="p-4 bg-slate-50 rounded-xl border border-shell-border space-y-3">
                        <div class="flex items-center gap-2 text-[13px] font-bold text-text-primary">
                            <PhBank :size="16" class="text-dept-hr-main" />
                            Bank / Payment Details
                        </div>
                        <div>
                            <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Bank Name</label>
                            <input v-model="form.bank_name" type="text" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Account Number</label>
                                <input v-model="form.bank_account" type="text" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main" />
                            </div>
                            <div>
                                <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Routing / SWIFT</label>
                                <input v-model="form.bank_routing" type="text" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Emergency Contact -->
                <div v-if="step === 4" class="space-y-4 animate-in fade-in duration-200">
                    <div class="p-4 bg-red-50/50 rounded-xl border border-red-100 space-y-3">
                        <div class="flex items-center gap-2 text-[13px] font-bold text-red-700">
                            <PhFirstAid :size="16" />
                            Emergency Contact
                        </div>
                        <div>
                            <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Contact Name</label>
                            <input v-model="form.emergency_name" type="text" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Phone Number</label>
                                <input v-model="form.emergency_phone" type="text" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main" />
                            </div>
                            <div>
                                <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Relationship</label>
                                <input v-model="form.emergency_relationship" type="text" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main" />
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Footer Navigation -->
            <div class="px-6 py-4 border-t border-shell-border flex justify-between items-center shrink-0 bg-slate-50">
                <button v-if="step > 1" @click="prevStep" class="px-4 py-2 text-[13px] font-medium text-text-secondary hover:bg-slate-200 rounded-btn border-0 bg-transparent cursor-pointer transition-colors">
                    ← Back
                </button>
                <div v-else></div>

                <div class="flex gap-2">
                    <button v-if="step < 4" @click="nextStep" class="px-5 py-2 rounded-btn bg-dept-hr-main text-white text-[13px] font-bold hover:bg-cyan-600 transition-colors border-0 cursor-pointer">
                        Next →
                    </button>
                    <button v-else @click="submit" :disabled="isSubmitting" class="px-5 py-2 rounded-btn bg-green-600 text-white text-[13px] font-bold hover:bg-green-700 transition-colors border-0 cursor-pointer disabled:opacity-50">
                        {{ isSubmitting ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </div>
        </template>
    </div>
</template>
