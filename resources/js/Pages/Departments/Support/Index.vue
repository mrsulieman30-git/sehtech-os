<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { 
    PhLifebuoy, PhWarningCircle, PhChatCircleText, PhClock,
    PhPaperPlaneRight, PhRobot, PhCaretRight, PhTag
} from '@phosphor-icons/vue';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import { useModalStore } from '@/Stores/useModalStore';

const modalStore = useModalStore();

dayjs.extend(relativeTime);

interface Ticket {
    id: string; ticket_number: string; title: string; priority: string; status: string;
    product: string; sla_due_date: string; created_at: string;
    client: { first_name: string; last_name: string; organization: string | null; } | null;
    replies_count: number;
}

const tickets = ref<Ticket[]>([]);
const metrics = ref({ open_tickets: 0, escalated: 0, sla_breached: 0 });
const selectedTicket = ref<Ticket | null>(null);

const fetchDashboard = async () => {
    try {
        const response = await axios.get('/api/support/dashboard');
        tickets.value = response.data.tickets;
        metrics.value = response.data.metrics;
    } catch (error) {
        console.error('Failed to fetch support data', error);
    }
};

const getPriorityColor = (priority: string) => {
    const p: Record<string, string> = { 'p0': 'bg-state-error text-white', 'p1': 'bg-state-warning text-white', 'p2': 'bg-state-info text-white', 'p3': 'bg-shell-border text-text-secondary' };
    return p[priority] || p['p2'];
};

const escalateTicket = async (id: string) => {
    if(!confirm('Escalate this ticket to development?')) return;
    try {
        await axios.post(`/api/support/tickets/${id}/escalate`);
        fetchDashboard();
    } catch (e) {
        console.error(e);
    }
};

const triggerAIToast = (msg: string) => {
    alert(`HELPER AI: ${msg}`);
};

const handleDashboardRefresh = () => fetchDashboard();

onMounted(() => {
    fetchDashboard();
    window.addEventListener('refresh-support-dashboard', handleDashboardRefresh);
});
</script>

