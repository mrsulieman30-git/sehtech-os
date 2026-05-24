<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PortalLayout from '@/Pages/Portal/Layouts/PortalLayout.vue';
import { useI18n } from 'vue-i18n';
import { PhLifebuoy, PhReceipt, PhFileDoc, PhArrowRight } from '@phosphor-icons/vue';

const { t, locale } = useI18n();

defineProps<{
    portalUser: any;
    metrics: {
        open_tickets: number;
        unpaid_invoices: number;
        recent_documents: number;
    };
}>();
</script>

<template>
    <Head title="Dashboard | Portal" />

    <PortalLayout>
        <div class="max-w-5xl mx-auto">
            
            <!-- Welcome Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">{{ t('dashboard.welcome') }}, {{ portalUser?.client?.first_name || portalUser?.name || 'Client' }}!</h1>
                <p class="text-sm text-gray-500 mt-1">Here is a summary of your account activity.</p>
            </div>

            <!-- Metric Cards Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                
                <!-- Active Tickets -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex flex-col relative overflow-hidden group hover:border-blue-300 transition-colors">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 rounded-lg bg-orange-50 text-orange-600">
                            <PhLifebuoy :size="24" weight="fill" />
                        </div>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 mb-1">{{ metrics.open_tickets || 0 }}</h3>
                    <p class="text-sm font-medium text-gray-500">{{ t('dashboard.active_tickets') }}</p>
                    <Link :href="route('portal.tickets')" class="absolute inset-0 opacity-0"></Link>
                </div>

                <!-- Unpaid Invoices -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex flex-col relative overflow-hidden group hover:border-blue-300 transition-colors">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 rounded-lg bg-red-50 text-red-600">
                            <PhReceipt :size="24" weight="fill" />
                        </div>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 mb-1">{{ metrics.unpaid_invoices || 0 }}</h3>
                    <p class="text-sm font-medium text-gray-500">{{ t('dashboard.unpaid_invoices') }}</p>
                    <Link :href="route('portal.invoices')" class="absolute inset-0 opacity-0"></Link>
                </div>

                <!-- Recent Documents -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex flex-col relative overflow-hidden group hover:border-blue-300 transition-colors">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 rounded-lg bg-blue-50 text-[#2563EB]">
                            <PhFileDoc :size="24" weight="fill" />
                        </div>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 mb-1">{{ metrics.recent_documents || 0 }}</h3>
                    <p class="text-sm font-medium text-gray-500">{{ t('dashboard.recent_documents') }}</p>
                    <Link :href="route('portal.documents')" class="absolute inset-0 opacity-0"></Link>
                </div>

            </div>

            <!-- Bottom Grid: Recent Tickets + Latest Invoices -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Tickets Panel -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden flex flex-col">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                        <h3 class="font-bold text-gray-900 text-sm">Recent Tickets</h3>
                        <Link :href="route('portal.tickets')" class="text-sm text-[#2563EB] hover:underline font-medium flex items-center gap-1">{{ t('dashboard.view_all') }} <PhArrowRight :size="12" :class="{'rotate-180': locale === 'ar'}"/></Link>
                    </div>
                    <div class="p-6 text-center text-gray-400 text-sm flex-1 flex items-center justify-center">
                        {{ t('tickets.no_tickets') }}
                    </div>
                </div>

                <!-- Latest Invoices Panel -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden flex flex-col">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                        <h3 class="font-bold text-gray-900 text-sm">Latest Invoices</h3>
                        <Link :href="route('portal.invoices')" class="text-sm text-[#2563EB] hover:underline font-medium flex items-center gap-1">{{ t('dashboard.view_all') }} <PhArrowRight :size="12" :class="{'rotate-180': locale === 'ar'}"/></Link>
                    </div>
                    <div class="p-6 text-center text-gray-400 text-sm flex-1 flex items-center justify-center">
                        No outstanding invoices.
                    </div>
                </div>
            </div>

        </div>
    </PortalLayout>
</template>
