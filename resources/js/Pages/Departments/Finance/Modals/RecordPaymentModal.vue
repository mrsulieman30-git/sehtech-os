<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { PhX } from '@phosphor-icons/vue';
const emit = defineEmits(['close']);
const props = defineProps<{ invoiceId: string }>();
const form = ref({ invoice_id: props.invoiceId, amount: 0, payment_date: '', method: 'bank_transfer' });
const submit = async () => {
    await axios.post('/api/finance/payments', form.value);
    window.dispatchEvent(new CustomEvent('refresh-finance-dashboard'));
    emit('close');
};
</script>
<template>
    <div class="w-[500px] bg-white rounded-modal p-6 shadow-modal">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-bold">Record Payment</h2>
            <button @click="$emit('close')" class="p-1 text-text-disabled hover:text-state-error transition-colors"><PhX :size="18" /></button>
        </div>
        <form @submit.prevent="submit" class="flex flex-col gap-4">
            <input v-model="form.invoice_id" placeholder="Invoice UUID" class="border border-shell-border p-2 text-[13px] rounded outline-none focus:ring-1 focus:ring-dept-finance-main" required />
            <input v-model="form.amount" type="number" step="0.01" placeholder="Amount" class="border border-shell-border p-2 text-[13px] rounded outline-none focus:ring-1 focus:ring-dept-finance-main" required />
            <div class="grid grid-cols-2 gap-4">
                <input v-model="form.payment_date" type="date" class="border border-shell-border p-2 text-[13px] rounded outline-none focus:ring-1 focus:ring-dept-finance-main" required />
                <select v-model="form.method" class="border border-shell-border p-2 text-[13px] rounded outline-none focus:ring-1 focus:ring-dept-finance-main" required>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="credit_card">Credit Card</option>
                    <option value="cash">Cash</option>
                    <option value="mobile_money">Mobile Money</option>
                    <option value="crypto">Crypto</option>
                </select>
            </div>
            <button type="submit" class="bg-dept-finance-main hover:bg-[#A16207] transition-colors text-[13px] font-medium text-white py-2 rounded-btn mt-2">Record</button>
        </form>
    </div>
</template>
