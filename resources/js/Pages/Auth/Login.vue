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

        <div v-if="status" class="mb-6 p-4 bg-green-100 border border-green-300 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-600 mr-3"></i>
                <span class="text-green-800">{{ status }}</span>
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
