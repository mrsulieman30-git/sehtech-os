<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { PhX, PhPencilSimple, PhCheckCircle, PhPhone, PhWhatsappLogo, PhEnvelopeSimple, PhTrash, PhFileDoc } from '@phosphor-icons/vue';
import { useToastStore } from '@/Stores/useToastStore';
import { countryCodesList } from '@/Utils/Countries';
import dayjs from 'dayjs';

const props = defineProps<{
    dealId: string;
}>();

const emit = defineEmits(['close']);
const toastStore = useToastStore();

const deal = ref<any>(null);
const activities = ref<any[]>([]);
const isLoading = ref(true);

const editMode = ref(false);
const form = ref({
    title: '',
    value: '',
    expected_close_date: '',
    priority: '',
    description: '',
    requirements: '',
    payment_type: 'one_time',
    recurring_frequency: '',
    recurring_amount: '',
    collection_date: ''
});
const isSaving = ref(false);

const countryCodes = countryCodesList;
const editingContactId = ref<string | null>(null);
const contactForm = ref<any>({});

const splitPhoneNumber = (fullNumber: string) => {
    if (!fullNumber) return { code: '+1', number: '' };
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
            crm_account_id: deal.value.account.id,
            phone: newContactForm.value.phone_code ? `${newContactForm.value.phone_code}${newContactForm.value.phone}` : newContactForm.value.phone,
            whatsapp: newContactForm.value.whatsapp_code ? `${newContactForm.value.whatsapp_code}${newContactForm.value.whatsapp}` : newContactForm.value.whatsapp,
        };
        const response = await axios.post('/api/sales/contacts', payload);
        deal.value.account.contacts.push(response.data.contact);
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
        const index = deal.value.account.contacts.findIndex((c: any) => c.id === contactForm.value.id);
        if (index !== -1) {
            deal.value.account.contacts[index] = response.data.contact;
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
        deal.value.account.contacts = deal.value.account.contacts.filter((c: any) => c.id !== contactId);
        toastStore.showToast('Contact deleted successfully', 'success');
        window.dispatchEvent(new CustomEvent('refresh-sales-crm'));
    } catch (error: any) {
        toastStore.showToast('Failed to delete contact', 'error');
    }
};

onMounted(async () => {
    try {
        const res = await axios.get(`/api/sales/deals/${props.dealId}`);
        deal.value = res.data.deal;
        activities.value = res.data.activities;
        
        form.value = {
            title: deal.value.title || '',
            value: deal.value.value || '',
            expected_close_date: deal.value.expected_close_date ? dayjs(deal.value.expected_close_date).format('YYYY-MM-DD') : '',
            priority: deal.value.priority || 'medium',
            description: deal.value.description || '',
            requirements: deal.value.requirements || '',
            payment_type: deal.value.payment_type || 'one_time',
            recurring_frequency: deal.value.recurring_frequency || '',
            recurring_amount: deal.value.recurring_amount || '',
            collection_date: deal.value.collection_date ? dayjs(deal.value.collection_date).format('YYYY-MM-DD') : ''
        };
        isLoading.value = false;
    } catch (e) {
        toastStore.addToast('error', 'Failed to load deal details.');
        emit('close');
    } finally {
        isLoading.value = false;
    }
});

const saveChanges = async () => {
    isSaving.value = true;
    try {
        const res = await axios.put(`/api/sales/deals/${props.dealId}`, form.value);
        deal.value = res.data.deal;
        editMode.value = false;
        toastStore.addToast('success', 'Deal updated successfully.');
        window.dispatchEvent(new CustomEvent('refresh-sales-crm'));
        window.dispatchEvent(new CustomEvent('refresh-finance-dashboard'));
    } catch (e) {
        toastStore.addToast('error', 'Failed to update deal.');
    } finally {
        isSaving.value = false;
    }
};

const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val);
};
</script>

