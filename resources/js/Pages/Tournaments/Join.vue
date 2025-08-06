<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import TournamentLayout from '@/Layouts/TournamentLayout.vue';
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

    <TournamentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">
                        Join Tournament
                    </h2>
                    <p class="text-gray-600 mt-2">
                        Enter a tournament code to join the competition
                    </p>
                </div>
                <div class="text-right">
                    <div class="bg-green-50 rounded-lg px-4 py-2 border border-green-200 shadow-md">
                        <i class="fas fa-users text-green-600 text-lg"></i>
                    </div>
                </div>
            </div>
        </template>

        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-xl p-8 border border-green-200 shadow-lg">
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-green-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                        <i class="fas fa-sign-in-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Enter Tournament Code</h3>
                    <p class="text-gray-600 text-sm">
                        Get the join code from your tournament organizer
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <InputLabel for="join_code" value="Tournament Code" />
                        <TextInput
                            id="join_code"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.join_code"
                            required
                            autofocus
                            placeholder="Enter the tournament code"
                        />
                        <InputError class="mt-2" :message="form.errors.join_code" />
                    </div>

                    <div class="text-center">
                        <p class="text-sm text-gray-500 mb-4">
                            Don't have a join code? Ask your tournament organizer.
                        </p>
                        <PrimaryButton
                            class="w-full"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Join Tournament
                        </PrimaryButton>
                    </div>
                </form>

                <!-- What happens next section -->
                <div class="mt-8 pt-6 border-t border-green-200">
                    <h4 class="font-semibold text-gray-900 mb-3 text-sm">What happens next?</h4>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-600 mt-1 mr-2"></i>
                            <span>You'll be added to the tournament immediately</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-600 mt-1 mr-2"></i>
                            <span>Start making your picks for upcoming gameweeks</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-600 mt-1 mr-2"></i>
                            <span>Compete with other participants for the top spot</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </TournamentLayout>
</template> 