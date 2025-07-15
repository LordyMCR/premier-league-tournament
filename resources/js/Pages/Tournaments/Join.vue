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
            <h2 class="text-2xl font-bold text-white">
                Join Tournament
            </h2>
            <p class="text-white/70 mt-2">
                Enter your tournament code to join the competition
            </p>
        </template>

        <div class="max-w-md mx-auto">
            <div class="bg-white/10 backdrop-blur-md rounded-xl p-8 border border-white/20">
                <!-- Icon and Title -->
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Enter Tournament Code</h3>
                    <p class="text-white/70 text-sm">
                        Enter the 8-character code provided by the tournament creator
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <InputLabel for="join_code" value="Tournament Join Code" />
                        <TextInput
                            id="join_code"
                            type="text"
                            class="mt-2 text-center text-lg font-mono tracking-wider uppercase"
                            v-model="form.join_code"
                            required
                            autofocus
                            placeholder="ABC12345"
                            maxlength="8"
                            @input="form.join_code = form.join_code.toUpperCase()"
                        />
                        <InputError class="mt-2" :message="form.errors.join_code" />
                        <p class="mt-2 text-sm text-white/60 text-center">
                            Code should be 8 characters long
                        </p>
                    </div>

                    <!-- What happens next info -->
                    <div class="bg-gradient-to-r from-blue-500/20 to-purple-600/20 backdrop-blur-md rounded-lg p-4 border border-blue-500/30">
                        <div class="flex items-start space-x-3">
                            <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-white mb-2 text-sm">What happens next?</h4>
                                <ul class="text-sm text-white/80 space-y-1">
                                    <li class="flex items-center space-x-2">
                                        <svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span>You'll be added to the tournament</span>
                                    </li>
                                    <li class="flex items-center space-x-2">
                                        <svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span>Start making picks for game weeks</span>
                                    </li>
                                    <li class="flex items-center space-x-2">
                                        <svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span>View leaderboards and compete</span>
                                    </li>
                                    <li class="flex items-center space-x-2">
                                        <svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span>Track progress throughout the season</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-between pt-4">
                        <a
                            :href="route('tournaments.index')"
                            class="inline-flex items-center px-4 py-2 bg-white/20 border border-white/30 rounded-lg text-white hover:bg-white/30 transition-all"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Dashboard
                        </a>
                        
                        <PrimaryButton 
                            :class="{ 'opacity-50': form.processing || form.join_code.length !== 8 }" 
                            :disabled="form.processing || form.join_code.length !== 8"
                            class="inline-flex items-center"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 515.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 919.288 0M15 7a3 3 0 11-6 0 3 3 0 616 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            {{ form.processing ? 'Joining...' : 'Join Tournament' }}
                        </PrimaryButton>
                    </div>
                </form>

                <!-- Alternative Action -->
                <div class="mt-8 pt-6 border-t border-white/20 text-center">
                    <p class="text-white/60 mb-4 text-sm">Don't have a join code?</p>
                    <a 
                        :href="route('tournaments.create')"
                        class="inline-flex items-center text-emerald-400 hover:text-emerald-300 font-medium transition-colors"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Create Your Own Tournament
                    </a>
                </div>
            </div>
        </div>
    </TournamentLayout>
</template> 