<template>
    <div class="bg-white rounded-xl w-[700px] max-w-[95vw] shadow-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200" style="max-height: 90vh;">
        
        <!-- Header -->
        <div class="h-[60px] px-6 border-b border-shell-border flex items-center justify-between shrink-0 bg-slate-50">
            <div class="flex items-center gap-3">
                <h3 class="text-[16px] font-bold text-text-primary">Deal Details</h3>
                <span v-if="deal" class="px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider"
                      :class="{
                          'bg-red-100 text-red-600': deal.priority === 'high',
                          'bg-amber-100 text-amber-600': deal.priority === 'medium',
                          'bg-blue-100 text-blue-600': deal.priority === 'low'
                      }">
                    {{ deal.priority }} Priority
                </span>
            </div>
            <button @click="$emit('close')" class="w-8 h-8 flex items-center justify-center rounded-btn hover:bg-slate-200 text-text-secondary transition-colors">
                <PhX :size="16" />
            </button>
        </div>

        <div v-if="isLoading" class="p-12 flex justify-center text-text-disabled">
            Loading...
        </div>
        
        <div v-else class="flex-1 overflow-y-auto flex flex-col md:flex-row">
            
            <!-- Main Content Left -->
            <div class="flex-1 p-6 border-r border-shell-border space-y-6">
                <div class="flex items-center justify-between border-b border-shell-border pb-2">
                    <h4 class="text-[14px] font-bold text-text-primary">General Information</h4>
                    <button v-if="!editMode" @click="editMode = true" class="text-[12px] font-bold text-dept-sales-main flex items-center gap-1 hover:text-[#CA8A04] transition-colors">
                        <PhPencilSimple /> Edit
                    </button>
                    <div v-else class="flex items-center gap-2">
                        <button @click="editMode = false" class="text-[12px] font-medium text-text-secondary hover:text-text-primary">Cancel</button>
                        <button @click="saveChanges" :disabled="isSaving" class="text-[12px] font-bold text-green-600 flex items-center gap-1 hover:text-green-700">
                            <PhCheckCircle /> Save
                        </button>
                    </div>
                </div>

                <div v-if="!editMode" class="space-y-4">
                    <div>
                        <div class="text-[11px] font-bold text-text-disabled uppercase">Deal Title</div>
                        <div class="text-[15px] font-medium text-text-primary mt-1">{{ deal.title }}</div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-[11px] font-bold text-text-disabled uppercase">Estimated Value</div>
                            <div class="text-[14px] font-bold text-green-600 mt-1">{{ formatCurrency(deal.value || 0) }}</div>
                        </div>
                        <div>
                            <div class="text-[11px] font-bold text-text-disabled uppercase">Expected Close</div>
                            <div class="text-[14px] font-medium text-text-primary mt-1">{{ deal.expected_close_date ? dayjs(deal.expected_close_date).format('MMM D, YYYY') : 'Not Set' }}</div>
                        </div>
                    </div>
                    
                    <!-- Financial Terms -->
                    <div class="grid grid-cols-2 gap-4 bg-slate-50 p-3 rounded-lg border border-shell-border">
                        <div>
                            <div class="text-[11px] font-bold text-text-disabled uppercase">Payment Type</div>
                            <div class="text-[13px] font-medium text-text-primary mt-1 capitalize">{{ deal.payment_type ? deal.payment_type.replace('_', '-') : 'One-Time' }}</div>
                        </div>
                        <div>
                            <div class="text-[11px] font-bold text-text-disabled uppercase">Collection Date</div>
                            <div class="text-[13px] font-medium text-text-primary mt-1">{{ deal.collection_date ? dayjs(deal.collection_date).format('MMM D, YYYY') : 'N/A' }}</div>
                        </div>
                        <template v-if="deal.payment_type === 'recurring'">
                            <div>
                                <div class="text-[11px] font-bold text-text-disabled uppercase">Recurring Frequency</div>
                                <div class="text-[13px] font-medium text-text-primary mt-1 capitalize">{{ deal.recurring_frequency }}</div>
                            </div>
                            <div>
                                <div class="text-[11px] font-bold text-text-disabled uppercase">Recurring Amount</div>
                                <div class="text-[13px] font-medium text-green-600 mt-1">{{ formatCurrency(deal.recurring_amount || 0) }}</div>
                            </div>
                        </template>
                    </div>

                    <!-- Contract Document -->
                    <div v-if="deal.contract_file_path" class="mt-4">
                        <div class="text-[11px] font-bold text-text-disabled uppercase mb-1">Legal Contract</div>
                        <a :href="`/storage/${deal.contract_file_path}`" target="_blank" class="inline-flex items-center gap-2 px-3 py-2 bg-slate-50 hover:bg-slate-100 border border-shell-border rounded-btn text-[13px] text-dept-sales-main font-medium transition-colors">
                            <PhFileDoc /> Download Contract
                        </a>
                    </div>

                    <div>
                        <div class="text-[11px] font-bold text-text-disabled uppercase">Customer Requirements</div>
                        <div class="text-[13px] text-text-secondary mt-1 whitespace-pre-wrap bg-slate-50 p-3 rounded-lg border border-shell-border">{{ deal.requirements || 'No requirements outlined.' }}</div>
                    </div>

                    <div>
                        <div class="text-[11px] font-bold text-text-disabled uppercase">Description</div>
                        <div class="text-[13px] text-text-secondary mt-1 whitespace-pre-wrap">{{ deal.description || 'No description provided.' }}</div>
                    </div>
                </div>

                <!-- Edit Form -->
                <div v-else class="space-y-4">
                    <div>
                        <label class="block text-[11px] font-bold text-text-disabled uppercase mb-1">Title *</label>
                        <input v-model="form.title" type="text" class="w-full h-9 px-3 rounded border border-shell-border text-[13px] focus:border-dept-sales-main" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-text-disabled uppercase mb-1">Value ($)</label>
                            <input v-model="form.value" type="number" step="0.01" class="w-full h-9 px-3 rounded border border-shell-border text-[13px] focus:border-dept-sales-main" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-text-disabled uppercase mb-1">Expected Close</label>
                            <input v-model="form.expected_close_date" type="date" class="w-full h-9 px-3 rounded border border-shell-border text-[13px] focus:border-dept-sales-main" />
                        </div>
                    </div>

                    <!-- Financial Terms Edit -->
                    <div class="grid grid-cols-2 gap-4 bg-slate-50 p-3 rounded-lg border border-shell-border">
                        <div class="col-span-2">
                            <label class="block text-[11px] font-bold text-text-disabled uppercase mb-1">Payment Type</label>
                            <select v-model="form.payment_type" class="w-full h-9 px-3 rounded border border-shell-border text-[13px] focus:border-dept-sales-main bg-white">
                                <option value="one_time">One-Time</option>
                                <option value="recurring">Recurring</option>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-[11px] font-bold text-text-disabled uppercase mb-1">Collection Date</label>
                            <input v-model="form.collection_date" type="date" class="w-full h-9 px-3 rounded border border-shell-border text-[13px] focus:border-dept-sales-main bg-white" />
                        </div>
                        <template v-if="form.payment_type === 'recurring'">
                            <div>
                                <label class="block text-[11px] font-bold text-text-disabled uppercase mb-1">Frequency</label>
                                <select v-model="form.recurring_frequency" class="w-full h-9 px-3 rounded border border-shell-border text-[13px] focus:border-dept-sales-main bg-white">
                                    <option value="monthly">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-text-disabled uppercase mb-1">Recur Amount ($)</label>
                                <input v-model="form.recurring_amount" type="number" step="0.01" class="w-full h-9 px-3 rounded border border-shell-border text-[13px] focus:border-dept-sales-main" />
                            </div>
                        </template>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-text-disabled uppercase mb-1">Requirements</label>
                        <textarea v-model="form.requirements" rows="3" class="w-full p-3 rounded border border-shell-border text-[13px] focus:border-dept-sales-main"></textarea>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-text-disabled uppercase mb-1">Priority</label>
                        <select v-model="form.priority" class="w-full h-9 px-3 rounded border border-shell-border text-[13px] focus:border-dept-sales-main bg-white">
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-text-disabled uppercase mb-1">Description</label>
                        <textarea v-model="form.description" rows="3" class="w-full p-3 rounded border border-shell-border text-[13px] focus:border-dept-sales-main"></textarea>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar: Contacts & Account -->
            <div class="w-full md:w-[250px] bg-slate-50 p-6 flex flex-col gap-6 shrink-0">
                <div>
                    <div class="text-[11px] font-bold text-text-disabled uppercase border-b border-shell-border pb-1 mb-3">Account</div>
                    <div class="font-bold text-[14px] text-text-primary">{{ deal.account.name }}</div>
                    <div class="text-[12px] text-text-secondary mt-0.5">{{ deal.account.industry || 'No Industry' }}</div>
                    <div class="text-[12px] text-text-secondary">{{ deal.account.city ? deal.account.city + ', ' + deal.account.country : deal.account.country }}</div>
                </div>

                <div>
                    <div class="flex items-center justify-between border-b border-shell-border pb-1 mb-3">
                        <div class="text-[11px] font-bold text-text-disabled uppercase">Contacts</div>
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
                    
                    <div v-if="!deal.account.contacts.length && !isAddingContact" class="text-[12px] text-text-disabled italic">No contacts linked.</div>
                    <div class="space-y-4">
                        <div v-for="contact in deal.account.contacts" :key="contact.id" class="text-[13px] bg-white p-3 border border-shell-border rounded-xl shadow-sm">
                            
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
