<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { 
    PhBuildings, PhUsers, PhShieldCheck, PhListDashes, 
    PhMagnifyingGlass, PhPlus, PhDotsThree, PhGear
} from '@phosphor-icons/vue';
import { useModalStore } from '@/Stores/useModalStore';

const modalStore = useModalStore();

const activeTab = ref('users');
const searchQuery = ref('');
const users = ref<any[]>([]);
const roles = ref<any[]>([]);
const selectedRole = ref<any>(null);
const isSavingRole = ref(false);

const saveAdminSettings = () => {
    window.alert('Settings saved successfully!');
};

const permissionCategories = [
    { name: 'HR & People', perms: ['hr.*', 'employees.manage', 'leave.approve', 'leave.request', 'performance.write', 'payroll.view', 'payroll.run'] },
    { name: 'Finance', perms: ['finance.*', 'finance.view', 'bills.manage', 'reports.export'] },
    { name: 'Sales & CRM', perms: ['sales.*', 'crm.manage', 'deals.manage', 'contacts.manage'] },
    { name: 'Development', perms: ['dev.manage', 'tasks.assign', 'projects.create'] },
    { name: 'Legal', perms: ['legal.*', 'contracts.manage', 'compliance.manage'] },
    { name: 'Support', perms: ['support.*', 'tickets.manage', 'kb.manage'] },
    { name: 'System', perms: ['profile.edit', 'profile.view', 'files.access', 'files.read', 'calendar.view', 'notes.access', 'reports.view', 'dept.manage', 'dept.users', 'dept.reports'] },
];

const fetchUsers = async () => {
    try {
        const res = await axios.get('/api/admin/users');
        users.value = res.data.users;
    } catch(e) { console.error(e); }
};

const fetchRoles = async () => {
    try {
        const res = await axios.get('/api/admin/roles');
        roles.value = res.data.roles;
        if (roles.value.length > 0 && !selectedRole.value) {
            selectedRole.value = roles.value[0];
        }
    } catch(e) { console.error(e); }
};

const handleRefresh = () => {
    fetchUsers();
    fetchRoles();
};

const saveRole = async (role: any) => {
    if (role.is_super_admin) return;
    isSavingRole.value = true;
    try {
        await axios.put(`/api/admin/roles/${role.id}`, { name: role.name, permissions: role.permissions });
        window.alert('Role permissions updated successfully!');
    } catch(e) {
        console.error(e);
        window.alert('Failed to update role.');
    } finally {
        isSavingRole.value = false;
    }
};

const hasPermission = (role: any, perm: string) => {
    if (!role) return false;
    if (role.permissions.includes('*')) return true;
    return role.permissions.includes(perm);
};

const togglePermission = (role: any, perm: string) => {
    if (role.is_super_admin) return;
    if (role.permissions.includes(perm)) {
        role.permissions = role.permissions.filter((p: string) => p !== perm);
    } else {
        role.permissions.push(perm);
    }
};

onMounted(() => {
    fetchUsers();
    fetchRoles();
    window.addEventListener('refresh-admin-dashboard', handleRefresh);
});
onUnmounted(() => window.removeEventListener('refresh-admin-dashboard', handleRefresh));
</script>

