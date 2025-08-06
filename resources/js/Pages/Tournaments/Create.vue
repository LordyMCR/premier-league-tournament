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
    max_participants: 20,
    is_private: true,
    start_date: '',
    end_date: '',
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
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">
                        Create Tournament
                    </h2>
                    <p class="text-gray-600 mt-2">
                        Set up a new Premier League prediction tournament
                    </p>
                </div>
                <div class="text-right">
                    <div class="bg-green-50 rounded-lg px-4 py-2 border border-green-200 shadow-md">
                        <i class="fas fa-plus text-green-600 text-lg"></i>
                    </div>
                </div>
            </div>
        </template>

        <div class="max-w-4xl mx-auto">
            <form @submit.prevent="submit" class="space-y-8">
                <!-- Tournament Details -->
                <div class="bg-white rounded-xl p-6 border border-green-200 shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Tournament Details</h3>
                    <p class="text-gray-600 text-sm mb-6">Basic information about your tournament</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="name" value="Tournament Name" />
                            <TextInput
                                id="name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.name"
                                required
                                autofocus
                                placeholder="Enter tournament name"
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>
                        
                        <div>
                            <InputLabel for="max_participants" value="Max Participants" />
                            <TextInput
                                id="max_participants"
                                type="number"
                                class="mt-1 block w-full"
                                v-model="form.max_participants"
                                required
                                min="2"
                                max="100"
                            />
                            <InputError class="mt-2" :message="form.errors.max_participants" />
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <InputLabel for="description" value="Description" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            class="mt-1 block w-full bg-white border border-gray-300 text-gray-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200"
                            rows="4"
                            placeholder="Describe your tournament..."
                        ></textarea>
                        <InputError class="mt-2" :message="form.errors.description" />
                    </div>
                </div>

                <!-- Tournament Settings -->
                <div class="bg-white rounded-xl p-6 border border-green-200 shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Tournament Settings</h3>
                    <p class="text-gray-600 text-sm mb-6">Configure the competition parameters</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="start_date" value="Start Date" />
                            <TextInput
                                id="start_date"
                                type="date"
                                class="mt-1 block w-full"
                                v-model="form.start_date"
                                required
                            />
                            <p class="mt-1 text-sm text-gray-500">
                                When the tournament will begin
                            </p>
                            <InputError class="mt-2" :message="form.errors.start_date" />
                        </div>
                        
                        <div>
                            <InputLabel for="end_date" value="End Date" />
                            <TextInput
                                id="end_date"
                                type="date"
                                class="mt-1 block w-full"
                                v-model="form.end_date"
                                required
                            />
                            <p class="mt-1 text-sm text-gray-500">
                                When the tournament will end
                            </p>
                            <InputError class="mt-2" :message="form.errors.end_date" />
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="form.is_private"
                                class="rounded border-gray-300 bg-white text-green-600 shadow-sm focus:border-green-500 focus:ring-green-500"
                            />
                            <span class="ml-2 text-gray-700">Private Tournament</span>
                        </label>
                        <p class="text-gray-600 text-sm mt-1">Only people with the join code can participate</p>
                    </div>
                </div>

                <!-- Tournament Rules -->
                <div class="bg-white rounded-xl p-6 border border-green-200 shadow-lg">
                    <h3 class="font-semibold text-gray-900 mb-2">Tournament Rules</h3>
                    <div class="text-sm text-gray-600 space-y-2">
                        <div class="flex items-start">
                            <i class="fas fa-check text-green-600 mt-1 mr-2"></i>
                            <span>Each participant picks one Premier League team per gameweek</span>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-check text-green-600 mt-1 mr-2"></i>
                            <span>Win = 3 points, Draw = 1 point, Loss = 0 points</span>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-check text-green-600 mt-1 mr-2"></i>
                            <span>Once a team is picked, it cannot be used again</span>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-check text-green-600 mt-1 mr-2"></i>
                            <span>Tournament runs for 20 Premier League gameweeks</span>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-check text-green-600 mt-1 mr-2"></i>
                            <span>Highest total score at the end wins the tournament</span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-4">
                    <Link
                        :href="route('tournaments.index')"
                        class="bg-white border border-green-200 text-gray-700 px-6 py-3 rounded-lg font-medium transition-all hover:bg-green-50"
                    >
                        Cancel
                    </Link>
                    <PrimaryButton
                        class="px-6 py-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Create Tournament
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </TournamentLayout>
</template> 