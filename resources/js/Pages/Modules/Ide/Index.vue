<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { PhCode, PhWarning, PhCircleNotch } from '@phosphor-icons/vue';
import { useToastStore } from '@/Stores/useToastStore';

const toastStore = useToastStore();
const isLoading = ref(true);
const ideUrl = ref<string | null>(null);
const hasError = ref(false);

const checkIframeLoaded = () => {
    isLoading.value = false;
};

onMounted(async () => {
    try {
        const response = await axios.get('/api/ide/url');
        if (response.data && response.data.url) {
            let url = response.data.url;
            
            // Allow code-server to restore its own state (last opened folder/files)
            // by stripping the ?workspace= parameter after the very first load
            if (!localStorage.getItem('ide_initialized_v2')) {
                localStorage.setItem('ide_initialized_v2', 'true');
            } else {
                url = url.split('?')[0];
            }
            
            ideUrl.value = url;
        } else {
            hasError.value = true;
        }
    } catch (error) {
        console.error('Failed to load IDE URL', error);
        hasError.value = true;
        toastStore.add({
            title: 'IDE Error',
            message: 'Failed to connect to the Code-Server backend.',
            type: 'error'
        });
    }
});

const retryConnection = async () => {
    isLoading.value = true;
    hasError.value = false;
    try {
        const response = await axios.get('/api/ide/url');
        if (response.data && response.data.url) {
            let url = response.data.url;
            if (localStorage.getItem('ide_initialized_v2')) {
                url = url.split('?')[0];
            }
            ideUrl.value = url;
        } else {
            hasError.value = true;
        }
    } catch (error) {
        hasError.value = true;
    } finally {
        if (hasError.value) {
            isLoading.value = false;
        }
    }
};
</script>

<template>
    <div class="w-full h-full bg-[#1e1e1e] flex flex-col overflow-hidden relative">
        <!-- Loading State -->
        <div v-if="isLoading" class="absolute inset-0 flex flex-col items-center justify-center bg-[#1e1e1e] z-10 text-white/50">
            <PhCircleNotch :size="48" class="animate-spin text-[#0EA5E9] mb-4" />
            <p class="text-sm font-medium tracking-wide">Connecting to Development Server...</p>
        </div>

        <!-- Error State -->
        <div v-if="hasError && !ideUrl" class="absolute inset-0 flex flex-col items-center justify-center bg-[#1e1e1e] z-20 text-white p-6 text-center">
            <div class="w-20 h-20 rounded-full bg-red-500/10 flex items-center justify-center mb-6 border border-red-500/20">
                <PhWarning :size="40" class="text-red-400" />
            </div>
            <h2 class="text-xl font-bold mb-2">Connection Failed</h2>
            <p class="text-white/60 max-w-md mb-8">
                We couldn't connect to your dedicated Code-Server instance. Make sure the Node.js IDE backend is running on your Hostinger VPS.
            </p>
            <button @click="retryConnection" class="px-6 py-2.5 bg-[#0EA5E9] hover:bg-[#0284C7] text-white rounded-lg font-medium transition-colors shadow-lg shadow-[#0EA5E9]/20">
                Retry Connection
            </button>
        </div>

        <!-- The IDE Iframe -->
        <iframe 
            v-if="ideUrl"
            :src="ideUrl" 
            class="w-full h-full border-none outline-none flex-1"
            @load="checkIframeLoaded"
            allow="clipboard-read; clipboard-write; fullscreen"
        ></iframe>
    </div>
</template>
