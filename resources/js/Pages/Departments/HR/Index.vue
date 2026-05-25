<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import axios from 'axios';
import { 
    PhUsers, PhIdentificationBadge, PhCalendarCheck, PhChartPieSlice, 
    PhPlus, PhMagnifyingGlass, PhCheckCircle, PhXCircle, PhRobot,
    PhMoney, PhStar, PhEye, PhCurrencyDollar, PhArrowRight,
    PhPencilSimple, PhTrash
} from '@phosphor-icons/vue';
import dayjs from 'dayjs';
import { useModalStore } from '@/Stores/useModalStore';
import RichMessageRenderer from '@/Components/Ai/RichMessageRenderer.vue';

const modalStore = useModalStore();
const showMobileAi = ref(false);

interface Employee {
    id: string; name: string; email: string; avatar: string | null;
    status: string; job_title: string; hire_date: string; salary: number;
    employment_type: string;
    department: { name: string; primary_color: string; } | null;
    role: { name: string; } | null;
}

interface LeaveRequest {
    id: string; type: string; start_date: string; end_date: string; total_days: string;
    status: string;
    user: { name: string; avatar: string | null; };
}

const employees = ref<Employee[]>([]);
const leaveRequests = ref<LeaveRequest[]>([]);
const metrics = ref({ total_employees: 0, on_leave_today: 0, pending_requests: 0, avg_performance: 0, total_annual_payroll: 0 });
const searchQuery = ref('');
const activeTab = ref('directory');

const fetchDashboard = async () => {
    try {
        const response = await axios.get('/api/hr/dashboard');
        employees.value = response.data.employees;
        leaveRequests.value = response.data.leave_requests;
        metrics.value = response.data.metrics;
        
        // Dynamically update HR Nexus Agent welcome message with live data
        if (chatMessages.value.length === 1 && chatMessages.value[0].role === 'assistant') {
            chatMessages.value[0].content = `I noticed ${metrics.value.total_employees} active employees in the system with a total payroll of <strong>${formatCurrency(metrics.value.total_annual_payroll)}</strong>/year. ` + 
                (metrics.value.pending_requests > 0 
                    ? `There are <strong class="text-amber-600">${metrics.value.pending_requests} pending leave requests</strong> awaiting your review.` 
                    : 'All leave requests are up to date! ✅');
        }
    } catch (error) {
        console.error('Failed to fetch HR data', error);
    }
};

const updateLeaveStatus = async (id: string, status: string) => {
    try {
        await axios.put(`/api/hr/leave/${id}/status`, { status });
        fetchDashboard();
        // Synergy: refresh finance as salary data may change (leave impacts payroll)
        window.dispatchEvent(new CustomEvent('refresh-finance-dashboard'));
    } catch (error) {
        console.error('Failed to update leave status', error);
    }
};

const filteredEmployees = computed(() => {
    if (!searchQuery.value) return employees.value;
    const query = searchQuery.value.toLowerCase();
    return employees.value.filter(e => 
        e.name.toLowerCase().includes(query) || 
        e.job_title.toLowerCase().includes(query) ||
        (e.department?.name || '').toLowerCase().includes(query)
    );
});

const pendingLeaves = computed(() => leaveRequests.value.filter(r => r.status === 'pending'));
const recentLeaves = computed(() => leaveRequests.value.filter(r => r.status !== 'pending').slice(0, 5));

const formatCurrency = (n: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(n || 0);
const formatEmploymentType = (t: string) => (t || 'full_time').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());

const openEmployeeProfile = (id: string) => {
    modalStore.openModal('view-employee', { userId: id });
};

const deleteEmployee = async (employee: Employee) => {
    if (!confirm(`Are you sure you want to permanently delete ${employee.name}?`)) return;
    try {
        await axios.delete(`/api/hr/employees/${employee.id}`);
        fetchDashboard();
        window.dispatchEvent(new CustomEvent('refresh-finance-dashboard'));
    } catch (e) {
        console.error('Failed to delete employee', e);
    }
};

