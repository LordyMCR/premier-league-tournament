<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const form = useForm({
    join_code: '',
});

const submit = () => {
    form.post(route('tournaments.join'));
};
</script>

<template>
    <Head title="Join Tournament" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Join Tournament
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-md mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-6 text-center">
                            <svg class="mx-auto h-12 w-12 text-blue-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Enter Tournament Code</h3>
                            <p class="text-gray-600">
                                Enter the 8-character code provided by the tournament creator
                            </p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <InputLabel for="join_code" value="Tournament Join Code" />
                                <TextInput
                                    id="join_code"
                                    type="text"
                                    class="mt-1 block w-full text-center text-lg font-mono tracking-wider uppercase"
                                    v-model="form.join_code"
                                    required
                                    autofocus
                                    placeholder="ABC12345"
                                    maxlength="8"
                                    @input="form.join_code = form.join_code.toUpperCase()"
                                />
                                <InputError class="mt-2" :message="form.errors.join_code" />
                                <p class="mt-1 text-sm text-gray-600">
                                    Code should be 8 characters long
                                </p>
                            </div>

                            <div class="bg-blue-50 p-4 rounded-lg">
                                <h4 class="font-semibold text-blue-900 mb-2">What happens next?</h4>
                                <ul class="text-sm text-blue-800 space-y-1">
                                    <li>• You'll be added to the tournament</li>
                                    <li>• You can start making picks for game weeks</li>
                                    <li>• View leaderboards and compete with others</li>
                                    <li>• Track your progress throughout the season</li>
                                </ul>
                            </div>

                            <div class="flex items-center justify-between">
                                <a
                                    :href="route('tournaments.index')"
                                    class="text-gray-600 hover:text-gray-900"
                                >
                                    Back to Dashboard
                                </a>
                                
                                <PrimaryButton 
                                    :class="{ 'opacity-25': form.processing }" 
                                    :disabled="form.processing || form.join_code.length !== 8"
                                >
                                    Join Tournament
                                </PrimaryButton>
                            </div>
                        </form>

                        <div class="mt-8 pt-6 border-t border-gray-200 text-center">
                            <p class="text-gray-600 mb-4">Don't have a join code?</p>
                            <a 
                                :href="route('tournaments.create')"
                                class="text-blue-600 hover:text-blue-800 font-medium"
                            >
                                Create Your Own Tournament
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template> 