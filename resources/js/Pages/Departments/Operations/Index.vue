<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { 
    PhCpu, PhHardDrives, PhGlobe, PhShieldCheck,
    PhWarning, PhRobot, PhCaretRight, PhPlus 
} from '@phosphor-icons/vue';
import dayjs from 'dayjs';
import { useModalStore } from '@/Stores/useModalStore';

const modalStore = useModalStore();

const assets = ref<any[]>([]);
const metrics = ref({ total_assets: 0, expiring_30_days: 0, monthly_cost: 0 });

const fetchDashboard = async () => {
    try {
        const response = await axios.get('/api/operations/dashboard');
        assets.value = response.data.assets;
        metrics.value = response.data.metrics;
    } catch (error) {
        console.error(error);
    }
};

const getTypeIcon = (type: string) => {
    const icons: Record<string, any> = { 'server': PhHardDrives, 'domain': PhGlobe, 'ssl': PhShieldCheck };
    return icons[type] || PhCpu;
};

const handleRefresh = () => fetchDashboard();

onMounted(() => {
    fetchDashboard();
    window.addEventListener('refresh-ops-dashboard', handleRefresh);
});

onUnmounted(() => window.removeEventListener('refresh-ops-dashboard', handleRefresh));
</script>

<template>
    <div class="h-full flex flex-row bg-shell-panel text-text-primary overflow-hidden font-sans">
        
        <div class="flex-1 flex flex-col h-full bg-[#F8FAFC] overflow-hidden border-r border-shell-border">
            <div class="h-[56px] bg-white border-b border-shell-border flex items-center px-6 shrink-0 justify-between">
                <div class="flex items-center gap-3">
                    <PhCpu :size="24" class="text-dept-ops-main" weight="fill" />
                    <h2 class="text-[16px] font-semibold text-text-primary">Operations & Infrastructure</h2>
                </div>
                <button @click="modalStore.openModal('add-asset')" class="px-4 py-1.5 bg-dept-ops-main text-white text-[13px] font-medium rounded-btn hover:bg-[#475569] transition-colors shadow-sm flex items-center gap-2">
                    <PhPlus :size="16" weight="bold" /> Track New Asset
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-6">
                
                <div class="grid grid-cols-3 gap-6 mb-8">
                    <div class="bg-white p-5 rounded-card border border-shell-border shadow-sm flex flex-col">
                        <span class="text-[13px] font-medium text-text-secondary mb-1">Total Assets Tracked</span>
                        <span class="text-[24px] font-bold text-text-primary">{{ metrics.total_assets }}</span>
                    </div>
                    <div class="bg-white p-5 rounded-card border border-state-warning/30 shadow-sm flex flex-col">
                        <span class="text-[13px] font-medium text-text-secondary mb-1">Expiring in 30 Days</span>
                        <span class="text-[24px] font-bold text-state-warning">{{ metrics.expiring_30_days }}</span>
                    </div>
                    <div class="bg-white p-5 rounded-card border border-shell-border shadow-sm flex flex-col">
                        <span class="text-[13px] font-medium text-text-secondary mb-1">Total Monthly Cost</span>
                        <span class="text-[24px] font-bold text-text-primary">${{ Number(metrics.monthly_cost).toFixed(2) }}</span>
                    </div>
                </div>

                <div class="bg-white border border-shell-border rounded-card shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-shell-border bg-shell-panel font-semibold text-[14px]">Infrastructure Renewals</div>
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white border-b border-shell-border text-[12px] text-text-disabled uppercase tracking-wider">
                                <th class="px-6 py-3 font-semibold">Asset Name</th>
                                <th class="px-6 py-3 font-semibold">Provider</th>
                                <th class="px-6 py-3 font-semibold">Cost</th>
                                <th class="px-6 py-3 font-semibold">Expiry Date</th>
                                <th class="px-6 py-3 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-[13px]">
                            <tr v-if="assets.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-text-disabled">No infrastructure assets tracked.</td>
                            </tr>
                            <tr v-for="asset in assets" :key="asset.id" class="border-b border-shell-border hover:bg-shell-panel/50">
                                <td class="px-6 py-4 font-medium text-text-primary flex items-center gap-3">
                                    <component :is="getTypeIcon(asset.type)" :size="18" class="text-dept-ops-main" />
                                    {{ asset.name }}
                                </td>
                                <td class="px-6 py-4">{{ asset.provider }}</td>
                                <td class="px-6 py-4 font-medium">${{ Number(asset.cost).toFixed(2) }}</td>
                                <td class="px-6 py-4 text-text-secondary">{{ dayjs(asset.expiry_date).format('MMM D, YYYY') }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-0.5 rounded-tag text-[10px] font-bold uppercase border bg-state-success/10 text-state-success border-state-success/20">
                                        {{ asset.status.replace('_', ' ') }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