const openPerformanceReview = (employee: Employee) => {
    modalStore.openModal('record-performance', { userId: employee.id, userName: employee.name });
};

// HR Nexus Agent Chat logic
const chatMessage = ref('');
const chatMessages = ref<{ role: string; content: string }>([
    {
        role: 'assistant',
        content: 'Connecting to HR Nexus...'
    }
]);
const isChatTyping = ref(false);
const chatScrollContainer = ref<HTMLElement | null>(null);

const scrollToBottom = () => {
    setTimeout(() => {
        if (chatScrollContainer.value) {
            chatScrollContainer.value.scrollTop = chatScrollContainer.value.scrollHeight;
        }
    }, 50);
};

const sendChatMessage = async () => {
    if (!chatMessage.value.trim() || isChatTyping.value) return;
    
    const userMsg = chatMessage.value;
    chatMessages.value.push({ role: 'user', content: userMsg });
    chatMessage.value = '';
    isChatTyping.value = true;
    scrollToBottom();
    
    try {
        const response = await axios.post('/api/agents/hr-agent/chat', {
            message: userMsg
        });
        chatMessages.value.push({ role: 'assistant', content: response.data.response_text });
    } catch (e) {
        chatMessages.value.push({ 
            role: 'assistant', 
            content: 'HR Nexus is currently offline. Please ensure the AI service is running.' 
        });
    } finally {
        isChatTyping.value = false;
        scrollToBottom();
    }
};

onMounted(() => {
    fetchDashboard();
    window.addEventListener('refresh-hr-dashboard', fetchDashboard);
});

onUnmounted(() => {
    window.removeEventListener('refresh-hr-dashboard', fetchDashboard);
});
</script>

