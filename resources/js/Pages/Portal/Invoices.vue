<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PortalLayout from './Layouts/PortalLayout.vue';
import dayjs from 'dayjs';

interface Invoice {
    id: string; invoice_number: string; status: string;
    total_amount: string; issue_date: string; due_date: string;
}

const props = defineProps<{ invoices: Invoice[] }>();

const formatCurrency = (amount: string | number) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(amount));
};

const getStatusColor = (s: string) => {
    const colors: Record<string, string> = {
        'paid': 'text-green-600 bg-green-50',
        'sent': 'text-blue-600 bg-blue-50',
        'overdue': 'text-red-600 bg-red-50',
        'draft': 'text-gray-500 bg-gray-100',
        'partially_paid': 'text-orange-600 bg-orange-50',
    };
    return colors[s] || colors['draft'];
};
</script>

<template>
    <Head title="Invoices — SEHTECH Portal" />
    <PortalLayout>
        <div class="mb-6">
            <h1 class="text-[24px] font-bold text-gray-900">Invoices & Billing</h1>
            <p class="text-[14px] text-gray-500 mt-0.5">Review your invoices and payment history.</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-[12px] text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-3 font-semibold">Invoice #</th>
                        <th class="px-6 py-3 font-semibold text-right">Amount</th>
                        <th class="px-6 py-3 font-semibold text-center">Status</th>
                        <th class="px-6 py-3 font-semibold">Issue Date</th>
                        <th class="px-6 py-3 font-semibold">Due Date</th>
                    </tr>
                </thead>
                <tbody class="text-[13px]">
                    <tr v-if="invoices.length === 0">
                        <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                            No invoices found for your account.
                        </td>
                    </tr>
                    <tr v-for="invoice in invoices" :key="invoice.id" class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-mono font-semibold text-gray-900">{{ invoice.invoice_number }}</td>
                        <td class="px-6 py-4 text-right font-semibold text-gray-900">{{ formatCurrency(invoice.total_amount) }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 rounded-lg text-[11px] font-semibold capitalize" :class="getStatusColor(invoice.status)">{{ invoice.status.replace('_', ' ') }}</span>
                        </td>
                        <td class="px-6 py-4 text-gray-500">{{ dayjs(invoice.issue_date).format('MMM D, YYYY') }}</td>
                        <td class="px-6 py-4" :class="dayjs(invoice.due_date).isBefore(dayjs()) && invoice.status !== 'paid' ? 'text-red-500 font-semibold' : 'text-gray-500'">
                            {{ dayjs(invoice.due_date).format('MMM D, YYYY') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PortalLayout>
</template>
