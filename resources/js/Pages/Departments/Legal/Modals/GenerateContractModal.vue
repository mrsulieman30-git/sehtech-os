<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { PhX, PhRobot } from '@phosphor-icons/vue';
import { useToastStore } from '@/Stores/useToastStore';

const props = defineProps<{
    template: any;
}>();

const emit = defineEmits(['close']);
const toastStore = useToastStore();

const accounts = ref<any[]>([]);
const form = ref({
    crm_account_id: '',
    contract_template_id: props.template.id,
    variables: {} as Record<string, string>
});

const isSubmitting = ref(false);

const fetchAccounts = async () => {
    try {
        const response = await axios.get('/api/sales/crm');
        accounts.value = response.data.accounts;
        if (accounts.value.length > 0) form.value.crm_account_id = accounts.value[0].id;
    } catch (e) {
        console.error(e);
    }
};

const submit = async () => {
    isSubmitting.value = true;
    try {
        const response = await axios.post('/api/legal/contracts/generate', form.value);
        window.dispatchEvent(new CustomEvent('refresh-legal'));
        toastStore.addToast('success', response.data.message);
        emit('close');
    } catch (error) {
        console.error(error);
        toastStore.addToast('error', 'Failed to generate contract draft.');
    } finally {
        isSubmitting.value = false;
    }
};

onMounted(() => {
    fetchAccounts();
    // Initialize variables
    if (props.template.variables) {
        props.template.variables.forEach((v: string) => {
            form.value.variables[v] = '';
        });
    }
});
</script>

<template>
    <div class="fixed inset-0 z-[5000] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4 animate-in fade-in duration-200" @click.self="emit('close')">
        <div class="bg-white rounded-xl shadow-modal w-full max-w-[500px] flex flex-col overflow-hidden animate-in slide-in-from-bottom-4 duration-300">
            <div class="h-14 px-6 border-b border-shell-border flex items-center justify-between bg-shell-panel shrink-0">
                <div class="flex items-center gap-2 text-dept-legal-main font-bold">
                    <PhRobot :size="20" weight="fill" /> Generate {{ template.type }}
                </div>
                <button @click="emit('close')" class="p-1.5 text-text-secondary hover:text-text-primary rounded-md hover:bg-black/5 transition-colors">
                    <PhX :size="18" />
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto max-h-[70vh]">
                <div class="text-[13px] text-text-secondary mb-6 border p-3 rounded bg-dept-legal-main/5 border-dept-legal-main/20">
                    <span class="font-bold text-dept-legal-main block mb-1">AI Prompt Preview:</span>
                    {{ template.ai_prompt }}
                </div>

                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[12px] font-bold text-text-secondary uppercase tracking-wider">Related CRM Account</label>
                        <select v-model="form.crm_account_id" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-legal-main outline-none">
                            <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
                        </select>
                    </div>

                    <div v-for="variable in template.variables" :key="variable" class="flex flex-col gap-1.5">
                        <label class="text-[12px] font-bold text-text-secondary uppercase tracking-wider">{{ variable.replace(/_/g, ' ') }}</label>
                        <input v-if="variable === 'requirements'" v-model="form.variables[variable]" type="text" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-legal-main outline-none" placeholder="e.g. Standard terms, net-30..." />
                        <input v-else v-model="form.variables[variable]" type="text" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-legal-main outline-none" :placeholder="'Enter ' + variable.replace(/_/g, ' ')" />
                    </div>
                </div>
            </div>

            <div class="p-6 border-t border-shell-border bg-shell-panel flex justify-end gap-3 shrink-0">
                <button @click="emit('close')" class="px-4 py-2 text-[13px] font-medium text-text-secondary hover:text-text-primary transition-colors">
                    Cancel
                </button>
                <button 
                    @click="submit" 
                    :disabled="isSubmitting || !form.crm_account_id"
                    class="px-5 py-2 bg-dept-legal-main text-white text-[13px] font-bold rounded-btn flex items-center gap-2 hover:bg-[#5B21B6] transition-colors shadow-sm disabled:opacity-50"
                >
                    <PhRobot :size="16" weight="fill" v-if="!isSubmitting" />
                    <svg v-else class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    {{ isSubmitting ? 'Generating...' : 'Generate with AI' }}
                </button>
            </div>
        </div>
    </div>
</template>
