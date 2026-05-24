<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { 
    PhGear, PhBuildings, PhPalette, PhUsers, 
    PhRobot, PhBellRinging, PhHardDrives, PhShieldCheck,
    PhGlobe, PhCreditCard, PhPlus, PhFloppyDisk
} from '@phosphor-icons/vue';

const activeTab = ref('company_profile');
const isSaving = ref(false);

const defaultSettings = {
    company_profile: { name: 'SEHTECH', currency: 'USD', timezone: 'Africa/Mogadishu' },
    appearance: { theme: 'light', dock_position: 'bottom', compact_mode: false },
    localization: { default_lang: 'en', date_format: 'YYYY-MM-DD' },
    email: { host: '', port: '587', username: '', password: '', encryption: 'tls', from_address: '', from_name: '' },
    notifications: { email_onboarding: true, email_leave: true, email_bills: true },
    security: { enforce_2fa: true, session_timeout: 120, ip_allowlist: '' },
    storage: { max_upload_size: 50, allowed_types: 'pdf,jpg,png,doc,docx' },
    ai_providers: {
        provider: 'deepseek',
        deepseek: { model: 'deepseek-chat', api_key: '' },
        openai: { model: 'gpt-4o', api_key: '' },
        gemini: { model: 'gemini-1.5-pro', api_key: '' },
        claude: { model: 'claude-3-opus', api_key: '' }
    }
};

const data = ref({
    config: JSON.parse(JSON.stringify(defaultSettings)),
    users: [],
    departments: [],
    kb_articles: []
});

const fetchSettings = async () => {
    try {
        const response = await axios.get('/api/settings');
        // Merge the database settings into the defaults so missing keys don't break the UI
        for (const key in defaultSettings) {
            if (response.data.config && response.data.config[key]) {
                data.value.config[key] = { ...data.value.config[key], ...response.data.config[key] };
            }
        }
        data.value.users = response.data.users || [];
        data.value.departments = response.data.departments || [];
        data.value.kb_articles = response.data.kb_articles || [];
    } catch (error) {
        console.error('Failed to fetch settings', error);
    }
};

const saveSection = async (key: string, payload: any) => {
    isSaving.value = true;
    try {
        await axios.put(`/api/settings/${key}`, { value: payload });
    } catch (error) {
        console.error('Failed to save settings', error);
    } finally {
        setTimeout(() => isSaving.value = false, 500); // UI UX delay
    }
};

const isTestingSmtp = ref(false);

const testSmtp = async () => {
    isTestingSmtp.value = true;
    try {
        const res = await axios.post('/api/settings/test-smtp', data.value.config.email);
        alert(res.data.message);
    } catch (error: any) {
        alert(error.response?.data?.message || 'Failed to send test email.');
    } finally {
        isTestingSmtp.value = false;
    }
};

onMounted(() => fetchDashboard());

const menuItems = [
    { id: 'company_profile', label: 'Company Profile', icon: PhBuildings },
    { id: 'appearance', label: 'Appearance', icon: PhPalette },
    { id: 'localization', label: 'Localization', icon: PhGlobe },
    { id: 'users', label: 'Users & Roles', icon: PhUsers },
    { id: 'email', label: 'Email / SMTP', icon: PhGlobe }, // Using PhGlobe or similar
    { id: 'agents', label: 'AI & Knowledge Base', icon: PhRobot },
    { id: 'notifications', label: 'Notifications', icon: PhBellRinging },
    { id: 'storage', label: 'File System', icon: PhHardDrives },
    { id: 'security', label: 'Security', icon: PhShieldCheck },
    { id: 'billing', label: 'Billing & License', icon: PhCreditCard },
];

function fetchDashboard() {
    fetchSettings();
}
</script>