<template>
    <div class="h-full flex flex-row bg-shell-panel text-text-primary overflow-hidden font-sans">
        <div class="w-[240px] flex-shrink-0 border-r border-shell-border bg-shell-panel flex flex-col h-full">
            <div class="h-[56px] flex items-center px-6 border-b border-shell-border bg-white shrink-0 gap-3">
                <PhBuildings :size="24" class="text-dept-admin-main" weight="fill" />
                <h2 class="text-[15px] font-bold text-text-primary">Administration</h2>
            </div>
            <div class="flex-1 overflow-y-auto p-3 flex flex-col gap-1">
                <button @click="activeTab = 'users'" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-btn text-[13px] font-medium transition-colors" :class="activeTab === 'users' ? 'bg-dept-admin-main/10 text-dept-admin-main' : 'text-text-secondary hover:bg-shell-border/50'">
                    <PhUsers :size="18" :weight="activeTab === 'users' ? 'fill' : 'regular'" /> User Management
                </button>
                <button @click="activeTab = 'roles'" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-btn text-[13px] font-medium transition-colors" :class="activeTab === 'roles' ? 'bg-dept-admin-main/10 text-dept-admin-main' : 'text-text-secondary hover:bg-shell-border/50'">
                    <PhShieldCheck :size="18" :weight="activeTab === 'roles' ? 'fill' : 'regular'" /> Roles & Permissions
                </button>
                <button @click="activeTab = 'settings'" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-btn text-[13px] font-medium transition-colors" :class="activeTab === 'settings' ? 'bg-dept-admin-main/10 text-dept-admin-main' : 'text-text-secondary hover:bg-shell-border/50'">
                    <PhGear :size="18" :weight="activeTab === 'settings' ? 'fill' : 'regular'" /> System Settings
                </button>
                <button @click="activeTab = 'logs'" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-btn text-[13px] font-medium transition-colors" :class="activeTab === 'logs' ? 'bg-dept-admin-main/10 text-dept-admin-main' : 'text-text-secondary hover:bg-shell-border/50'">
                    <PhListDashes :size="18" :weight="activeTab === 'logs' ? 'fill' : 'regular'" /> Audit Logs
                </button>
            </div>
        </div>

        <div class="flex-1 flex flex-col h-full bg-[#F8FAFC] overflow-hidden">
            <div class="h-[56px] bg-white border-b border-shell-border flex items-center px-6 shrink-0 justify-between">
                <div class="relative w-64">
                    <PhMagnifyingGlass :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-text-disabled" />
                    <input v-model="searchQuery" type="text" placeholder="Search records..." class="w-full pl-9 pr-4 py-1.5 bg-shell-panel border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-admin-main outline-none transition-all" />
                </div>
                <button @click="modalStore.openModal('add-user')" class="flex items-center gap-2 px-4 py-1.5 bg-dept-admin-main text-white text-[13px] font-medium rounded-btn hover:bg-[#0F172A] transition-colors shadow-sm">
                    <PhPlus :size="16" weight="bold" /> Add New User
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-6">
                <!-- User Management -->
                <div v-if="activeTab === 'users'" class="bg-white border border-shell-border rounded-card shadow-sm overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-shell-panel border-b border-shell-border text-[12px] text-text-disabled uppercase tracking-wider">
                                <th class="px-6 py-3 font-semibold">User</th>
                                <th class="px-6 py-3 font-semibold">Role</th>
                                <th class="px-6 py-3 font-semibold">Status</th>
                                <th class="px-6 py-3 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-[13px]">
                            <tr v-for="user in users" :key="user.id" class="border-b border-shell-border hover:bg-shell-panel/50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-text-primary">{{ user.name }}</div>
                                    <div class="text-[11px] text-text-secondary">{{ user.email }}</div>
                                </td>
                                <td class="px-6 py-4 text-text-secondary">{{ user.role?.name || 'User' }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase" :class="user.status === 'active' ? 'bg-state-success/10 text-state-success' : 'bg-shell-border text-text-secondary'">
                                        {{ user.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button class="p-1.5 hover:bg-white hover:text-text-primary rounded transition-colors"><PhDotsThree :size="16" weight="bold" /></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Roles & Permissions -->
                <div v-else-if="activeTab === 'roles'">
                    <div class="bg-white border border-shell-border rounded-card shadow-sm overflow-hidden flex">
                        <!-- Roles Sidebar -->
                        <div class="w-64 border-r border-shell-border flex flex-col">
                            <div class="p-4 border-b border-shell-border bg-shell-panel flex items-center justify-between">
                                <span class="font-bold text-[13px]">System Roles</span>
                                <button class="text-dept-admin-main hover:bg-dept-admin-main/10 p-1 rounded transition-colors"><PhPlus :size="16" weight="bold" /></button>
                            </div>
                            <div class="flex-1 overflow-y-auto max-h-[600px]">
                                <div v-for="role in roles" :key="role.id" @click="selectedRole = role" 
                                    class="px-4 py-3 border-b border-shell-border hover:bg-shell-panel cursor-pointer"
                                    :class="selectedRole?.id === role.id ? 'bg-shell-panel border-l-4 border-l-dept-admin-main' : ''">
                                    <div class="font-medium text-[13px] text-text-primary">{{ role.name }}</div>
                                    <div class="text-[11px] text-text-secondary mt-0.5 flex justify-between">
                                        <span>{{ role.users_count }} Users</span>
                                        <span v-if="role.is_super_admin" class="text-state-warning font-semibold">Super Admin</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Permissions Matrix -->
                        <div class="flex-1 flex flex-col bg-white overflow-y-auto max-h-[600px]" v-if="selectedRole">
                            <div class="p-5 border-b border-shell-border flex justify-between items-center sticky top-0 bg-white/90 backdrop-blur z-10">
                                <div>
                                    <h3 class="font-bold text-[15px]">{{ selectedRole.name }}</h3>
                                    <p class="text-[12px] text-text-secondary">Configure access levels for this role across modules.</p>
                                </div>
                                <button v-if="!selectedRole.is_super_admin" @click="saveRole(selectedRole)" :disabled="isSavingRole" class="bg-dept-admin-main text-white px-4 py-1.5 rounded-btn text-[13px] font-medium transition-colors shadow-sm disabled:opacity-50">
                                    {{ isSavingRole ? 'Saving...' : 'Save Permissions' }}
                                </button>
                                <div v-else class="text-[12px] font-medium text-state-warning bg-state-warning/10 px-3 py-1 rounded">Super Admin roles cannot be modified</div>
                            </div>
                            <div class="p-5 grid grid-cols-2 gap-x-8 gap-y-6">
                                <div v-for="category in permissionCategories" :key="category.name" class="flex flex-col gap-3">
                                    <h4 class="font-bold text-[13px] text-text-primary border-b border-shell-border pb-1">{{ category.name }}</h4>
                                    <div class="flex flex-col gap-2 pl-2">
                                        <label v-for="perm in category.perms" :key="perm" class="flex items-center gap-2 cursor-pointer group">
                                            <input 
                                                type="checkbox" 
                                                :checked="hasPermission(selectedRole, perm)"
                                                @change="togglePermission(selectedRole, perm)"
                                                :disabled="selectedRole.is_super_admin"
                                                class="w-3.5 h-3.5 accent-dept-admin-main rounded-sm border-shell-border" 
                                            />
                                            <span class="text-[12px] text-text-secondary group-hover:text-text-primary transition-colors font-medium">{{ perm }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Settings -->
                <div v-else-if="activeTab === 'settings'">
                    <div class="bg-white p-6 rounded-card border border-shell-border shadow-sm max-w-3xl">
                        <h3 class="font-bold text-[16px] mb-4">Global Configurations</h3>
                        <form @submit.prevent="saveAdminSettings" class="flex flex-col gap-5">
                            <div class="flex flex-col gap-1">
                                <label class="text-[13px] font-semibold">Company Name</label>
                                <input type="text" value="SEHTECH" class="px-3 py-2 border border-shell-border rounded-input outline-none focus:ring-1 focus:ring-dept-admin-main text-[13px]">
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[13px] font-semibold">Support Email</label>
                                <input type="email" value="support@sehtech.com" class="px-3 py-2 border border-shell-border rounded-input outline-none focus:ring-1 focus:ring-dept-admin-main text-[13px]">
                            </div>
                            <div class="flex items-center justify-between py-3 border-y border-shell-border">
                                <div class="flex flex-col">
                                    <span class="text-[13px] font-bold">Two-Factor Authentication</span>
                                    <span class="text-[12px] text-text-secondary">Enforce 2FA for all active users</span>
                                </div>
                                <input type="checkbox" checked class="w-4 h-4 accent-dept-admin-main">
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="bg-dept-admin-main text-white px-5 py-2 rounded-btn text-[13px] font-medium hover:bg-[#0F172A] transition-colors">Save Settings</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Audit Logs -->
                <div v-else-if="activeTab === 'logs'">
                    <div class="bg-white border border-shell-border rounded-card shadow-sm overflow-hidden">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-shell-panel border-b border-shell-border text-[12px] text-text-disabled uppercase tracking-wider">
                                    <th class="px-6 py-3 font-semibold">Timestamp</th>
                                    <th class="px-6 py-3 font-semibold">Event</th>
                                    <th class="px-6 py-3 font-semibold">User</th>
                                    <th class="px-6 py-3 font-semibold">IP Address</th>
                                </tr>
                            </thead>
                            <tbody class="text-[13px]">
                                <tr class="border-b border-shell-border hover:bg-shell-panel/50">
                                <td class="px-6 py-3 text-text-secondary">2026-05-21 14:32:11</td>
                                <td class="px-6 py-3 font-medium text-dept-admin-main">User Created</td>
                                <td class="px-6 py-3">admin@sehtech.com</td>
                                <td class="px-6 py-3 text-text-secondary">192.168.1.100</td>
                            </tr>
                            <tr class="border-b border-shell-border text-[13px]">
                                <td class="px-6 py-3 text-text-secondary">2026-05-21 13:15:45</td>
                                <td class="px-6 py-3 font-medium text-state-error">Failed Login</td>
                                <td class="px-6 py-3">unknown@sehtech.com</td>
                                <td class="px-6 py-3 text-text-secondary">45.22.19.112</td>
                            </tr>
                            <tr class="border-b border-shell-border hover:bg-shell-panel/50">
                                <td class="px-6 py-3 text-text-secondary">2026-05-21 10:05:01</td>
                                <td class="px-6 py-3 font-medium text-state-success">Settings Updated</td>
                                <td class="px-6 py-3">admin@sehtech.com</td>
                                <td class="px-6 py-3 text-text-secondary">192.168.1.100</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
