<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { 
    PhLifebuoy, PhWarningCircle, PhChatCircleText, PhClock,
    PhPaperPlaneRight, PhRobot, PhCaretRight, PhTag, PhArrowRight,
    PhChartLineUp, PhUsers, PhDatabase, PhShieldCheck, PhFiles,
    PhTrendUp, PhTrendDown, PhCheckCircle, PhX, PhStethoscope,
    PhActivity, PhDesktop, PhShareNetwork
} from '@phosphor-icons/vue';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import { useModalStore } from '@/Stores/useModalStore';
import { useToastStore } from '@/Stores/useToastStore';
import RichMessageRenderer from '@/Components/Ai/RichMessageRenderer.vue';

const modalStore = useModalStore();
const toastStore = useToastStore();
const showMobileAi = ref(false);

dayjs.extend(relativeTime);

interface Ticket {
    id: string; ticket_number: string; title: string; priority: string; status: string;
    product: string; sla_due_date: string; created_at: string;
    client: { first_name: string; last_name: string; organization: string | null; } | null;
    replies_count: number;
}

const activeTab = ref('dashboard');
const tickets = ref<Ticket[]>([]);
const incidents = ref<any[]>([]);
const deployments = ref<any[]>([]);
const slas = ref<any[]>([]);
const escalations = ref<any[]>([]);
const kbArticles = ref<any[]>([]);

const metrics = ref({ 
    open_tickets: 0, escalated: 0, sla_breached: 0, 
    active_incidents: 0, csat_score: 0, avg_response_time: '', avg_resolution_time: ''
});
const selectedTicket = ref<Ticket | null>(null);

const fetchDashboard = async () => {
    try {
        const response = await axios.get('/api/support/dashboard');
        tickets.value = response.data.tickets;
        incidents.value = response.data.incidents;
        deployments.value = response.data.deployments;
        slas.value = response.data.slas;
        escalations.value = response.data.escalations;
        kbArticles.value = response.data.kb_articles;
        metrics.value = response.data.metrics;
        
        if (tickets.value.length > 0 && !selectedTicket.value) {
            selectedTicket.value = tickets.value[0];
        }
    } catch (error) {
        console.error('Failed to fetch support data', error);
    }
};

const changeTab = (tab: string) => {
    activeTab.value = tab;
};

const getPriorityColor = (priority: string) => {
    const p: Record<string, string> = { 'p0': 'bg-red-600 text-white', 'p1': 'bg-orange-500 text-white', 'p2': 'bg-blue-500 text-white', 'p3': 'bg-slate-200 text-slate-700' };
    return p[priority] || p['p2'];
};

const getIncidentColor = (severity: string) => {
    const p: Record<string, string> = { 'P1': 'bg-red-50 text-red-700 border-red-200', 'P2': 'bg-orange-50 text-orange-700 border-orange-200', 'P3': 'bg-yellow-50 text-yellow-700 border-yellow-200', 'P4': 'bg-slate-50 text-slate-700 border-slate-200' };
    return p[severity] || p['P3'];
};

const escalateTicket = async (id: string) => {
    if(!confirm('Escalate this ticket to development?')) return;
    try {
        await axios.post(`/api/support/tickets/${id}/escalate`);
        toastStore.showToast('Ticket escalated to DevOps', 'warning');
        fetchDashboard();
    } catch (e) {
        toastStore.showToast('Failed to escalate', 'error');
    }
};

const resolveTicket = async (id: string) => {
    if(!confirm('Mark this ticket as resolved?')) return;
    try {
        await axios.post(`/api/support/tickets/${id}/resolve`);
        toastStore.showToast('Ticket resolved successfully', 'success');
        fetchDashboard();
    } catch (e) {
        toastStore.showToast('Failed to resolve', 'error');
    }
};

