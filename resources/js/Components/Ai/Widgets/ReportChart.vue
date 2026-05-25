<script setup lang="ts">
import { computed } from 'vue';
import { Bar, Line } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, PointElement, LineElement } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, PointElement, LineElement);

const props = defineProps({
    type: { type: String, default: 'bar' }, // 'bar' or 'line'
    title: { type: String, required: true },
    labels: { type: String, required: true }, // comma separated
    data: { type: String, required: true }, // comma separated numbers
});

const chartData = computed(() => {
    return {
        labels: props.labels.split(',').map(l => l.trim()),
        datasets: [
            {
                label: props.title,
                backgroundColor: props.type === 'line' ? 'rgba(15, 118, 110, 0.2)' : '#0F766E', // dept-ai-main color
                borderColor: '#0F766E',
                borderWidth: 1,
                data: props.data.split(',').map(d => parseFloat(d.trim())),
                fill: props.type === 'line'
            }
        ]
    }
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        title: {
            display: true,
            text: props.title,
            color: '#334155', // slate-700
            font: { size: 14, family: "'Inter', sans-serif" }
        }
    },
    scales: {
        y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
        x: { grid: { display: false } }
    }
};
</script>

<template>
    <div class="bg-white border border-shell-border rounded-xl p-4 shadow-sm w-full max-w-md my-3 h-[250px]">
        <Bar v-if="type === 'bar'" :data="chartData" :options="chartOptions" />
        <Line v-else :data="chartData" :options="chartOptions" />
    </div>
</template>
