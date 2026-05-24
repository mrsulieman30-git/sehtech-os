<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { PhX } from '@phosphor-icons/vue';
const props = defineProps<{ ticketId: string }>();
const emit = defineEmits(['close']);
const note = ref('');
const submit = async () => {
    await axios.post(`/api/support/tickets/${props.ticketId}/resolve`, { resolution_note: note.value });
    window.dispatchEvent(new CustomEvent('refresh-support-dashboard'));
    emit('close');
};
</script>
<template>
    <div class="w-[500px] bg-white rounded-modal p-6 shadow-modal">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-bold">Resolve Ticket</h2>
            <button @click="$emit('close')" class="p-1 text-text-disabled hover:text-state-error transition-colors"><PhX :size="18" /></button>
        </div>
        <form @submit.prevent="submit" class="flex flex-col gap-4">
            <textarea v-model="note" placeholder="Resolution note..." class="border border-shell-border p-2 text-[13px] rounded outline-none focus:ring-1 focus:ring-state-success" rows="4" required></textarea>
            <button type="submit" class="bg-state-success hover:bg-opacity-90 transition-colors text-white py-2 rounded-btn text-[13px] font-medium mt-2">Confirm Resolution</button>
        </form>
    </div>
</template>
