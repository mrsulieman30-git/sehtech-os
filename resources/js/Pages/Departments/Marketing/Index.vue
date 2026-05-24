<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import axios from 'axios';
import { 
    PhMegaphone, PhUsers, PhShareNetwork, 
    PhPlus, PhBuilding, PhChartPieSlice,
    PhPhone, PhWhatsappLogo, PhEnvelopeSimple
} from '@phosphor-icons/vue';
import { useModalStore } from '@/Stores/useModalStore';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';

dayjs.extend(relativeTime);

const modalStore = useModalStore();

// Marketing State
const accounts = ref<any[]>([]);
const contents = ref<any[]>([]);
const channels = ref<any[]>([]);

const activeTab = ref('dashboard'); // dashboard, content, leads

const fetchMarketingData = async () => {
    try {
        const response = await axios.get('/api/marketing/crm');
        accounts.value = response.data.accounts;
        contents.value = response.data.contents;
        channels.value = response.data.channels;
    } catch (error) {
        console.error(error);
    }
};

const handleRefresh = () => fetchMarketingData();

onMounted(() => {
    fetchMarketingData();
    window.addEventListener('refresh-marketing', handleRefresh);
});

onUnmounted(() => window.removeEventListener('refresh-marketing', handleRefresh));

// Metrics
const totalLeads = computed(() => accounts.value.length);
const totalContent = computed(() => contents.value.length);
const activeChannels = computed(() => channels.value.length);
</script>

