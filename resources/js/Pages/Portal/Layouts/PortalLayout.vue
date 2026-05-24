<script setup lang="ts">
import { ref, computed, watchEffect } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { 
    PhSquaresFour, PhLifebuoy, PhReceipt, PhFileDoc, 
    PhUser, PhSignOut, PhTranslate
} from '@phosphor-icons/vue';

const { t, locale } = useI18n();
const page = usePage();
const user = computed(() => (page.props as any).portalUser ?? (page.props as any).auth?.user);

const currentLang = ref(locale.value);
const isRTL = computed(() => currentLang.value === 'ar');

// Watch language change to update document direction
watchEffect(() => {
    locale.value = currentLang.value;
    document.documentElement.dir = isRTL.value ? 'rtl' : 'ltr';
    document.documentElement.lang = currentLang.value;
});

const navItems = [
    { name: 'nav.dashboard', route: 'portal.dashboard', icon: PhSquaresFour },
    { name: 'nav.tickets', route: 'portal.tickets', icon: PhLifebuoy },
    { name: 'nav.invoices', route: 'portal.invoices', icon: PhReceipt },
    { name: 'nav.documents', route: 'portal.documents', icon: PhFileDoc },
];
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex text-gray-900 font-sans" :dir="isRTL ? 'rtl' : 'ltr'">
        
        <!-- Sidebar Navigation -->
        <aside class="w-64 bg-white border-r border-gray-200 flex flex-col hidden md:flex shrink-0 fixed h-full z-10" :class="{'border-l border-r-0': isRTL}">
            <!-- Logo -->
            <div class="h-20 flex items-center px-8 border-b border-gray-200">
                <span class="text-2xl font-bold text-[#2563EB] tracking-tight">SEHTECH</span>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                <Link 
                    v-for="item in navItems" 
                    :key="item.name"
                    :href="route(item.route)"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors"
                    :class="route().current(item.route) ? 'bg-blue-50 text-[#2563EB]' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                >
                    <component :is="item.icon" :size="20" :weight="route().current(item.route) ? 'fill' : 'regular'" />
                    {{ t(item.name) }}
                </Link>
            </nav>

            <!-- Bottom Actions -->
            <div class="p-4 border-t border-gray-200 space-y-2">
                <Link href="/portal/profile" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                    <PhUser :size="20" />
                    {{ t('nav.profile') }}
                </Link>
                <Link :href="route('portal.logout')" method="post" as="button" class="w-full flex items-center gap-3 px-4 py-2 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
                    <PhSignOut :size="20" />
                    {{ t('nav.logout') }}
                </Link>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 md:ml-64 flex flex-col min-h-screen transition-all" :class="{'md:ml-0 md:mr-64': isRTL}">
            
            <!-- Top Header Bar -->
            <header class="h-20 bg-white border-b border-gray-200 flex items-center justify-between px-8 sticky top-0 z-10">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-blue-100 text-[#2563EB] flex items-center justify-center font-bold">
                        {{ user?.name?.charAt(0) || 'C' }}
                    </div>
                    <div>
                        <p class="text-sm font-bold">{{ user?.name || 'Client User' }}</p>
                        <p class="text-xs text-gray-500">{{ t('dashboard.last_login') }} Today</p>
                    </div>
                </div>

                <!-- Language Selector -->
                <div class="flex items-center gap-2">
                    <PhTranslate :size="20" class="text-gray-400" />
                    <select v-model="currentLang" class="text-sm bg-transparent border-none text-gray-600 focus:ring-0 cursor-pointer outline-none font-medium">
                        <option value="en">English</option>
                        <option value="ar">العربية</option>
                        <option value="so">Soomaali</option>
                    </select>
                </div>
            </header>

            <!-- Page Content Slot -->
            <main class="flex-1 p-8">
                <slot />
            </main>
            
        </div>
    </div>
</template>
