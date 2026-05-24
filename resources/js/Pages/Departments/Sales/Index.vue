<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import axios from 'axios';
import { 
    PhHandshake, PhUsers, PhBuilding, PhPhone, PhWhatsappLogo,
    PhChartLineUp, PhPlus, PhFunnel, PhMagnifyingGlass, PhDotsThree, 
    PhKanban, PhCurrencyCircleDollar, PhTrendUp, PhEnvelopeSimple, PhActivity,
    PhChartBar
} from '@phosphor-icons/vue';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import { useModalStore } from '@/Stores/useModalStore';
import { VueDraggable } from 'vue-draggable-plus';
import { usePage } from '@inertiajs/vue3';

dayjs.extend(relativeTime);

const modalStore = useModalStore();
const auth = usePage().props.auth as any;

// Sales CRM State
const deals = ref<any[]>([]);
const accounts = ref<any[]>([]);
const activities = ref<any[]>([]);
const forecast = ref<any[]>([]);

const activeTab = ref('pipeline'); // pipeline, accounts, activities, forecast

const fetchSalesData = async () => {
    try {
        const response = await axios.get('/api/sales/crm');
        deals.value = response.data.deals;
        accounts.value = response.data.accounts;
        activities.value = response.data.activities;
        forecast.value = response.data.forecast;
        buildPipeline();
        buildForecastMetrics();
    } catch (error) {
        console.error(error);
    }
};

const formatCurrency = (amount: number | string | null) => {
    if (!amount) return '$0';
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0 }).format(Number(amount));
};

const handleRefresh = () => fetchSalesData();

onMounted(() => {
    fetchSalesData();
    window.addEventListener('refresh-sales-crm', handleRefresh);
});

onUnmounted(() => window.removeEventListener('refresh-sales-crm', handleRefresh));

// -----------------------------------------
// PIPELINE LOGIC (Deals)
// -----------------------------------------
const dealStages = [
    { id: 'lead', title: 'Lead', color: 'bg-slate-100 border-slate-300' },
    { id: 'qualified', title: 'Qualified', color: 'bg-blue-50 border-blue-300' },
    { id: 'proposal', title: 'Proposal Sent', color: 'bg-purple-50 border-purple-300' },
    { id: 'negotiation', title: 'Negotiation', color: 'bg-orange-50 border-orange-300' },
    { id: 'won', title: 'Closed Won', color: 'bg-green-50 border-green-300' },
    { id: 'lost', title: 'Closed Lost', color: 'bg-red-50 border-red-300' }
];

const pipeline = ref<Record<string, any[]>>({
    lead: [], qualified: [], proposal: [], negotiation: [], won: [], lost: []
});

const buildPipeline = () => {
    // Also map legacy Client statuses if any exist in the database
    const statusMap: Record<string, string> = {
        'lead': 'lead',
        'contacted': 'qualified',
        'demo_scheduled': 'qualified',
        'proposal_sent': 'proposal',
        'negotiation': 'negotiation',
        'closed_won': 'won',
        'closed_lost': 'lost'
    };

    pipeline.value = { lead: [], qualified: [], proposal: [], negotiation: [], won: [], lost: [] };
    deals.value.forEach(deal => {
        let mappedStage = statusMap[deal.stage] || deal.stage;
        if (pipeline.value[mappedStage]) {
            pipeline.value[mappedStage].push(deal);
        } else {
            pipeline.value.lead.push(deal);
        }
    });
};

const onDealDragEnd = async (e: any) => {
    for (const [stage, stageDeals] of Object.entries(pipeline.value)) {
        for (const deal of stageDeals) {
            let actualStage = deal.stage;
            if (['contacted', 'demo_scheduled'].includes(deal.stage)) actualStage = 'qualified';
            if (['proposal_sent'].includes(deal.stage)) actualStage = 'proposal';
            if (['closed_won'].includes(deal.stage)) actualStage = 'won';
            if (['closed_lost'].includes(deal.stage)) actualStage = 'lost';

            if (actualStage !== stage) {
                // Determine what to save back to the DB based on the UI stage mapping
                let newDbStage = stage;
                if (stage === 'won') newDbStage = 'closed_won';
                if (stage === 'lost') newDbStage = 'closed_lost';
                if (stage === 'proposal') newDbStage = 'proposal_sent';

                deal.stage = newDbStage;
                // Optimistic UI, fire async update
                axios.put(`/api/sales/deals/${deal.id}/stage`, { stage: newDbStage }).then(() => {
                    fetchSalesData(); // Refresh forecast and other metrics
                    window.dispatchEvent(new CustomEvent('refresh-finance-dashboard'));
                }).catch(err => {
                    console.error("Failed to update deal stage", err);
                    fetchSalesData(); // Revert on failure
                });
            }
        }
    }
};

