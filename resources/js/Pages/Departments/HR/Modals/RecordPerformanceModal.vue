<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { PhX, PhStar } from '@phosphor-icons/vue';
import { useToastStore } from '@/Stores/useToastStore';

const props = defineProps<{ userId: string; userName?: string }>();
const emit = defineEmits(['close']);
const toastStore = useToastStore();
const isSubmitting = ref(false);

const form = ref({
    user_id: props.userId,
    review_cycle: '',
    rating: 0,
    feedback: '',
    goals: [] as string[],
});

const newGoal = ref('');
const addGoal = () => {
    if (newGoal.value.trim()) {
        form.value.goals.push(newGoal.value.trim());
        newGoal.value = '';
    }
};
const removeGoal = (i: number) => form.value.goals.splice(i, 1);
const setRating = (r: number) => { form.value.rating = r; };

const submit = async () => {
    if (!form.value.review_cycle || !form.value.rating || !form.value.feedback) {
        toastStore.showToast('Please fill in all required fields.', 'error');
        return;
    }
    isSubmitting.value = true;
    try {
        await axios.post('/api/hr/performance', form.value);
        window.dispatchEvent(new CustomEvent('refresh-hr-dashboard'));
        toastStore.showToast('Performance review recorded!', 'success');
        emit('close');
    } catch (e: any) {
        toastStore.showToast(e.response?.data?.message || 'Failed to record review.', 'error');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div class="bg-white rounded-xl w-[520px] max-w-[95vw] shadow-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200 max-h-[90vh]">
        <!-- Header -->
        <div class="h-[56px] px-6 border-b border-shell-border flex items-center justify-between shrink-0 bg-gradient-to-r from-amber-500 to-orange-400">
            <div class="flex items-center gap-2 text-white">
                <PhStar :size="20" weight="fill" />
                <h3 class="text-[15px] font-bold">Performance Review</h3>
            </div>
            <button @click="$emit('close')" class="w-8 h-8 flex items-center justify-center rounded-btn hover:bg-white/20 text-white transition-colors border-0 bg-transparent cursor-pointer">
                <PhX :size="16" />
            </button>
        </div>

        <!-- Form -->
        <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4">
            <div class="text-[13px] text-text-secondary">
                Reviewing: <span class="font-bold text-text-primary">{{ userName || 'Employee' }}</span>
            </div>

            <div>
                <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Review Cycle *</label>
                <input v-model="form.review_cycle" type="text" class="w-full h-10 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500" placeholder="e.g. Q2 2026, Annual 2026" />
            </div>

            <div>
                <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Rating *</label>
                <div class="flex gap-1">
                    <button v-for="s in 5" :key="s" @click="setRating(s)" class="bg-transparent border-0 cursor-pointer p-0.5 transition-transform hover:scale-110">
                        <PhStar :size="28" :weight="s <= form.rating ? 'fill' : 'regular'" :class="s <= form.rating ? 'text-amber-400' : 'text-slate-300'" />
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Feedback *</label>
                <textarea v-model="form.feedback" rows="4" class="w-full px-3 py-2 rounded-lg border border-shell-border text-[13px] resize-none focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500" placeholder="Write detailed performance feedback..."></textarea>
            </div>

            <div>
                <label class="block text-[12px] font-bold text-text-secondary uppercase mb-1">Goals for Next Cycle</label>
                <div class="flex gap-2 mb-2">
                    <input v-model="newGoal" @keydown.enter.prevent="addGoal" type="text" class="flex-1 h-9 px-3 rounded-lg border border-shell-border text-[13px] focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500" placeholder="Add a goal and press Enter" />
                    <button @click="addGoal" class="px-3 h-9 rounded-btn bg-amber-100 text-amber-700 text-[12px] font-bold hover:bg-amber-200 transition-colors border-0 cursor-pointer">+ Add</button>
                </div>
                <div v-if="form.goals.length" class="space-y-1">
                    <div v-for="(g, i) in form.goals" :key="i" class="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-shell-border text-[12px]">
                        <span class="text-text-primary">{{ g }}</span>
                        <button @click="removeGoal(i)" class="text-red-400 hover:text-red-600 bg-transparent border-0 cursor-pointer text-[11px]">✕</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-shell-border flex justify-end gap-2 shrink-0 bg-slate-50">
            <button @click="$emit('close')" class="px-4 py-2 text-[13px] font-medium text-text-secondary hover:bg-slate-200 rounded-btn border-0 bg-transparent cursor-pointer transition-colors">Cancel</button>
            <button @click="submit" :disabled="isSubmitting" class="px-5 py-2 rounded-btn bg-amber-500 text-white text-[13px] font-bold hover:bg-amber-600 transition-colors border-0 cursor-pointer disabled:opacity-50">
                {{ isSubmitting ? 'Saving...' : 'Submit Review' }}
            </button>
        </div>
    </div>
</template>
