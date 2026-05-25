<script setup lang="ts">
import { computed, defineAsyncComponent } from 'vue';
import { useModalStore } from '@/Stores/useModalStore';

const modalStore = useModalStore();

// Modal Registry
const modals: Record<string, any> = {
    'create-task': defineAsyncComponent(() => import('@/Pages/Departments/Development/Modals/CreateTaskModal.vue')),
    'create-node': defineAsyncComponent(() => import('@/Pages/Departments/Development/Modals/CreateNodeModal.vue')),
    'add-lead': defineAsyncComponent(() => import('@/Pages/Departments/Sales/Modals/AddLeadModal.vue')),
    'new-invoice': defineAsyncComponent(() => import('@/Pages/Departments/Finance/Modals/NewInvoiceModal.vue')),
    'add-user': defineAsyncComponent(() => import('@/Pages/Departments/Admin/Modals/AddUserModal.vue')),
    'edit-user': defineAsyncComponent(() => import('@/Pages/Departments/Admin/Modals/EditUserModal.vue')),
    'create-campaign': defineAsyncComponent(() => import('@/Pages/Departments/Marketing/Modals/CreateCampaignModal.vue')),
    'add-competitor': defineAsyncComponent(() => import('@/Pages/Departments/Marketing/Modals/AddCompetitorModal.vue')),
    'add-asset': defineAsyncComponent(() => import('@/Pages/Departments/Operations/Modals/AddAssetModal.vue')),
    'draft-contract': defineAsyncComponent(() => import('@/Pages/Departments/Legal/Modals/DraftContractModal.vue')),
    'generate-contract': defineAsyncComponent(() => import('@/Pages/Departments/Legal/Modals/GenerateContractModal.vue')),
    'create-risk': defineAsyncComponent(() => import('@/Pages/Departments/Legal/Modals/CreateRiskModal.vue')),
    'onboard-employee': defineAsyncComponent(() => import('@/Pages/Departments/HR/Modals/OnboardEmployeeModal.vue')),
    'view-employee': defineAsyncComponent(() => import('@/Pages/Departments/HR/Modals/EmployeeProfileModal.vue')),
    'edit-employee': defineAsyncComponent(() => import('@/Pages/Departments/HR/Modals/EditEmployeeModal.vue')),
    'record-performance': defineAsyncComponent(() => import('@/Pages/Departments/HR/Modals/RecordPerformanceModal.vue')),
    'record-payment': defineAsyncComponent(() => import('@/Pages/Departments/Finance/Modals/RecordPaymentModal.vue')),
    'new-bill': defineAsyncComponent(() => import('@/Pages/Departments/Finance/Modals/NewBillModal.vue')),
    'edit-bill': defineAsyncComponent(() => import('@/Pages/Departments/Finance/Modals/EditBillModal.vue')),
    'task-details': defineAsyncComponent(() => import('@/Pages/Departments/Development/Modals/TaskDetailsModal.vue')),
    'request-leave': defineAsyncComponent(() => import('@/Pages/Departments/HR/Modals/RequestLeaveModal.vue')),
    'resolve-ticket': defineAsyncComponent(() => import('@/Pages/Departments/Support/Modals/ResolveTicketModal.vue')),
    'new-ticket': defineAsyncComponent(() => import('@/Pages/Departments/Support/Modals/NewTicketModal.vue')),
    'declare-outage': defineAsyncComponent(() => import('@/Pages/Departments/Support/Modals/DeclareOutageModal.vue')),
    'create-kb-article': defineAsyncComponent(() => import('@/Pages/Departments/Support/Modals/CreateKbArticleModal.vue')),
    'add-research-idea': defineAsyncComponent(() => import('@/Pages/Departments/Research/Modals/AddResearchIdeaModal.vue')),
    'create-project': defineAsyncComponent(() => import('@/Pages/Departments/Development/Modals/CreateProjectModal.vue')),
    'create-crm-account': defineAsyncComponent(() => import('@/Pages/Departments/Sales/Modals/CreateCrmAccountModal.vue')),
    'view-account': defineAsyncComponent(() => import('@/Pages/Departments/Sales/Modals/ViewAccountModal.vue')),
    'create-crm-deal': defineAsyncComponent(() => import('@/Pages/Departments/Sales/Modals/CreateCrmDealModal.vue')),
    'view-deal': defineAsyncComponent(() => import('@/Pages/Departments/Sales/Modals/ViewDealModal.vue')),
    'create-crm-content': defineAsyncComponent(() => import('@/Pages/Departments/Marketing/Modals/CreateCrmContentModal.vue')),
};

const currentModalComponent = computed(() => {
    if (!modalStore.activeModal) return null;
    return modals[modalStore.activeModal] || null;
});
</script>

<template>
    <div 
        v-if="modalStore.activeModal"
        class="fixed inset-0 z-[5000] flex items-center justify-center bg-shell-bg/60 backdrop-blur-sm transition-opacity"
        @click.self="modalStore.closeModal"
    >
        <component 
            :is="currentModalComponent" 
            v-bind="modalStore.modalProps"
            @close="modalStore.closeModal"
        />
    </div>
</template>
