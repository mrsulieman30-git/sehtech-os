<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { PhX } from '@phosphor-icons/vue';
import { useToastStore } from '@/Stores/useToastStore';

const emit = defineEmits(['close']);
const toastStore = useToastStore();

const form = ref({
    title: '',
    type: 'blog_post',
    status: 'draft',
    url: ''
});

const isSubmitting = ref(false);

const submit = async () => {
    if (isSubmitting.value) return;
    isSubmitting.value = true;
    try {
        await axios.post('/api/marketing/contents', form.value);
        window.dispatchEvent(new CustomEvent('refresh-marketing'));
        toastStore.addToast('success', 'Content created successfully!');
        emit('close');
    } catch (error) {
        console.error(error);
        toastStore.addToast('error', 'Failed to create content.');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div class="bg-white rounded-xl w-[500px] max-w-[90vw] shadow-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
        <div class="h-[56px] px-6 border-b border-shell-border flex items-center justify-between shrink-0 bg-slate-50">
            <h3 class="text-[15px] font-bold text-text-primary">Create Marketing Content</h3>
            <button @click="$emit('close')" class="w-8 h-8 flex items-center justify-center rounded-btn hover:bg-slate-200 text-text-secondary transition-colors">
                <PhX :size="16" />
            </button>
        </div>
        
        <div class="p-6 flex-1 overflow-y-auto">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Content Title</label>
                    <input v-model="form.title" type="text" required class="w-full h-10 px-3 rounded-btn border border-shell-border text-[14px] focus:outline-none focus:border-dept-marketing-main focus:ring-1 focus:ring-dept-marketing-main" placeholder="e.g. Q3 Healthcare Newsletter" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Content Type</label>
                        <select v-model="form.type" required class="w-full h-10 px-3 rounded-btn border border-shell-border text-[14px] focus:outline-none focus:border-dept-marketing-main focus:ring-1 focus:ring-dept-marketing-main">
                            <option value="blog_post">Blog Post</option>
                            <option value="case_study">Case Study</option>
                            <option value="video">Video</option>
                            <option value="social_post">Social Post</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Status</label>
                        <select v-model="form.status" required class="w-full h-10 px-3 rounded-btn border border-shell-border text-[14px] focus:outline-none focus:border-dept-marketing-main focus:ring-1 focus:ring-dept-marketing-main">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">URL (Optional)</label>
                    <input v-model="form.url" type="url" class="w-full h-10 px-3 rounded-btn border border-shell-border text-[14px] focus:outline-none focus:border-dept-marketing-main focus:ring-1 focus:ring-dept-marketing-main" placeholder="https://..." />
                </div>
            </form>
        </div>

        <div class="p-4 border-t border-shell-border bg-slate-50 flex justify-end gap-3 shrink-0">
            <button type="button" @click="$emit('close')" class="px-4 py-2 rounded-btn text-[13px] font-medium text-text-secondary hover:bg-slate-200 transition-colors">
                Cancel
            </button>
            <button @click="submit" :disabled="isSubmitting" class="px-5 py-2 rounded-btn bg-dept-marketing-main text-white text-[13px] font-bold hover:bg-[#E11D48] transition-colors disabled:opacity-50">
                Create Content
            </button>
        </div>
    </div>
</template>
