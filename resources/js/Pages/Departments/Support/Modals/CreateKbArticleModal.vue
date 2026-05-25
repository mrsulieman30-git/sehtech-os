<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { useToastStore } from '@/Stores/useToastStore';
import { PhX } from '@phosphor-icons/vue';

const emit = defineEmits(['close']);
const toastStore = useToastStore();

const form = ref({
    title: '',
    category: 'troubleshooting',
    content: '',
    access_level: 'public',
});

const isSubmitting = ref(false);

const submit = async () => {
    if (!form.value.title || !form.value.content) {
        toastStore.showToast('Please fill in all required fields', 'error');
        return;
    }
    
    isSubmitting.value = true;
    try {
        await axios.post('/api/support/kb-articles', form.value);
        toastStore.showToast('Article created successfully', 'success');
        window.dispatchEvent(new CustomEvent('refresh-support-dashboard'));
        emit('close');
    } catch (e) {
        toastStore.showToast('Failed to create article', 'error');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div class="bg-white w-full max-w-2xl rounded-xl shadow-2xl overflow-hidden animate-fade-in-up border border-shell-border flex flex-col max-h-[90vh]">
        <div class="h-12 bg-shell-panel border-b border-shell-border flex items-center justify-between px-4 shrink-0">
            <h3 class="font-bold text-[14px] text-text-primary">Create Knowledge Base Article</h3>
            <button @click="$emit('close')" class="p-1.5 hover:bg-shell-border/50 rounded-btn transition-colors text-text-secondary cursor-pointer border-0 bg-transparent">
                <PhX :size="16" />
            </button>
        </div>
        
        <div class="p-5 flex-1 overflow-y-auto space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-[11px] font-bold text-text-secondary uppercase mb-1.5">Title</label>
                    <input v-model="form.title" type="text" placeholder="Article title..." class="w-full px-3 py-2 bg-slate-50 border border-shell-border rounded-input text-[13px] text-text-primary focus:ring-1 focus:ring-dept-support-main outline-none" />
                </div>
                
                <div>
                    <label class="block text-[11px] font-bold text-text-secondary uppercase mb-1.5">Category</label>
                    <select v-model="form.category" class="w-full px-3 py-2 bg-slate-50 border border-shell-border rounded-input text-[13px] text-text-primary focus:ring-1 focus:ring-dept-support-main outline-none appearance-none">
                        <option value="troubleshooting">Troubleshooting</option>
                        <option value="faq">FAQ</option>
                        <option value="guide">User Guide</option>
                        <option value="policy">Policy</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-[11px] font-bold text-text-secondary uppercase mb-1.5">Access Level</label>
                    <select v-model="form.access_level" class="w-full px-3 py-2 bg-slate-50 border border-shell-border rounded-input text-[13px] text-text-primary focus:ring-1 focus:ring-dept-support-main outline-none appearance-none">
                        <option value="public">Public (Visible to everyone)</option>
                        <option value="internal">Internal (Logged-in Users)</option>
                        <option value="agent_only">Agent Only (Support Staff)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-text-secondary uppercase mb-1.5">Content (Markdown supported)</label>
                <textarea v-model="form.content" rows="10" placeholder="Write article content here..." class="w-full px-3 py-2 bg-slate-50 border border-shell-border rounded-input text-[13px] text-text-primary focus:ring-1 focus:ring-dept-support-main outline-none resize-none font-mono"></textarea>
            </div>
        </div>
        
        <div class="p-4 bg-shell-panel border-t border-shell-border flex justify-end gap-2 shrink-0">
            <button @click="$emit('close')" type="button" class="px-4 py-2 text-[13px] font-medium text-text-secondary hover:text-text-primary cursor-pointer border-0 bg-transparent transition-colors">
                Cancel
            </button>
            <button @click="submit" :disabled="isSubmitting" class="px-4 py-2 bg-dept-support-main hover:bg-opacity-90 text-white text-[13px] font-bold rounded-btn transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-sm border-0 cursor-pointer">
                {{ isSubmitting ? 'Publishing...' : 'Publish Article' }}
            </button>
        </div>
    </div>
</template>
