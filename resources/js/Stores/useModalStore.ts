import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useModalStore = defineStore('modal', () => {
    const activeModal = ref<string | null>(null);
    const modalProps = ref<Record<string, any>>({});

    function openModal(modalName: string, props: Record<string, any> = {}) {
        activeModal.value = modalName;
        modalProps.value = props;
    }

    function closeModal() {
        activeModal.value = null;
        modalProps.value = {};
    }

    return {
        activeModal,
        modalProps,
        openModal,
        closeModal
    };
});
