<script setup lang="ts">
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
});

const submit = () => {
    form.post('/portal/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="SEHTECH Client Portal — Login" />
    <div class="min-h-screen bg-gradient-to-br from-[#EFF6FF] to-[#DBEAFE] flex items-center justify-center px-4">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <h1 class="text-[28px] font-bold text-[#2563EB]">SEHTECH</h1>
                <p class="text-[14px] text-gray-500 mt-1">Client Portal</p>
            </div>

            <!-- Login Card -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
                <h2 class="text-[20px] font-semibold text-gray-900 mb-6">Sign in to your account</h2>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label for="email" class="block text-[13px] font-medium text-gray-700 mb-1.5">Email Address</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autofocus
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl text-[14px] outline-none focus:ring-2 focus:ring-[#2563EB]/20 focus:border-[#2563EB] transition-all"
                            placeholder="you@company.com"
                        />
                        <p v-if="form.errors.email" class="text-red-500 text-[12px] mt-1.5">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label for="password" class="block text-[13px] font-medium text-gray-700 mb-1.5">Password</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl text-[14px] outline-none focus:ring-2 focus:ring-[#2563EB]/20 focus:border-[#2563EB] transition-all"
                            placeholder="••••••••"
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3 bg-[#2563EB] text-white font-semibold text-[14px] rounded-xl hover:bg-[#1D4ED8] transition-colors shadow-sm disabled:opacity-50"
                    >
                        <span v-if="form.processing">Signing in...</span>
                        <span v-else>Sign In</span>
                    </button>
                </form>
            </div>

            <p class="text-center text-[12px] text-gray-400 mt-6">
                Need access? Contact your account manager at SEHTECH.
            </p>
        </div>
    </div>
</template>
