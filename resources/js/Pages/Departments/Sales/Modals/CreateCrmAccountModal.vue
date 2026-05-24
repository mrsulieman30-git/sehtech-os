<script setup lang="ts">
import { ref, computed } from 'vue';
import axios from 'axios';
import { PhX, PhPlus, PhTrash } from '@phosphor-icons/vue';
import { useToastStore } from '@/Stores/useToastStore';
import { fullCountryList, countryCodesList } from '@/Utils/Countries';

const emit = defineEmits(['close']);
const toastStore = useToastStore();

const industries = [
    'Healthcare', 'Technology', 'Finance', 'Real Estate', 
    'Manufacturing', 'Retail', 'Logistics', 'Education', 
    'Utilities', 'Telecommunications', 'Other'
];

const countries = fullCountryList;
const countryCodes = countryCodesList;

const form = ref({
    name: '',
    industry: '',
    city: '',
    country: '',
    contacts: [] as any[]
});

const defaultCountryCode = ref(localStorage.getItem('preferred_country_code') || '+1');

const addContact = () => {
    form.value.contacts.push({
        first_name: '',
        last_name: '',
        email: '',
        phone_code: defaultCountryCode.value,
        phone: '',
        whatsapp_code: defaultCountryCode.value,
        whatsapp: '',
        same_as_phone: false
    });
};

const removeContact = (index: number) => {
    form.value.contacts.splice(index, 1);
};

const savePreferredCode = (code: string) => {
    defaultCountryCode.value = code;
    localStorage.setItem('preferred_country_code', code);
};

const syncWhatsapp = (index: number) => {
    const contact = form.value.contacts[index];
    if (contact.same_as_phone) {
        contact.whatsapp_code = contact.phone_code;
        contact.whatsapp = contact.phone;
    }
};

const isSubmitting = ref(false);

