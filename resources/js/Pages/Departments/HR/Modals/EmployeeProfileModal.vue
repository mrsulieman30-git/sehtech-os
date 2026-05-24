<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { PhX, PhUser, PhEnvelope, PhCalendarBlank, PhMoney, PhBriefcase, PhStar, PhArrowDown, PhArrowUp, PhFirstAid, PhBank, PhIdentificationBadge } from '@phosphor-icons/vue';

const props = defineProps<{ userId: string }>();
const emit = defineEmits(['close']);

const loading = ref(true);
const employee = ref<any>(null);
const manager = ref<any>(null);
const leaveHistory = ref<any[]>([]);
const reviews = ref<any[]>([]);
const activeTab = ref<'overview'|'leave'|'performance'>('overview');

onMounted(async () => {
    try {
        const resp = await axios.get(`/api/hr/employees/${props.userId}`);
        employee.value = resp.data.user;
        manager.value = resp.data.manager;
        leaveHistory.value = resp.data.leave_history || [];
        reviews.value = resp.data.performance_reviews || [];
    } catch (e) {
        console.error('Failed to load employee profile', e);
    } finally {
        loading.value = false;
    }
});

const formatDate = (d: string | null) => d ? new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : '—';
const formatCurrency = (n: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(n || 0);
const statusColor = (s: string) => ({ approved: 'text-green-600 bg-green-50', rejected: 'text-red-600 bg-red-50', pending: 'text-amber-600 bg-amber-50' }[s] || 'text-slate-500 bg-slate-50');
const ratingStars = (r: number) => '★'.repeat(r) + '☆'.repeat(5 - r);
</script>

<template>
    <div class="bg-white rounded-xl w-[680px] max-w-[95vw] shadow-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200 max-h-[90vh]">
        <!-- Header -->
        <div class="h-[56px] px-6 border-b border-shell-border flex items-center justify-between shrink-0 bg-gradient-to-r from-dept-hr-main to-cyan-500">
            <div class="flex items-center gap-2 text-white">
                <PhUser :size="20" weight="fill" />
                <h3 class="text-[15px] font-bold">Employee Profile</h3>
            </div>
            <button @click="$emit('close')" class="w-8 h-8 flex items-center justify-center rounded-btn hover:bg-white/20 text-white transition-colors border-0 bg-transparent cursor-pointer">
                <PhX :size="16" />
            </button>
        </div>

        <div v-if="loading" class="flex items-center justify-center py-16 text-[13px] text-text-secondary">Loading profile...</div>

        <template v-else-if="employee">
            <!-- Identity Banner -->
            <div class="px-6 py-4 flex items-center gap-4 bg-slate-50 border-b border-shell-border">
                <div class="w-14 h-14 rounded-full bg-gradient-to-br from-dept-hr-main to-cyan-400 text-white text-[20px] font-black flex items-center justify-center shrink-0">
                    {{ (employee.name || 'U').charAt(0) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-[17px] font-black text-text-primary truncate">{{ employee.name }}</div>
                    <div class="text-[12px] text-text-secondary">{{ employee.employee_profile?.job_title || 'N/A' }} · {{ employee.department?.name || 'No Dept' }}</div>
                    <div class="text-[11px] text-text-disabled">{{ employee.email }}</div>
                </div>
                <div class="text-right shrink-0 space-y-0.5">
                    <div class="text-[16px] font-black text-text-primary">{{ formatCurrency(employee.employee_profile?.salary) }}</div>
                    <div class="text-[10px] font-bold uppercase text-text-disabled">Annual Salary</div>
                </div>
            </div>

            <!-- Tab Switcher -->
            <div class="px-6 pt-2 flex gap-1 border-b border-shell-border">
                <button v-for="tab in (['overview', 'leave', 'performance'] as const)" :key="tab" @click="activeTab = tab"
                    class="px-3 py-2 text-[12px] font-bold uppercase transition-colors cursor-pointer bg-transparent border-0"
                    :class="activeTab === tab ? 'text-dept-hr-main border-b-2 border-dept-hr-main' : 'text-text-disabled hover:text-text-secondary'">
                    {{ tab }}
                </button>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto px-6 py-4">
                <!-- Overview Tab -->
                <div v-if="activeTab === 'overview'" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-50 rounded-xl p-3 border border-shell-border">
                            <div class="text-[10px] font-bold text-text-disabled uppercase mb-1">Hire Date</div>
                            <div class="flex items-center gap-1.5 text-[13px] font-medium text-text-primary">
                                <PhCalendarBlank :size="14" class="text-dept-hr-main" />
                                {{ formatDate(employee.employee_profile?.hire_date) }}
                            </div>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-3 border border-shell-border">
                            <div class="text-[10px] font-bold text-text-disabled uppercase mb-1">Employment Type</div>
                            <div class="flex items-center gap-1.5 text-[13px] font-medium text-text-primary">
                                <PhBriefcase :size="14" class="text-dept-hr-main" />
                                {{ (employee.employee_profile?.employment_type || 'full_time').replace('_', ' ') }}
                            </div>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-3 border border-shell-border">
                            <div class="text-[10px] font-bold text-text-disabled uppercase mb-1">Reports To</div>
                            <div class="text-[13px] font-medium text-text-primary">{{ manager?.name || 'None' }}</div>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-3 border border-shell-border">
                            <div class="text-[10px] font-bold text-text-disabled uppercase mb-1">System Role</div>
                            <div class="text-[13px] font-medium text-text-primary">{{ employee.role?.name || 'N/A' }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-green-50/50 rounded-xl p-3 border border-green-100">
                            <div class="text-[10px] font-bold text-green-700 uppercase mb-1">Annual Leave</div>
                            <div class="text-[18px] font-black text-green-700">{{ employee.employee_profile?.annual_leave_balance ?? 21 }} <span class="text-[11px] font-medium">days remaining</span></div>
                        </div>
                        <div class="bg-amber-50/50 rounded-xl p-3 border border-amber-100">
                            <div class="text-[10px] font-bold text-amber-700 uppercase mb-1">Sick Leave</div>
                            <div class="text-[18px] font-black text-amber-700">{{ employee.employee_profile?.sick_leave_balance ?? 14 }} <span class="text-[11px] font-medium">days remaining</span></div>
                        </div>
                    </div>

                    <!-- ID & Bank Section -->
                    <div class="p-4 bg-slate-50 rounded-xl border border-shell-border space-y-2">
                        <div class="flex items-center gap-2 text-[12px] font-bold text-text-secondary uppercase">
                            <PhIdentificationBadge :size="14" />
                            Identification & Banking
                        </div>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-[12px]">
                            <div><span class="text-text-disabled">National ID:</span> <span class="font-medium text-text-primary">{{ employee.employee_profile?.national_id || '—' }}</span></div>
                            <div><span class="text-text-disabled">Tax ID:</span> <span class="font-medium text-text-primary">{{ employee.employee_profile?.tax_id || '—' }}</span></div>
                            <div><span class="text-text-disabled">Bank:</span> <span class="font-medium text-text-primary">{{ employee.employee_profile?.bank_details?.bank_name || '—' }}</span></div>
                            <div><span class="text-text-disabled">Account:</span> <span class="font-medium text-text-primary">{{ employee.employee_profile?.bank_details?.account_number || '—' }}</span></div>
                        </div>
                    </div>

                    <!-- Emergency Contact -->
                    <div v-if="employee.employee_profile?.emergency_contact" class="p-4 bg-red-50/30 rounded-xl border border-red-100 space-y-1">
                        <div class="flex items-center gap-2 text-[12px] font-bold text-red-700 uppercase">
                            <PhFirstAid :size="14" />
                            Emergency Contact
                        </div>
                        <div class="text-[13px] font-medium text-text-primary">{{ employee.employee_profile.emergency_contact.name }}</div>
                        <div class="text-[12px] text-text-secondary">{{ employee.employee_profile.emergency_contact.phone }} · {{ employee.employee_profile.emergency_contact.relationship }}</div>
                    </div>
                </div>

                <!-- Leave Tab -->
                <div v-if="activeTab === 'leave'">
                    <div v-if="!leaveHistory.length" class="text-center py-10 text-[13px] text-text-disabled">No leave records found.</div>
                    <div v-else class="space-y-2">
                        <div v-for="l in leaveHistory" :key="l.id" class="flex items-center justify-between p-3 rounded-lg bg-slate-50 border border-shell-border">
                            <div>
                                <div class="text-[13px] font-bold text-text-primary capitalize">{{ l.type }} Leave</div>
                                <div class="text-[11px] text-text-secondary">{{ formatDate(l.start_date) }} → {{ formatDate(l.end_date) }} ({{ l.total_days }}d)</div>
                            </div>
                            <span class="text-[11px] font-bold uppercase px-2 py-0.5 rounded-full" :class="statusColor(l.status)">{{ l.status }}</span>
                        </div>
                    </div>
                </div>

                <!-- Performance Tab -->
                <div v-if="activeTab === 'performance'">
                    <div v-if="!reviews.length" class="text-center py-10 text-[13px] text-text-disabled">No performance reviews yet.</div>
                    <div v-else class="space-y-3">
                        <div v-for="r in reviews" :key="r.id" class="p-4 rounded-xl bg-slate-50 border border-shell-border">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-[13px] font-bold text-text-primary">{{ r.review_cycle }}</div>
                                <div class="text-amber-500 text-[14px]">{{ ratingStars(r.rating) }}</div>
                            </div>
                            <p class="text-[12px] text-text-secondary">{{ r.feedback }}</p>
                            <div class="mt-2 text-[10px] text-text-disabled">Reviewed by {{ r.reviewer?.name || 'N/A' }} · {{ formatDate(r.created_at) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
