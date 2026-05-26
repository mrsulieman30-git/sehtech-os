<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue';
import { 
    PhRobot, PhCpu, PhDatabase, PhPaperPlaneRight, 
    PhBrain, PhNetwork
} from '@phosphor-icons/vue';
import axios from 'axios';
import RichMessageRenderer from '@/Components/Ai/RichMessageRenderer.vue';

const message = ref('');
const chatHistory = ref([
    { role: 'assistant', content: 'System online. I am NEXAR, your Master AI Agent. How can I assist with SEHTECH CompanyOS today?' }
]);

const isTyping = ref(false);
const chatContainer = ref<HTMLElement | null>(null);

const scrollToBottom = () => {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
        }
    });
};

const fetchHistory = async () => {
    try {
        const response = await axios.get('/api/agents/master-chat/history');
        if (response.data.history && response.data.history.length > 0) {
            chatHistory.value = response.data.history;
            scrollToBottom();
        }
    } catch (error) {
        console.error('Failed to load chat history', error);
    }
};

onMounted(() => {
    fetchHistory();
    scrollToBottom();
});



const sendMessage = async () => {
    if (!message.value.trim()) return;
    
    chatHistory.value.push({ role: 'user', content: message.value });
    const userMessage = message.value;
    message.value = '';
    isTyping.value = true;
    scrollToBottom();
    
    try {
        const response = await axios.post('/api/agents/master-chat', {
            message: userMessage,
            history: chatHistory.value.slice(0, -1) // Send all history except the latest message
        });
        chatHistory.value.push({ role: 'assistant', content: response.data.response_text });
        scrollToBottom();
    } catch (error: any) {
        chatHistory.value.push({ 
            role: 'assistant', 
            content: 'SYSTEM ERROR: Connection to Python Universal Backend failed. Ensure the FastApi service is running on port 8001.' 
        });
        scrollToBottom();
        console.error(error);
    } finally {
        isTyping.value = false;
    }
};
</script>

<template>
    <div class="h-full flex flex-row bg-shell-panel text-text-primary overflow-hidden font-sans">
        
        <div class="w-[280px] flex-shrink-0 border-r border-shell-border bg-shell-panel flex flex-col h-full">
            <div class="h-[56px] flex items-center px-6 border-b border-shell-border bg-dept-ai-main text-white shrink-0 gap-3">
                <PhRobot :size="24" weight="fill" />
                <h2 class="text-[15px] font-bold tracking-wide">AI Master Control</h2>
            </div>
            
            <div class="flex-1 overflow-y-auto p-4 flex flex-col gap-6">
                
                <div class="flex flex-col gap-2">
                    <h3 class="text-[11px] font-bold text-text-disabled uppercase tracking-wider mb-1">System Status</h3>
                    <div class="flex items-center justify-between p-3 bg-white border border-shell-border rounded-card shadow-sm">
                        <div class="flex items-center gap-2 text-[13px] font-medium">
                            <PhCpu :size="16" class="text-text-secondary"/> Core Engine
                        </div>
                        <div class="flex items-center gap-1.5 text-[11px] font-bold text-state-success uppercase">
                            <div class="w-1.5 h-1.5 rounded-full bg-state-success animate-pulse"></div> Online
                        </div>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-white border border-shell-border rounded-card shadow-sm">
                        <div class="flex items-center gap-2 text-[13px] font-medium">
                            <PhDatabase :size="16" class="text-text-secondary"/> Vector DB
                        </div>
                        <div class="flex items-center gap-1.5 text-[11px] font-bold text-state-warning uppercase">
                            <div class="w-1.5 h-1.5 rounded-full bg-state-warning"></div> Standby
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <h3 class="text-[11px] font-bold text-text-disabled uppercase tracking-wider mb-1">Sub-Agents</h3>
                    <div class="flex items-center justify-between px-3 py-2 bg-shell-panel border border-shell-border rounded text-[12px] text-text-secondary">
                        <span>DEV NEXUS</span>
                        <span class="text-state-success">Active</span>
                    </div>
                    <div class="flex items-center justify-between px-3 py-2 bg-shell-panel border border-shell-border rounded text-[12px] text-text-secondary">
                        <span>DEALMAKER</span>
                        <span class="text-state-success">Active</span>
                    </div>
                    <div class="flex items-center justify-between px-3 py-2 bg-shell-panel border border-shell-border rounded text-[12px] text-text-secondary">
                        <span>LEXOR</span>
                        <span class="text-state-success">Active</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 flex flex-col h-full bg-[#F8FAFC] overflow-hidden">
            
            <div ref="chatContainer" class="flex-1 overflow-y-auto p-6 flex flex-col gap-4">
                <div 
                    v-for="(msg, index) in chatHistory" 
                    :key="index" 
                    class="flex w-full"
                    :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
                >
                    <div 
                        class="max-w-[85%] p-4 rounded-xl text-[14px] leading-relaxed shadow-sm overflow-hidden"
                        :class="msg.role === 'user' ? 'bg-dept-ai-main text-white rounded-tr-sm' : 'bg-white border border-shell-border text-text-primary rounded-tl-sm w-full max-w-[800px]'"
                    >
                        <RichMessageRenderer v-if="msg.role === 'assistant'" :content="msg.content" />
                        <div v-else :dir="/[\u0600-\u06FF]/.test(msg.content) ? 'rtl' : 'ltr'" :class="/[\u0600-\u06FF]/.test(msg.content) ? 'font-arabic' : ''">{{ msg.content }}</div>
                    </div>
                </div>
                
                <div v-if="isTyping" class="flex justify-start">
                    <div class="bg-white border border-shell-border p-4 rounded-xl rounded-tl-sm flex items-center gap-2">
                        <div class="w-1.5 h-1.5 bg-dept-ai-main rounded-full animate-bounce"></div>
                        <div class="w-1.5 h-1.5 bg-dept-ai-main rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-1.5 h-1.5 bg-dept-ai-main rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-white border-t border-shell-border shrink-0">
                <div class="max-w-4xl mx-auto relative">
                    <textarea 
                        v-model="message"
                        @keydown.enter.exact.prevent="sendMessage"
                        rows="2" 
                        placeholder="Command the Master Agent..." 
                        class="w-full pl-4 pr-12 py-3 bg-[#F8FAFC] border border-shell-border rounded-card text-[14px] focus:ring-1 focus:ring-dept-ai-main outline-none resize-none shadow-sm"
                    ></textarea>
                    <button 
                        @click="sendMessage"
                        :disabled="!message.trim()"
                        class="absolute right-3 top-1/2 -translate-y-1/2 p-2 bg-dept-ai-main text-white rounded-lg hover:bg-[#0F766E] transition-colors disabled:opacity-50"
                    >
                        <PhPaperPlaneRight :size="16" weight="fill" />
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>
