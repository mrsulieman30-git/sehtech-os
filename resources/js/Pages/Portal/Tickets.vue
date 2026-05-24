<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import PortalLayout from '@/Pages/Portal/Layouts/PortalLayout.vue';
import { useI18n } from 'vue-i18n';
import { PhLifebuoy, PhCheckCircle, PhClock } from '@phosphor-icons/vue';
import dayjs from 'dayjs';

const { t } = useI18n();

interface Ticket {
    id: string;
    ticket_number: string;
    title: string;
    status: string;
    updated_at: string;
}

const props = defineProps<{
    tickets: Ticket[];
}>();

const form = useForm({
    title: '',
    description: '',
    product: '',
});

const submitTicket = () => {
    form.post(route('portal.tickets.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const getStatusColor = (status: string) => {
    const maps: Record<string, string> = {
        'open': 'bg-blue-100 text-blue-700',
        'in_progress': 'bg-orange-100 text-orange-700',
        'escalated': 'bg-red-100 text-red-700',
        'resolved': 'bg-green-100 text-green-700',
        'closed': 'bg-gray-100 text-gray-700',
    };
    return maps[status] || 'bg-gray-100 text-gray-700';
};
</script>

<template>
    <Head title="Support Tickets | Portal" />

    <PortalLayout>
        <div class="max-w-6xl mx-auto">
            
            <!-- Page Header -->
            <div class="mb-8 flex items-center gap-3">
                <PhLifebuoy :size="28" class="text-[#2563EB]" weight="fill" />
                <h1 class="text-2xl font-bold text-gray-900">{{ t('tickets.title') }}</h1>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                
                <!-- LEFT: New Ticket Form (Sticky Sidebar) -->
                <div class="lg:col-span-1 bg-white p-6 rounded-xl border border-gray-200 shadow-sm sticky top-28">
                    <h2 class="font-bold text-gray-900 mb-6 text-lg">{{ t('tickets.new_ticket') }}</h2>
                    
                    <form @submit.prevent="submitTicket" class="space-y-5">
                        
                        <!-- Product Selector -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-semibold text-gray-700">{{ t('tickets.product') }}</label>
                            <select v-model="form.product" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-[#2563EB]/20 focus:border-[#2563EB] outline-none transition-all">
                                <option value="" disabled>{{ t('tickets.placeholders.product') }}</option>
                                <option value="HMS">Hospital Management System (HMS)</option>
                                <option value="Clinic MS">Clinic MS</option>
                                <option value="Pharmacy MS">Pharmacy MS</option>
                                <option value="Dental MS">Dental MS</option>
                            </select>
                            <p v-if="form.errors.product" class="text-red-500 text-xs mt-0.5">{{ form.errors.product }}</p>
                        </div>

                        <!-- Ticket Title -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-semibold text-gray-700">{{ t('tickets.ticket_title') }}</label>
                            <input 
                                v-model="form.title" 
                                type="text" 
                                required
                                :placeholder="t('tickets.placeholders.title')"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-[#2563EB]/20 focus:border-[#2563EB] outline-none transition-all"
                            />
                            <p v-if="form.errors.title" class="text-red-500 text-xs mt-0.5">{{ form.errors.title }}</p>
                        </div>

                        <!-- Description -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-semibold text-gray-700">{{ t('tickets.description') }}</label>
                            <textarea 
                                v-model="form.description" 
                                required
                                rows="4"
                                :placeholder="t('tickets.placeholders.description')"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-[#2563EB]/20 focus:border-[#2563EB] outline-none resize-none transition-all"
                            ></textarea>
                            <p v-if="form.errors.description" class="text-red-500 text-xs mt-0.5">{{ form.errors.description }}</p>
                        </div>

                        <!-- Submit -->
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="w-full flex items-center justify-center py-3 bg-[#2563EB] text-white rounded-lg text-sm font-bold hover:bg-blue-700 transition-colors shadow-sm disabled:opacity-50"
                        >
                            {{ form.processing ? 'Submitting...' : t('tickets.submit') }}
                        </button>
                    </form>
                </div>
                
                <!-- RIGHT: Ticket History List -->
                <div class="lg:col-span-2">
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                            <h3 class="font-bold text-gray-900 text-sm">Ticket History</h3>
                        </div>
                        
                        <!-- Empty State -->
                        <div v-if="!tickets || tickets.length === 0" class="p-12 text-center flex flex-col items-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4 text-gray-400">
                                <PhCheckCircle :size="32" weight="duotone" />
                            </div>
                            <h3 class="text-gray-900 font-bold mb-1">All caught up</h3>
                            <p class="text-sm text-gray-500">{{ t('tickets.no_tickets') }}</p>
                        </div>

                        <!-- Ticket Rows -->
                        <div v-else class="divide-y divide-gray-200">
                            <div 
                                v-for="ticket in tickets" 
                                :key="ticket.id" 
                                class="p-6 hover:bg-blue-50/50 cursor-pointer transition-colors group flex items-start justify-between"
                            >
                                <div>
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="text-xs font-bold text-gray-500">{{ ticket.ticket_number }}</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider" :class="getStatusColor(ticket.status)">
                                            {{ ticket.status.replace('_', ' ') }}
                                        </span>
                                    </div>
                                    <h4 class="font-bold text-gray-900 text-[15px] group-hover:text-[#2563EB] transition-colors">{{ ticket.title }}</h4>
                                    <div class="text-xs text-gray-500 mt-2 flex items-center gap-1">
                                        <PhClock :size="14" /> {{ t('tickets.last_updated') }} {{ dayjs(ticket.updated_at).format('MMM D, YYYY') }}
                                    </div>
                                </div>
                                <button class="px-4 py-2 border border-gray-200 rounded-lg text-xs font-bold text-gray-700 bg-white hover:border-[#2563EB] hover:text-[#2563EB] transition-colors shrink-0">
                                    View Thread
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </PortalLayout>
</template>
