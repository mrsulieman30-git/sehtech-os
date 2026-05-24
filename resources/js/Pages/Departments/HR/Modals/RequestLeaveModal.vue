<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { PhX } from '@phosphor-icons/vue';
const emit = defineEmits(['close']);
const form = ref({ type: 'annual', start_date: '', end_date: '', reason: '' });
const submit = async () => {
    await axios.post('/api/hr/leave', form.value);
    window.dispatchEvent(new CustomEvent('refresh-hr-dashboard'));
    emit('close');
};
</script>
<template>
    <div class="w-[500px] bg-white rounded-modal p-6 shadow-modal">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-bold">Request Leave</h2>
            <button @click="$emit('close')" class="p-1 text-text-disabled hover:text-state-error transition-colors"><PhX :size="18" /></button>
        </div>
        <form @submit.prevent="submit" class="flex flex-col gap-4">
            <select v-model="form.type" class="border border-shell-border p-2 text-[13px] rounded outline-none focus:ring-1 focus:ring-dept-hr-main" required>
                <option value="annual">Annual Leave</option>
                <option value="sick">Sick Leave</option>
                <option value="maternity">Maternity Leave</option>
                <option value="paternity">Paternity Leave</option>
                <option value="unpaid">Unpaid Leave</option>
                <option value="bereavement">Bereavement Leave</option>
            </select>
            <div class="grid grid-cols-2 gap-4">
                <input v-model="form.start_date" type="date" class="border border-shell-border p-2 text-[13px] rounded outline-none focus:ring-1 focus:ring-dept-hr-main" required />
                <input v-model="form.end_date" type="date" class="border border-shell-border p-2 text-[13px] rounded outline-none focus:ring-1 focus:ring-dept-hr-main" required />
            </div>
            <textarea v-model="form.reason" placeholder="Reason (Optional)" class="border border-shell-border p-2 text-[13px] rounded outline-none focus:ring-1 focus:ring-dept-hr-main" rows="3"></textarea>
            <button type="submit" class="bg-dept-hr-main hover:bg-[#06b6d4] transition-colors text-white py-2 rounded-btn text-[13px] font-medium mt-2">Submit Request</button>
        </form>
    </div>
</template>
