<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { useToastStore } from '@/Stores/useToastStore';
import { PhX } from '@phosphor-icons/vue';

const emit = defineEmits(['close']);
const toastStore = useToastStore();

const form = ref({
    title: '',
    description: '',
    severity: 'major',
    affected_services: '',
});

const isSubmitting = ref(false);

const submit = async () => {
    if (!form.value.title || !form.value.description) {
        toastStore.showToast('Please fill in all required fields', 'error');
        return;
    }
    
    isSubmitting.value = true;
    try {
        await axios.post('/api/support/incidents', form.value);
        toastStore.showToast('Outage declared successfully', 'success');
        window.dispatchEvent(new CustomEvent('refresh-support-dashboard'));
        emit('close');
    } catch (e) {
        toastStore.showToast('Failed to declare outage', 'error');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div class="bg-white w-full max-w-md rounded-xl shadow-2xl overflow-hidden animate-fade-in-up border border-shell-border flex flex-col max-h-[90vh]">
        <div class="h-12 bg-red-600 border-b border-red-700 flex items-center justify-between px-4 shrink-0 text-white">
            <h3 class="font-bold text-[14px]">Declare Outage</h3>
            <button @click="$emit('close')" class="p-1.5 hover:bg-white/20 rounded-btn transition-colors cursor-pointer border-0 bg-transparent text-white">
                <PhX :size="16" />
            </button>
        </div>
        
        <div class="p-5 flex-1 overflow-y-auto space-y-4">
            <div>
                <label class="block text-[11px] font-bold text-text-secondary uppercase mb-1.5">Incident Title</label>
                <input v-model="form.title" type="text" placeholder="e.g. Database Connection Issues..." class="w-full px-3 py-2 bg-slate-50 border border-shell-border rounded-input text-[13px] text-text-primary focus:ring-1 focus:ring-red-500 outline-none" />
            </div>

            <div>
                <label class="block text-[11px] font-bold text-text-secondary uppercase mb-1.5">Severity</label>
                <select v-model="form.severity" class="w-full px-3 py-2 bg-slate-50 border border-shell-border rounded-input text-[13px] text-text-primary focus:ring-1 focus:ring-red-500 outline-none appearance-none">
                    <option value="critical">Critical - System Offline</option>
                    <option value="major">Major - Significant Degredation</option>
                    <option value="minor">Minor - Partial Degredation</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-text-secondary uppercase mb-1.5">Affected Services</label>
                <input v-model="form.affected_services" type="text" placeholder="e.g. API, Database, Auth..." class="w-full px-3 py-2 bg-slate-50 border border-shell-border rounded-input text-[13px] text-text-primary focus:ring-1 focus:ring-red-500 outline-none" />
            </div>

            <div>
                <label class="block text-[11px] font-bold text-text-secondary uppercase mb-1.5">Initial Description & Update</label>
                <textarea v-model="form.description" rows="4" placeholder="Describe the impact and what the team is doing..." class="w-full px-3 py-2 bg-slate-50 border border-shell-border rounded-input text-[13px] text-text-primary focus:ring-1 focus:ring-red-500 outline-none resize-none"></textarea>
            </div>
        </div>
        
        <div class="p-4 bg-shell-panel border-t border-shell-border flex justify-end gap-2 shrink-0">
            <button @click="$emit('close')" type="button" class="px-4 py-2 text-[13px] font-medium text-text-secondary hover:text-text-primary cursor-pointer border-0 bg-transparent transition-colors">
                Cancel
            </button>
            <button @click="submit" :disabled="isSubmitting" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-[13px] font-bold rounded-btn transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-sm border-0 cursor-pointer">
                {{ isSubmitting ? 'Declaring...' : 'Declare Outage' }}
            </button>
        </div>
    </div>
</template>
