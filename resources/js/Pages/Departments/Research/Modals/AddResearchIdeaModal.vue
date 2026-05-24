<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import RichTextEditor from '@/Components/OS/RichTextEditor.vue';
import { PhX } from '@phosphor-icons/vue';
const emit = defineEmits(['close']);
const form = ref({ title: '', summary: '', content: '', category: 'other', priority: 'P2', tagsStr: '' });
const error = ref('');

const submit = async () => {
    error.value = '';
    try {
        const payload = {
            ...form.value,
            tags: form.value.tagsStr.split(',').map(t => t.trim()).filter(t => t)
        };
        const res = await axios.post('/api/research/ideas', payload);
        window.dispatchEvent(new CustomEvent('refresh-research-dashboard', { detail: { newIdea: res.data.idea } }));
        emit('close');
    } catch (e: any) {
        error.value = e.response?.data?.message || 'Failed to submit idea';
        console.error(e);
    }
};
</script>
<template>
    <div class="w-[600px] bg-white rounded-modal p-6 shadow-modal max-h-[85vh] overflow-y-auto flex flex-col gap-4">
        <div class="flex justify-between items-center">
            <h2 class="font-bold">New Research Idea</h2>
            <button @click="$emit('close')" class="p-1 text-text-disabled hover:text-state-error transition-colors"><PhX :size="18" /></button>
        </div>
        <div v-if="error" class="p-3 bg-state-error/10 text-state-error rounded text-[13px] font-medium">{{ error }}</div>
        <form @submit.prevent="submit" class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <label class="text-[12px] font-semibold text-text-primary">Idea Title</label>
                <input v-model="form.title" placeholder="e.g., AI Diagnostic Tool" class="border border-shell-border p-2 text-[13px] rounded outline-none focus:ring-1 focus:ring-dept-research-main" required />
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1">
                    <label class="text-[12px] font-semibold text-text-primary">Short Summary</label>
                    <textarea v-model="form.summary" placeholder="Brief elevator pitch..." class="border border-shell-border p-2 text-[13px] rounded outline-none focus:ring-1 focus:ring-dept-research-main resize-none" rows="2" required></textarea>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-[12px] font-semibold text-text-primary">Category</label>
                    <select v-model="form.category" class="border border-shell-border p-2 text-[13px] rounded outline-none focus:ring-1 focus:ring-dept-research-main">
                        <option value="hms">HMS</option>
                        <option value="clinic">Clinic</option>
                        <option value="pharmacy">Pharmacy</option>
                        <option value="dental">Dental</option>
                        <option value="inventory">Inventory</option>
                        <option value="video">Video</option>
                        <option value="internal">Internal</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1">
                    <label class="text-[12px] font-semibold text-text-primary">Priority</label>
                    <select v-model="form.priority" class="border border-shell-border p-2 text-[13px] rounded outline-none focus:ring-1 focus:ring-dept-research-main">
                        <option value="P0">P0 - Critical</option>
                        <option value="P1">P1 - High</option>
                        <option value="P2">P2 - Normal</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-[12px] font-semibold text-text-primary">Tags (comma separated)</label>
                    <input v-model="form.tagsStr" placeholder="e.g. AI, Backend, Urgent" class="border border-shell-border p-2 text-[13px] rounded outline-none focus:ring-1 focus:ring-dept-research-main" />
                </div>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[12px] font-semibold text-text-primary">Detailed Content / Document</label>
                <RichTextEditor v-model="form.content" />
            </div>
            
            <div class="flex justify-end mt-2">
                <button type="submit" class="bg-dept-research-main hover:bg-[#3730A3] transition-colors text-[13px] font-medium text-white px-5 py-2 rounded-btn">Submit Idea</button>
            </div>
        </form>
    </div>
</template>
