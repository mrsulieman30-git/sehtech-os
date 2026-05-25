<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { 
    PhScales, PhFileDoc, PhFolder, PhCheckCircle, 
    PhWarning, PhRobot, PhCaretRight, PhMagnifyingGlass,
    PhFiles, PhShieldCheck, PhWarningCircle, PhPlus
} from '@phosphor-icons/vue';
import dayjs from 'dayjs';
import { useModalStore } from '@/Stores/useModalStore';
import { useToastStore } from '@/Stores/useToastStore';
import RichMessageRenderer from '@/Components/Ai/RichMessageRenderer.vue';
import { PhArrowRight } from '@phosphor-icons/vue';

const modalStore = useModalStore();
const toastStore = useToastStore();

interface Contract {
    id: string; title: string; status: string; start_date: string; end_date: string; body: string;
    account: { id: string; name: string; industry: string | null; };
}

interface Template {
    id: string; name: string; type: string; ai_prompt: string; variables: string[];
}

interface ComplianceControl {
    id: string; name: string; description: string; status: string; evidence: string | null;
}

interface Framework {
    id: string; name: string; description: string; controls: ComplianceControl[];
}

interface Risk {
    id: string; title: string; description: string; likelihood: string; impact: string; status: string; mitigation_plan: string | null;
}

const activeTab = ref<'contracts' | 'templates' | 'compliance' | 'risks'>('contracts');

const contracts = ref<Contract[]>([]);
const templates = ref<Template[]>([]);
const frameworks = ref<Framework[]>([]);
const risks = ref<Risk[]>([]);
const metrics = ref({ active_contracts: 0, pending_signatures: 0, expiring_soon: 0 });

const selectedContract = ref<Contract | null>(null);
const searchQuery = ref('');

const fetchDashboard = async () => {
    try {
        const response = await axios.get('/api/legal/dashboard');
        contracts.value = response.data.contracts;
        templates.value = response.data.templates;
        frameworks.value = response.data.frameworks;
        risks.value = response.data.risks;
        metrics.value = response.data.metrics;
    } catch (error) {
        console.error('Failed to fetch legal data', error);
        toastStore.addToast('error', 'Failed to load legal data');
    }
};

const getStatusBadge = (status: string) => {
    const badges: Record<string, string> = {
        'active': 'bg-state-success/10 text-state-success border-state-success/20',
        'draft': 'bg-shell-border text-text-secondary border-shell-border',
        'sent': 'bg-state-info/10 text-state-info border-state-info/20',
        'signed': 'bg-dept-legal-main/10 text-dept-legal-main border-dept-legal-main/20',
        'expired': 'bg-state-error/10 text-state-error border-state-error/20',
        'terminated': 'bg-state-error/10 text-state-error border-state-error/20',
    };
    return badges[status] || badges['draft'];
};

const triggerAIToast = (msg: string) => {
    toastStore.addToast('info', `LEXOR AI: ${msg}`);
};

const openGenerateModal = (template: Template) => {
    modalStore.openModal('generate-contract', { template });
};

const isSaving = ref(false);

const saveContract = async () => {
    if (!selectedContract.value) return;
    isSaving.value = true;
    try {
        await axios.put(`/api/legal/contracts/${selectedContract.value.id}`, {
            body: selectedContract.value.body
        });
        toastStore.addToast('success', 'Contract saved successfully.');
    } catch (error) {
        console.error(error);
        toastStore.addToast('error', 'Failed to save contract.');
    } finally {
        isSaving.value = false;
    }
};

