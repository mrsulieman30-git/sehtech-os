<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-shell-bg">
        
        <div class="mb-8 text-center">
            <h1 class="text-white text-3xl font-bold tracking-tight">SEHTECH</h1>
            <p class="text-text-disabled text-[13px] tracking-widest uppercase mt-1">CompanyOS</p>
        </div>

        <div class="w-full sm:max-w-md px-8 py-10 bg-shell-panel shadow-modal rounded-card border border-shell-border">
            
            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <label for="email" class="block text-[13px] font-medium text-text-primary mb-1">
                        Email Address
                    </label>
                    <input
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full px-4 py-2.5 bg-white border border-shell-border rounded-input focus:ring-2 focus:ring-state-focus focus:border-state-focus transition-colors duration-120 text-text-primary outline-none"
                        :class="{'border-state-error focus:ring-state-error focus:border-state-error': form.errors.email}"
                    />
                    <p v-if="form.errors.email" class="mt-2 text-[12px] text-state-error">
                        {{ form.errors.email }}
                    </p>
                </div>

                <div>
                    <label for="password" class="block text-[13px] font-medium text-text-primary mb-1">
                        Password
                    </label>
                    <input
                        id="password"
                        type="password"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        class="w-full px-4 py-2.5 bg-white border border-shell-border rounded-input focus:ring-2 focus:ring-state-focus focus:border-state-focus transition-colors duration-120 text-text-primary outline-none"
                    />
                    <p v-if="form.errors.password" class="mt-2 text-[12px] text-state-error">
                        {{ form.errors.password }}
                    </p>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="remember" 
                            v-model="form.remember" 
                            class="rounded border-shell-border text-state-focus shadow-sm focus:ring-state-focus bg-white w-4 h-4"
                        />
                        <span class="ml-2 text-[13px] text-text-secondary">Remember me</span>
                    </label>
                </div>

                <div>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-btn shadow-sm text-[14px] font-medium text-white bg-dept-dev-main hover:bg-[#1E293B] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-state-focus transition-colors duration-120 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Sign in to Workspace
                    </button>
                </div>
            </form>
            
        </div>
    </div>
</template>
