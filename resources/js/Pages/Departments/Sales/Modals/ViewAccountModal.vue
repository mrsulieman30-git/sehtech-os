<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { PhX, PhPhone, PhWhatsappLogo, PhEnvelopeSimple, PhBuilding, PhKanban, PhPencilSimple, PhCheck, PhTrash } from '@phosphor-icons/vue';
import { useToastStore } from '@/Stores/useToastStore';
import { fullCountryList, countryCodesList } from '@/Utils/Countries';
import dayjs from 'dayjs';

const props = defineProps<{
    accountId: string;
}>();

const emit = defineEmits(['close']);
const toastStore = useToastStore();

const account = ref<any>(null);
const activities = ref<any[]>([]);
const isLoading = ref(true);

const editingContactId = ref<string | null>(null);
const contactForm = ref<any>({});

const isEditingAccount = ref(false);
const accountForm = ref<any>({});
const countries = fullCountryList;
const countryCodes = countryCodesList;
const industries = [
    'Technology', 'Healthcare', 'Finance', 'Education', 'Manufacturing',
    'Retail', 'Real Estate', 'Construction', 'Energy', 'Transportation',
    'Agriculture', 'Entertainment', 'Hospitality', 'Media', 'Government',
    'Non-Profit', 'Legal', 'Consulting', 'Insurance', 'Pharmaceuticals',
    'Utilities', 'Telecommunications', 'Other'
];

const startEditingAccount = () => {
    isEditingAccount.value = true;
    accountForm.value = {
        name: account.value.name,
        industry: account.value.industry,
        city: account.value.city,
        country: account.value.country,
    };
};

const cancelEditingAccount = () => {
    isEditingAccount.value = false;
    accountForm.value = {};
};

const saveAccount = async () => {
    try {
        const response = await axios.put(`/api/sales/accounts/${account.value.id}`, accountForm.value);
        account.value = { ...account.value, ...response.data.account };
        isEditingAccount.value = false;
        toastStore.showToast('Account updated successfully', 'success');
        window.dispatchEvent(new CustomEvent('refresh-sales-crm'));
        window.dispatchEvent(new CustomEvent('refresh-finance-dashboard'));
    } catch (error: any) {
        toastStore.showToast('Failed to update account', 'error');
    }
};

const splitPhoneNumber = (fullNumber: string) => {
    if (!fullNumber) return { code: '+1', number: '' };
    // Sort country codes by length descending to match longest code first (e.g. +1246 before +1)
    const sortedCodes = [...countryCodes].sort((a, b) => b.code.length - a.code.length);
    for (const c of sortedCodes) {
        if (fullNumber.startsWith(c.code)) {
            return {
                code: c.code,
                number: fullNumber.slice(c.code.length)
            };
        }
    }
    return { code: '+1', number: fullNumber };
};

const isAddingContact = ref(false);
const newContactForm = ref<any>({});

const startAddingContact = () => {
    isAddingContact.value = true;
    newContactForm.value = {
        first_name: '',
        last_name: '',
        job_title: '',
        email: '',
        phone_code: '+1',
        phone: '',
        whatsapp_code: '+1',
        whatsapp: '',
    };
};

const cancelAddingContact = () => {
    isAddingContact.value = false;
    newContactForm.value = {};
};

const saveNewContact = async () => {
    try {
        const payload = {
            ...newContactForm.value,
            crm_account_id: account.value.id,
            phone: newContactForm.value.phone_code ? `${newContactForm.value.phone_code}${newContactForm.value.phone}` : newContactForm.value.phone,
            whatsapp: newContactForm.value.whatsapp_code ? `${newContactForm.value.whatsapp_code}${newContactForm.value.whatsapp}` : newContactForm.value.whatsapp,
        };
        const response = await axios.post('/api/sales/contacts', payload);
        account.value.contacts.push(response.data.contact);
        isAddingContact.value = false;
        newContactForm.value = {};
        toastStore.showToast('Contact added successfully', 'success');
        window.dispatchEvent(new CustomEvent('refresh-sales-crm'));
    } catch (error: any) {
        toastStore.showToast('Failed to add contact', 'error');
    }
};