// Legal Agent Chat logic
const chatMessage = ref('');
const chatMessages = ref<{ role: string; content: string }>([
    {
        role: 'assistant',
        content: 'I am LEXOR AI. How can I help you draft contracts or review compliance?'
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
        const response = await axios.post('/api/agents/legal-agent/chat', {
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
    window.addEventListener('refresh-legal', fetchDashboard);
});
</script>

<template>
    <div class="h-full flex flex-col bg-shell-panel text-text-primary overflow-hidden font-sans">
        
        <!-- Header -->
        <div class="h-[64px] flex items-center justify-between px-6 border-b border-shell-border bg-white shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-dept-legal-main/10 flex items-center justify-center text-dept-legal-main">
                    <PhScales :size="24" weight="fill" />
                </div>
                <div>
                    <h2 class="text-[16px] font-bold text-text-primary leading-tight">Legal & Compliance</h2>
                    <div class="text-[12px] text-text-secondary">Manage contracts, AI templates, and compliance</div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex items-center bg-[#F1F5F9] p-1 rounded-lg border border-shell-border">
                <button @click="activeTab = 'contracts'" class="px-4 py-1.5 rounded-md text-[13px] font-semibold transition-all" :class="activeTab === 'contracts' ? 'bg-white shadow-sm text-text-primary' : 'text-text-secondary hover:text-text-primary'">
                    <div class="flex items-center gap-2"><PhFileDoc :size="16" /> Contracts</div>
                </button>
                <button @click="activeTab = 'templates'" class="px-4 py-1.5 rounded-md text-[13px] font-semibold transition-all" :class="activeTab === 'templates' ? 'bg-white shadow-sm text-text-primary' : 'text-text-secondary hover:text-text-primary'">
                    <div class="flex items-center gap-2"><PhFiles :size="16" /> AI Templates</div>
                </button>
                <button @click="activeTab = 'compliance'" class="px-4 py-1.5 rounded-md text-[13px] font-semibold transition-all" :class="activeTab === 'compliance' ? 'bg-white shadow-sm text-text-primary' : 'text-text-secondary hover:text-text-primary'">
                    <div class="flex items-center gap-2"><PhShieldCheck :size="16" /> Compliance</div>
                </button>
                <button @click="activeTab = 'risks'" class="px-4 py-1.5 rounded-md text-[13px] font-semibold transition-all" :class="activeTab === 'risks' ? 'bg-white shadow-sm text-text-primary' : 'text-text-secondary hover:text-text-primary'">
                    <div class="flex items-center gap-2"><PhWarningCircle :size="16" /> Risk Register</div>
                </button>
            </div>
            
            <div class="flex items-center gap-2">
                <button v-if="activeTab === 'risks'" @click="modalStore.openModal('create-risk')" class="px-4 py-2 bg-dept-legal-main text-white text-[13px] font-bold rounded-btn flex items-center gap-2 hover:bg-[#5B21B6] transition-colors">
                    <PhPlus :size="16" weight="bold" /> Log Risk
                </button>
            </div>
        </div>

        <div class="flex-1 flex overflow-hidden">
            
            <!-- LEFT: Contracts Directory (Only shown on Contracts tab) -->
            <div v-if="activeTab === 'contracts'" class="w-[320px] flex-shrink-0 border-r border-shell-border bg-shell-panel flex flex-col h-full">
                <div class="p-3 border-b border-shell-border bg-[#F8FAFC]">
                    <div class="relative w-full">
                        <PhMagnifyingGlass :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-text-disabled" />
                        <input v-model="searchQuery" type="text" placeholder="Search contracts..." class="w-full pl-9 pr-4 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-legal-main outline-none" />
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-3 flex flex-col gap-2">
                    <div class="text-[11px] font-bold text-text-disabled uppercase tracking-wider px-2 mb-1">Contract Directory</div>
                    <div v-if="contracts.length === 0" class="text-[12px] text-text-disabled px-2 py-4 text-center">No contracts found.</div>
                    
                    <div 
                        v-for="contract in contracts" 
                        :key="contract.id"
                        @click="selectedContract = contract"
                        class="p-3 rounded-card border border-shell-border bg-white cursor-pointer hover:border-dept-legal-main transition-colors"
                        :class="{'ring-1 ring-dept-legal-main border-dept-legal-main': selectedContract?.id === contract.id}"
                    >
                        <div class="flex justify-between items-start mb-1.5">
                            <span class="px-2 py-0.5 rounded-tag text-[10px] font-bold uppercase border" :class="getStatusBadge(contract.status)">
                                {{ contract.status }}
                            </span>
                            <span v-if="contract.end_date" class="text-[11px] text-text-secondary">{{ dayjs(contract.end_date).format('MMM YYYY') }}</span>
                        </div>
                        <div class="text-[13px] font-semibold text-text-primary truncate">{{ contract.title }}</div>
                        <div class="text-[11px] text-text-secondary truncate mt-1 flex items-center gap-1">
                            <PhFolder :size="12" /> {{ contract.account?.name || 'Internal' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- MAIN WORKSPACE -->
            <div class="flex-1 overflow-y-auto bg-[#F8FAFC]">
                
                <!-- CONTRACT VIEWER -->
                <div v-if="activeTab === 'contracts'" class="h-full flex flex-col p-6">
                    <div v-if="selectedContract" class="w-full max-w-4xl mx-auto h-full bg-white border border-shell-border shadow-card flex flex-col rounded-xl overflow-hidden">
                        <div class="px-6 py-4 border-b border-shell-border bg-shell-panel flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <PhFileDoc :size="24" class="text-dept-legal-main" />
                                <div>
                                    <div class="text-[15px] font-bold text-text-primary">{{ selectedContract.title }}</div>
                                    <div class="text-[12px] text-text-secondary">
                                        {{ selectedContract.start_date ? dayjs(selectedContract.start_date).format('MMM D, YYYY') : '' }}
                                        <span v-if="selectedContract.end_date"> — {{ dayjs(selectedContract.end_date).format('MMM D, YYYY') }}</span>
                                    </div>
                                </div>
                            </div>
                            <button @click="triggerAIToast('Analyzing contract clauses...')" class="px-4 py-1.5 bg-dept-legal-main/10 text-dept-legal-main text-[13px] font-bold rounded-btn hover:bg-dept-legal-main hover:text-white transition-colors flex items-center gap-2">
                                <PhRobot :size="16" weight="fill" /> AI Analysis
                            </button>
                        </div>
                        <div class="flex-1 bg-[#F1F5F9] p-4 flex flex-col relative">
                            <textarea v-model="selectedContract.body" class="w-full h-full bg-white border border-shell-border rounded-lg p-6 text-[14px] font-serif resize-none focus:outline-none focus:border-dept-legal-main/50 text-text-primary shadow-sm" placeholder="Contract content is empty."></textarea>
                            <button @click="saveContract" class="absolute bottom-8 right-8 px-5 py-2.5 bg-dept-legal-main text-white text-[13px] font-bold rounded-btn flex items-center gap-2 hover:bg-[#5B21B6] transition-colors shadow-md z-10" :disabled="isSaving">
                                <PhCheckCircle :size="18" weight="fill" v-if="!isSaving" />
                                <svg v-else class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                {{ isSaving ? 'Saving...' : 'Save Contract' }}
                            </button>
                        </div>
                    </div>
                    <div v-else class="h-full flex items-center justify-center text-text-disabled flex-col">
                        <PhScales :size="64" weight="thin" class="mx-auto mb-4 text-shell-border" />
                        <p class="text-[14px]">Select a document from the directory to view.</p>
                    </div>
                </div>

                <!-- AI TEMPLATES -->
                <div v-if="activeTab === 'templates'" class="p-8 max-w-5xl mx-auto">
                    <div class="mb-6">
                        <h3 class="text-[18px] font-bold text-text-primary">AI Contract Templates</h3>
                        <p class="text-[13px] text-text-secondary">Lexor AI can generate customized legal drafts instantly based on these standard templates.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="template in templates" :key="template.id" class="bg-white border border-shell-border rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow flex flex-col">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 rounded-full bg-[#F1F5F9] flex items-center justify-center text-dept-legal-main">
                                    <PhFiles :size="20" weight="duotone" />
                                </div>
                                <div>
                                    <h4 class="text-[14px] font-bold text-text-primary">{{ template.name }}</h4>
                                    <span class="text-[11px] font-bold text-dept-legal-main uppercase tracking-wider">{{ template.type }}</span>
                                </div>
                            </div>
                            <p class="text-[12px] text-text-secondary mb-4 flex-1 line-clamp-3 leading-relaxed">
                                {{ template.ai_prompt.substring(0, 100) }}...
                            </p>
                            <button @click="openGenerateModal(template)" class="w-full py-2 bg-dept-legal-main text-white text-[13px] font-bold rounded-btn flex justify-center items-center gap-2 hover:bg-[#5B21B6] transition-colors">
                                <PhRobot :size="16" weight="fill" /> Generate Draft
                            </button>
                        </div>
                    </div>
                </div>

                <!-- COMPLIANCE TRACKING -->
                <div v-if="activeTab === 'compliance'" class="p-8 max-w-5xl mx-auto">
                    <div class="mb-6">
                        <h3 class="text-[18px] font-bold text-text-primary">Compliance Tracking</h3>
                        <p class="text-[13px] text-text-secondary">Monitor organizational adherence to international standards.</p>
                    </div>

                    <div v-for="framework in frameworks" :key="framework.id" class="mb-8">
                        <div class="flex items-center gap-3 mb-4">
                            <PhShieldCheck :size="24" class="text-state-success" weight="fill" />
                            <div>
                                <h4 class="text-[16px] font-bold text-text-primary">{{ framework.name }}</h4>
                                <div class="text-[12px] text-text-secondary">{{ framework.description }}</div>
                            </div>
                        </div>

                        <div class="bg-white border border-shell-border rounded-xl overflow-hidden shadow-sm">
                            <table class="w-full text-left text-[13px]">
                                <thead class="bg-shell-panel border-b border-shell-border text-text-secondary">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Control Name</th>
                                        <th class="px-4 py-3 font-semibold">Description</th>
                                        <th class="px-4 py-3 font-semibold">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-shell-border">
                                    <tr v-for="control in framework.controls" :key="control.id" class="hover:bg-shell-panel/50">
                                        <td class="px-4 py-3 font-semibold text-text-primary">{{ control.name }}</td>
                                        <td class="px-4 py-3 text-text-secondary">{{ control.description }}</td>
                                        <td class="px-4 py-3">
                                            <span class="px-2.5 py-1 rounded-tag text-[11px] font-bold uppercase border"
                                                :class="{
                                                    'bg-state-success/10 text-state-success border-state-success/20': control.status === 'compliant',
                                                    'bg-state-error/10 text-state-error border-state-error/20': control.status === 'non_compliant',
                                                    'bg-state-warning/10 text-state-warning border-state-warning/20': control.status === 'gap',
                                                    'bg-slate-100 text-slate-500 border-slate-200': control.status === 'not_applicable'
                                                }">
                                                {{ control.status.replace('_', ' ') }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- RISK REGISTER -->
                <div v-if="activeTab === 'risks'" class="p-8 max-w-5xl mx-auto">
                    <div class="mb-6 flex justify-between items-end">
                        <div>
                            <h3 class="text-[18px] font-bold text-text-primary">Risk Register</h3>
                            <p class="text-[13px] text-text-secondary">Identify, assess, and mitigate organizational risks.</p>
                        </div>
                    </div>

                    <div class="bg-white border border-shell-border rounded-xl overflow-hidden shadow-sm">
                        <table class="w-full text-left text-[13px]">
                            <thead class="bg-shell-panel border-b border-shell-border text-text-secondary">
                                <tr>
                                    <th class="px-4 py-3 font-semibold">Risk Title</th>
                                    <th class="px-4 py-3 font-semibold">Likelihood</th>
                                    <th class="px-4 py-3 font-semibold">Impact</th>
                                    <th class="px-4 py-3 font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-shell-border">
                                <tr v-for="risk in risks" :key="risk.id" class="hover:bg-shell-panel/50">
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-text-primary">{{ risk.title }}</div>
                                        <div class="text-[11px] text-text-secondary truncate max-w-[300px]">{{ risk.mitigation_plan || 'No mitigation plan' }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="capitalize" :class="{
                                            'text-state-error font-semibold': risk.likelihood === 'high',
                                            'text-state-warning font-semibold': risk.likelihood === 'medium',
                                            'text-state-success': risk.likelihood === 'low'
                                        }">{{ risk.likelihood }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="capitalize" :class="{
                                            'text-state-error font-semibold': risk.impact === 'high',
                                            'text-state-warning font-semibold': risk.impact === 'medium',
                                            'text-state-success': risk.impact === 'low'
                                        }">{{ risk.impact }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2.5 py-1 rounded-tag text-[11px] font-bold uppercase border"
                                            :class="{
                                                'bg-state-info/10 text-state-info border-state-info/20': risk.status === 'open',
                                                'bg-state-success/10 text-state-success border-state-success/20': risk.status === 'mitigated',
                                                'bg-state-warning/10 text-state-warning border-state-warning/20': risk.status === 'accepted',
                                                'bg-slate-100 text-slate-500 border-slate-200': risk.status === 'closed'
                                            }">
                                            {{ risk.status }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="risks.length === 0">
                                    <td colspan="4" class="px-4 py-8 text-center text-text-disabled">No risks documented.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        <!-- Right AI Sidebar: LEXOR AI -->
        <div class="w-[300px] flex-shrink-0 border-l border-shell-border bg-shell-panel flex flex-col h-full z-10">
            <div class="h-[64px] flex items-center px-4 border-b border-shell-border bg-dept-legal-main text-white shrink-0 gap-2">
                <PhRobot :size="20" weight="fill" />
                <div class="flex flex-col">
                    <span class="text-[14px] font-bold tracking-wide leading-tight">LEXOR AI</span>
                    <span class="text-[10px] text-white/70 uppercase font-semibold">Legal Assistant</span>
                </div>
            </div>
            
            <div class="flex-1 p-4 overflow-y-auto text-[12px] flex flex-col gap-3 bg-white" ref="chatScrollContainer">
                <div v-for="(m, idx) in chatMessages" :key="idx" :class="m.role === 'user' ? 'text-right' : 'text-left'">
                    <div class="inline-block p-3 rounded-card shadow-sm text-text-secondary max-w-[95%] leading-relaxed text-left" 
                         :class="m.role === 'user' ? 'bg-dept-legal-main/10 text-dept-legal-main border border-dept-legal-main/20 rounded-br-none' : 'bg-shell-panel border border-shell-border rounded-bl-none'">
                        <RichMessageRenderer :content="m.content" />
                    </div>
                </div>
                <div v-if="isChatTyping" class="text-[11px] text-text-disabled animate-pulse flex items-center gap-1.5 pl-1">
                    <PhRobot :size="14" class="animate-bounce text-dept-legal-main" /> LEXOR is typing...
                </div>
            </div>

            <div class="p-3 border-t border-shell-border bg-shell-panel shrink-0">
                <div class="relative">
                    <input 
                        v-model="chatMessage"
                        @keyup.enter="sendChatMessage"
                        type="text" 
                        placeholder="Ask Lexor..." 
                        class="w-full pl-3 pr-10 py-2.5 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-legal-main outline-none" 
                    />
                    <button @click="sendChatMessage" class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 text-dept-legal-main hover:text-[#5B21B6] transition-colors cursor-pointer bg-transparent border-0 flex items-center justify-center">
                        <PhArrowRight :size="16" weight="bold" />
                    </button>
                </div>
            </div>
        </div>
        </div>
    </div>
</template>
