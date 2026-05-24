<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { PhX, PhFileText } from '@phosphor-icons/vue';
import { useToastStore } from '@/Stores/useToastStore';

const emit = defineEmits(['close']);
const toastStore = useToastStore();

const form = ref({
    vendor_name: '',
    category: 'software',
    amount: '',
    due_date: '',
    notes: '',
    is_recurring: false,
    recurring_frequency: ''
});

const isSubmitting = ref(false);

const submit = async () => {
    if (isSubmitting.value) return;
    isSubmitting.value = true;
    try {
        await axios.post('/api/finance/bills', form.value);
        toastStore.showToast('Vendor bill recorded successfully!', 'success');
        window.dispatchEvent(new CustomEvent('refresh-finance-dashboard'));
        emit('close');
    } catch (e: any) {
        toastStore.showToast('Failed to record vendor bill.', 'error');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div class="bg-white rounded-xl w-[500px] max-w-[95vw] shadow-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
        <div class="h-[56px] px-6 border-b border-shell-border flex items-center justify-between shrink-0 bg-slate-50">
            <div class="flex items-center gap-2">
                <PhFileText :size="20" class="text-dept-finance-main" />
                <h3 class="text-[15px] font-bold text-text-primary">Record Vendor Bill</h3>
            </div>
            <button @click="$emit('close')" class="w-8 h-8 flex items-center justify-center rounded-btn hover:bg-slate-200 text-text-secondary transition-colors border-0 bg-transparent cursor-pointer">
                <PhX :size="16" />
            </button>
        </div>
        
        <form @submit.prevent="submit" class="p-6 flex-1 space-y-4">
            <div>
                <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Vendor / Payee Name *</label>
                <input v-model="form.vendor_name" type="text" required class="w-full h-10 px-3 rounded border border-shell-border text-[13px] focus:outline-none focus:border-dept-finance-main focus:ring-1 focus:ring-dept-finance-main" placeholder="e.g. AWS, Slack, Office Rent" />
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Category</label>
                    <select v-model="form.category" class="w-full h-10 px-3 rounded border border-shell-border text-[13px] bg-white focus:outline-none focus:border-dept-finance-main focus:ring-1 focus:ring-dept-finance-main">
                        <option value="software">Software & SaaS</option>
                        <option value="marketing">Marketing & Ads</option>
                        <option value="office">Office & Rent</option>
                        <option value="travel">Travel & Meals</option>
                        <option value="payroll">Payroll Overhead</option>
                        <option value="other">Other Expenses</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Amount ($) *</label>
                    <input v-model="form.amount" type="number" step="0.01" required class="w-full h-10 px-3 rounded border border-shell-border text-[13px] focus:outline-none focus:border-dept-finance-main focus:ring-1 focus:ring-dept-finance-main" placeholder="499.00" />
                </div>
            </div>
            
            <div>
                <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Due Date *</label>
                <input v-model="form.due_date" type="date" required class="w-full h-10 px-3 rounded border border-shell-border text-[13px] focus:outline-none focus:border-dept-finance-main focus:ring-1 focus:ring-dept-finance-main" />
            </div>

            <!-- Recurring Options -->
            <div class="space-y-2 bg-slate-50 p-4 rounded-xl border border-shell-border">
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="is_recurring" v-model="form.is_recurring" class="rounded text-dept-finance-main focus:ring-dept-finance-main border-shell-border w-4 h-4 cursor-pointer" />
                    <label for="is_recurring" class="text-[13px] font-semibold text-text-primary cursor-pointer select-none">This is a Recurring Bill (Rent, Subscription, etc.)</label>
                </div>
                
                <div v-if="form.is_recurring" class="animate-in fade-in duration-200">
                    <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Billing Frequency</label>
                    <select v-model="form.recurring_frequency" required class="w-full h-10 px-3 rounded border border-shell-border text-[13px] bg-white focus:outline-none focus:border-dept-finance-main focus:ring-1 focus:ring-dept-finance-main">
                        <option value="" disabled>Select frequency...</option>
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>
            </div>
            
            <div>
                <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Notes / Description</label>
                <textarea v-model="form.notes" rows="2" class="w-full p-3 rounded border border-shell-border text-[13px] focus:outline-none focus:border-dept-finance-main focus:ring-1 focus:ring-dept-finance-main" placeholder="Provide additional details..."></textarea>
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t border-shell-border">
                <button type="button" @click="$emit('close')" class="px-4 py-2 text-[13px] font-medium text-text-secondary hover:bg-slate-100 rounded border-0 bg-transparent cursor-pointer">
                    Cancel
                </button>
                <button type="submit" :disabled="isSubmitting" class="px-5 py-2 rounded bg-dept-finance-main text-white text-[13px] font-bold hover:bg-[#A16207] transition-colors border-0 cursor-pointer disabled:opacity-50">
                    Record Bill
                </button>
            </div>
        </form>
    </div>
</template>