const startEditingContact = (contact: any) => {
    editingContactId.value = contact.id;
    const phoneSplit = splitPhoneNumber(contact.phone || '');
    const whatsappSplit = splitPhoneNumber(contact.whatsapp || '');
    contactForm.value = { 
        ...contact,
        phone_code: phoneSplit.code,
        phone: phoneSplit.number,
        whatsapp_code: whatsappSplit.code,
        whatsapp: whatsappSplit.number,
    };
};

const cancelEditingContact = () => {
    editingContactId.value = null;
    contactForm.value = {};
};

const saveContact = async () => {
    try {
        const payload = {
            ...contactForm.value,
            phone: contactForm.value.phone_code ? `${contactForm.value.phone_code}${contactForm.value.phone}` : contactForm.value.phone,
            whatsapp: contactForm.value.whatsapp_code ? `${contactForm.value.whatsapp_code}${contactForm.value.whatsapp}` : contactForm.value.whatsapp,
        };
        const response = await axios.put(`/api/sales/contacts/${contactForm.value.id}`, payload);
        
        const index = account.value.contacts.findIndex((c: any) => c.id === contactForm.value.id);
        if (index !== -1) {
            account.value.contacts[index] = response.data.contact;
        }
        
        editingContactId.value = null;
        toastStore.showToast('Contact updated successfully', 'success');
        window.dispatchEvent(new CustomEvent('refresh-sales-crm'));
    } catch (error: any) {
        toastStore.showToast('Failed to update contact', 'error');
    }
};

const deleteContact = async (contactId: string) => {
    if (!confirm('Are you sure you want to delete this contact?')) return;
    try {
        await axios.delete(`/api/sales/contacts/${contactId}`);
        account.value.contacts = account.value.contacts.filter((c: any) => c.id !== contactId);
        toastStore.showToast('Contact deleted successfully', 'success');
        window.dispatchEvent(new CustomEvent('refresh-sales-crm'));
    } catch (error: any) {
        toastStore.showToast('Failed to delete contact', 'error');
    }
};

const fetchAccount = async () => {
    try {
        const res = await axios.get(`/api/sales/accounts/${props.accountId}`);
        account.value = res.data.account;
        activities.value = res.data.activities;
    } catch (e) {
        toastStore.showToast('Failed to load account details.', 'error');
        emit('close');
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchAccount();
});
</script>