// Support Agent Chat logic
const chatMessage = ref('');
const chatMessages = ref<{ role: string; content: string }>([
    {
        role: 'assistant',
        content: 'I am NEXUS, the Enterprise Support AI. I can triage tickets, detect duplicate incidents, and suggest root cause fixes. How can I assist you today?'
    }
]);
const isChatTyping = ref(false);
const chatScrollContainer = ref<HTMLElement | null>(null);

const scrollToBottom = () => {
    setTimeout(() => {
        if (chatScrollContainer.value) {
            chatScrollContainer.value.scrollTop = chatScrollContainer.value.scrollHeight;
        }
    }, 50);
};

const sendChatMessage = async () => {
    if (!chatMessage.value.trim() || isChatTyping.value) return;
    
    const userMsg = chatMessage.value;
    chatMessages.value.push({ role: 'user', content: userMsg });
    chatMessage.value = '';
    isChatTyping.value = true;
    scrollToBottom();
    
    try {
        const response = await axios.post('/api/agents/support-agent/chat', {
            message: userMsg
        });
        chatMessages.value.push({ role: 'assistant', content: response.data.response_text });
    } catch (e) {
        chatMessages.value.push({ 
            role: 'assistant', 
            content: 'NEXUS is currently offline or analyzing heavy logs. Please try again later.' 
        });
    } finally {
        isChatTyping.value = false;
        scrollToBottom();
    }
};

const handleDashboardRefresh = () => fetchDashboard();

onMounted(() => {
    fetchDashboard();
    window.addEventListener('refresh-support-dashboard', handleDashboardRefresh);
    scrollToBottom();
});

onUnmounted(() => {
    window.removeEventListener('refresh-support-dashboard', handleDashboardRefresh);
});
</script>

