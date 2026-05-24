<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { PhX, PhUserPlus, PhIdentificationBadge, PhBank, PhFirstAid, PhGear, PhCheckCircle, PhCopy } from '@phosphor-icons/vue';
import { useToastStore } from '@/Stores/useToastStore';

const emit = defineEmits(['close']);
const toastStore = useToastStore();

const step = ref(1); // 1=Personal, 2=Job, 3=Financial, 4=Emergency & Synergy, 5=Success
const isSubmitting = ref(false);
const createdCredentials = ref<{ email: string; default_password: string } | null>(null);

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
    // Synergy Checklist
    draft_contract: false,
    hardware_bundle: false,
});

onMounted(async () => {
    try {
        const resp = await axios.get('/api/hr/metadata');
        departments.value = resp.data.departments || [];
        roles.value = resp.data.roles || [];
        managers.value = resp.data.managers || [];
    } catch (e) {
        console.error('Failed to fetch HR metadata', e);
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
        const response = await axios.post('/api/hr/employees', form.value);
        createdCredentials.value = response.data.credentials;
        step.value = 5;
        window.dispatchEvent(new CustomEvent('refresh-hr-dashboard'));
        window.dispatchEvent(new CustomEvent('refresh-finance-dashboard'));
        toastStore.showToast(`${form.value.name} has been onboarded successfully!`, 'success');
    } catch (e: any) {
        const msg = e.response?.data?.message || 'Failed to onboard employee.';
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

const stepLabels = ['Personal Info', 'Job Details', 'Financial & IDs', 'Emergency & Synergy'];
</script>

<template>
    <div class="bg-white rounded-xl w-[620px] max-w-[95vw] shadow-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200 max-h-[90vh]">
        <!-- Header -->
        <div class="h-[56px] px-6 border-b border-shell-border flex items-center justify-between shrink-0 bg-gradient-to-r from-dept-hr-main to-cyan-500">
            <div class="flex items-center gap-2 text-white">
                <PhUserPlus :size="20" weight="fill" />
                <h3 class="text-[15px] font-bold">Employee Onboarding</h3>
            </div>
            <button @click="$emit('close')" class="w-8 h-8 flex items-center justify-center rounded-btn hover:bg-white/20 text-white transition-colors border-0 bg-transparent cursor-pointer">
                <PhX :size="16" />
            </button>
        </div>

        <!-- Step Indicator (steps 1-4) -->
        <div v-if="step <= 4" class="px-6 pt-4 pb-2 flex items-center gap-2">
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
                <div>
                    <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Full Name *</label>
                    <input v-model="form.name" type="text" required class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main" placeholder="e.g. Mariam Warsame" />
                </div>
                <div>
                    <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Work Email *</label>
                    <input v-model="form.email" type="email" required class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main" placeholder="mariam@sehtech.com" />
                </div>
            </div>

            <!-- Step 2: Job Details -->
            <div v-if="step === 2" class="space-y-4 animate-in fade-in duration-200">
                <div>
                    <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Job Title *</label>
                    <input v-model="form.job_title" type="text" required class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main" placeholder="e.g. Senior Software Engineer" />
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
                        <input v-model="form.salary" type="number" step="0.01" required class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main" placeholder="48000.00" />
                    </div>
                </div>
            </div>

            <!-- Step 3: Financial & IDs -->
            <div v-if="step === 3" class="space-y-4 animate-in fade-in duration-200">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">National ID</label>
                        <input v-model="form.national_id" type="text" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main" placeholder="ID Number" />
                    </div>
                    <div>
                        <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Tax ID / TIN</label>
                        <input v-model="form.tax_id" type="text" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main" placeholder="Tax Identification Number" />
                    </div>
                </div>

                <div class="p-4 bg-slate-50 rounded-xl border border-shell-border space-y-3">
                    <div class="flex items-center gap-2 text-[13px] font-bold text-text-primary">
                        <PhBank :size="16" class="text-dept-hr-main" />
                        Bank / Payment Details
                    </div>
                    <div>
                        <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Bank Name</label>
                        <input v-model="form.bank_name" type="text" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main" placeholder="e.g. Dahabshiil Bank" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Account Number</label>
                            <input v-model="form.bank_account" type="text" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main" placeholder="Account #" />
                        </div>
                        <div>
                            <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Routing / SWIFT</label>
                            <input v-model="form.bank_routing" type="text" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main" placeholder="Routing / SWIFT Code" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 4: Emergency Contact & Synergy -->
            <div v-if="step === 4" class="space-y-4 animate-in fade-in duration-200">
                <div class="p-4 bg-red-50/50 rounded-xl border border-red-100 space-y-3">
                    <div class="flex items-center gap-2 text-[13px] font-bold text-red-700">
                        <PhFirstAid :size="16" />
                        Emergency Contact
                    </div>
                    <div>
                        <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Contact Name</label>
                        <input v-model="form.emergency_name" type="text" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main" placeholder="Full Name" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Phone Number</label>
                            <input v-model="form.emergency_phone" type="text" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main" placeholder="+252 XX XXXXXXX" />
                        </div>
                        <div>
                            <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Relationship</label>
                            <input v-model="form.emergency_relationship" type="text" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-dept-hr-main focus:ring-1 focus:ring-dept-hr-main" placeholder="e.g. Spouse, Parent" />
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-blue-50/50 rounded-xl border border-blue-100 space-y-3">
                    <div class="flex items-center gap-2 text-[13px] font-bold text-blue-700">
                        <PhGear :size="16" />
                        Automated Synergy Actions
                    </div>
                    <p class="text-[12px] text-blue-600">These optional actions automatically trigger workflows in other departments when the employee is onboarded.</p>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="form.draft_contract" class="rounded text-dept-hr-main focus:ring-dept-hr-main border-shell-border w-4 h-4" />
                        <span class="text-[13px] text-text-primary font-medium">Auto-draft Employment Contract (Legal)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="form.hardware_bundle" class="rounded text-dept-hr-main focus:ring-dept-hr-main border-shell-border w-4 h-4" />
                        <span class="text-[13px] text-text-primary font-medium">Request Standard Hardware Bundle (Operations — $1,200)</span>
                    </label>
                </div>
            </div>

            <!-- Step 5: Success & Credentials -->
            <div v-if="step === 5" class="flex flex-col items-center justify-center py-8 text-center animate-in fade-in duration-300">
                <div class="w-16 h-16 rounded-full bg-green-100 text-green-600 flex items-center justify-center mb-4">
                    <PhCheckCircle :size="32" weight="fill" />
                </div>
                <h3 class="text-[18px] font-black text-text-primary mb-1">Employee Onboarded!</h3>
                <p class="text-[13px] text-text-secondary mb-6">{{ form.name }} has been registered in the system. Share these credentials securely.</p>

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
            <button v-if="step > 1 && step <= 4" @click="prevStep" class="px-4 py-2 text-[13px] font-medium text-text-secondary hover:bg-slate-200 rounded-btn border-0 bg-transparent cursor-pointer transition-colors">
                ← Back
            </button>
            <div v-else></div>

            <div class="flex gap-2">
                <button v-if="step === 5" @click="$emit('close')" class="px-5 py-2 rounded-btn bg-dept-hr-main text-white text-[13px] font-bold hover:bg-cyan-600 transition-colors border-0 cursor-pointer">
                    Done
                </button>
                <button v-else-if="step < 4" @click="nextStep" class="px-5 py-2 rounded-btn bg-dept-hr-main text-white text-[13px] font-bold hover:bg-cyan-600 transition-colors border-0 cursor-pointer">
                    Next →
                </button>
                <button v-else @click="submit" :disabled="isSubmitting" class="px-5 py-2 rounded-btn bg-green-600 text-white text-[13px] font-bold hover:bg-green-700 transition-colors border-0 cursor-pointer disabled:opacity-50">
                    {{ isSubmitting ? 'Onboarding...' : 'Complete Onboarding' }}
                </button>
            </div>
        </div>
    </div>
</template>