const submit = async () => {
    if (isSubmitting.value) return;
    isSubmitting.value = true;
    
    // Combine codes and numbers for backend
    const payload = {
        ...form.value,
        contacts: form.value.contacts.map(c => ({
            ...c,
            phone: c.phone ? `${c.phone_code}${c.phone}` : '',
            whatsapp: c.whatsapp ? `${c.whatsapp_code}${c.whatsapp}` : ''
        }))
    };

    try {
        await axios.post('/api/sales/accounts', payload);
        window.dispatchEvent(new CustomEvent('refresh-sales-crm'));
        window.dispatchEvent(new CustomEvent('refresh-marketing'));
        window.dispatchEvent(new CustomEvent('refresh-finance-dashboard'));
        toastStore.addToast('success', 'Account created successfully!');
        emit('close');
    } catch (error) {
        console.error(error);
        toastStore.addToast('error', 'Failed to create account.');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div class="bg-white rounded-xl w-[600px] max-w-[95vw] shadow-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200" style="max-height: 90vh;">
        <div class="h-[56px] px-6 border-b border-shell-border flex items-center justify-between shrink-0 bg-slate-50">
            <h3 class="text-[15px] font-bold text-text-primary">Create CRM Account</h3>
            <button @click="$emit('close')" class="w-8 h-8 flex items-center justify-center rounded-btn hover:bg-slate-200 text-text-secondary transition-colors">
                <PhX :size="16" />
            </button>
        </div>
        
        <div class="p-6 flex-1 overflow-y-auto">
            <form id="createAccountForm" @submit.prevent="submit" class="space-y-6">
                <!-- Account Info -->
                <div class="space-y-4">
                    <h4 class="text-[13px] font-bold text-dept-sales-main border-b border-shell-border pb-1">Company Details</h4>
                    
                    <div>
                        <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Company / Facility Name *</label>
                        <input v-model="form.name" type="text" required class="w-full h-10 px-3 rounded-btn border border-shell-border text-[14px] focus:outline-none focus:border-dept-sales-main focus:ring-1 focus:ring-dept-sales-main" placeholder="e.g. Acme Hospital" />
                    </div>
                    
                    <div>
                        <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Industry</label>
                        <select v-model="form.industry" class="w-full h-10 px-3 rounded-btn border border-shell-border text-[14px] focus:outline-none focus:border-dept-sales-main focus:ring-1 focus:ring-dept-sales-main bg-white">
                            <option value="" disabled>Select Industry...</option>
                            <option v-for="ind in industries" :key="ind" :value="ind">{{ ind }}</option>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Country</label>
                            <select v-model="form.country" class="w-full h-10 px-3 rounded-btn border border-shell-border text-[14px] focus:outline-none focus:border-dept-sales-main focus:ring-1 focus:ring-dept-sales-main bg-white">
                                <option value="" disabled>Select Country...</option>
                                <option v-for="c in countries" :key="c" :value="c">{{ c }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">City</label>
                            <input v-model="form.city" type="text" name="city" autocomplete="address-level2" class="w-full h-10 px-3 rounded-btn border border-shell-border text-[14px] focus:outline-none focus:border-dept-sales-main focus:ring-1 focus:ring-dept-sales-main" placeholder="Start typing city..." />
                        </div>
                    </div>
                </div>

                <!-- Contacts Section -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between border-b border-shell-border pb-1">
                        <h4 class="text-[13px] font-bold text-dept-sales-main">Key Contacts</h4>
                        <button type="button" @click="addContact" class="text-[12px] font-bold text-dept-sales-main flex items-center gap-1 hover:text-[#CA8A04] transition-colors">
                            <PhPlus weight="bold" /> Add Contact
                        </button>
                    </div>

                    <div v-if="form.contacts.length === 0" class="text-center p-4 border border-dashed border-shell-border rounded-xl text-text-disabled text-[13px]">
                        No contacts added yet. Click "Add Contact" to associate people with this account.
                    </div>

                    <div v-for="(contact, index) in form.contacts" :key="index" class="p-4 bg-slate-50 border border-shell-border rounded-xl relative space-y-3">
                        <button type="button" @click="removeContact(index)" class="absolute top-3 right-3 text-text-disabled hover:text-state-error transition-colors" title="Remove Contact">
                            <PhTrash :size="16" />
                        </button>
                        
                        <div class="grid grid-cols-2 gap-3 pr-6">
                            <div>
                                <label class="block text-[11px] font-bold text-text-secondary uppercase mb-1">First Name *</label>
                                <input v-model="contact.first_name" type="text" required class="w-full h-9 px-3 rounded border border-shell-border text-[13px] focus:outline-none focus:border-dept-sales-main" placeholder="First Name" />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-text-secondary uppercase mb-1">Last Name</label>
                                <input v-model="contact.last_name" type="text" class="w-full h-9 px-3 rounded border border-shell-border text-[13px] focus:outline-none focus:border-dept-sales-main" placeholder="Last Name" />
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-[11px] font-bold text-text-secondary uppercase mb-1">Email</label>
                            <input v-model="contact.email" type="email" class="w-full h-9 px-3 rounded border border-shell-border text-[13px] focus:outline-none focus:border-dept-sales-main" placeholder="Email Address" />
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3 mt-3">
                            <!-- Phone input group -->
                            <div>
                                <label class="block text-[11px] font-bold text-text-disabled uppercase mb-1">Phone Number</label>
                                <div class="flex">
                                    <select v-model="contact.phone_code" @change="savePreferredCode(contact.phone_code)" class="h-9 px-2 rounded-l-btn border border-shell-border border-r-0 text-[13px] bg-white focus:outline-none focus:border-dept-sales-main focus:ring-1 focus:ring-dept-sales-main shrink-0 w-[100px]">
                                        <option v-for="c in countryCodes" :key="c.code" :value="c.code">{{ c.code }} ({{ c.country }})</option>
                                    </select>
                                    <input v-model="contact.phone" @input="syncWhatsapp(index)" type="tel" class="w-full h-9 px-3 rounded-r-btn border border-shell-border text-[13px] focus:outline-none focus:border-dept-sales-main focus:ring-1 focus:ring-dept-sales-main" placeholder="555-1234" />
                                </div>
                            </div>
                            <!-- WhatsApp input group -->
                            <div>
                                <label class="block text-[11px] font-bold text-[#25D366] uppercase mb-1 flex items-center justify-between">
                                    <span>WhatsApp Number</span>
                                    <label class="flex items-center gap-1.5 text-text-secondary cursor-pointer lowercase normal-case text-[10px]">
                                        <input type="checkbox" v-model="contact.same_as_phone" @change="syncWhatsapp(index)" class="rounded text-[#25D366] focus:ring-[#25D366]" />
                                        Same as Phone
                                    </label>
                                </label>
                                <div class="flex">
                                    <select v-model="contact.whatsapp_code" :disabled="contact.same_as_phone" class="h-9 px-2 rounded-l-btn border border-shell-border border-r-0 text-[13px] bg-white focus:outline-none focus:border-[#25D366] focus:ring-1 focus:ring-[#25D366] shrink-0 w-[100px] disabled:opacity-50">
                                        <option v-for="c in countryCodes" :key="c.code" :value="c.code">{{ c.code }} ({{ c.country }})</option>
                                    </select>
                                    <input v-model="contact.whatsapp" type="tel" :disabled="contact.same_as_phone" class="w-full h-9 px-3 rounded-r-btn border border-shell-border text-[13px] focus:outline-none focus:border-[#25D366] focus:ring-1 focus:ring-[#25D366] disabled:opacity-50" placeholder="555-1234" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="p-4 border-t border-shell-border bg-slate-50 flex justify-end gap-3 shrink-0">
            <button type="button" @click="$emit('close')" class="px-4 py-2 rounded-btn text-[13px] font-medium text-text-secondary hover:bg-slate-200 transition-colors">
                Cancel
            </button>
            <button form="createAccountForm" type="submit" :disabled="isSubmitting" class="px-5 py-2 rounded-btn bg-dept-sales-main text-white text-[13px] font-bold hover:bg-[#CA8A04] transition-colors disabled:opacity-50">
                Create Account & Contacts
            </button>
        </div>
    </div>
</template>