<template>
    <div class="h-full flex flex-col lg:flex-row bg-shell-panel text-text-primary overflow-hidden font-sans relative">
        
        <!-- Sidebar Navigation -->
        <div class="w-full lg:w-[240px] flex-shrink-0 border-b lg:border-b-0 lg:border-r border-shell-border bg-shell-panel flex flex-col lg:h-full z-20">
            <div class="h-[48px] lg:h-[56px] flex items-center px-4 lg:px-6 border-b border-shell-border bg-white shrink-0 gap-3">
                <PhStethoscope :size="24" class="text-dept-support-main" weight="fill" />
                <h2 class="text-[15px] font-bold text-text-primary">Support Center</h2>
            </div>
            
            <div class="flex-none lg:flex-1 overflow-x-auto lg:overflow-y-auto p-2 lg:p-3 flex flex-row lg:flex-col gap-1 whitespace-nowrap scrollbar-hide">
                <button 
                    @click="changeTab('dashboard')"
                    class="w-auto lg:w-full flex items-center gap-2 lg:gap-3 px-3 py-2 lg:py-2.5 rounded-btn text-[13px] font-medium transition-colors border-0 bg-transparent cursor-pointer text-left"
                    :class="activeTab === 'dashboard' ? 'bg-dept-support-main/10 text-dept-support-main font-bold' : 'text-text-secondary hover:bg-shell-border/50'"
                >
                    <PhChartLineUp :size="18" :weight="activeTab === 'dashboard' ? 'fill' : 'regular'" />
                    Overview
                </button>
                <button 
                    @click="changeTab('tickets')"
                    class="w-auto lg:w-full flex items-center gap-2 lg:gap-3 px-3 py-2 lg:py-2.5 rounded-btn text-[13px] font-medium transition-colors border-0 bg-transparent cursor-pointer text-left"
                    :class="activeTab === 'tickets' ? 'bg-dept-support-main/10 text-dept-support-main font-bold' : 'text-text-secondary hover:bg-shell-border/50'"
                >
                    <PhLifebuoy :size="18" :weight="activeTab === 'tickets' ? 'fill' : 'regular'" />
                    Ticket Queue
                    <span v-if="metrics.open_tickets" class="ml-2 lg:ml-auto w-5 h-5 rounded-full bg-red-500 text-white text-[10px] flex items-center justify-center font-bold">{{ metrics.open_tickets }}</span>
                </button>
                <button 
                    @click="changeTab('incidents')"
                    class="w-auto lg:w-full flex items-center gap-2 lg:gap-3 px-3 py-2 lg:py-2.5 rounded-btn text-[13px] font-medium transition-colors border-0 bg-transparent cursor-pointer text-left"
                    :class="activeTab === 'incidents' ? 'bg-dept-support-main/10 text-dept-support-main font-bold' : 'text-text-secondary hover:bg-shell-border/50'"
                >
                    <PhWarningCircle :size="18" :weight="activeTab === 'incidents' ? 'fill' : 'regular'" />
                    Incidents War Room
                </button>
                <button 
                    @click="changeTab('customers')"
                    class="w-auto lg:w-full flex items-center gap-2 lg:gap-3 px-3 py-2 lg:py-2.5 rounded-btn text-[13px] font-medium transition-colors border-0 bg-transparent cursor-pointer text-left"
                    :class="activeTab === 'customers' ? 'bg-dept-support-main/10 text-dept-support-main font-bold' : 'text-text-secondary hover:bg-shell-border/50'"
                >
                    <PhDatabase :size="18" :weight="activeTab === 'customers' ? 'fill' : 'regular'" />
                    Deployments 360
                </button>
                <button 
                    @click="changeTab('kb')"
                    class="w-auto lg:w-full flex items-center gap-2 lg:gap-3 px-3 py-2 lg:py-2.5 rounded-btn text-[13px] font-medium transition-colors border-0 bg-transparent cursor-pointer text-left"
                    :class="activeTab === 'kb' ? 'bg-dept-support-main/10 text-dept-support-main font-bold' : 'text-text-secondary hover:bg-shell-border/50'"
                >
                    <PhFiles :size="18" :weight="activeTab === 'kb' ? 'fill' : 'regular'" />
                    Knowledge Base
                </button>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col h-full bg-[#F8FAFC] overflow-hidden border-r-0 lg:border-r border-shell-border z-10">
            <div class="h-[56px] bg-white border-b border-shell-border flex items-center px-4 lg:px-6 shrink-0 justify-between gap-2 overflow-x-auto hide-scrollbar">
                <h2 class="text-[16px] font-bold text-text-primary capitalize whitespace-nowrap">
                    {{ activeTab === 'kb' ? 'Knowledge Base' : activeTab.replace('-', ' ') }}
                </h2>
                <div class="flex gap-2 flex-shrink-0">
                    <button v-if="activeTab === 'tickets'" @click="modalStore.openModal('new-ticket')" class="px-3 lg:px-4 py-1.5 bg-dept-support-main text-white text-[12px] lg:text-[13px] font-medium rounded-btn hover:opacity-90 transition-colors shadow-sm border-0 cursor-pointer whitespace-nowrap">
                        + New Ticket
                    </button>
                    <button v-if="activeTab === 'incidents'" @click="modalStore.openModal('declare-outage')" class="px-3 lg:px-4 py-1.5 bg-red-600 text-white text-[12px] lg:text-[13px] font-medium rounded-btn hover:bg-red-700 transition-colors shadow-sm border-0 cursor-pointer whitespace-nowrap flex items-center gap-1.5">
                        <PhWarningCircle /> Declare Outage
                    </button>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="flex-1 overflow-y-auto p-4 lg:p-6 pb-20 lg:pb-6">
                
                <!-- Overview Tab -->
                <div v-if="activeTab === 'dashboard'" class="space-y-6">
                    <!-- KPI Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-white p-5 rounded-card border border-shell-border shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[12px] font-bold text-text-disabled uppercase">CSAT Score</span>
                                <PhTrendUp class="text-green-500" :size="16" />
                            </div>
                            <div class="text-[28px] font-black text-text-primary">{{ metrics.csat_score }}%</div>
                            <div class="text-[11px] text-text-secondary mt-1">Global satisfaction (30 days)</div>
                        </div>

                        <div class="bg-white p-5 rounded-card border border-shell-border shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[12px] font-bold text-text-disabled uppercase">Open Tickets</span>
                                <PhLifebuoy class="text-dept-support-main" :size="16" />
                            </div>
                            <div class="text-[28px] font-black text-text-primary">{{ metrics.open_tickets }}</div>
                            <div class="text-[11px] text-text-secondary mt-1">Awaiting resolution</div>
                        </div>

                        <div class="bg-white p-5 rounded-card border border-red-200 shadow-sm bg-red-50/30">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[12px] font-bold text-red-700 uppercase">SLA Breaches</span>
                                <PhWarningCircle class="text-red-600" :size="16" />
                            </div>
                            <div class="text-[28px] font-black text-red-600">{{ metrics.sla_breached }}</div>
                            <div class="text-[11px] text-red-700/80 mt-1">Requires immediate attention</div>
                        </div>

                        <div class="bg-white p-5 rounded-card border border-shell-border shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[12px] font-bold text-text-disabled uppercase">Active Incidents</span>
                                <PhActivity class="text-orange-500" :size="16" />
                            </div>
                            <div class="text-[28px] font-black text-orange-600">{{ metrics.active_incidents }}</div>
                            <div class="text-[11px] text-text-secondary mt-1">System-wide outages</div>
                        </div>
                    </div>

                    <!-- Layout for Dashboard elements -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- SLAs & Resolution Times -->
                        <div class="bg-white border border-shell-border rounded-card shadow-sm p-5">
                            <h3 class="font-bold text-[14px] mb-4 text-text-primary border-b border-shell-border pb-2 flex items-center gap-1.5"><PhClock /> Operational SLAs</h3>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-[13px] text-text-secondary">Average First Response</span>
                                    <span class="text-[14px] font-bold text-text-primary">{{ metrics.avg_response_time }}</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-1.5">
                                    <div class="bg-green-500 h-1.5 rounded-full" style="width: 85%"></div>
                                </div>
                                
                                <div class="flex justify-between items-center mt-4">
                                    <span class="text-[13px] text-text-secondary">Average Resolution Time</span>
                                    <span class="text-[14px] font-bold text-text-primary">{{ metrics.avg_resolution_time }}</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-1.5">
                                    <div class="bg-blue-500 h-1.5 rounded-full" style="width: 70%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Escalations -->
                        <div class="bg-white border border-shell-border rounded-card shadow-sm p-5">
                            <h3 class="font-bold text-[14px] mb-4 text-text-primary border-b border-shell-border pb-2 flex items-center gap-1.5"><PhShareNetwork /> Tier 3 Escalations</h3>
                            <div class="space-y-3">
                                <div v-if="!escalations.length" class="text-[13px] text-text-disabled">No active escalations.</div>
                                <div v-for="esc in escalations.slice(0, 4)" :key="esc.id" class="flex items-center justify-between p-3 border border-shell-border rounded-lg bg-slate-50">
                                    <div>
                                        <div class="text-[13px] font-bold text-text-primary">{{ esc.ticket?.ticket_number || 'Unknown Ticket' }}</div>
                                        <div class="text-[11px] text-text-secondary mt-0.5">Assigned to: {{ esc.escalated_to_team }}</div>
                                    </div>
                                    <span class="text-[10px] font-bold px-2 py-1 rounded bg-orange-100 text-orange-700 border border-orange-200">Pending</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tickets Tab -->
                <div v-else-if="activeTab === 'tickets'" class="h-full flex flex-col md:flex-row gap-4">
                    <!-- Inbox -->
                    <div class="w-full md:w-[350px] flex flex-col gap-2 shrink-0 h-full overflow-y-auto">
                        <div v-if="!tickets.length" class="text-[12px] text-text-disabled p-8 text-center bg-white border border-shell-border rounded-card">
                            Queue is empty. Great job!
                        </div>
                        <div 
                            v-for="ticket in tickets" 
                            :key="ticket.id"
                            @click="selectedTicket = ticket"
                            class="p-3.5 rounded-card border border-shell-border bg-white cursor-pointer hover:border-dept-support-main transition-colors shadow-sm"
                            :class="{'ring-1 ring-dept-support-main border-dept-support-main': selectedTicket?.id === ticket.id}"
                        >
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-[11px] font-bold text-text-secondary">{{ ticket.ticket_number }}</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase" :class="getPriorityColor(ticket.priority)">
                                    {{ ticket.priority }}
                                </span>
                            </div>
                            <div class="text-[13px] font-semibold text-text-primary leading-tight mb-1.5">{{ ticket.title }}</div>
                            <div class="text-[11px] text-text-secondary flex justify-between items-center mt-3 pt-2 border-t border-shell-border">
                                <span class="font-medium text-dept-support-main truncate max-w-[120px]">{{ ticket.client?.organization || ticket.client?.first_name || 'Internal' }}</span>
                                <span v-if="ticket.sla_due_date" class="flex items-center gap-1 font-medium" :class="dayjs(ticket.sla_due_date).isBefore(dayjs()) ? 'text-red-600' : 'text-slate-500'">
                                    <PhClock :size="12" /> {{ dayjs(ticket.sla_due_date).fromNow() }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Ticket Detail -->
                    <div class="flex-1 bg-white border border-shell-border rounded-card shadow-sm flex flex-col overflow-hidden h-[600px] md:h-auto">
                        <template v-if="selectedTicket">
                            <div class="p-4 border-b border-shell-border flex items-center justify-between shrink-0 bg-slate-50">
                                <div>
                                    <h3 class="text-[16px] font-bold text-text-primary">{{ selectedTicket.ticket_number }}</h3>
                                    <div class="text-[12px] text-text-secondary mt-0.5">{{ selectedTicket.status.replace('_', ' ') }}</div>
                                </div>
                                <div class="flex gap-2">
                                    <button @click="escalateTicket(selectedTicket.id)" class="px-3 py-1.5 bg-white border border-red-300 text-red-600 hover:bg-red-50 text-[12px] font-bold rounded cursor-pointer transition-colors">
                                        Escalate
                                    </button>
                                    <button @click="resolveTicket(selectedTicket.id)" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white border border-green-700 text-[12px] font-bold rounded cursor-pointer transition-colors shadow-sm">
                                        Resolve
                                    </button>
                                </div>
                            </div>
                            
                            <div class="flex-1 p-5 overflow-y-auto bg-white flex flex-col gap-6">
                                <div class="bg-slate-50 p-4 rounded-xl border border-shell-border">
                                    <h4 class="text-[16px] font-bold text-text-primary mb-3">{{ selectedTicket.title }}</h4>
                                    <p class="text-[14px] text-text-secondary leading-relaxed">Client description goes here. Real-time fetch required to display full thread history and attachments.</p>
                                </div>
                            </div>
                            
                            <div class="p-4 border-t border-shell-border shrink-0 bg-white">
                                <div class="relative">
                                    <textarea rows="3" placeholder="Write reply to customer or internal note..." class="w-full p-3 pr-12 bg-slate-50 border border-shell-border rounded-xl text-[13px] focus:ring-1 focus:ring-dept-support-main outline-none resize-none"></textarea>
                                    <button class="absolute right-3 bottom-3 p-2 bg-dept-support-main text-white rounded-lg hover:bg-opacity-90 transition-colors shadow-sm cursor-pointer border-0">
                                        <PhPaperPlaneRight :size="16" weight="fill" />
                                    </button>
                                </div>
                            </div>
                        </template>
                        <div v-else class="flex-1 flex flex-col items-center justify-center text-text-disabled">
                            <PhChatCircleText :size="48" weight="thin" class="mb-3" />
                            <p class="text-[14px]">Select a ticket to view thread</p>
                        </div>
                    </div>
                </div>

                <!-- Incidents Tab -->
                <div v-else-if="activeTab === 'incidents'" class="space-y-6">
                    <div class="bg-white rounded-card border border-shell-border shadow-sm overflow-hidden overflow-x-auto">
                        <table class="w-full min-w-[800px] text-left border-collapse">
                            <thead>
                                <tr class="bg-shell-panel border-b border-shell-border text-[12px] text-text-disabled uppercase tracking-wider">
                                    <th class="px-6 py-3 font-semibold">Incident ID</th>
                                    <th class="px-6 py-3 font-semibold">Title & Type</th>
                                    <th class="px-6 py-3 font-semibold">Severity</th>
                                    <th class="px-6 py-3 font-semibold">Status</th>
                                    <th class="px-6 py-3 font-semibold">Downtime</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-shell-border">
                                <tr v-if="!incidents.length">
                                    <td colspan="5" class="px-6 py-8 text-center text-text-disabled text-[13px]">No active incidents. Systems are operational.</td>
                                </tr>
                                <tr v-for="inc in incidents" :key="inc.id" class="hover:bg-shell-panel/50 transition-colors">
                                    <td class="px-6 py-4 text-[13px] font-bold text-text-primary">{{ inc.incident_number }}</td>
                                    <td class="px-6 py-4">
                                        <div class="text-[13px] font-bold text-text-primary mb-0.5">{{ inc.title }}</div>
                                        <div class="text-[11px] text-text-secondary uppercase">{{ inc.type.replace('_', ' ') }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 rounded text-[10px] font-bold border" :class="getIncidentColor(inc.severity)">{{ inc.severity }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-[12px] font-bold capitalize" :class="inc.status === 'resolved' ? 'text-green-600' : 'text-orange-600'">{{ inc.status }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-[13px] text-text-secondary">
                                        {{ inc.resolved_at ? 'Resolved' : dayjs(inc.started_at).fromNow(true) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Customers Tab -->
                <div v-else-if="activeTab === 'customers'" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="dep in deployments" :key="dep.id" class="bg-white p-5 rounded-card border border-shell-border shadow-sm flex flex-col h-full">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-bold text-[15px] text-text-primary">{{ dep.client?.organization || dep.name }}</h3>
                                    <div class="text-[12px] text-text-secondary flex items-center gap-1 mt-1">
                                        <PhDatabase :size="14" /> {{ dep.deployment_type.replace('_', ' ').toUpperCase() }}
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                                    v{{ dep.software_version }}
                                </span>
                            </div>
                            
                            <div class="mt-auto space-y-3 pt-4 border-t border-shell-border">
                                <div class="flex justify-between text-[12px]">
                                    <span class="text-text-secondary">Status</span>
                                    <span class="font-bold text-green-600 flex items-center gap-1"><div class="w-2 h-2 rounded-full bg-green-500"></div> {{ dep.status }}</span>
                                </div>
                                <div class="flex justify-between text-[12px]">
                                    <span class="text-text-secondary">SLA Tier</span>
                                    <span class="font-bold text-text-primary capitalize">{{ dep.sla_tier }}</span>
                                </div>
                                <div class="flex justify-between text-[12px]">
                                    <span class="text-text-secondary">Contract Expiry</span>
                                    <span class="font-bold text-text-primary">{{ dayjs(dep.contract_expires_at).format('MMM YYYY') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Knowledge Base Tab -->
                <div v-else-if="activeTab === 'kb'" class="space-y-6">
                    <div class="bg-white border border-shell-border rounded-card p-5 flex items-center justify-between mb-6 shadow-sm">
                        <div class="flex-1 relative max-w-xl">
                            <input type="text" placeholder="Search knowledge base articles, manuals..." class="w-full pl-10 pr-4 py-2 border border-shell-border rounded-input text-[13px] outline-none focus:ring-1 focus:ring-dept-support-main" />
                            <PhFiles class="absolute left-3 top-1/2 -translate-y-1/2 text-text-disabled" :size="18" />
                        </div>
                        <button @click="modalStore.openModal('create-kb-article')" class="px-4 py-2 bg-dept-support-main text-white font-bold text-[13px] rounded-btn shadow-sm border-0 cursor-pointer">
                            + Create Article
                        </button>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div v-if="!kbArticles.length" class="text-[13px] text-text-disabled col-span-2">No articles published yet.</div>
                        <div v-for="kb in kbArticles" :key="kb.id" class="bg-white border border-shell-border rounded-card p-5 hover:border-dept-support-main transition-colors cursor-pointer shadow-sm">
                            <h4 class="font-bold text-[15px] text-dept-support-main mb-1.5">{{ kb.title }}</h4>
                            <p class="text-[13px] text-text-secondary line-clamp-2 mb-4">{{ kb.content }}</p>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200 uppercase">{{ kb.category }}</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Right AI Sidebar: Nexus -->
        <div 
            class="fixed inset-0 z-50 bg-white flex flex-col transition-transform duration-300 lg:relative lg:inset-auto lg:z-10 lg:w-[320px] lg:flex-shrink-0 lg:translate-x-0"
            :class="showMobileAi ? 'translate-x-0' : 'translate-x-full'"
        >
            <div class="h-[48px] lg:h-[56px] flex items-center justify-between px-4 border-b border-shell-border bg-dept-support-main text-white shrink-0 gap-2">
                <div class="flex items-center gap-2">
                    <PhRobot :size="20" weight="fill" />
                    <div class="flex flex-col">
                        <span class="text-[14px] font-bold tracking-wide leading-tight">SUPPORT NEXUS</span>
                        <span class="hidden lg:block text-[10px] text-white/70 uppercase font-semibold">Enterprise AI</span>
                    </div>
                </div>
                <button @click="showMobileAi = false" class="lg:hidden p-2 hover:bg-white/20 rounded-btn cursor-pointer border-0 bg-transparent text-white">
                    <PhX :size="20" />
                </button>
            </div>
            
            <div class="flex-1 p-4 overflow-y-auto text-[12px] flex flex-col gap-3 bg-white" ref="chatScrollContainer">
                <div v-for="(m, idx) in chatMessages" :key="idx" :class="m.role === 'user' ? 'text-right' : 'text-left'">
                    <div class="inline-block p-3 rounded-card shadow-sm text-text-secondary max-w-[95%] leading-relaxed text-left" 
                         :class="m.role === 'user' ? 'bg-dept-support-main/10 text-dept-support-main border border-dept-support-main/20 rounded-br-none' : 'bg-shell-panel border border-shell-border rounded-bl-none'">
                        <RichMessageRenderer :content="m.content" />
                    </div>
                </div>
                <div v-if="isChatTyping" class="text-[11px] text-text-disabled animate-pulse flex items-center gap-1.5 pl-1">
                    <PhRobot :size="14" class="animate-bounce text-dept-support-main" /> NEXUS is thinking...
                </div>
            </div>

            <div class="p-3 border-t border-shell-border bg-shell-panel shrink-0">
                <div class="relative">
                    <input 
                        v-model="chatMessage"
                        @keyup.enter="sendChatMessage"
                        type="text" 
                        placeholder="Ask Nexus to triage..." 
                        class="w-full pl-3 pr-10 py-2.5 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-support-main outline-none" 
                    />
                    <button @click="sendChatMessage" class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 text-dept-support-main hover:text-[#C2410C] transition-colors cursor-pointer bg-transparent border-0 flex items-center justify-center">
                        <PhArrowRight :size="16" weight="bold" />
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile FAB -->
        <button 
            v-if="!showMobileAi"
            @click="showMobileAi = true; scrollToBottom()"
            class="lg:hidden absolute bottom-6 right-6 w-14 h-14 bg-dept-support-main text-white rounded-full shadow-2xl flex items-center justify-center z-40 border-0 cursor-pointer active:scale-95 transition-transform"
        >
            <PhRobot :size="28" weight="fill" />
        </button>
    </div>
</template>
