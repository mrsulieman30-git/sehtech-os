import { defineStore } from 'pinia';
import { ref } from 'vue';

export interface ToastMessage {
    id: number;
    type: 'success' | 'error' | 'warning' | 'info';
    message: string;
    duration?: number;
}

export const useToastStore = defineStore('toast', () => {
    const toasts = ref<ToastMessage[]>([]);
    let nextId = 1;

    function addToast(type: ToastMessage['type'], message: string, duration = 3000) {
        const id = nextId++;
        toasts.value.push({ id, type, message, duration });
        
        if (duration > 0) {
            setTimeout(() => {
                removeToast(id);
            }, duration);
        }
    }

    function removeToast(id: number) {
        const index = toasts.value.findIndex(t => t.id === id);
        if (index > -1) {
            toasts.value.splice(index, 1);
        }
    }

    function showToast(message: string, type: ToastMessage['type'] = 'info', duration = 3000) {
        addToast(type, message, duration);
    }

    return {
        toasts,
        addToast,
        showToast,
        removeToast
    };
});
