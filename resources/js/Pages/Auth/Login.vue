<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    error: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    // Ensure CSRF token is fresh before submitting
    if (window.axios && window.axios.defaults.headers.common['X-CSRF-TOKEN']) {
        // Refresh CSRF token if available
        const metaToken = document.querySelector('meta[name="csrf-token"]');
        if (metaToken) {
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = metaToken.getAttribute('content');
        }
    }
    
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
        onError: (errors) => {
            // Handle 419 CSRF errors specifically
            if (errors.message && errors.message.includes('419')) {
                // Reload page to get fresh CSRF token
                window.location.reload();
            }
        }
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Sign In" />

        <div class="text-center mb-8">
            <div class="flex justify-center mb-6">
                <div class="w-16 h-16 bg-green-600 rounded-full flex items-center justify-center shadow-lg">
                    <i class="fas fa-sign-in-alt text-white text-2xl"></i>
                </div>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome Back</h1>
            <p class="text-gray-600">Sign in to your PL Tournament account</p>
        </div>

        <!-- Status Messages -->
        <div v-if="status || error" class="mb-6 p-4 rounded-lg" :class="(status && (status.includes('approval') || status.includes('pending'))) || error ? 'bg-amber-100 border border-amber-300' : 'bg-green-100 border border-green-300'">
            <div class="flex items-start">
                <i class="mr-3 mt-0.5" :class="(status && (status.includes('approval') || status.includes('pending'))) || error ? 'fas fa-exclamation-triangle text-amber-600' : 'fas fa-check-circle text-green-600'"></i>
                <div>
                    <span :class="(status && (status.includes('approval') || status.includes('pending'))) || error ? 'text-amber-800' : 'text-green-800'">{{ status || error }}</span>
                    <div v-if="(status && (status.includes('approval') || status.includes('pending'))) || (error && error.includes('approval'))" class="mt-2">
                        <a 
                            href="mailto:support@pl-tournament.com?subject=Account Approval Request"
                            class="inline-flex items-center text-amber-700 hover:text-amber-800 text-sm font-medium"
                        >
                            <i class="fas fa-envelope mr-1"></i>
                            Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Enter your email"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="Enter your password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600">Remember me</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-green-600 hover:text-green-700 transition-colors"
                >
                    Forgot password?
                </Link>
            </div>

            <PrimaryButton
                class="w-full"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                Sign In
            </PrimaryButton>

            <div class="text-center">
                <p class="text-gray-600 text-sm">
                    Don't have an account? 
                    <Link :href="route('register')" class="text-green-600 hover:text-green-700 font-medium">
                        Sign up here
                    </Link>
                </p>
            </div>
        </form>
    </GuestLayout>
</template>
