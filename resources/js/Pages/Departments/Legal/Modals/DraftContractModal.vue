<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { PhX } from '@phosphor-icons/vue';
const emit = defineEmits(['close']);
const form = ref({ client_id: '', title: '', start_date: '', end_date: '' });
const submit = async () => {
    await axios.post('/api/legal/contracts', form.value);
    window.dispatchEvent(new CustomEvent('refresh-legal-dashboard'));
    emit('close');
};
</script>
<template>
    <div class="w-[500px] bg-white rounded-modal p-6 shadow-modal">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-bold">Draft New Contract</h2>
            <button @click="$emit('close')" class="p-1 text-text-disabled hover:text-state-error transition-colors"><PhX :size="18" /></button>
        </div>
        <form @submit.prevent="submit" class="flex flex-col gap-4">
            <input v-model="form.client_id" placeholder="Client UUID" class="border border-shell-border p-2 text-[13px] rounded outline-none focus:ring-1 focus:ring-dept-legal-main" required />
            <input v-model="form.title" placeholder="Title" class="border border-shell-border p-2 text-[13px] rounded outline-none focus:ring-1 focus:ring-dept-legal-main" required />
            <div class="grid grid-cols-2 gap-4">
                <input v-model="form.start_date" type="date" class="border border-shell-border p-2 text-[13px] rounded outline-none focus:ring-1 focus:ring-dept-legal-main" required />
                <input v-model="form.end_date" type="date" class="border border-shell-border p-2 text-[13px] rounded outline-none focus:ring-1 focus:ring-dept-legal-main" required />
            </div>
            <button type="submit" class="bg-dept-legal-main hover:bg-[#5B21B6] transition-colors text-[13px] font-medium text-white py-2 rounded-btn mt-2">Draft Contract</button>
        </form>
    </div>
</template>
