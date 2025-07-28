<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import TournamentLayout from '@/Layouts/TournamentLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { computed } from 'vue';

const props = defineProps({
    remainingGameWeeks: {
        type: Number,
        default: 38
    },
    nextGameWeekNumber: {
        type: Number,
        default: 1
    },
    maxGameWeeks: {
        type: Number,
        default: 20
    },
});

const form = useForm({
    name: '',
    description: '',
    start_game_week: props.nextGameWeekNumber || 1,
    total_game_weeks: String(Math.min(20, props.maxGameWeeks || 20)),
    max_participants: '20',
    is_private: false,
});

const submit = () => {
    form.post(route('tournaments.store'));
};

// Calculate end gameweek
const endGameWeek = computed(() => {
    return parseInt(form.start_game_week) + parseInt(form.total_game_weeks) - 1;
});
</script>

<template>
    <Head title="Create Tournament" />

    <TournamentLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-white">
                Create New Tournament
            </h2>
            <p class="text-white/70 mt-2">
                Set up your Premier League prediction competition
            </p>
        </template>

        <div class="max-w-3xl mx-auto">
            <div class="bg-white/10 backdrop-blur-md rounded-xl p-8 border border-white/20">
                <form @submit.prevent="submit" class="space-y-8">
                    <!-- Tournament Details Section -->
                    <div class="space-y-6">
                        <div class="border-b border-white/20 pb-4">
                            <h3 class="text-lg font-semibold text-white mb-2">Tournament Details</h3>
                            <p class="text-white/60 text-sm">Basic information about your tournament</p>
                        </div>

                        <!-- Tournament Name -->
                        <div>
                            <InputLabel for="name" value="Tournament Name" />
                            <TextInput
                                id="name"
                                type="text"
                                class="mt-2"
                                v-model="form.name"
                                required
                                autofocus
                                placeholder="e.g., Premier League Predictions 2025"
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <!-- Description -->
                        <div>
                            <InputLabel for="description" value="Description (Optional)" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                class="mt-2 w-full rounded-lg border-0 bg-white/20 backdrop-blur-sm px-4 py-3 text-white placeholder-white/60 shadow-sm ring-1 ring-white/30 focus:ring-2 focus:ring-emerald-500 focus:bg-white/30 transition-all"
                                rows="4"
                                placeholder="Tell participants what this tournament is about..."
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>
                    </div>

                    <!-- Tournament Settings Section -->
                    <div class="space-y-6">
                        <div class="border-b border-white/20 pb-4">
                            <h3 class="text-lg font-semibold text-white mb-2">Tournament Settings</h3>
                            <p class="text-white/60 text-sm">Configure the competition parameters</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Start Gameweek -->
                            <div>
                                <InputLabel for="start_game_week" value="Start Gameweek" />
                                <select
                                    id="start_game_week"
                                    v-model.number="form.start_game_week"
                                    class="mt-2 w-full rounded-lg border-0 bg-white/20 backdrop-blur-sm px-4 py-3 text-white shadow-sm ring-1 ring-white/30 focus:ring-2 focus:ring-emerald-500 focus:bg-white/30 transition-all"
                                    required
                                >
                                    <option v-for="week in 38" :key="week" :value="week" 
                                            :disabled="week < (nextGameWeekNumber || 1)"
                                            class="bg-gray-800 text-white">
                                        Gameweek {{ week }}{{ week < (nextGameWeekNumber || 1) ? ' (Completed)' : '' }}
                                    </option>
                                </select>
                                <p class="mt-1 text-sm text-white/60">
                                    Choose which gameweek your tournament starts
                                </p>
                                <InputError class="mt-2" :message="form.errors.start_game_week" />
                            </div>

                            <!-- Total Gameweeks -->
                            <div>
                                <InputLabel for="total_game_weeks" value="Number of Gameweeks" />
                                <TextInput
                                    id="total_game_weeks"
                                    type="number"
                                    class="mt-2"
                                    v-model="form.total_game_weeks"
                                    required
                                    :min="1"
                                    :max="maxGameWeeks"
                                />
                                <p class="mt-1 text-sm text-white/60">
                                    Tournament will run for {{ form.total_game_weeks }} gameweeks (Gameweek {{ form.start_game_week }} to {{ endGameWeek }})
                                </p>
                                <InputError class="mt-2" :message="form.errors.total_game_weeks" />
                            </div>
                        </div>

                        <!-- Max Participants -->
                        <div>
                            <InputLabel for="max_participants" value="Maximum Participants" />
                            <TextInput
                                id="max_participants"
                                type="number"
                                class="mt-2"
                                v-model="form.max_participants"
                                required
                                min="2"
                                max="100"
                            />
                            <p class="mt-1 text-sm text-white/60">
                                Between 2 and 100 participants
                            </p>
                            <InputError class="mt-2" :message="form.errors.max_participants" />
                        </div>

                        <!-- Privacy Setting -->
                        <div class="bg-white/5 rounded-lg p-4 border border-white/10">
                            <label class="flex items-center space-x-3">
                                <Checkbox name="is_private" v-model:checked="form.is_private" />
                                <div>
                                    <span class="text-white font-medium">Private Tournament</span>
                                    <p class="text-white/60 text-sm">Only people with the join code can participate</p>
                                </div>
                            </label>
                            <InputError class="mt-2" :message="form.errors.is_private" />
                        </div>
                    </div>

                    <!-- Tournament Rules Section -->
                    <div class="bg-gradient-to-r from-emerald-500/20 to-green-600/20 backdrop-blur-md rounded-xl p-6 border border-emerald-500/30">
                        <div class="flex items-start space-x-3 mb-4">
                            <div class="w-8 h-8 bg-emerald-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-white mb-2">Tournament Rules</h3>
                                <div class="text-sm text-white/90 space-y-2">
                                    <div class="flex items-start space-x-2">
                                        <span class="font-semibold text-emerald-300">•</span>
                                        <span>Each gameweek, pick one Premier League team to win their match</span>
                                    </div>
                                    <div class="flex items-start space-x-2">
                                        <span class="font-semibold text-emerald-300">•</span>
                                        <span>Scoring: Win = 3 points, Draw = 1 point, Loss = 0 points</span>
                                    </div>
                                    <div class="flex items-start space-x-2">
                                        <span class="font-semibold text-emerald-300">•</span>
                                        <span>Once you pick a team, you can't pick them again in this tournament</span>
                                    </div>
                                    <div class="flex items-start space-x-2">
                                        <span class="font-semibold text-emerald-300">•</span>
                                        <span>Tournament runs for {{ form.total_game_weeks }} gameweeks (maximum 20 - one per team)</span>
                                    </div>
                                    <div class="flex items-start space-x-2">
                                        <span class="font-semibold text-emerald-300">•</span>
                                        <span>Highest total score wins the tournament!</span>
                                    </div>
                                    <div class="flex items-start space-x-2">
                                        <span class="font-semibold text-emerald-300">•</span>
                                        <span class="text-emerald-200">{{ remainingGameWeeks || 38 }} gameweeks remaining in the season</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-between pt-6 border-t border-white/20">
                        <a
                            :href="route('tournaments.index')"
                            class="inline-flex items-center px-4 py-2 bg-white/20 border border-white/30 rounded-lg text-white hover:bg-white/30 transition-all"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Cancel
                        </a>
                        
                        <PrimaryButton 
                            :class="{ 'opacity-50': form.processing }" 
                            :disabled="form.processing"
                            class="inline-flex items-center"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            {{ form.processing ? 'Creating...' : 'Create Tournament' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </TournamentLayout>
</template> 