<template>
    <div class="bg-white rounded-xl w-[700px] max-w-[95vw] shadow-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200" style="max-height: 90vh;">
        
        <!-- Header -->
        <div class="h-[60px] px-6 border-b border-shell-border flex items-center justify-between shrink-0 bg-slate-50">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-dept-sales-main text-white flex items-center justify-center font-bold">
                    {{ account ? account.name.charAt(0) : '' }}
                </div>
                <h3 class="text-[16px] font-bold text-text-primary">{{ account?.name || 'Account Details' }}</h3>
                <span v-if="account" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                      :class="account.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'">
                    {{ account.status }}
                </span>
            </div>
            <div class="flex items-center gap-2">
                <button v-if="account && !isEditingAccount" @click="startEditingAccount" class="flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-bold rounded-btn bg-white border border-shell-border hover:bg-slate-50 text-text-secondary transition-colors shadow-sm">
                    <PhPencilSimple :size="14" /> Edit Account
                </button>
                <button @click="$emit('close')" class="w-8 h-8 flex items-center justify-center rounded-btn hover:bg-slate-200 text-text-secondary transition-colors">
                    <PhX :size="16" />
                </button>
            </div>
        </div>

        <div v-if="isLoading" class="p-12 flex justify-center text-text-disabled">
            Loading...
        </div>
        
        <div v-else class="flex-1 overflow-y-auto flex flex-col md:flex-row">
            
            <!-- Main Content Left -->
            <div class="flex-1 p-6 border-r border-shell-border space-y-6">
                <div>
                    <h4 class="text-[11px] font-bold text-text-disabled uppercase mb-3 flex items-center gap-1"><PhBuilding /> Company Profile</h4>
                    <div v-if="isEditingAccount" class="bg-slate-50 p-4 rounded-xl border border-shell-border space-y-3">
                        <div>
                            <label class="block text-[11px] font-bold text-text-disabled uppercase mb-1">Company Name</label>
                            <input v-model="accountForm.name" type="text" class="w-full h-9 px-3 rounded border border-shell-border text-[13px] focus:outline-none focus:border-dept-sales-main bg-white" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-text-disabled uppercase mb-1">Industry</label>
                            <select v-model="accountForm.industry" class="w-full h-9 px-3 rounded border border-shell-border text-[13px] focus:outline-none focus:border-dept-sales-main bg-white">
                                <option value="" disabled>Select Industry...</option>
                                <option v-for="ind in industries" :key="ind" :value="ind">{{ ind }}</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-text-disabled uppercase mb-1">Country</label>
                                <select v-model="accountForm.country" class="w-full h-9 px-3 rounded border border-shell-border text-[13px] focus:outline-none focus:border-dept-sales-main bg-white">
                                    <option value="" disabled>Select Country...</option>
                                    <option v-for="c in countries" :key="c" :value="c">{{ c }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-text-disabled uppercase mb-1">City</label>
                                <input v-model="accountForm.city" type="text" class="w-full h-9 px-3 rounded border border-shell-border text-[13px] focus:outline-none focus:border-dept-sales-main bg-white" />
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button @click="cancelEditingAccount" class="px-3 py-1.5 text-[12px] font-bold text-text-secondary hover:text-text-primary transition-colors">Cancel</button>
                            <button @click="saveAccount" class="px-3 py-1.5 text-[12px] font-bold bg-dept-sales-main text-white rounded hover:bg-dept-sales-dark transition-colors flex items-center gap-1"><PhCheck weight="bold" /> Save</button>
                        </div>
                    </div>
                    <div v-else class="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-shell-border">
                        <div>
                            <div class="text-[11px] font-bold text-text-disabled uppercase">Industry</div>
                            <div class="text-[13px] font-medium text-text-primary mt-0.5">{{ account.industry || 'Not Specified' }}</div>
                        </div>
                        <div>
                            <div class="text-[11px] font-bold text-text-disabled uppercase">Location</div>
                            <div class="text-[13px] font-medium text-text-primary mt-0.5">
                                {{ account.city ? account.city + ', ' + account.country : (account.country || 'Not Specified') }}
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="text-[11px] font-bold text-text-disabled uppercase mb-3 flex items-center gap-1"><PhKanban /> Deals ({{ account.deals.length }})</h4>
                    <div class="space-y-2">
                        <div v-if="account.deals.length === 0" class="text-[13px] text-text-disabled italic">No deals created yet.</div>
                        <div v-for="deal in account.deals" :key="deal.id" class="p-3 bg-white border border-shell-border rounded-xl flex items-center justify-between">
                            <div>
                                <div class="font-bold text-[13px] text-text-primary">{{ deal.title }}</div>
                                <div class="text-[11px] text-text-secondary mt-0.5">Stage: <span class="capitalize">{{ deal.stage.replace('_', ' ') }}</span></div>
                            </div>
                            <div class="font-bold text-dept-sales-main text-[13px]">
                                ${{ Number(deal.value).toLocaleString() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar: Contacts -->
            <div class="w-full md:w-[280px] bg-slate-50 p-6 flex flex-col gap-6 shrink-0">
                <div>
                    <div class="flex items-center justify-between border-b border-shell-border pb-1 mb-3">
                        <div class="text-[11px] font-bold text-text-disabled uppercase">Key Contacts ({{ account.contacts.length }})</div>
                        <button v-if="!isAddingContact" @click="startAddingContact" class="text-[10px] font-bold text-dept-sales-main hover:text-[#CA8A04] transition-colors bg-transparent border-0 cursor-pointer" title="Add New Contact">
                            + Add
                        </button>
                    </div>

                    <!-- Add Contact Card -->
                    <div v-if="isAddingContact" class="text-[13px] bg-white p-3 border border-shell-border rounded-xl shadow-sm mb-4 space-y-2">
                        <div class="font-bold text-[12px] text-text-primary mb-1">Add New Contact</div>
                        <div class="grid grid-cols-2 gap-2">
                            <input v-model="newContactForm.first_name" type="text" placeholder="First Name *" class="w-full h-8 px-2 text-[12px] rounded border border-shell-border focus:border-dept-sales-main focus:outline-none" />
                            <input v-model="newContactForm.last_name" type="text" placeholder="Last Name" class="w-full h-8 px-2 text-[12px] rounded border border-shell-border focus:border-dept-sales-main focus:outline-none" />
                        </div>
                        <input v-model="newContactForm.job_title" type="text" placeholder="Job Title" class="w-full h-8 px-2 text-[12px] rounded border border-shell-border focus:border-dept-sales-main focus:outline-none" />
                        <input v-model="newContactForm.email" type="email" placeholder="Email" class="w-full h-8 px-2 text-[12px] rounded border border-shell-border focus:border-dept-sales-main focus:outline-none" />
                        <div class="flex gap-1">
                            <select v-model="newContactForm.phone_code" class="w-[80px] h-8 px-1 rounded border border-shell-border text-[12px] bg-slate-50 focus:outline-none focus:border-dept-sales-main">
                                <option v-for="c in countryCodes" :key="c.code" :value="c.code">{{ c.code }} ({{ c.country }})</option>
                            </select>
                            <input v-model="newContactForm.phone" type="tel" placeholder="Phone" class="flex-1 h-8 px-2 text-[12px] rounded border border-shell-border focus:border-dept-sales-main focus:outline-none" />
                        </div>
                        <div class="flex gap-1">
                            <select v-model="newContactForm.whatsapp_code" class="w-[80px] h-8 px-1 rounded border border-shell-border text-[12px] bg-slate-50 focus:outline-none focus:border-dept-sales-main">
                                <option v-for="c in countryCodes" :key="c.code" :value="c.code">{{ c.code }} ({{ c.country }})</option>
                            </select>
                            <input v-model="newContactForm.whatsapp" type="tel" placeholder="WhatsApp" class="flex-1 h-8 px-2 text-[12px] rounded border border-shell-border focus:border-dept-sales-main focus:outline-none" />
                        </div>
                        
                        <div class="flex justify-end gap-2 pt-2 border-t border-shell-border mt-2">
                            <button @click="cancelAddingContact" class="px-2 py-1 text-[11px] font-medium text-text-secondary hover:bg-slate-100 rounded bg-transparent border-0 cursor-pointer">Cancel</button>
                            <button @click="saveNewContact" :disabled="!newContactForm.first_name" class="px-2 py-1 text-[11px] font-bold text-white bg-dept-sales-main hover:bg-[#CA8A04] rounded disabled:opacity-50 border-0 cursor-pointer">Save</button>
                        </div>
                    </div>
                    
                    <div v-if="account.contacts.length === 0 && !isAddingContact" class="text-[12px] text-text-disabled italic">No contacts linked.</div>
                    
                    <div class="space-y-4">
                        <div v-for="contact in account.contacts" :key="contact.id" class="text-[13px] bg-white p-3 border border-shell-border rounded-xl shadow-sm">
                            
                            <!-- Display Mode -->
                            <div v-if="editingContactId !== contact.id">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="font-bold text-text-primary">{{ contact.first_name }} {{ contact.last_name }}</div>
                                        <div v-if="contact.job_title" class="text-[11px] text-text-secondary mb-2">{{ contact.job_title }}</div>
                                    </div>
                                    <div class="flex gap-2 shrink-0">
                                        <button @click="startEditingContact(contact)" class="text-slate-400 hover:text-dept-sales-main transition-colors bg-transparent border-0 cursor-pointer p-0" title="Edit Contact">
                                            <PhPencilSimple :size="14" />
                                        </button>
                                        <button @click="deleteContact(contact.id)" class="text-slate-400 hover:text-red-600 transition-colors bg-transparent border-0 cursor-pointer p-0" title="Delete Contact">
                                            <PhTrash :size="14" />
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="flex flex-col gap-1.5 mt-2 pt-2 border-t border-shell-border">
                                    <a v-if="contact.email" :href="'mailto:'+contact.email" class="flex items-center gap-1.5 text-text-secondary hover:text-dept-sales-main transition-colors text-[12px]">
                                        <PhEnvelopeSimple /> <span class="truncate">{{ contact.email }}</span>
                                    </a>
                                    <a v-if="contact.phone" :href="'tel:'+contact.phone" class="flex items-center gap-1.5 text-text-secondary hover:text-dept-sales-main transition-colors text-[12px]">
                                        <PhPhone /> <span>{{ contact.phone }}</span>
                                    </a>
                                    <a v-if="contact.whatsapp" :href="'https://wa.me/'+contact.whatsapp.replace(/[^0-9]/g,'')" target="_blank" class="flex items-center gap-1.5 text-[#25D366] hover:text-green-700 transition-colors text-[12px] font-medium">
                                        <PhWhatsappLogo weight="fill" /> <span>WhatsApp</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Edit Mode -->
                            <div v-else class="space-y-2">
                                <div class="grid grid-cols-2 gap-2">
                                    <input v-model="contactForm.first_name" type="text" placeholder="First Name" class="w-full h-8 px-2 text-[12px] rounded border border-shell-border focus:border-dept-sales-main" />
                                    <input v-model="contactForm.last_name" type="text" placeholder="Last Name" class="w-full h-8 px-2 text-[12px] rounded border border-shell-border focus:border-dept-sales-main" />
                                </div>
                                <input v-model="contactForm.job_title" type="text" placeholder="Job Title" class="w-full h-8 px-2 text-[12px] rounded border border-shell-border focus:border-dept-sales-main" />
                                <input v-model="contactForm.email" type="email" placeholder="Email" class="w-full h-8 px-2 text-[12px] rounded border border-shell-border focus:border-dept-sales-main" />
                                <div class="flex gap-1">
                                    <select v-model="contactForm.phone_code" class="w-[80px] h-8 px-1 rounded border border-shell-border text-[12px] bg-slate-50 focus:outline-none focus:border-dept-sales-main">
                                        <option v-for="c in countryCodes" :key="c.code" :value="c.code">{{ c.code }} ({{ c.country }})</option>
                                    </select>
                                    <input v-model="contactForm.phone" type="tel" placeholder="Phone" class="flex-1 h-8 px-2 text-[12px] rounded border border-shell-border focus:border-dept-sales-main" />
                                </div>
                                <div class="flex gap-1">
                                    <select v-model="contactForm.whatsapp_code" class="w-[80px] h-8 px-1 rounded border border-shell-border text-[12px] bg-slate-50 focus:outline-none focus:border-dept-sales-main">
                                        <option v-for="c in countryCodes" :key="c.code" :value="c.code">{{ c.code }} ({{ c.country }})</option>
                                    </select>
                                    <input v-model="contactForm.whatsapp" type="tel" placeholder="WhatsApp" class="flex-1 h-8 px-2 text-[12px] rounded border border-shell-border focus:border-dept-sales-main" />
                                </div>
                                
                                <div class="flex justify-between items-center pt-2 border-t border-shell-border mt-2">
                                    <button @click="deleteContact(contact.id)" class="px-2 py-1 text-[11px] font-bold text-red-600 hover:bg-red-50 rounded bg-transparent border-0 cursor-pointer flex items-center gap-1 transition-colors p-1" title="Delete Contact Entirely">
                                        <PhTrash :size="12" /> Delete
                                    </button>
                                    <div class="flex gap-2">
                                        <button @click="cancelEditingContact" class="px-2 py-1 text-[11px] font-medium text-text-secondary hover:bg-slate-100 rounded bg-transparent border-0 cursor-pointer">Cancel</button>
                                        <button @click="saveContact" class="px-2 py-1 text-[11px] font-bold text-white bg-dept-sales-main hover:bg-[#CA8A04] rounded border-0 cursor-pointer">Save</button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>
