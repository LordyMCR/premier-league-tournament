<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    mode: {
        type: String,
        default: 'invite_closed',
    },
    restrictionsEnabled: {
        type: Boolean,
        default: false,
    },
    turnstileSiteKey: {
        type: String,
        default: null,
    },
    inviteToken: {
        type: String,
        default: null,
    },
    inviteEmail: {
        type: String,
        default: null,
    },
});

const showForm = ['public', 'invite'].includes(props.mode);
const turnstileWidgetId = ref(null);

const form = useForm({
    name: '',
    email: props.inviteEmail || '',
    password: '',
    password_confirmation: '',
    website: '',
    invite_token: props.inviteToken || '',
    turnstile_token: '',
});

const renderTurnstile = () => {
    if (!props.turnstileSiteKey || typeof window.turnstile === 'undefined') {
        return;
    }

    const container = document.getElementById('turnstile-container');
    if (!container) {
        return;
    }

    container.innerHTML = '';
    turnstileWidgetId.value = window.turnstile.render(container, {
        sitekey: props.turnstileSiteKey,
        callback: (token) => {
            form.turnstile_token = token;
        },
        'expired-callback': () => {
            form.turnstile_token = '';
        },
    });
};

onMounted(() => {
    if (props.mode !== 'public' || !props.turnstileSiteKey) {
        return;
    }

    if (typeof window.turnstile !== 'undefined') {
        renderTurnstile();
        return;
    }

    const script = document.createElement('script');
    script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
    script.async = true;
    script.onload = renderTurnstile;
    document.head.appendChild(script);
});

onBeforeUnmount(() => {
    if (turnstileWidgetId.value !== null && typeof window.turnstile !== 'undefined') {
        window.turnstile.remove(turnstileWidgetId.value);
    }
});

const submit = () => {
    if (props.inviteEmail) {
        form.email = props.inviteEmail;
    }

    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
            if (turnstileWidgetId.value !== null && typeof window.turnstile !== 'undefined') {
                window.turnstile.reset(turnstileWidgetId.value);
                form.turnstile_token = '';
            }
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="showForm ? 'Create Account - PL Picks' : 'Registration - PL Picks'" />

        <div class="text-center mb-8">
            <div class="flex justify-center mb-6">
                <div class="w-16 h-16 bg-green-600 rounded-full flex items-center justify-center shadow-lg">
                    <i class="fas fa-user-plus text-white text-2xl"></i>
                </div>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                {{ mode === 'invite' ? 'You’re invited' : 'Join PL Picks' }}
            </h1>
            <p class="text-gray-600">
                <template v-if="mode === 'invite' && inviteEmail">This invite is for {{ inviteEmail }}.</template>
                <template v-else-if="mode === 'invite'">Create your account with this unique invite link.</template>
                <template v-else-if="mode === 'public'">Create your account and start playing</template>
                <template v-else>This is a private picks group</template>
            </p>
        </div>

        <div
            v-if="mode === 'invite_closed'"
            class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg"
        >
            <h3 class="text-sm font-medium text-amber-800 mb-2">Registration is by invite only</h3>
            <p class="text-sm text-amber-700 mb-4">
                New accounts need a unique invite link from an admin. If you already have an approved account, sign in below.
            </p>
            <Link
                :href="route('login')"
                class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md"
            >
                Sign in
            </Link>
        </div>

        <div
            v-else-if="mode === 'invite_invalid'"
            class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg"
        >
            <h3 class="text-sm font-medium text-red-800 mb-2">This invite link isn’t valid</h3>
            <p class="text-sm text-red-700 mb-4">
                It may have already been used, expired, or been revoked. Ask whoever invited you for a new link.
            </p>
            <Link
                :href="route('login')"
                class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md"
            >
                Sign in
            </Link>
        </div>

        <div
            v-else-if="restrictionsEnabled"
            class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg"
        >
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-amber-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-amber-800">
                        Account Approval Required
                    </h3>
                    <div class="mt-2 text-sm text-amber-700">
                        <p>
                            New accounts require approval before gaining access. You will receive an email once approved.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <form v-if="showForm" @submit.prevent="submit" class="space-y-6">
            <div class="absolute -left-[9999px] h-0 w-0 overflow-hidden" aria-hidden="true">
                <label for="website">Company website</label>
                <input
                    id="website"
                    type="text"
                    name="website"
                    tabindex="-1"
                    autocomplete="off"
                    v-model="form.website"
                />
            </div>

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
                    :disabled="Boolean(inviteEmail)"
                    :placeholder="inviteEmail ? '' : 'Enter your email address'"
                />
                <p v-if="inviteEmail" class="mt-2 text-sm text-gray-500">
                    This invite can only be used with this email address.
                </p>
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

            <div v-if="mode === 'public' && turnstileSiteKey" class="space-y-2">
                <div id="turnstile-container" class="flex justify-center"></div>
                <InputError class="text-center" :message="form.errors.turnstile_token" />
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
