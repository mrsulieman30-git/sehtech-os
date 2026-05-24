<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PortalLayout from './Layouts/PortalLayout.vue';
import dayjs from 'dayjs';

interface Document {
    id: string; title: string; status: string;
    start_date: string | null; end_date: string | null; created_at: string;
}

const props = defineProps<{ documents: Document[] }>();

const getStatusColor = (s: string) => {
    const colors: Record<string, string> = {
        'active': 'text-green-600 bg-green-50',
        'signed': 'text-blue-600 bg-blue-50',
        'sent': 'text-orange-600 bg-orange-50',
        'draft': 'text-gray-500 bg-gray-100',
        'expired': 'text-red-500 bg-red-50',
        'terminated': 'text-red-500 bg-red-50',
    };
    return colors[s] || colors['draft'];
};
</script>

<template>
    <Head title="Documents — SEHTECH Portal" />
    <PortalLayout>
        <div class="mb-6">
            <h1 class="text-[24px] font-bold text-gray-900">Contracts & Documents</h1>
            <p class="text-[14px] text-gray-500 mt-0.5">Access your signed contracts and shared documents.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-if="documents.length === 0" class="col-span-full text-center py-16 text-gray-400 bg-white rounded-xl border border-gray-200">
                No documents shared with your account yet.
            </div>

            <div v-for="doc in documents" :key="doc.id" class="bg-white border border-gray-200 rounded-xl p-5 hover:border-[#2563EB] hover:shadow-md transition-all cursor-pointer group">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-500 flex items-center justify-center text-[18px] group-hover:bg-purple-100 transition-colors">📄</div>
                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-semibold uppercase" :class="getStatusColor(doc.status)">{{ doc.status }}</span>
                </div>
                <h3 class="text-[14px] font-semibold text-gray-900 mb-1 line-clamp-2">{{ doc.title }}</h3>
                <div class="text-[12px] text-gray-400 mt-2 space-y-0.5">
                    <p v-if="doc.start_date">Effective: {{ dayjs(doc.start_date).format('MMM D, YYYY') }}</p>
                    <p v-if="doc.end_date">Expires: {{ dayjs(doc.end_date).format('MMM D, YYYY') }}</p>
                    <p v-if="!doc.start_date">Created: {{ dayjs(doc.created_at).format('MMM D, YYYY') }}</p>
                </div>
            </div>
        </div>
    </PortalLayout>
</template>