const getActivityIcon = (type: string) => {
    switch (type) {
        case 'call': return PhPhone;
        case 'email': return PhEnvelopeSimple;
        case 'meeting': return PhUsers;
        case 'visit': return PhBuilding;
        default: return PhActivity;
    }
};

// -----------------------------------------
// FORECAST LOGIC
// -----------------------------------------
const forecastMetrics = ref({ totalExpected: 0, weightedPipeline: 0, wonThisMonth: 0 });
const monthlyForecast = ref<any[]>([]);

const buildForecastMetrics = () => {
    let expected = 0;
    let weighted = 0;
    let won = 0;
    const currentMonth = dayjs().format('YYYY-MM');
    const monthsMap: Record<string, number> = {};

    forecast.value.forEach(deal => {
        const value = parseFloat(deal.value || '0');
        expected += value;

        // Simple probability mapping
        let probability = 0.1; // Default
        if (['proposal_sent', 'proposal'].includes(deal.stage)) probability = 0.5;
        if (deal.stage === 'negotiation') probability = 0.8;
        if (['closed_won', 'won'].includes(deal.stage)) probability = 1.0;

        weighted += (value * probability);

        if (probability === 1.0 && dayjs(deal.expected_close_date || deal.updated_at).format('YYYY-MM') === currentMonth) {
            won += value;
        }

        if (deal.expected_close_date) {
            const m = dayjs(deal.expected_close_date).format('MMM YYYY');
            monthsMap[m] = (monthsMap[m] || 0) + (value * probability);
        }
    });

    forecastMetrics.value = { totalExpected: expected, weightedPipeline: weighted, wonThisMonth: won };
    
    monthlyForecast.value = Object.keys(monthsMap).map(k => ({
        month: k,
        value: monthsMap[k]
    })).sort((a, b) => dayjs(a.month).unix() - dayjs(b.month).unix());
};

</script>

