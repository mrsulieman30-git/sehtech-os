<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { PhX, PhFileDoc, PhCurrencyDollar } from '@phosphor-icons/vue';
import { useToastStore } from '@/Stores/useToastStore';

const emit = defineEmits(['close']);
const toastStore = useToastStore();

const accounts = ref<any[]>([]);

onMounted(async () => {
    try {
        const res = await axios.get('/api/sales/clients/list');
        accounts.value = res.data;
    } catch (e) {
        console.error(e);
    }
});

const form = ref({
    crm_account_id: '',
    title: '',
    value: '',
    stage: 'lead',
    expected_close_date: '',
    requirements: '',
    payment_type: 'one_time',
    recurring_frequency: '',
    recurring_amount: '',
    collection_date: ''
});

const fileInput = ref<HTMLInputElement | null>(null);
const selectedFileName = ref('');

const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        selectedFileName.value = target.files[0].name;
    } else {
        selectedFileName.value = '';
    }
};

const isSubmitting = ref(false);

const submit = async () => {
    if (isSubmitting.value) return;
    isSubmitting.value = true;
    
    const formData = new FormData();
    for (const [key, val] of Object.entries(form.value)) {
        if (val) formData.append(key, val);
    }
    
    if (fileInput.value?.files?.length) {
        formData.append('contract_file', fileInput.value.files[0]);
    }
    
    try {
        await axios.post('/api/sales/deals', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        window.dispatchEvent(new CustomEvent('refresh-sales-crm'));
        window.dispatchEvent(new CustomEvent('refresh-finance-dashboard'));
        toastStore.addToast('success', 'Deal created successfully!');
        emit('close');
    } catch (error) {
        console.error(error);
        toastStore.addToast('error', 'Failed to create deal.');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div class="bg-white rounded-xl w-[700px] max-w-[95vw] shadow-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200" style="max-height: 90vh;">
        <div class="h-[56px] px-6 border-b border-shell-border flex items-center justify-between shrink-0 bg-slate-50">
            <h3 class="text-[15px] font-bold text-text-primary">Create New Deal</h3>
            <button @click="$emit('close')" class="w-8 h-8 flex items-center justify-center rounded-btn hover:bg-slate-200 text-text-secondary transition-colors">
                <PhX :size="16" />
            </button>
        </div>
        
        <div class="p-6 flex-1 overflow-y-auto">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Info -->
                <div class="space-y-4">
                    <h4 class="text-[11px] font-bold text-text-disabled uppercase flex items-center gap-1 border-b border-shell-border pb-1">Basic Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[12px] font-bold text-text-secondary mb-1">Select Account</label>
                            <select v-model="form.crm_account_id" required class="w-full h-10 px-3 rounded-btn border border-shell-border text-[14px] focus:outline-none focus:border-dept-sales-main focus:ring-1 focus:ring-dept-sales-main">
                                <option value="" disabled>Select an account...</option>
                                <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.organization || acc.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[12px] font-bold text-text-secondary mb-1">Deal Title</label>
                            <input v-model="form.title" type="text" required class="w-full h-10 px-3 rounded-btn border border-shell-border text-[14px] focus:outline-none focus:border-dept-sales-main focus:ring-1 focus:ring-dept-sales-main" placeholder="e.g. Acme Q3 License" />
                        </div>
                    </div>
                </div>

                <!-- Customer Requirements -->
                <div class="space-y-4">
                    <h4 class="text-[11px] font-bold text-text-disabled uppercase flex items-center gap-1 border-b border-shell-border pb-1">Customer Requirements</h4>
                    <div>
                        <textarea v-model="form.requirements" rows="3" class="w-full p-3 rounded-xl border border-shell-border text-[13px] focus:outline-none focus:border-dept-sales-main focus:ring-1 focus:ring-dept-sales-main" placeholder="Outline the requirements as mentioned by the customer..."></textarea>
                    </div>
                </div>

                <!-- Financial Synergy -->
                <div class="space-y-4">
                    <h4 class="text-[11px] font-bold text-text-disabled uppercase flex items-center gap-1 border-b border-shell-border pb-1"><PhCurrencyDollar /> Financial Synergy</h4>
                    
                    <div class="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-shell-border">
                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-[12px] font-bold text-text-secondary mb-1">Total Deal Value ($)</label>
                            <input v-model="form.value" type="number" required class="w-full h-10 px-3 rounded-btn border border-shell-border text-[14px] focus:outline-none focus:border-dept-sales-main" placeholder="15000" />
                        </div>
                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-[12px] font-bold text-text-secondary mb-1">Expected Close</label>
                            <input v-model="form.expected_close_date" type="date" required class="w-full h-10 px-3 rounded-btn border border-shell-border text-[14px] focus:outline-none focus:border-dept-sales-main" />
                        </div>

                        <div class="col-span-2">
                            <label class="block text-[12px] font-bold text-text-secondary mb-2">Payment Structure</label>
                            <div class="flex items-center gap-4">
                                <label class="flex items-center gap-2 text-[13px] text-text-primary cursor-pointer">
                                    <input type="radio" v-model="form.payment_type" value="one_time" class="text-dept-sales-main focus:ring-dept-sales-main" /> One-Time
                                </label>
                                <label class="flex items-center gap-2 text-[13px] text-text-primary cursor-pointer">
                                    <input type="radio" v-model="form.payment_type" value="recurring" class="text-dept-sales-main focus:ring-dept-sales-main" /> Recurring
                                </label>
                            </div>
                        </div>

                        <template v-if="form.payment_type === 'recurring'">
                            <div class="col-span-2 md:col-span-1">
                                <label class="block text-[12px] font-bold text-text-secondary mb-1">Recurring Frequency</label>
                                <select v-model="form.recurring_frequency" required class="w-full h-10 px-3 rounded-btn border border-shell-border text-[14px] focus:outline-none focus:border-dept-sales-main">
                                    <option value="" disabled>Select...</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                            </div>
                            <div class="col-span-2 md:col-span-1">
                                <label class="block text-[12px] font-bold text-text-secondary mb-1">Amount per cycle ($)</label>
                                <input v-model="form.recurring_amount" type="number" required class="w-full h-10 px-3 rounded-btn border border-shell-border text-[14px] focus:outline-none focus:border-dept-sales-main" placeholder="2500" />
                            </div>
                            <div class="col-span-2 md:col-span-1">
                                <label class="block text-[12px] font-bold text-text-secondary mb-1">First Collection Date</label>
                                <input v-model="form.collection_date" type="date" required class="w-full h-10 px-3 rounded-btn border border-shell-border text-[14px] focus:outline-none focus:border-dept-sales-main" />
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Legal Synergy -->
                <div class="space-y-4">
                    <h4 class="text-[11px] font-bold text-text-disabled uppercase flex items-center gap-1 border-b border-shell-border pb-1"><PhFileDoc /> Legal Synergy</h4>
                    <div class="border-2 border-dashed border-shell-border rounded-xl p-6 flex flex-col items-center justify-center text-center bg-slate-50 relative hover:bg-slate-100 transition-colors group cursor-pointer" @click="fileInput?.click()">
                        <input type="file" ref="fileInput" @change="handleFileChange" accept=".doc,.docx,.pdf" class="hidden" />
                        
                        <PhFileDoc :size="32" class="text-text-disabled group-hover:text-dept-sales-main mb-2 transition-colors" />
                        <div v-if="!selectedFileName">
                            <p class="text-[13px] font-bold text-text-primary">Upload Legal Agreement</p>
                            <p class="text-[11px] text-text-secondary mt-1">Upload a PDF or DOC copy of the negotiated contract.</p>
                        </div>
                        <div v-else>
                            <p class="text-[13px] font-bold text-dept-sales-main">{{ selectedFileName }}</p>
                            <p class="text-[11px] text-text-secondary mt-1">Click to change file</p>
                        </div>
                    </div>
                </div>

            </form>
        </div>

        <div class="p-4 border-t border-shell-border bg-slate-50 flex justify-end gap-3 shrink-0">
            <button type="button" @click="$emit('close')" class="px-4 py-2 rounded-btn text-[13px] font-medium text-text-secondary hover:bg-slate-200 transition-colors">
                Cancel
            </button>
            <button @click="submit" :disabled="isSubmitting" class="px-5 py-2 rounded-btn bg-dept-sales-main text-white text-[13px] font-bold hover:bg-[#CA8A04] transition-colors disabled:opacity-50">
                Create & Register Deal
            </button>
        </div>
    </div>
</template>
