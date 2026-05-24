import { defineAsyncComponent } from 'vue';

export const AppRegistry: Record<string, any> = {
    // Modules
    'files': defineAsyncComponent(() => import('@/Pages/Modules/FileSystem/Index.vue')),
    'settings': defineAsyncComponent(() => import('@/Pages/Modules/Settings/Index.vue')),
    'forms': defineAsyncComponent(() => import('@/Pages/Modules/FormsBuilder/Index.vue')),
    'calendar': defineAsyncComponent(() => import('@/Pages/Modules/Calendar/Index.vue')),
    'agents': defineAsyncComponent(() => import('@/Pages/Modules/Agents/ControlRoom.vue')),
    
    // Departments
    'admin': defineAsyncComponent(() => import('@/Pages/Departments/Admin/Index.vue')),
    'research': defineAsyncComponent(() => import('@/Pages/Departments/Research/Index.vue')),
    'dev': defineAsyncComponent(() => import('@/Pages/Departments/Development/Index.vue')),
    'marketing': defineAsyncComponent(() => import('@/Pages/Departments/Marketing/Index.vue')),
    'sales': defineAsyncComponent(() => import('@/Pages/Departments/Sales/Index.vue')),
    'legal': defineAsyncComponent(() => import('@/Pages/Departments/Legal/Index.vue')),
    'finance': defineAsyncComponent(() => import('@/Pages/Departments/Finance/Index.vue')),
    'hr': defineAsyncComponent(() => import('@/Pages/Departments/HR/Index.vue')),
    'support': defineAsyncComponent(() => import('@/Pages/Departments/Support/Index.vue')),
    'operations': defineAsyncComponent(() => import('@/Pages/Departments/Operations/Index.vue')),
    
    // Fallback
    'fallback': defineAsyncComponent(() => import('@/Components/OS/FallbackApp.vue')),
};
