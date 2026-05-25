<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { 
    PhCpu, PhHardDrives, PhGlobe, PhShieldCheck,
    PhWarning, PhRobot, PhCaretRight, PhPlus 
} from '@phosphor-icons/vue';
import dayjs from 'dayjs';
import { useModalStore } from '@/Stores/useModalStore';
import RichMessageRenderer from '@/Components/Ai/RichMessageRenderer.vue';
import { PhArrowRight } from '@phosphor-icons/vue';

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

// Operations Agent Chat logic
const chatMessage = ref('');
const chatMessages = ref<{ role: string; content: string }>([
    {
        role: 'assistant',
        content: 'I am OPS COMMAND. How can I assist with infrastructure monitoring and asset management?'
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
        const response = await axios.post('/api/agents/operations-agent/chat', {
            message: userMsg
        });
        chatMessages.value.push({ role: 'assistant', content: response.data.response_text });
    } catch (e) {
        chatMessages.value.push({ 
            role: 'assistant', 
            content: 'Agent is currently offline. Please ensure the AI service is running.' 
        });
    } finally {
        isChatTyping.value = false;
        scrollToBottom();
    }
};

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
        

        <!-- Right AI Sidebar: OPS COMMAND -->
        <div class="w-[300px] flex-shrink-0 border-l border-shell-border bg-shell-panel flex flex-col h-full z-10">
            <div class="h-[56px] flex items-center px-4 border-b border-shell-border bg-dept-ops-main text-white shrink-0 gap-2">
                <PhRobot :size="20" weight="fill" />
                <div class="flex flex-col">
                    <span class="text-[14px] font-bold tracking-wide leading-tight">OPS COMMAND</span>
                    <span class="text-[10px] text-white/70 uppercase font-semibold">Infrastructure AI</span>
                </div>
            </div>
            
            <div class="flex-1 p-4 overflow-y-auto text-[12px] flex flex-col gap-3 bg-white" ref="chatScrollContainer">
                <div v-for="(m, idx) in chatMessages" :key="idx" :class="m.role === 'user' ? 'text-right' : 'text-left'">
                    <div class="inline-block p-3 rounded-card shadow-sm text-text-secondary max-w-[95%] leading-relaxed text-left" 
                         :class="m.role === 'user' ? 'bg-dept-ops-main/10 text-dept-ops-main border border-dept-ops-main/20 rounded-br-none' : 'bg-shell-panel border border-shell-border rounded-bl-none'">
                        <RichMessageRenderer :content="m.content" />
                    </div>
                </div>
                <div v-if="isChatTyping" class="text-[11px] text-text-disabled animate-pulse flex items-center gap-1.5 pl-1">
                    <PhRobot :size="14" class="animate-bounce text-dept-ops-main" /> OPS COMMAND is typing...
                </div>
            </div>

            <div class="p-3 border-t border-shell-border bg-shell-panel shrink-0">
                <div class="relative">
                    <input 
                        v-model="chatMessage"
                        @keyup.enter="sendChatMessage"
                        type="text" 
                        placeholder="Ask Ops Command..." 
                        class="w-full pl-3 pr-10 py-2.5 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-ops-main outline-none" 
                    />
                    <button @click="sendChatMessage" class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 text-dept-ops-main hover:text-[#334155] transition-colors cursor-pointer bg-transparent border-0 flex items-center justify-center">
                        <PhArrowRight :size="16" weight="bold" />
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>