<template>
    <div class="h-full flex flex-row bg-shell-panel text-text-primary overflow-hidden font-sans">
        
        <div class="w-[260px] flex-shrink-0 border-r border-shell-border bg-shell-panel flex flex-col h-full">
            <div class="h-[56px] flex items-center px-6 border-b border-shell-border bg-white shrink-0 gap-3">
                <PhGear :size="24" class="text-text-secondary" weight="fill" />
                <h2 class="text-[15px] font-bold text-text-primary">Universal Settings</h2>
            </div>
            
            <div class="flex-1 overflow-y-auto py-4 px-3 flex flex-col gap-1">
                <button 
                    v-for="item in menuItems" 
                    :key="item.id"
                    @click="activeTab = item.id"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-btn text-[13px] font-medium transition-colors"
                    :class="activeTab === item.id ? 'bg-state-focus/10 text-state-focus' : 'text-text-secondary hover:bg-shell-border/50'"
                >
                    <component :is="item.icon" :size="18" :weight="activeTab === item.id ? 'fill' : 'regular'" />
                    {{ item.label }}
                </button>
            </div>
        </div>

        <div class="flex-1 flex flex-col h-full bg-[#F8FAFC] overflow-hidden">
            
            <div class="h-[56px] bg-white border-b border-shell-border flex items-center px-8 shrink-0 justify-between">
                <h2 class="text-[16px] font-semibold text-text-primary">{{ menuItems.find(i => i.id === activeTab)?.label }}</h2>
                <button 
                    v-if="['company_profile', 'appearance', 'localization', 'email', 'agents', 'notifications', 'security', 'storage'].includes(activeTab)"
                    @click="saveSection(activeTab === 'agents' ? 'ai_providers' : activeTab, data.config[activeTab === 'agents' ? 'ai_providers' : activeTab])"
                    :disabled="isSaving"
                    class="flex items-center gap-2 px-4 py-1.5 bg-state-focus text-white text-[13px] font-medium rounded-btn hover:bg-blue-700 transition-colors shadow-sm disabled:opacity-50"
                >
                    <PhFloppyDisk :size="16" weight="bold" /> 
                    {{ isSaving ? 'Saving...' : 'Save Changes' }}
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-8">
                <div class="max-w-3xl">

                    <div v-if="activeTab === 'company_profile'" class="bg-white border border-shell-border rounded-card p-6 flex flex-col gap-6 shadow-sm">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[13px] font-semibold text-text-primary">Company Name</label>
                            <input v-model="data.config.company_profile.name" type="text" class="w-full px-4 py-2 bg-gray-50 border border-shell-border rounded-input text-[13px] focus:bg-white focus:ring-1 focus:ring-state-focus outline-none" />
                        </div>
                        <div class="grid grid-cols-2 gap-6">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[13px] font-semibold text-text-primary">Default Currency</label>
                                <select v-model="data.config.company_profile.currency" class="w-full px-4 py-2 bg-gray-50 border border-shell-border rounded-input text-[13px] focus:bg-white focus:ring-1 focus:ring-state-focus outline-none">
                                    <option value="USD">USD ($)</option>
                                    <option value="EUR">EUR (€)</option>
                                    <option value="GBP">GBP (£)</option>
                                </select>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[13px] font-semibold text-text-primary">System Timezone</label>
                                <input v-model="data.config.company_profile.timezone" type="text" class="w-full px-4 py-2 bg-gray-50 border border-shell-border rounded-input text-[13px] focus:bg-white focus:ring-1 focus:ring-state-focus outline-none" />
                            </div>
                        </div>
                    </div>

                    <div v-if="activeTab === 'appearance'" class="bg-white border border-shell-border rounded-card p-6 flex flex-col gap-6 shadow-sm">
                        <div class="flex flex-col gap-3">
                            <label class="text-[13px] font-semibold text-text-primary">Desktop Theme</label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 text-[13px] cursor-pointer">
                                    <input type="radio" v-model="data.config.appearance.theme" value="light" class="text-state-focus focus:ring-state-focus"> Light Shell
                                </label>
                                <label class="flex items-center gap-2 text-[13px] cursor-pointer">
                                    <input type="radio" v-model="data.config.appearance.theme" value="dark" class="text-state-focus focus:ring-state-focus"> Dark Shell
                                </label>
                            </div>
                        </div>
                        <div class="flex flex-col gap-3">
                            <label class="text-[13px] font-semibold text-text-primary">Dock Position</label>
                            <select v-model="data.config.appearance.dock_position" class="w-64 px-4 py-2 bg-gray-50 border border-shell-border rounded-input text-[13px] focus:bg-white focus:ring-1 focus:ring-state-focus outline-none">
                                <option value="bottom">Bottom (macOS style)</option>
                                <option value="left">Left (Ubuntu style)</option>
                                <option value="right">Right</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="checkbox" v-model="data.config.appearance.compact_mode" class="rounded text-state-focus focus:ring-state-focus border-shell-border w-4 h-4">
                            <label class="text-[13px] font-semibold text-text-primary">Enable Compact Mode (reduces UI padding)</label>
                        </div>
                    </div>

                    <div v-if="activeTab === 'agents'" class="flex flex-col gap-6">
                        <div class="bg-white border border-shell-border rounded-card shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-shell-border bg-shell-panel flex justify-between items-center">
                                <div class="font-bold text-[14px]">Knowledge Base Articles (Agent Training Data)</div>
                                <button class="flex items-center gap-2 px-3 py-1.5 bg-state-focus text-white text-[12px] font-medium rounded hover:bg-blue-700 transition-colors">
                                    <PhPlus :size="14" weight="bold" /> Add Article
                                </button>
                            </div>
                            <table class="w-full text-left">
                                <thead class="bg-gray-50 border-b border-shell-border text-[11px] text-text-disabled uppercase tracking-wider">
                                    <tr>
                                        <th class="px-6 py-3 font-semibold">Title</th>
                                        <th class="px-6 py-3 font-semibold">Category</th>
                                        <th class="px-6 py-3 font-semibold">Access</th>
                                        <th class="px-6 py-3 font-semibold">Embedding Status</th>
                                    </tr>
                                </thead>
                                <tbody class="text-[13px]">
                                    <tr v-if="data.kb_articles.length === 0">
                                        <td colspan="4" class="px-6 py-8 text-center text-text-disabled">No Knowledge Base articles created yet.</td>
                                    </tr>
                                    <tr v-for="article in data.kb_articles" :key="article.id" class="border-b border-shell-border hover:bg-gray-50 cursor-pointer">
                                        <td class="px-6 py-3 font-medium">{{ article.title }}</td>
                                        <td class="px-6 py-3">{{ article.category }}</td>
                                        <td class="px-6 py-3 capitalize">{{ article.access_level.replace('_', ' ') }}</td>
                                        <td class="px-6 py-3">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase" :class="article.embedding_status === 'indexed' ? 'bg-state-success/10 text-state-success' : 'bg-state-warning/10 text-state-warning'">
                                                {{ article.embedding_status }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="bg-white border border-shell-border rounded-card p-6 shadow-sm">
                            <h3 class="font-bold text-[14px] mb-4">Python AI Service Connection</h3>
                            <div class="grid grid-cols-2 gap-6">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[13px] font-semibold text-text-primary">Service URL</label>
                                    <input type="text" disabled value="http://127.0.0.1:8001" class="w-full px-4 py-2 bg-gray-100 border border-shell-border rounded-input text-[13px] text-text-secondary outline-none cursor-not-allowed" />
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[13px] font-semibold text-text-primary">Connection Status</label>
                                    <div class="h-[38px] px-4 bg-state-success/10 border border-state-success/20 rounded-input text-[13px] font-bold text-state-success flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full bg-state-success animate-pulse"></div> Online
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white border border-shell-border rounded-card p-6 shadow-sm">
                            <h3 class="font-bold text-[14px] mb-4">AI Providers Configuration</h3>
                            
                            <div class="flex flex-col gap-1.5 mb-6">
                                <label class="text-[13px] font-semibold text-text-primary">Active AI Provider</label>
                                <select v-model="data.config.ai_providers.provider" class="w-1/2 px-4 py-2 bg-gray-50 border border-shell-border rounded-input text-[13px] focus:bg-white focus:ring-1 focus:ring-state-focus outline-none">
                                    <option value="deepseek">DeepSeek (Default)</option>
                                    <option value="openai">OpenAI</option>
                                    <option value="gemini">Google Gemini</option>
                                    <option value="claude">Anthropic Claude</option>
                                </select>
                            </div>

                            <div v-if="data.config.ai_providers.provider === 'deepseek'" class="p-4 border border-shell-border rounded bg-gray-50 flex flex-col gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[13px] font-semibold">DeepSeek Model</label>
                                    <select v-model="data.config.ai_providers.deepseek.model" class="w-full px-4 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-state-focus outline-none">
                                        <option value="deepseek-chat">DeepSeek V4 Flash (deepseek-chat)</option>
                                        <option value="deepseek-reasoner">DeepSeek V4 Pro (deepseek-reasoner)</option>
                                    </select>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[13px] font-semibold">DeepSeek API Key</label>
                                    <input type="password" v-model="data.config.ai_providers.deepseek.api_key" placeholder="sk-..." class="w-full px-4 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-state-focus outline-none" />
                                </div>
                            </div>

                            <div v-if="data.config.ai_providers.provider === 'openai'" class="p-4 border border-shell-border rounded bg-gray-50 flex flex-col gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[13px] font-semibold">OpenAI Model</label>
                                    <select v-model="data.config.ai_providers.openai.model" class="w-full px-4 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-state-focus outline-none">
                                        <option value="gpt-4o">GPT-4o</option>
                                        <option value="gpt-4-turbo">GPT-4 Turbo</option>
                                        <option value="gpt-3.5-turbo">GPT-3.5 Turbo</option>
                                    </select>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[13px] font-semibold">OpenAI API Key</label>
                                    <input type="password" v-model="data.config.ai_providers.openai.api_key" placeholder="sk-proj-..." class="w-full px-4 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-state-focus outline-none" />
                                </div>
                            </div>

                            <div v-if="data.config.ai_providers.provider === 'gemini'" class="p-4 border border-shell-border rounded bg-gray-50 flex flex-col gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[13px] font-semibold">Gemini Model</label>
                                    <select v-model="data.config.ai_providers.gemini.model" class="w-full px-4 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-state-focus outline-none">
                                        <option value="gemini-1.5-pro">Gemini 1.5 Pro</option>
                                        <option value="gemini-1.5-flash">Gemini 1.5 Flash</option>
                                    </select>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[13px] font-semibold">Gemini API Key</label>
                                    <input type="password" v-model="data.config.ai_providers.gemini.api_key" placeholder="AIza..." class="w-full px-4 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-state-focus outline-none" />
                                </div>
                            </div>

                            <div v-if="data.config.ai_providers.provider === 'claude'" class="p-4 border border-shell-border rounded bg-gray-50 flex flex-col gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[13px] font-semibold">Claude Model</label>
                                    <select v-model="data.config.ai_providers.claude.model" class="w-full px-4 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-state-focus outline-none">
                                        <option value="claude-3-5-sonnet-20240620">Claude 3.5 Sonnet</option>
                                        <option value="claude-3-opus-20240229">Claude 3 Opus</option>
                                    </select>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[13px] font-semibold">Anthropic API Key</label>
                                    <input type="password" v-model="data.config.ai_providers.claude.api_key" placeholder="sk-ant-..." class="w-full px-4 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-state-focus outline-none" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="activeTab === 'localization'" class="bg-white border border-shell-border rounded-card p-6 flex flex-col gap-6 shadow-sm">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[13px] font-semibold text-text-primary">Default Language</label>
                                <select v-model="data.config.localization.default_lang" class="w-full px-4 py-2 bg-gray-50 border border-shell-border rounded-input text-[13px] focus:bg-white focus:ring-1 focus:ring-state-focus outline-none">
                                    <option value="en">English (US)</option>
                                    <option value="ar">Arabic (العربية)</option>
                                    <option value="so">Somali (Soomaaliga)</option>
                                </select>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[13px] font-semibold text-text-primary">Date Format</label>
                                <select v-model="data.config.localization.date_format" class="w-full px-4 py-2 bg-gray-50 border border-shell-border rounded-input text-[13px] focus:bg-white focus:ring-1 focus:ring-state-focus outline-none">
                                    <option value="YYYY-MM-DD">YYYY-MM-DD (2026-05-23)</option>
                                    <option value="DD/MM/YYYY">DD/MM/YYYY (23/05/2026)</option>
                                    <option value="MM/DD/YYYY">MM/DD/YYYY (05/23/2026)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div v-if="activeTab === 'email'" class="bg-white border border-shell-border rounded-card p-6 flex flex-col gap-6 shadow-sm">
                        <div class="flex justify-between items-center pb-4 border-b border-shell-border">
                            <div>
                                <h3 class="font-bold text-[15px]">SMTP Configuration</h3>
                                <p class="text-[12px] text-text-secondary mt-1">Configure email delivery for welcome emails, notifications, and resets.</p>
                            </div>
                            <button @click="testSmtp" :disabled="isTestingSmtp" class="bg-white border border-shell-border px-4 py-1.5 rounded-btn text-[12px] font-bold shadow-sm hover:bg-gray-50 disabled:opacity-50 text-state-focus">
                                {{ isTestingSmtp ? 'Testing...' : 'Send Test Email' }}
                            </button>
                        </div>
                        <div class="grid grid-cols-2 gap-6">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[13px] font-semibold">SMTP Host</label>
                                <input v-model="data.config.email.host" type="text" placeholder="smtp.mailgun.org" class="w-full px-4 py-2 bg-gray-50 border border-shell-border rounded-input text-[13px] focus:bg-white focus:ring-1 focus:ring-state-focus outline-none" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[13px] font-semibold">SMTP Port</label>
                                <input v-model="data.config.email.port" type="text" placeholder="587" class="w-full px-4 py-2 bg-gray-50 border border-shell-border rounded-input text-[13px] focus:bg-white focus:ring-1 focus:ring-state-focus outline-none" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[13px] font-semibold">Username</label>
                                <input v-model="data.config.email.username" type="text" class="w-full px-4 py-2 bg-gray-50 border border-shell-border rounded-input text-[13px] focus:bg-white focus:ring-1 focus:ring-state-focus outline-none" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[13px] font-semibold">Password</label>
                                <input v-model="data.config.email.password" type="password" class="w-full px-4 py-2 bg-gray-50 border border-shell-border rounded-input text-[13px] focus:bg-white focus:ring-1 focus:ring-state-focus outline-none" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[13px] font-semibold">From Address</label>
                                <input v-model="data.config.email.from_address" type="email" placeholder="no-reply@sehtech.com" class="w-full px-4 py-2 bg-gray-50 border border-shell-border rounded-input text-[13px] focus:bg-white focus:ring-1 focus:ring-state-focus outline-none" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[13px] font-semibold">From Name</label>
                                <input v-model="data.config.email.from_name" type="text" placeholder="SEHTECH OS" class="w-full px-4 py-2 bg-gray-50 border border-shell-border rounded-input text-[13px] focus:bg-white focus:ring-1 focus:ring-state-focus outline-none" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[13px] font-semibold">Encryption</label>
                                <select v-model="data.config.email.encryption" class="w-full px-4 py-2 bg-gray-50 border border-shell-border rounded-input text-[13px] focus:bg-white focus:ring-1 focus:ring-state-focus outline-none">
                                    <option value="tls">TLS</option>
                                    <option value="ssl">SSL</option>
                                    <option value="">None</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div v-if="activeTab === 'notifications'" class="bg-white border border-shell-border rounded-card p-6 flex flex-col gap-6 shadow-sm">
                        <div class="flex items-center justify-between py-2">
                            <div class="flex flex-col">
                                <span class="text-[14px] font-semibold">Employee Onboarding</span>
                                <span class="text-[12px] text-text-secondary">Send welcome email with credentials to new hires.</span>
                            </div>
                            <input type="checkbox" v-model="data.config.notifications.email_onboarding" class="w-4 h-4 accent-state-focus">
                        </div>
                        <div class="flex items-center justify-between py-2 border-t border-shell-border">
                            <div class="flex flex-col">
                                <span class="text-[14px] font-semibold">Leave Requests</span>
                                <span class="text-[12px] text-text-secondary">Email employees when their leave request is approved/rejected.</span>
                            </div>
                            <input type="checkbox" v-model="data.config.notifications.email_leave" class="w-4 h-4 accent-state-focus">
                        </div>
                        <div class="flex items-center justify-between py-2 border-t border-shell-border">
                            <div class="flex flex-col">
                                <span class="text-[14px] font-semibold">Finance Bills</span>
                                <span class="text-[12px] text-text-secondary">Email alerts when a recurring bill is due.</span>
                            </div>
                            <input type="checkbox" v-model="data.config.notifications.email_bills" class="w-4 h-4 accent-state-focus">
                        </div>
                    </div>

                    <div v-if="activeTab === 'storage'" class="bg-white border border-shell-border rounded-card p-6 flex flex-col gap-6 shadow-sm">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[13px] font-semibold">Max Upload Size (MB)</label>
                                <input v-model="data.config.storage.max_upload_size" type="number" class="w-full px-4 py-2 bg-gray-50 border border-shell-border rounded-input text-[13px] focus:bg-white focus:ring-1 focus:ring-state-focus outline-none" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[13px] font-semibold">Allowed Extensions (comma separated)</label>
                                <input v-model="data.config.storage.allowed_types" type="text" class="w-full px-4 py-2 bg-gray-50 border border-shell-border rounded-input text-[13px] focus:bg-white focus:ring-1 focus:ring-state-focus outline-none" />
                            </div>
                        </div>
                        <div class="mt-4 p-4 bg-shell-panel border border-shell-border rounded text-[13px]">
                            <span class="font-bold">Current Driver:</span> Local Storage<br>
                            <span class="text-text-secondary">To configure S3, update the .env file on the server.</span>
                        </div>
                    </div>

                    <div v-if="activeTab === 'security'" class="bg-white border border-shell-border rounded-card p-6 flex flex-col gap-6 shadow-sm">
                        <div class="flex items-center justify-between py-2">
                            <div class="flex flex-col">
                                <span class="text-[14px] font-semibold">Enforce 2FA</span>
                                <span class="text-[12px] text-text-secondary">Require all system users to setup Two-Factor Authentication.</span>
                            </div>
                            <input type="checkbox" v-model="data.config.security.enforce_2fa" class="w-4 h-4 accent-state-focus">
                        </div>
                        <div class="flex flex-col gap-1.5 mt-2 border-t border-shell-border pt-4">
                            <label class="text-[13px] font-semibold">Session Timeout (minutes)</label>
                            <input v-model="data.config.security.session_timeout" type="number" class="w-64 px-4 py-2 bg-gray-50 border border-shell-border rounded-input text-[13px] focus:bg-white focus:ring-1 focus:ring-state-focus outline-none" />
                        </div>
                        <div class="flex flex-col gap-1.5 mt-2">
                            <label class="text-[13px] font-semibold">IP Allowlist (leave blank to allow all)</label>
                            <textarea v-model="data.config.security.ip_allowlist" rows="2" placeholder="e.g. 192.168.1.1, 10.0.0.1" class="w-full px-4 py-2 bg-gray-50 border border-shell-border rounded-input text-[13px] focus:bg-white focus:ring-1 focus:ring-state-focus outline-none"></textarea>
                        </div>
                    </div>

                    <div v-if="activeTab === 'billing'" class="bg-white border border-shell-border rounded-card p-6 flex flex-col gap-6 shadow-sm text-text-primary">
                        <div class="flex justify-between items-center pb-4 border-b border-shell-border">
                            <div>
                                <h3 class="font-bold text-[18px]">SEHTECH OS Enterprise</h3>
                                <p class="text-[13px] text-text-secondary">Self-hosted Lifetime License</p>
                            </div>
                            <div class="px-3 py-1 bg-state-success/10 text-state-success font-bold text-[12px] rounded uppercase">Active</div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 text-[13px]">
                            <div><span class="font-semibold block">License Key</span><span class="font-mono text-text-secondary">XXXX-XXXX-XXXX-XXXX</span></div>
                            <div><span class="font-semibold block">Total Users Allowed</span><span class="text-text-secondary">Unlimited</span></div>
                            <div><span class="font-semibold block">Current Users</span><span class="text-text-secondary">{{ data.users.length }} Active</span></div>
                            <div><span class="font-semibold block">Support Expiry</span><span class="text-text-secondary">Lifetime</span></div>
                        </div>
                    </div>

                    <div v-if="activeTab === 'users'" class="bg-white border border-shell-border rounded-card shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-shell-border flex justify-between items-center">
                            <span class="font-bold text-[14px]">System Users ({{ data.users.length }})</span>
                            <span class="text-[12px] text-text-secondary">Manage users and roles in the Administration module.</span>
                        </div>
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 border-b border-shell-border text-[11px] text-text-disabled uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-3 font-semibold">Name</th>
                                    <th class="px-6 py-3 font-semibold">Email</th>
                                    <th class="px-6 py-3 font-semibold">Role</th>
                                </tr>
                            </thead>
                            <tbody class="text-[13px]">
                                <tr v-for="user in data.users" :key="user.id" class="border-b border-shell-border hover:bg-gray-50">
                                    <td class="px-6 py-3 font-medium">{{ user.name }}</td>
                                    <td class="px-6 py-3 text-text-secondary">{{ user.email }}</td>
                                    <td class="px-6 py-3">
                                        <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-shell-panel border border-shell-border">
                                            {{ user.role?.name || 'User' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

    </div>
</template>
