<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { PhRobot, PhPaperPlaneRight, PhX } from '@phosphor-icons/vue';

const props = defineProps<{ agentId: string; }>();
const emit = defineEmits(['close']);

const message = ref('');
const messages = ref<{role: string, content: string}[]>([]);
const isTyping = ref(false);

const sendMessage = async () => {
    if (!message.value.trim()) return;
    
    const userMsg = message.value;
    messages.value.push({ role: 'user', content: userMsg });
    message.value = '';
    isTyping.value = true;
    
    try {
        const res = await axios.post(`/api/agents/${props.agentId}/chat`, { message: userMsg });
        messages.value.push({ role: 'assistant', content: res.data.response_text });
    } catch (e) {
        messages.value.push({ role: 'assistant', content: 'System error: Could not reach Agent.' });
    } finally {
        isTyping.value = false;
    }
};
</script>

<template>
    <div class="absolute right-0 top-0 h-full w-[350px] bg-white border-l border-shell-border flex flex-col shadow-modal z-[1000]">
        <div class="p-4 border-b border-shell-border flex justify-between items-center bg-shell-panel">
            <div class="flex items-center gap-2 font-bold text-[14px]"><PhRobot :size="18"/> Agent Chat</div>
            <button @click="$emit('close')"><PhX :size="16"/></button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-4 space-y-4">
            <div v-for="(m, idx) in messages" :key="idx" :class="m.role === 'user' ? 'text-right' : 'text-left'">
                <span class="inline-block px-3 py-2 rounded-card text-[13px] max-w-[85%]" :class="m.role === 'user' ? 'bg-dept-dev-main text-white' : 'bg-shell-panel border border-shell-border text-text-primary'">
                    {{ m.content }}
                </span>
            </div>
            <div v-if="isTyping" class="text-[12px] text-text-disabled animate-pulse">Agent is thinking...</div>
        </div>
        
        <div class="p-3 border-t border-shell-border">
            <div class="relative">
                <input v-model="message" @keyup.enter="sendMessage" class="w-full p-2.5 pr-10 border border-shell-border rounded-input text-[13px] outline-none focus:ring-1 focus:ring-dept-dev-main bg-[#F8FAFC]" placeholder="Ask agent..." />
                <button @click="sendMessage" class="absolute right-2 top-1/2 -translate-y-1/2 text-dept-dev-main hover:text-[#4338CA] transition-colors"><PhPaperPlaneRight :size="16" weight="fill" /></button>
            </div>
        </div>
    </div>
</template>
