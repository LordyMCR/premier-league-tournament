<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    restrictionsEnabled: {
        type: Boolean,
        default: false,
    },
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Create Account" />

        <div class="text-center mb-8">
            <div class="flex justify-center mb-6">
                <div class="w-16 h-16 bg-green-600 rounded-full flex items-center justify-center shadow-lg">
                    <i class="fas fa-user-plus text-white text-2xl"></i>
                </div>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Join PL Tournament</h1>
            <p class="text-gray-600">Create your account and start playing</p>
        </div>

        <!-- Restrictions Notice -->
        <div v-if="restrictionsEnabled" class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-amber-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-amber-800">
                        Account Approval Required
                    </h3>
                    <div class="mt-2 text-sm text-amber-700">
                        <p class="mb-3">
                            New accounts require approval before gaining access. After registration, contact our support team 
                            to request account activation. You will receive an email confirmation once approved.
                        </p>
                        <div class="flex items-center space-x-4">
                            <a 
                                href="mailto:support@pl-tournament.com?subject=Account Approval Request"
                                class="inline-flex items-center px-3 py-2 border border-amber-300 rounded-md text-sm font-medium text-amber-800 bg-amber-50 hover:bg-amber-100 transition-colors duration-200"
                            >
                                <i class="fas fa-envelope mr-2"></i>
                                Contact Support
                            </a>
                            <span class="text-amber-600">support@pl-tournament.com</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <InputLabel for="name" value="Full Name" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Enter your full name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    placeholder="Enter your email address"
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
                    autocomplete="new-password"
                    placeholder="Create a strong password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Confirm Password" />
                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Confirm your password"
                />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <PrimaryButton
                class="w-full"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                Create Account
            </PrimaryButton>

            <div class="text-center">
                <p class="text-gray-600 text-sm">
                    Already have an account? 
                    <Link :href="route('login')" class="text-green-600 hover:text-green-700 font-medium">
                        Sign in here
                    </Link>
                </p>
            </div>
        </form>
    </GuestLayout>
</template>