<template>
    <div class="h-full flex flex-row bg-shell-panel text-text-primary overflow-hidden font-sans">
        
        <!-- LEFT: Ticket Inbox -->
        <div class="w-[320px] flex-shrink-0 border-r border-shell-border bg-shell-panel flex flex-col h-full">
            <div class="h-[56px] flex items-center px-6 border-b border-shell-border bg-white shrink-0 gap-3">
                <PhLifebuoy :size="24" class="text-dept-support-main" weight="fill" />
                <h2 class="text-[15px] font-bold text-text-primary">Support Desk</h2>
            </div>
            
            <div class="flex-1 overflow-y-auto p-3 flex flex-col gap-2">
                <div class="flex justify-between items-center px-2 mb-1">
                    <span class="text-[11px] font-bold text-text-disabled uppercase tracking-wider">Active Inbox</span>
                    <span class="bg-state-error text-white text-[10px] font-bold px-2 py-0.5 rounded-full">{{ metrics.open_tickets }}</span>
                </div>

                <div v-if="tickets.length === 0" class="text-[12px] text-text-disabled px-2 py-8 text-center">
                    <PhLifebuoy :size="32" weight="thin" class="mx-auto mb-2 text-shell-border" />
                    No tickets in the queue.
                </div>
                
                <div 
                    v-for="ticket in tickets" 
                    :key="ticket.id"
                    @click="selectedTicket = ticket"
                    class="p-3 rounded-card border border-shell-border bg-white cursor-pointer hover:border-dept-support-main transition-colors"
                    :class="{'ring-1 ring-dept-support-main border-dept-support-main': selectedTicket?.id === ticket.id}"
                >
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-[11px] font-bold text-text-disabled">{{ ticket.ticket_number }}</span>
                        <span class="px-2 py-0.5 rounded-tag text-[10px] font-bold uppercase" :class="getPriorityColor(ticket.priority)">
                            {{ ticket.priority }}
                        </span>
                    </div>
                    <div class="text-[13px] font-semibold text-text-primary leading-tight mb-1 line-clamp-2">{{ ticket.title }}</div>
                    <div class="text-[11px] text-text-secondary flex justify-between items-center mt-2">
                        <span>{{ ticket.client?.organization || ticket.client?.first_name || 'Internal' }}</span>
                        <span v-if="ticket.sla_due_date" class="flex items-center gap-1 text-state-error font-medium">
                            <PhClock :size="12" /> {{ dayjs(ticket.sla_due_date).fromNow() }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- CENTER: Ticket Detail & Reply -->
        <div class="flex-1 flex flex-col h-full bg-white overflow-hidden border-r border-shell-border">
            <template v-if="selectedTicket">
                <div class="h-[60px] border-b border-shell-border flex items-center px-6 shrink-0 justify-between bg-shell-panel">
                    <div class="flex items-center gap-3">
                        <span class="px-2.5 py-1 rounded border border-shell-border text-[11px] font-bold uppercase bg-white">{{ selectedTicket.status.replace('_', ' ') }}</span>
                        <h2 class="text-[16px] font-semibold text-text-primary">{{ selectedTicket.ticket_number }}</h2>
                    </div>
                    <div class="flex gap-2">
                        <button @click="escalateTicket(selectedTicket.id)" class="px-4 py-1.5 bg-white border border-state-error text-state-error text-[13px] font-medium rounded-btn hover:bg-state-error/10 transition-colors">
                            Escalate to Dev
                        </button>
                        <button @click="modalStore.openModal('resolve-ticket', { ticketId: selectedTicket.id })" class="px-4 py-1.5 bg-state-success text-white text-[13px] font-medium rounded-btn hover:bg-opacity-90 transition-colors shadow-sm flex items-center gap-2">
                            Resolve Ticket
                        </button>
                    </div>
                </div>
                
                <div class="flex-1 overflow-y-auto p-6 bg-[#F8FAFC] flex flex-col gap-6">
                    <div class="bg-white border border-shell-border rounded-card p-5 shadow-sm">
                        <div class="flex items-center gap-2 mb-3 border-b border-shell-border pb-3">
                            <div class="w-8 h-8 rounded-full bg-dept-support-sec text-dept-support-main flex items-center justify-center font-bold text-[12px]">
                                {{ selectedTicket.client?.first_name?.charAt(0) || 'C' }}
                            </div>
                            <div>
                                <div class="text-[13px] font-bold text-text-primary">{{ selectedTicket.client?.organization || selectedTicket.client?.first_name || 'Client' }}</div>
                                <div class="text-[11px] text-text-secondary">{{ dayjs(selectedTicket.created_at).format('MMM D, YYYY h:mm A') }}</div>
                            </div>
                        </div>
                        <h3 class="text-[16px] font-bold text-text-primary mb-2">{{ selectedTicket.title }}</h3>
                        <p class="text-[14px] text-text-secondary leading-relaxed whitespace-pre-wrap">Client issue description will render here. Currently fetching details...</p>
                    </div>
                </div>

                <div class="p-4 bg-white border-t border-shell-border shrink-0">
                    <div class="flex gap-4">
                        <div class="flex-1 relative">
                            <textarea rows="3" placeholder="Type your reply to the client..." class="w-full p-3 pr-12 bg-[#F8FAFC] border border-shell-border rounded-card text-[13px] focus:ring-1 focus:ring-dept-support-main outline-none resize-none"></textarea>
                            <button class="absolute right-3 bottom-3 p-1.5 bg-dept-support-main text-white rounded hover:bg-[#C2410C] transition-colors">
                                <PhPaperPlaneRight :size="14" weight="fill" />
                            </button>
                        </div>
                    </div>
                </div>
            </template>
            <div v-else class="flex-1 flex items-center justify-center text-text-disabled flex-col">
                <PhLifebuoy :size="64" weight="thin" class="mb-4 text-shell-border" />
                <p class="text-[14px]">Select a ticket from the inbox to view details.</p>
            </div>
        </div>

        <!-- RIGHT: HELPER AI Agent -->
        <div class="w-[300px] flex-shrink-0 bg-white flex flex-col h-full z-10">
            <div class="h-[56px] flex items-center px-4 border-b border-shell-border bg-dept-support-main text-white shrink-0 gap-2">
                <PhRobot :size="20" weight="fill" />
                <div class="flex flex-col">
                    <span class="text-[14px] font-bold tracking-wide leading-tight">HELPER</span>
                    <span class="text-[10px] text-white/70 uppercase font-semibold">Support Triage AI</span>
                </div>
            </div>
            
            <div class="flex-1 overflow-y-auto p-4 flex flex-col gap-4 text-[13px] bg-[#F8FAFC]">
                <div class="p-4 bg-white border border-shell-border rounded-card shadow-sm text-text-primary">
                    I found <strong class="text-dept-support-main">3 KB articles</strong> related to this issue. Would you like me to draft a response using them?
                </div>

                <div class="flex flex-col gap-2">
                    <button @click="triggerAIToast('Drafting response based on KB articles...')" class="w-full px-4 py-2.5 bg-white border border-shell-border hover:border-dept-support-main hover:text-dept-support-main text-text-primary font-medium rounded-btn transition-colors text-left flex items-center justify-between group">
                        Draft Client Reply <PhCaretRight :size="14" class="opacity-0 group-hover:opacity-100" />
                    </button>
                    <button @click="triggerAIToast('Searching knowledge base for solutions...')" class="w-full px-4 py-2.5 bg-white border border-shell-border hover:border-dept-support-main hover:text-dept-support-main text-text-primary font-medium rounded-btn transition-colors text-left flex items-center justify-between group">
                        Search Knowledge Base <PhTag :size="14" />
                    </button>
                </div>
            </div>

            <div class="p-4 bg-white border-t border-shell-border shrink-0">
                <div class="relative">
                    <input type="text" placeholder="Ask Helper..." class="w-full pl-3 pr-10 py-2.5 bg-[#F8FAFC] border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-support-main outline-none" />
                    <button class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 text-text-disabled hover:text-dept-support-main transition-colors">
                        <PhRobot :size="18" weight="fill" />
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>
