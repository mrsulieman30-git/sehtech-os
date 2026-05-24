<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { PhX, PhTrash, PhPlus } from '@phosphor-icons/vue';

const emit = defineEmits(['close']);

interface ClientOption {
    id: string;
    organization: string | null;
    first_name: string;
    last_name: string;
}

const clients = ref<ClientOption[]>([]);

const form = ref({
    client_id: '',
    issue_date: new Date().toISOString().split('T')[0],
    due_date: '',
    tax_rate: 0,
    items: [
        { description: '', quantity: 1, price: 0 }
    ]
});

const isSubmitting = ref(false);
const error = ref('');

const fetchClients = async () => {
    try {
        const res = await axios.get('/api/sales/clients/list');
        clients.value = res.data;
    } catch (err) {
        console.error('Failed to load clients', err);
    }
};

const subtotal = computed(() => {
    return form.value.items.reduce((sum, item) => sum + (item.quantity * item.price), 0);
});

const total = computed(() => {
    return subtotal.value + (subtotal.value * (form.value.tax_rate / 100));
});

const addItem = () => {
    form.value.items.push({ description: '', quantity: 1, price: 0 });
};

const removeItem = (index: number) => {
    if (form.value.items.length > 1) {
        form.value.items.splice(index, 1);
    }
};

const submitInvoice = async () => {
    isSubmitting.value = true;
    error.value = '';
    
    try {
        await axios.post('/api/finance/invoices', form.value);
        window.dispatchEvent(new CustomEvent('refresh-finance-dashboard'));
        emit('close');
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Failed to generate invoice';
    } finally {
        isSubmitting.value = false;
    }
};

onMounted(() => {
    fetchClients();
});
</script>

<template>
    <div class="w-[700px] bg-white rounded-modal shadow-modal border border-shell-border flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200 max-h-[90vh]">
        
        <div class="h-[56px] flex items-center justify-between px-6 border-b border-shell-border bg-shell-panel shrink-0">
            <h2 class="text-[15px] font-bold text-text-primary">Generate New Invoice</h2>
            <button @click="$emit('close')" class="p-1.5 text-text-disabled hover:text-state-error rounded transition-colors">
                <PhX :size="18" weight="bold" />
            </button>
        </div>

        <form @submit.prevent="submitInvoice" class="flex flex-col overflow-hidden flex-1">
            <div class="p-6 overflow-y-auto flex flex-col gap-6">
                
                <div v-if="error" class="p-3 bg-state-error/10 border border-state-error/20 text-state-error rounded-input text-[13px] font-medium">
                    {{ error }}
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1 col-span-2">
                        <label class="text-[13px] font-semibold text-text-primary">Select Client *</label>
                        <select v-model="form.client_id" required class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-finance-main outline-none">
                            <option value="" disabled>-- Choose a Client --</option>
                            <option v-for="client in clients" :key="client.id" :value="client.id">
                                {{ client.organization || `${client.first_name} ${client.last_name}` }}
                            </option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-[13px] font-semibold text-text-primary">Issue Date *</label>
                        <input v-model="form.issue_date" type="date" required class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-finance-main outline-none" />
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-[13px] font-semibold text-text-primary">Due Date *</label>
                        <input v-model="form.due_date" type="date" required class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-finance-main outline-none" />
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <div class="flex justify-between items-end mb-1">
                        <label class="text-[13px] font-semibold text-text-primary">Line Items</label>
                    </div>
                    
                    <div class="bg-shell-panel border border-shell-border rounded-card overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-white border-b border-shell-border text-[11px] text-text-disabled uppercase tracking-wider">
                                <tr>
                                    <th class="px-4 py-2 w-1/2">Description</th>
                                    <th class="px-4 py-2 w-20">Qty</th>
                                    <th class="px-4 py-2 w-32">Price ($)</th>
                                    <th class="px-4 py-2 w-32">Total</th>
                                    <th class="px-4 py-2 w-12 text-center"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in form.items" :key="index" class="border-b border-shell-border last:border-0">
                                    <td class="p-2">
                                        <input v-model="item.description" required type="text" placeholder="Service or product" class="w-full px-2 py-1.5 bg-white border border-shell-border rounded text-[13px] focus:ring-1 focus:ring-dept-finance-main outline-none" />
                                    </td>
                                    <td class="p-2">
                                        <input v-model="item.quantity" required type="number" min="1" class="w-full px-2 py-1.5 bg-white border border-shell-border rounded text-[13px] focus:ring-1 focus:ring-dept-finance-main outline-none" />
                                    </td>
                                    <td class="p-2">
                                        <input v-model="item.price" required type="number" min="0" step="0.01" class="w-full px-2 py-1.5 bg-white border border-shell-border rounded text-[13px] focus:ring-1 focus:ring-dept-finance-main outline-none" />
                                    </td>
                                    <td class="p-2 text-[13px] font-medium text-text-primary px-4">
                                        ${{ (item.quantity * item.price).toFixed(2) }}
                                    </td>
                                    <td class="p-2 text-center">
                                        <button @click.prevent="removeItem(index)" :disabled="form.items.length === 1" class="p-1 text-text-disabled hover:text-state-error disabled:opacity-30 transition-colors">
                                            <PhTrash :size="16" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="p-2 bg-white">
                            <button @click.prevent="addItem" class="flex items-center gap-1 text-[12px] font-medium text-dept-finance-main hover:underline">
                                <PhPlus :size="12" weight="bold" /> Add Line Item
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-shell-border">
                    <div class="w-64 flex flex-col gap-2 text-[13px]">
                        <div class="flex justify-between text-text-secondary">
                            <span>Subtotal</span>
                            <span>${{ subtotal.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-text-secondary">
                            <span>Tax Rate (%)</span>
                            <input v-model="form.tax_rate" type="number" min="0" max="100" class="w-16 px-2 py-1 bg-white border border-shell-border rounded text-right focus:ring-1 focus:ring-dept-finance-main outline-none" />
                        </div>
                        <div class="flex justify-between font-bold text-[16px] text-text-primary mt-2 pt-2 border-t border-shell-border">
                            <span>Total Due</span>
                            <span class="text-dept-finance-main">${{ total.toFixed(2) }}</span>
                        </div>
                    </div>
                </div>

            </div>

            <div class="shrink-0 flex items-center justify-end gap-3 p-4 border-t border-shell-border bg-shell-panel">
                <button type="button" @click="$emit('close')" class="px-4 py-2 text-[13px] font-medium text-text-secondary hover:text-text-primary transition-colors">
                    Cancel
                </button>
                <button type="submit" :disabled="isSubmitting" class="px-5 py-2 bg-dept-finance-main text-white text-[13px] font-medium rounded-btn hover:bg-[#A16207] transition-colors disabled:opacity-50">
                    {{ isSubmitting ? 'Generating...' : 'Save & Send Invoice' }}
                </button>
            </div>
        </form>
    </div>
</template>