<template>
    <div class="h-full flex flex-col lg:flex-row bg-shell-panel text-text-primary overflow-hidden font-sans relative">
        
        <!-- Sidebar Nav -->
        <div class="w-full lg:w-[240px] flex-shrink-0 border-b lg:border-b-0 lg:border-r border-shell-border bg-shell-panel flex flex-col lg:h-full z-20">
            <div class="h-[48px] lg:h-[56px] flex items-center px-4 lg:px-6 border-b border-shell-border bg-white shrink-0 gap-3">
                <PhUsers :size="24" class="text-dept-hr-main" weight="fill" />
                <h2 class="text-[15px] font-bold text-text-primary">People & HR</h2>
            </div>
            
            <div class="flex-none lg:flex-1 overflow-x-auto lg:overflow-y-auto p-2 lg:p-3 flex flex-row lg:flex-col gap-1 whitespace-nowrap scrollbar-hide">
                <button 
                    @click="activeTab = 'directory'"
                    class="w-auto lg:w-full flex items-center gap-2 lg:gap-3 px-3 py-2 lg:py-2.5 rounded-btn text-[13px] font-medium transition-colors border-0 bg-transparent cursor-pointer"
                    :class="activeTab === 'directory' ? 'bg-dept-hr-main/10 text-dept-hr-main' : 'text-text-secondary hover:bg-shell-border/50'"
                >
                    <PhIdentificationBadge :size="18" :weight="activeTab === 'directory' ? 'fill' : 'regular'" />
                    Team Directory
                </button>
                <button 
                    @click="activeTab = 'leave'"
                    class="w-auto lg:w-full flex items-center gap-2 lg:gap-3 px-3 py-2 lg:py-2.5 rounded-btn text-[13px] font-medium transition-colors justify-between border-0 bg-transparent cursor-pointer"
                    :class="activeTab === 'leave' ? 'bg-dept-hr-main/10 text-dept-hr-main' : 'text-text-secondary hover:bg-shell-border/50'"
                >
                    <div class="flex items-center gap-2 lg:gap-3">
                        <PhCalendarCheck :size="18" :weight="activeTab === 'leave' ? 'fill' : 'regular'" />
                        Time Off
                    </div>
                    <span v-if="metrics.pending_requests > 0" class="ml-2 bg-state-warning text-white text-[10px] px-1.5 py-0.5 rounded-full font-bold">
                        {{ metrics.pending_requests }}
                    </span>
                </button>
                <button 
                    @click="activeTab = 'performance'"
                    class="w-auto lg:w-full flex items-center gap-2 lg:gap-3 px-3 py-2 lg:py-2.5 rounded-btn text-[13px] font-medium transition-colors border-0 bg-transparent cursor-pointer"
                    :class="activeTab === 'performance' ? 'bg-dept-hr-main/10 text-dept-hr-main' : 'text-text-secondary hover:bg-shell-border/50'"
                >
                    <PhChartPieSlice :size="18" :weight="activeTab === 'performance' ? 'fill' : 'regular'" />
                    Performance
                </button>
                <button 
                    @click="activeTab = 'payroll'"
                    class="w-auto lg:w-full flex items-center gap-2 lg:gap-3 px-3 py-2 lg:py-2.5 rounded-btn text-[13px] font-medium transition-colors border-0 bg-transparent cursor-pointer"
                    :class="activeTab === 'payroll' ? 'bg-dept-hr-main/10 text-dept-hr-main' : 'text-text-secondary hover:bg-shell-border/50'"
                >
                    <PhCurrencyDollar :size="18" :weight="activeTab === 'payroll' ? 'fill' : 'regular'" />
                    Payroll Overview
                </button>
            </div>

            <!-- Sidebar Quick Stats -->
            <div class="hidden lg:block p-3 border-t border-shell-border space-y-2 shrink-0">
                <div class="px-3 py-2 bg-white rounded-lg border border-shell-border">
                    <div class="text-[10px] font-bold text-text-disabled uppercase">Headcount</div>
                    <div class="text-[18px] font-black text-text-primary">{{ metrics.total_employees }}</div>
                </div>
                <div class="px-3 py-2 bg-white rounded-lg border border-shell-border">
                    <div class="text-[10px] font-bold text-text-disabled uppercase">On Leave Today</div>
                    <div class="text-[18px] font-black text-state-warning">{{ metrics.on_leave_today }}</div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col h-full bg-[#F8FAFC] overflow-hidden border-r-0 lg:border-r border-shell-border z-10">
            <!-- Top Bar -->
            <div class="h-[56px] bg-white border-b border-shell-border flex items-center px-4 lg:px-6 shrink-0 justify-between gap-2 overflow-x-auto hide-scrollbar">
                <div class="relative w-64">
                    <PhMagnifyingGlass :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-text-disabled" />
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        placeholder="Search by name, title, department..." 
                        class="w-full pl-9 pr-4 py-1.5 bg-shell-panel border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-hr-main outline-none transition-all"
                    />
                </div>
                <div class="flex gap-2 flex-shrink-0">
                    <button @click="modalStore.openModal('request-leave')" class="flex items-center gap-2 px-3 lg:px-4 py-1.5 bg-white border border-shell-border text-text-secondary text-[12px] lg:text-[13px] font-medium rounded-btn hover:bg-shell-panel transition-colors cursor-pointer whitespace-nowrap">
                        <PhCalendarCheck :size="16" /> Request Leave
                    </button>
                    <button @click="modalStore.openModal('onboard-employee')" class="flex items-center gap-2 px-3 lg:px-4 py-1.5 bg-dept-hr-main text-white text-[12px] lg:text-[13px] font-medium rounded-btn hover:bg-[#06b6d4] transition-colors shadow-sm border-0 cursor-pointer whitespace-nowrap">
                        <PhPlus :size="16" weight="bold" /> Onboard
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-4 lg:p-6 pb-20 lg:pb-6">

                <!-- ═══════════════ DIRECTORY TAB ═══════════════ -->
                <template v-if="activeTab === 'directory'">
                    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <div 
                            v-for="employee in filteredEmployees" 
                            :key="employee.id"
                            @click="openEmployeeProfile(employee.id)"
                            class="bg-white p-5 rounded-card border border-shell-border shadow-sm flex flex-col items-center text-center hover:border-dept-hr-main hover:shadow-card transition-all cursor-pointer relative group"
                        >
                            <div v-if="employee.department" class="absolute top-3 right-3 flex items-center gap-1.5">
                                <span class="text-[9px] font-bold text-text-disabled uppercase opacity-0 group-hover:opacity-100 transition-opacity">{{ employee.department.name }}</span>
                                <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: employee.department.primary_color }" :title="employee.department.name"></div>
                            </div>

                            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-dept-hr-main to-cyan-400 mb-3 overflow-hidden flex items-center justify-center text-[20px] font-bold text-white border-2 border-white shadow-sm">
                                <img v-if="employee.avatar" :src="employee.avatar" class="w-full h-full object-cover"/>
                                <span v-else>{{ employee.name.charAt(0) }}</span>
                            </div>
                            
                            <h3 class="text-[14px] font-semibold text-text-primary leading-tight">{{ employee.name }}</h3>
                            <p class="text-[12px] text-text-secondary mt-1">{{ employee.job_title }}</p>
                            <span class="mt-1 text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-slate-100 text-text-disabled">{{ formatEmploymentType(employee.employment_type) }}</span>
                            
                            <div class="mt-4 pt-3 border-t border-shell-border w-full flex justify-center gap-3">
                                <button @click.stop="openEmployeeProfile(employee.id)" class="hover:text-dept-hr-main transition-colors text-[12px] font-medium text-text-disabled flex items-center gap-1 bg-transparent border-0 cursor-pointer">
                                    <PhEye :size="13" /> Profile
                                </button>
                                <button @click.stop="modalStore.openModal('edit-employee', { userId: employee.id })" class="hover:text-blue-500 transition-colors text-[12px] font-medium text-text-disabled flex items-center gap-1 bg-transparent border-0 cursor-pointer">
                                    <PhPencilSimple :size="13" /> Edit
                                </button>
                                <button @click.stop="openPerformanceReview(employee)" class="hover:text-amber-500 transition-colors text-[12px] font-medium text-text-disabled flex items-center gap-1 bg-transparent border-0 cursor-pointer">
                                    <PhStar :size="13" /> Review
                                </button>
                                <button @click.stop="deleteEmployee(employee)" class="hover:text-red-500 transition-colors text-[12px] font-medium text-text-disabled flex items-center gap-1 bg-transparent border-0 cursor-pointer">
                                    <PhTrash :size="13" /> Delete
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="filteredEmployees.length === 0" class="text-center text-text-disabled py-12">
                        <PhUsers :size="48" weight="thin" class="mx-auto mb-3 text-shell-border" />
                        <p class="text-[13px]">No employees found matching your search.</p>
                    </div>
                </template>

                <!-- ═══════════════ LEAVE TAB ═══════════════ -->
                <template v-if="activeTab === 'leave'">
                    <h3 class="text-[14px] font-bold text-text-primary mb-4">Leave Management</h3>

                    <!-- All leave requests table -->
                    <div class="bg-white rounded-card border border-shell-border shadow-sm overflow-hidden overflow-x-auto">
                        <table class="w-full min-w-[600px]">
                            <thead>
                                <tr class="bg-slate-50 text-[11px] font-bold text-text-disabled uppercase">
                                    <th class="text-left px-4 py-3">Employee</th>
                                    <th class="text-left px-4 py-3">Type</th>
                                    <th class="text-left px-4 py-3">Dates</th>
                                    <th class="text-center px-4 py-3">Days</th>
                                    <th class="text-center px-4 py-3">Status</th>
                                    <th class="text-center px-4 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="req in leaveRequests" :key="req.id" class="border-t border-shell-border hover:bg-slate-50/50 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 rounded-full bg-dept-hr-main text-white flex items-center justify-center text-[10px] font-bold shrink-0">
                                                {{ req.user.name.charAt(0) }}
                                            </div>
                                            <span class="text-[13px] font-medium text-text-primary">{{ req.user.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-[11px] font-bold uppercase tracking-wider bg-shell-panel px-2 py-0.5 rounded border border-shell-border">{{ req.type }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-[12px] text-text-secondary">
                                        {{ dayjs(req.start_date).format('MMM D') }} → {{ dayjs(req.end_date).format('MMM D, YYYY') }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-[13px] font-bold text-text-primary">{{ req.total_days }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full"
                                            :class="{
                                                'text-green-700 bg-green-100': req.status === 'approved',
                                                'text-red-700 bg-red-100': req.status === 'rejected',
                                                'text-amber-700 bg-amber-100': req.status === 'pending',
                                            }">
                                            {{ req.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div v-if="req.status === 'pending'" class="flex justify-center gap-1">
                                            <button @click="updateLeaveStatus(req.id, 'approved')" class="px-2 py-1 bg-green-600 text-white rounded text-[10px] font-bold flex items-center gap-1 hover:bg-green-700 transition-colors border-0 cursor-pointer">
                                                <PhCheckCircle :size="12"/> Approve
                                            </button>
                                            <button @click="updateLeaveStatus(req.id, 'rejected')" class="px-2 py-1 bg-white border border-red-300 text-red-600 rounded text-[10px] font-bold flex items-center gap-1 hover:bg-red-50 transition-colors cursor-pointer">
                                                <PhXCircle :size="12"/> Reject
                                            </button>
                                        </div>
                                        <span v-else class="text-[11px] text-text-disabled">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-if="leaveRequests.length === 0" class="text-center py-10 text-[13px] text-text-disabled">
                            No leave requests found.
                        </div>
                    </div>
                </template>

                <!-- ═══════════════ PERFORMANCE TAB ═══════════════ -->
                <template v-if="activeTab === 'performance'">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-[14px] font-bold text-text-primary">Performance Reviews</h3>
                        <div class="flex items-center gap-3">
                            <div class="px-3 py-1.5 bg-amber-50 border border-amber-200 rounded-lg text-[12px] font-bold text-amber-700 flex items-center gap-1.5">
                                <PhStar :size="14" weight="fill" />
                                Company Avg: {{ metrics.avg_performance }}/5
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <div 
                            v-for="employee in filteredEmployees" 
                            :key="employee.id"
                            class="bg-white p-4 rounded-card border border-shell-border shadow-sm hover:border-amber-400 hover:shadow-card transition-all"
                        >
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-amber-400 to-orange-400 text-white text-[14px] font-bold flex items-center justify-center shrink-0">
                                    {{ employee.name.charAt(0) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="text-[13px] font-bold text-text-primary truncate">{{ employee.name }}</div>
                                    <div class="text-[11px] text-text-secondary truncate">{{ employee.job_title }}</div>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button @click="openPerformanceReview(employee)" class="flex-1 py-1.5 bg-amber-500 text-white rounded-btn text-[11px] font-bold flex items-center justify-center gap-1 hover:bg-amber-600 transition-colors border-0 cursor-pointer">
                                    <PhStar :size="12" /> Write Review
                                </button>
                                <button @click="openEmployeeProfile(employee.id)" class="px-2 py-1.5 bg-slate-100 text-text-secondary rounded-btn text-[11px] font-bold hover:bg-slate-200 transition-colors border-0 cursor-pointer" title="View past reviews">
                                    <PhEye :size="14" />
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- ═══════════════ PAYROLL TAB ═══════════════ -->
                <template v-if="activeTab === 'payroll'">
                    <h3 class="text-[14px] font-bold text-text-primary mb-4">Payroll Overview</h3>

                    <!-- Payroll Summary Cards -->
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="bg-gradient-to-br from-dept-hr-main to-cyan-400 p-5 rounded-xl text-white">
                            <div class="text-[11px] font-bold uppercase opacity-80">Total Annual Payroll</div>
                            <div class="text-[24px] font-black mt-1">{{ formatCurrency(metrics.total_annual_payroll) }}</div>
                            <div class="text-[11px] opacity-80 mt-1">{{ metrics.total_employees }} employees</div>
                        </div>
                        <div class="bg-white p-5 rounded-xl border border-shell-border shadow-sm">
                            <div class="text-[11px] font-bold uppercase text-text-disabled">Avg Monthly / Employee</div>
                            <div class="text-[20px] font-black text-text-primary mt-1">{{ formatCurrency(metrics.total_employees ? metrics.total_annual_payroll / metrics.total_employees / 12 : 0) }}</div>
                        </div>
                        <div class="bg-white p-5 rounded-xl border border-shell-border shadow-sm">
                            <div class="text-[11px] font-bold uppercase text-text-disabled">Monthly Payroll Run</div>
                            <div class="text-[20px] font-black text-text-primary mt-1">{{ formatCurrency(metrics.total_annual_payroll / 12) }}</div>
                        </div>
                    </div>

                    <!-- Payroll Table -->
                    <div class="bg-white rounded-card border border-shell-border shadow-sm overflow-hidden">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-slate-50 text-[11px] font-bold text-text-disabled uppercase">
                                    <th class="text-left px-4 py-3">Employee</th>
                                    <th class="text-left px-4 py-3">Department</th>
                                    <th class="text-left px-4 py-3">Type</th>
                                    <th class="text-right px-4 py-3">Annual Salary</th>
                                    <th class="text-right px-4 py-3">Monthly</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="emp in employees" :key="emp.id" class="border-t border-shell-border hover:bg-slate-50/50 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 rounded-full bg-dept-hr-main text-white flex items-center justify-center text-[10px] font-bold shrink-0">
                                                {{ emp.name.charAt(0) }}
                                            </div>
                                            <div>
                                                <div class="text-[13px] font-medium text-text-primary">{{ emp.name }}</div>
                                                <div class="text-[11px] text-text-secondary">{{ emp.job_title }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-[12px] text-text-secondary">{{ emp.department?.name || '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-slate-100 text-text-disabled">{{ formatEmploymentType(emp.employment_type) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-[13px] font-bold text-text-primary">{{ formatCurrency(emp.salary) }}</td>
                                    <td class="px-4 py-3 text-right text-[13px] text-text-secondary">{{ formatCurrency(emp.salary / 12) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>

            </div>
        </div>

        </div>

        <!-- Right Sidebar (AI) -->
        <div 
            class="fixed inset-0 z-50 bg-white flex flex-col transition-transform duration-300 lg:relative lg:inset-auto lg:z-10 lg:w-[320px] lg:flex-shrink-0 lg:translate-x-0"
            :class="showMobileAi ? 'translate-x-0' : 'translate-x-full'"
        >
            <div class="lg:hidden h-[48px] bg-dept-hr-main text-white flex items-center justify-between px-4 shrink-0">
                <div class="flex items-center gap-2 font-bold text-[14px]">
                    <PhRobot :size="20" weight="fill" /> HR Nexus
                </div>
                <button @click="showMobileAi = false" class="p-2 hover:bg-white/20 rounded-btn cursor-pointer border-0 bg-transparent text-white">
                    <PhX :size="20" />
                </button>
            </div>
            
            <!-- Pending Leave Panel -->
            <div class="h-1/2 flex flex-col border-b border-shell-border">
                <div class="h-[44px] flex items-center px-4 border-b border-shell-border bg-shell-panel shrink-0 justify-between">
                    <span class="text-[13px] font-semibold">Pending Time Off</span>
                    <span class="text-[11px] text-text-secondary">{{ pendingLeaves.length }} requests</span>
                </div>
                
                <div class="flex-1 overflow-y-auto p-3 flex flex-col gap-2">
                    <div v-if="pendingLeaves.length === 0" class="text-center text-text-disabled py-8 text-[12px]">
                        <PhCheckCircle :size="28" class="mx-auto mb-2 text-green-400" />
                        All caught up! No pending requests.
                    </div>
                    
                    <div 
                        v-for="req in pendingLeaves" 
                        :key="req.id"
                        class="p-3 bg-[#F8FAFC] border border-shell-border rounded-card"
                    >
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-dept-hr-main text-white flex items-center justify-center text-[10px]">
                                    {{ req.user.name.charAt(0) }}
                                </div>
                                <span class="text-[12px] font-semibold">{{ req.user.name }}</span>
                            </div>
                            <span class="text-[10px] font-bold uppercase tracking-wider bg-shell-panel px-1.5 py-0.5 rounded border border-shell-border">{{ req.type }}</span>
                        </div>
                        
                        <div class="text-[11px] text-text-secondary mb-3">
                            <span class="font-medium text-text-primary">{{ req.total_days }} Days</span> • 
                            {{ dayjs(req.start_date).format('MMM D') }} to {{ dayjs(req.end_date).format('MMM D') }}
                        </div>

                        <div class="flex gap-2">
                            <button @click="updateLeaveStatus(req.id, 'approved')" class="flex-1 py-1.5 bg-state-success text-white rounded text-[11px] font-medium flex items-center justify-center gap-1 hover:bg-opacity-90 border-0 cursor-pointer">
                                <PhCheckCircle :size="14"/> Approve
                            </button>
                            <button @click="updateLeaveStatus(req.id, 'rejected')" class="flex-1 py-1.5 bg-white border border-state-error text-state-error rounded text-[11px] font-medium flex items-center justify-center gap-1 hover:bg-state-error/10 cursor-pointer">
                                <PhXCircle :size="14"/> Reject
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- HR Nexus Agent -->
            <div class="h-1/2 flex flex-col bg-shell-panel">
                <div class="h-[44px] flex items-center px-4 border-b border-shell-border bg-dept-hr-main text-white shrink-0 gap-2">
                    <PhRobot :size="18" weight="fill" />
                    <span class="text-[13px] font-semibold tracking-wide">HR NEXUS Agent</span>
                </div>
                
                <div class="flex-1 p-4 overflow-y-auto text-[12px] flex flex-col gap-3 bg-white" ref="chatScrollContainer">
                    <div v-for="(m, idx) in chatMessages" :key="idx" :class="m.role === 'user' ? 'text-right' : 'text-left'">
                        <div class="inline-block p-3 rounded-card shadow-sm text-text-secondary max-w-[95%] leading-relaxed text-left" 
                             :class="m.role === 'user' ? 'bg-dept-hr-main/10 text-dept-hr-main border border-dept-hr-main/20 rounded-br-none' : 'bg-shell-panel border border-shell-border rounded-bl-none'">
                            <RichMessageRenderer :content="m.content" />
                        </div>
                    </div>
                    <div v-if="isChatTyping" class="text-[11px] text-text-disabled animate-pulse flex items-center gap-1.5 pl-1">
                        <PhRobot :size="14" class="animate-bounce text-dept-hr-main" /> HR Nexus is typing...
                    </div>
                </div>
                
                <div class="p-3 border-t border-shell-border bg-shell-panel">
                    <div class="relative">
                        <input 
                            v-model="chatMessage"
                            @keyup.enter="sendChatMessage"
                            type="text" 
                            placeholder="Ask Nexus to draft a policy..." 
                            class="w-full pl-3 pr-10 py-2 bg-white border border-shell-border rounded-input text-[12px] focus:ring-1 focus:ring-dept-hr-main outline-none"
                        />
                        <button @click="sendChatMessage" class="absolute right-2 top-1/2 -translate-y-1/2 text-dept-hr-main hover:text-cyan-600 transition-colors bg-transparent border-0 cursor-pointer flex items-center justify-center">
                            <PhArrowRight :size="16" weight="bold" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile FAB -->
        <button 
            v-if="!showMobileAi"
            @click="showMobileAi = true"
            class="lg:hidden absolute bottom-6 right-6 w-14 h-14 bg-dept-hr-main text-white rounded-full shadow-2xl flex items-center justify-center z-40 border-0 cursor-pointer active:scale-95 transition-transform"
        >
            <PhRobot :size="28" weight="fill" />
        </button>
    </div>
</template>
