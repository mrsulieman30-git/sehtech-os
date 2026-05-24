<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { PhX } from '@phosphor-icons/vue';
import { countryCodesList } from '@/Utils/Countries';

const emit = defineEmits(['close']);

const countryCodes = countryCodesList;

const form = ref({
    first_name: '',
    last_name: '',
    organization: '',
    email: '',
    phone_code: localStorage.getItem('preferred_country_code') || '+1',
    phone: '',
    whatsapp_code: localStorage.getItem('preferred_country_code') || '+1',
    whatsapp: '',
    same_as_phone: false,
    status: 'lead',
    priority: 'warm',
});

const saveCountryCode = (code: string) => {
    localStorage.setItem('preferred_country_code', code);
};

const syncWhatsapp = () => {
    if (form.value.same_as_phone) {
        form.value.whatsapp_code = form.value.phone_code;
        form.value.whatsapp = form.value.phone;
    }
};

const isSubmitting = ref(false);
const error = ref('');

const submitLead = async () => {
    isSubmitting.value = true;
    error.value = '';
    
    const payload = {
        ...form.value,
        phone: form.value.phone ? `${form.value.phone_code}${form.value.phone}` : '',
        whatsapp: form.value.whatsapp ? `${form.value.whatsapp_code}${form.value.whatsapp}` : ''
    };

    try {
        await axios.post('/api/sales/clients', payload);
        window.dispatchEvent(new CustomEvent('refresh-sales-pipeline'));
        emit('close');
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Failed to add lead';
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div class="w-[500px] bg-white rounded-modal shadow-modal border border-shell-border flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        
        <div class="h-[56px] flex items-center justify-between px-6 border-b border-shell-border bg-shell-panel shrink-0">
            <h2 class="text-[15px] font-bold text-text-primary">Add New Lead</h2>
            <button @click="$emit('close')" class="p-1.5 text-text-disabled hover:text-state-error rounded transition-colors">
                <PhX :size="18" weight="bold" />
            </button>
        </div>

        <form @submit.prevent="submitLead" class="p-6 flex flex-col gap-4">
            
            <div v-if="error" class="p-3 bg-state-error/10 border border-state-error/20 text-state-error rounded-input text-[13px] font-medium">
                {{ error }}
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[13px] font-semibold text-text-primary">Organization / Company</label>
                <input 
                    v-model="form.organization" 
                    type="text" 
                    autofocus
                    placeholder="e.g. City General Hospital"
                    class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-sales-main outline-none"
                />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1">
                    <label class="text-[13px] font-semibold text-text-primary">First Name *</label>
                    <input 
                        v-model="form.first_name" 
                        type="text" 
                        required
                        class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-sales-main outline-none"
                    />
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-[13px] font-semibold text-text-primary">Last Name *</label>
                    <input 
                        v-model="form.last_name" 
                        type="text" 
                        required
                        class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-sales-main outline-none"
                    />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1">
                    <label class="text-[13px] font-semibold text-text-primary">Email Address</label>
                    <input 
                        v-model="form.email" 
                        type="email" 
                        placeholder="contact@company.com"
                        class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-sales-main outline-none"
                    />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-[13px] font-semibold text-text-primary">Phone Number</label>
                    <div class="flex gap-1">
                        <select v-model="form.phone_code" @change="saveCountryCode(form.phone_code); syncWhatsapp()" class="w-[100px] px-1 py-2 bg-slate-50 border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-sales-main outline-none">
                            <option v-for="c in countryCodes" :key="c.code" :value="c.code">{{ c.code }} ({{ c.country }})</option>
                        </select>
                        <input 
                            v-model="form.phone" 
                            @input="syncWhatsapp"
                            type="tel" 
                            placeholder="Phone Number"
                            class="flex-1 px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-sales-main outline-none"
                        />
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[13px] font-semibold text-text-primary">WhatsApp Number</label>
                <div class="flex gap-1">
                    <select v-model="form.whatsapp_code" :disabled="form.same_as_phone" @change="saveCountryCode(form.whatsapp_code)" class="w-[100px] px-1 py-2 bg-slate-50 border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-sales-main outline-none disabled:bg-slate-100 disabled:text-text-secondary">
                        <option v-for="c in countryCodes" :key="c.code" :value="c.code">{{ c.code }} ({{ c.country }})</option>
                    </select>
                    <input 
                        v-model="form.whatsapp" 
                        type="tel" 
                        :disabled="form.same_as_phone"
                        placeholder="WhatsApp Number"
                        class="flex-1 px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-sales-main outline-none disabled:bg-slate-100 disabled:text-text-secondary"
                    />
                </div>
                <div class="flex items-center gap-2 mt-1">
                    <input type="checkbox" id="wa-sync" v-model="form.same_as_phone" @change="syncWhatsapp" class="rounded border-shell-border text-dept-sales-main focus:ring-dept-sales-main w-4 h-4" />
                    <label for="wa-sync" class="text-[12px] font-medium text-text-secondary cursor-pointer select-none">
                        Same as Phone
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1">
                    <label class="text-[13px] font-semibold text-text-primary">Pipeline Stage</label>
                    <select v-model="form.status" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-sales-main outline-none">
                        <option value="lead">New Lead</option>
                        <option value="contacted">Contacted</option>
                        <option value="demo_scheduled">Demo Scheduled</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-[13px] font-semibold text-text-primary">Priority Level</label>
                    <select v-model="form.priority" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-sales-main outline-none">
                        <option value="hot">Hot (High Intent)</option>
                        <option value="warm">Warm</option>
                        <option value="cold">Cold</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-end gap-3 pt-4 border-t border-shell-border">
                <button 
                    type="button" 
                    @click="$emit('close')"
                    class="px-4 py-2 text-[13px] font-medium text-text-secondary hover:text-text-primary transition-colors"
                >
                    Cancel
                </button>
                <button 
                    type="submit" 
                    :disabled="isSubmitting"
                    class="px-5 py-2 bg-dept-sales-main text-white text-[13px] font-medium rounded-btn hover:bg-[#047857] transition-colors disabled:opacity-50"
                >
                    {{ isSubmitting ? 'Saving...' : 'Add Lead' }}
                </button>
            </div>
        </form>
    </div>
</template>