<template>
    <div class="h-full flex flex-row bg-shell-panel text-text-primary overflow-hidden font-sans">
        
        <!-- Sidebar Navigation -->
        <div class="w-[260px] flex-shrink-0 border-r border-shell-border bg-shell-panel flex flex-col h-full">
            <div class="h-[56px] flex items-center px-6 border-b border-shell-border bg-white shrink-0">
                <h1 class="text-[15px] font-bold text-dept-marketing-main flex items-center gap-2">
                    <PhMegaphone :size="18" weight="fill" />
                    Marketing Hub
                </h1>
            </div>

            <div class="flex-1 overflow-y-auto p-4 flex flex-col gap-1">
                <button 
                    @click="activeTab = 'dashboard'"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-btn text-[13px] font-medium transition-colors"
                    :class="activeTab === 'dashboard' ? 'bg-dept-marketing-main text-white' : 'text-text-secondary hover:bg-shell-border/50 hover:text-text-primary'"
                >
                    <PhChartPieSlice :size="18" :weight="activeTab === 'dashboard' ? 'fill' : 'regular'" />
                    Performance Overview
                </button>
                <button 
                    @click="activeTab = 'content'"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-btn text-[13px] font-medium transition-colors"
                    :class="activeTab === 'content' ? 'bg-dept-marketing-main text-white' : 'text-text-secondary hover:bg-shell-border/50 hover:text-text-primary'"
                >
                    <PhShareNetwork :size="18" :weight="activeTab === 'content' ? 'fill' : 'regular'" />
                    Content & Channels
                </button>
                <button 
                    @click="activeTab = 'leads'"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-btn text-[13px] font-medium transition-colors"
                    :class="activeTab === 'leads' ? 'bg-dept-marketing-main text-white' : 'text-text-secondary hover:bg-shell-border/50 hover:text-text-primary'"
                >
                    <PhUsers :size="18" :weight="activeTab === 'leads' ? 'fill' : 'regular'" />
                    Lead Generation
                </button>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 bg-[#F8FAFC]">
            
            <!-- Dashboard Tab -->
            <div v-if="activeTab === 'dashboard'" class="flex-1 flex flex-col h-full overflow-auto p-8">
                <div class="flex justify-between items-end mb-8">
                    <div>
                        <h2 class="text-[20px] font-bold text-text-primary mb-1">Marketing Performance</h2>
                        <p class="text-[13px] text-text-secondary">Overview of your marketing efforts and top-of-funnel generation.</p>
                    </div>
                    <button @click="modalStore.openModal('create-campaign')" class="flex items-center gap-2 px-4 py-2 bg-dept-marketing-main text-white text-[13px] font-medium rounded-btn hover:bg-[#E11D48] transition-colors shadow-sm">
                        <PhPlus :size="14" weight="bold" /> New Campaign
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-2xl border border-shell-border shadow-sm flex flex-col gap-2 relative overflow-hidden">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-dept-marketing-main/10 rounded-full blur-2xl"></div>
                        <div class="flex items-center gap-2 text-text-secondary text-[14px] font-medium z-10"><PhUsers :size="18" /> Total Leads Generated</div>
                        <div class="text-[32px] font-black text-dept-marketing-main z-10">{{ totalLeads }}</div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl border border-shell-border shadow-sm flex flex-col gap-2">
                        <div class="flex items-center gap-2 text-text-secondary text-[14px] font-medium"><PhShareNetwork :size="18" /> Active Channels</div>
                        <div class="text-[32px] font-black text-text-primary">{{ activeChannels }}</div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl border border-shell-border shadow-sm flex flex-col gap-2">
                        <div class="flex items-center gap-2 text-text-secondary text-[14px] font-medium"><PhMegaphone :size="18" /> Content Assets</div>
                        <div class="text-[32px] font-black text-text-primary">{{ totalContent }}</div>
                    </div>
                </div>

                <div class="bg-white border border-shell-border rounded-2xl p-6 shadow-sm">
                    <h3 class="font-bold text-[15px] mb-6 border-b border-shell-border pb-4">Recent Lead Gen Accounts</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="acc in accounts.slice(0, 6)" :key="acc.id" class="p-4 bg-shell-panel border border-shell-border rounded-xl">
                            <div class="font-bold text-[14px]">{{ acc.name }}</div>
                            <div class="text-[12px] text-text-secondary mt-1 flex items-center gap-1">
                                <PhBuilding :size="12" /> {{ acc.industry || 'Unknown Industry' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content & Channels Tab -->
            <div v-else-if="activeTab === 'content'" class="flex-1 flex flex-col h-full overflow-auto p-8">
                <div class="flex justify-between items-end mb-8">
                    <div>
                        <h2 class="text-[20px] font-bold text-text-primary mb-1">Marketing Content</h2>
                        <p class="text-[13px] text-text-secondary">Manage blog posts, videos, case studies and social channels.</p>
                    </div>
                    <button @click="modalStore.openModal('create-crm-content')" class="flex items-center gap-2 px-4 py-2 bg-dept-marketing-main text-white text-[13px] font-medium rounded-btn hover:bg-[#E11D48] transition-colors shadow-sm">
                        <PhPlus :size="14" weight="bold" /> Create Content
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <div v-for="content in contents" :key="content.id" class="bg-white border border-shell-border rounded-xl overflow-hidden hover:shadow-card transition-all flex flex-col">
                        <div class="h-24 bg-gradient-to-br from-dept-marketing-main/10 to-dept-marketing-main/5 border-b border-shell-border/50 flex items-center justify-center">
                            <PhMegaphone class="text-dept-marketing-main/40" :size="32" />
                        </div>
                        <div class="p-4 flex flex-col flex-1">
                            <span class="text-[10px] font-bold uppercase text-dept-marketing-main tracking-wider mb-2">{{ content.type.replace('_', ' ') }}</span>
                            <h3 class="text-[14px] font-bold text-text-primary leading-snug mb-2 flex-1">{{ content.title }}</h3>
                            <div class="flex items-center justify-between text-[11px] text-text-disabled mt-4 pt-4 border-t border-shell-border">
                                <span>{{ dayjs(content.created_at).format('MMM D, YYYY') }}</span>
                                <span class="px-2 py-0.5 rounded-sm bg-slate-100 text-text-secondary">{{ content.status }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="contents.length === 0" class="col-span-full py-12 text-center text-text-disabled">
                        No marketing content published yet.
                    </div>
                </div>
            </div>

            <!-- Leads Tab -->
            <div v-else-if="activeTab === 'leads'" class="flex-1 flex flex-col h-full overflow-auto p-8">
                <div class="flex justify-between items-end mb-8">
                    <div>
                        <h2 class="text-[20px] font-bold text-text-primary mb-1">Lead Generation</h2>
                        <p class="text-[13px] text-text-secondary">Accounts and leads brought in by marketing campaigns.</p>
                    </div>
                    <button @click="modalStore.openModal('create-crm-account')" class="flex items-center gap-2 px-4 py-2 bg-dept-marketing-main text-white text-[13px] font-medium rounded-btn hover:bg-[#E11D48] transition-colors shadow-sm">
                        <PhPlus :size="14" weight="bold" /> Generate Lead
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="acc in accounts" :key="acc.id" class="bg-white border border-shell-border rounded-xl p-5 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="font-bold text-text-primary text-[15px] truncate max-w-[180px]">{{ acc.name }}</h3>
                                <span class="text-[12px] text-text-disabled">{{ acc.industry || 'Unknown Industry' }}</span>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase" :class="acc.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'">
                                {{ acc.status }}
                            </span>
                        </div>
                        <div class="space-y-3 mt-4 pt-4 border-t border-shell-border">
                            <div class="flex items-center justify-between text-[11px] font-bold text-text-disabled uppercase">
                                <span>Contacts Collected ({{ acc.contacts?.length || 0 }})</span>
                            </div>
                            <div v-if="acc.contacts?.length" class="flex flex-col gap-2">
                                <div v-for="contact in acc.contacts" :key="contact.id" class="text-[12px] bg-slate-50 p-2 border border-shell-border rounded">
                                    <div class="font-bold text-text-primary">{{ contact.first_name }} {{ contact.last_name }}</div>
                                    <div class="flex flex-col gap-1 mt-1">
                                        <div v-if="contact.email" class="flex items-center gap-1.5 text-text-secondary">
                                            <PhEnvelopeSimple /> {{ contact.email }}
                                        </div>
                                        <div v-if="contact.phone && !contact.whatsapp" class="flex items-center gap-1.5 text-text-secondary">
                                            <PhPhone /> {{ contact.phone }}
                                        </div>
                                        <div v-if="contact.whatsapp" class="flex items-center gap-1.5 text-[#25D366] font-medium">
                                            <PhWhatsappLogo weight="fill" /> {{ contact.whatsapp }}
                                        </div>
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