<template>
    <div class="h-full flex flex-row bg-shell-panel text-text-primary overflow-hidden font-sans">
        
        <!-- Sidebar Navigation -->
        <div class="w-[260px] flex-shrink-0 border-r border-shell-border bg-shell-panel flex flex-col h-full">
            <div class="h-[56px] flex items-center px-6 border-b border-shell-border bg-white shrink-0">
                <h1 class="text-[15px] font-bold text-dept-sales-main flex items-center gap-2">
                    <PhCurrencyCircleDollar :size="18" weight="fill" />
                    Sales Pipeline
                </h1>
            </div>

            <div class="flex-1 overflow-y-auto p-4 flex flex-col gap-1">
                <button 
                    @click="activeTab = 'pipeline'"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-btn text-[13px] font-medium transition-colors"
                    :class="activeTab === 'pipeline' ? 'bg-dept-sales-main text-white' : 'text-text-secondary hover:bg-shell-border/50 hover:text-text-primary'"
                >
                    <PhKanban :size="18" :weight="activeTab === 'pipeline' ? 'fill' : 'regular'" />
                    Deals Kanban
                </button>
                <button 
                    @click="activeTab = 'accounts'"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-btn text-[13px] font-medium transition-colors"
                    :class="activeTab === 'accounts' ? 'bg-dept-sales-main text-white' : 'text-text-secondary hover:bg-shell-border/50 hover:text-text-primary'"
                >
                    <PhBuilding :size="18" :weight="activeTab === 'accounts' ? 'fill' : 'regular'" />
                    Accounts & Contacts
                </button>
                <button 
                    @click="activeTab = 'activities'"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-btn text-[13px] font-medium transition-colors"
                    :class="activeTab === 'activities' ? 'bg-dept-sales-main text-white' : 'text-text-secondary hover:bg-shell-border/50 hover:text-text-primary'"
                >
                    <PhActivity :size="18" :weight="activeTab === 'activities' ? 'fill' : 'regular'" />
                    Activity Timeline
                </button>
                <button 
                    @click="activeTab = 'forecast'"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-btn text-[13px] font-medium transition-colors"
                    :class="activeTab === 'forecast' ? 'bg-dept-sales-main text-white' : 'text-text-secondary hover:bg-shell-border/50 hover:text-text-primary'"
                >
                    <PhTrendUp :size="18" :weight="activeTab === 'forecast' ? 'fill' : 'regular'" />
                    Sales Forecasting
                </button>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 bg-[#F8FAFC]">
            
            <!-- Pipeline Tab -->
            <div v-if="activeTab === 'pipeline'" class="flex-1 flex flex-col h-full">
                <div class="h-[56px] flex items-center justify-between px-8 border-b border-shell-border bg-white shrink-0">
                    <h2 class="text-[16px] font-semibold text-text-primary">Active Pipeline</h2>
                    <button @click="modalStore.openModal('create-crm-deal')" class="flex items-center gap-2 px-3 py-1.5 bg-dept-sales-main text-white text-[13px] font-medium rounded-btn hover:bg-[#CA8A04] transition-colors">
                        <PhPlus :size="14" weight="bold" /> New Deal
                    </button>
                </div>
                
                <div class="flex-1 overflow-x-auto overflow-y-hidden p-6">
                    <div class="flex gap-4 h-full items-start min-w-max pb-4">
                        <div 
                            v-for="stage in dealStages" 
                            :key="stage.id"
                            class="w-[280px] h-full flex flex-col bg-shell-panel rounded-lg border border-shell-border shrink-0"
                        >
                            <div class="px-4 py-3 border-b border-shell-border bg-white rounded-t-lg flex justify-between items-center">
                                <h3 class="text-[13px] font-semibold text-text-primary uppercase tracking-wide flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full" :class="stage.color.split(' ')[0]"></div>
                                    {{ stage.title }}
                                </h3>
                                <span class="text-[12px] font-medium text-text-disabled bg-shell-border/30 px-2 py-0.5 rounded-full">
                                    {{ pipeline[stage.id]?.length || 0 }}
                                </span>
                            </div>

                            <div class="flex-1 overflow-y-auto p-3">
                                <VueDraggable
                                    v-model="pipeline[stage.id]"
                                    :animation="150"
                                    group="deals"
                                    ghost-class="opacity-50"
                                    class="min-h-[100px] h-full flex flex-col gap-3"
                                    item-key="id"
                                    @end="onDealDragEnd"
                                >
                                    <div 
                                        v-for="deal in pipeline[stage.id]" 
                                        :key="deal.id"
                                        @click="modalStore.openModal('view-deal', { dealId: deal.id })"
                                        class="p-3 bg-white rounded-card shadow-sm border border-shell-border cursor-pointer hover:shadow-md hover:border-dept-sales-main/30 transition-all flex flex-col gap-2"
                                    >
                                        <div class="flex justify-between items-start">
                                            <span class="text-[12px] font-semibold text-dept-sales-main">
                                                {{ formatCurrency(deal.value) }}
                                            </span>
                                            <img v-if="deal.assignee?.avatar" :src="deal.assignee.avatar" class="w-5 h-5 rounded-full border border-white" />
                                        </div>
                                        <h4 class="text-[13px] font-bold text-text-primary leading-tight">{{ deal.title }}</h4>
                                        <div class="flex items-center justify-between mt-1">
                                            <div class="text-[11px] text-text-secondary flex items-center gap-1">
                                                <PhBuilding :size="12" /> <span class="truncate max-w-[120px]">{{ deal.account?.name || 'Unknown' }}</span>
                                            </div>
                                            <div v-if="deal.account?.contacts?.length" class="flex items-center gap-1.5">
                                                <PhWhatsappLogo v-if="deal.account.contacts[0].whatsapp" :size="14" weight="fill" class="text-[#25D366]" title="WhatsApp Available" />
                                                <PhPhone v-if="deal.account.contacts[0].phone && !deal.account.contacts[0].whatsapp" :size="14" weight="fill" class="text-text-secondary" title="Phone Available" />
                                            </div>
                                        </div>
                                    </div>
                                </VueDraggable>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accounts & Contacts Tab -->
            <div v-else-if="activeTab === 'accounts'" class="flex-1 flex flex-col h-full">
                <div class="h-[56px] flex items-center justify-between px-8 border-b border-shell-border bg-white shrink-0">
                    <h2 class="text-[16px] font-semibold text-text-primary">CRM Accounts</h2>
                    <button @click="modalStore.openModal('create-crm-account')" class="flex items-center gap-2 px-3 py-1.5 bg-dept-sales-main text-white text-[13px] font-medium rounded-btn hover:bg-[#CA8A04] transition-colors">
                        <PhPlus :size="14" weight="bold" /> New Account
                    </button>
                </div>
                <div class="flex-1 overflow-auto p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <div v-for="acc in accounts" :key="acc.id" 
                            @click="modalStore.openModal('view-account', { accountId: acc.id })"
                            class="bg-white border border-shell-border rounded-xl p-5 hover:shadow-md cursor-pointer transition-shadow">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-bold text-text-primary text-[15px] truncate max-w-[180px]">{{ acc.name }}</h3>
                                    <span class="text-[12px] text-text-disabled">{{ acc.city ? acc.city + ', ' + acc.country : 'Unknown Location' }}</span>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-500">
                                    {{ acc.status }}
                                </span>
                            </div>
                            <div class="space-y-2 mt-4 pt-4 border-t border-shell-border">
                                <div class="flex items-center justify-between text-[12px]">
                                    <span class="text-text-disabled flex items-center gap-1"><PhUsers /> Contacts</span>
                                    <span class="font-medium text-text-secondary">{{ acc.contacts?.length || 0 }}</span>
                                </div>
                                <div class="flex items-center justify-between text-[12px]">
                                    <span class="text-text-disabled flex items-center gap-1"><PhHandshake /> Deals</span>
                                    <span class="font-medium text-text-secondary">{{ acc.deals?.length || 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Feed Tab -->
            <div v-else-if="activeTab === 'activities'" class="flex-1 flex flex-col h-full overflow-auto p-8 max-w-4xl mx-auto w-full">
                <h2 class="text-[20px] font-bold text-text-primary mb-6">Sales Activity Timeline</h2>
                
                <div class="space-y-6 relative before:absolute before:inset-0 before:ml-5 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-shell-border before:to-transparent">
                    
                    <div v-for="activity in activities" :key="activity.id" class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full border-4 border-white bg-dept-sales-sec text-dept-sales-main shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 z-10">
                            <component :is="getActivityIcon(activity.type)" :size="16" weight="fill" />
                        </div>
                        
                        <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] bg-white p-4 rounded-xl border border-shell-border shadow-sm">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-[13px] font-bold text-text-primary capitalize">{{ activity.type }}</span>
                                <span class="text-[11px] text-text-disabled">{{ dayjs(activity.created_at).fromNow() }}</span>
                            </div>
                            <p class="text-[13px] text-text-secondary leading-relaxed">{{ activity.description || 'No description provided.' }}</p>
                            <div class="mt-3 pt-3 border-t border-shell-border flex items-center gap-2 text-[11px] text-text-disabled">
                                <img v-if="activity.creator?.avatar" :src="activity.creator.avatar" class="w-4 h-4 rounded-full" />
                                <span>Logged by {{ activity.creator?.name || 'System' }}</span>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>

            <!-- Forecasting Tab -->
            <div v-else-if="activeTab === 'forecast'" class="flex-1 flex flex-col h-full overflow-auto p-8">
                <h2 class="text-[20px] font-bold text-text-primary mb-6">Revenue Forecasting</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-2xl border border-shell-border shadow-sm flex flex-col gap-2">
                        <div class="flex items-center gap-2 text-text-secondary text-[14px] font-medium"><PhHandshake :size="18" /> Total Expected</div>
                        <div class="text-[28px] font-black text-text-primary">{{ formatCurrency(forecastMetrics.totalExpected) }}</div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl border border-shell-border shadow-sm flex flex-col gap-2 relative overflow-hidden">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-dept-sales-main/10 rounded-full blur-2xl"></div>
                        <div class="flex items-center gap-2 text-text-secondary text-[14px] font-medium z-10"><PhChartBar :size="18" /> Weighted Pipeline</div>
                        <div class="text-[28px] font-black text-dept-sales-main z-10">{{ formatCurrency(forecastMetrics.weightedPipeline) }}</div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl border border-shell-border shadow-sm flex flex-col gap-2">
                        <div class="flex items-center gap-2 text-text-secondary text-[14px] font-medium"><PhCurrencyCircleDollar :size="18" /> Won This Month</div>
                        <div class="text-[28px] font-black text-green-600">{{ formatCurrency(forecastMetrics.wonThisMonth) }}</div>
                    </div>
                </div>

                <div class="bg-white border border-shell-border rounded-2xl p-6 shadow-sm">
                    <h3 class="font-bold text-[15px] mb-6 border-b border-shell-border pb-4">Weighted Expected Revenue by Month</h3>
                    <div class="flex gap-8 items-end h-[200px] mt-4">
                        <div v-for="metric in monthlyForecast" :key="metric.month" class="flex flex-col items-center gap-3 w-16 group">
                            <div class="w-full bg-dept-sales-main/10 rounded-t-sm relative transition-all group-hover:bg-dept-sales-main/30" :style="`height: ${Math.max((metric.value / (forecastMetrics.weightedPipeline || 1)) * 100, 10)}%`">
                                <div class="absolute bottom-full mb-2 opacity-0 group-hover:opacity-100 transition-opacity bg-slate-800 text-white text-[10px] py-1 px-2 rounded whitespace-nowrap z-10">
                                    {{ formatCurrency(metric.value) }}
                                </div>
                            </div>
                            <span class="text-[11px] font-medium text-text-secondary whitespace-nowrap">{{ metric.month }}</span>
                        </div>
                        <div v-if="monthlyForecast.length === 0" class="text-center w-full text-text-disabled pb-10">
                            No active forecasts available. Set Expected Close Dates on your Deals to see projections.
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>
