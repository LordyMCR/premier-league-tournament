<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import TournamentLayout from '@/Layouts/TournamentLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { computed, ref, onMounted, watch } from 'vue';

const props = defineProps({
    currentGameWeek: Object,
    nextGameWeekNumber: {
        type: Number,
        default: 1
    },
    remainingGameWeeks: {
        type: Number,
        default: 38
    },
    availableGameWeeks: {
        type: Array,
        default: () => []
    },
    fullSeasonEnd: {
        type: Number,
        default: 38
    },
    halfSeasonEnd: {
        type: Number,
        default: 19
    }
});

const form = useForm({
    name: '',
    description: '',
    max_participants: '',
    is_private: true,
    tournament_mode: 'full_season', // 'full_season', 'half_season', 'custom'
    start_game_week: null,
    end_game_week: null,
});

const submit = () => {
    // Ensure start_game_week and end_game_week are set for non-custom modes
    if (form.tournament_mode !== 'custom') {
        if (!form.start_game_week || !form.end_game_week) {
            updateStartGameWeek();
        }
    }
    
    // Validate that required fields are set
    if (!form.start_game_week || !form.end_game_week) {
        console.error('Missing game week values:', {
            mode: form.tournament_mode,
            start: form.start_game_week,
            end: form.end_game_week,
            props: {
                nextGameWeekNumber: props.nextGameWeekNumber,
                fullSeasonEnd: props.fullSeasonEnd,
                halfSeasonEnd: props.halfSeasonEnd,
            }
        });
        alert('Please select start and end game weeks.');
        return;
    }
    
    console.log('Submitting tournament form:', {
        name: form.name,
        tournament_mode: form.tournament_mode,
        start_game_week: form.start_game_week,
        end_game_week: form.end_game_week,
        max_participants: form.max_participants,
    });
    
    form.post(route('tournaments.store'), {
        onError: (errors) => {
            console.error('Form submission errors:', errors);
        },
        onSuccess: () => {
            console.log('Tournament created successfully');
        },
    });
};

// Calculate total game weeks based on mode
const totalGameWeeks = computed(() => {
    switch (form.tournament_mode) {
        case 'full_season':
            return props.fullSeasonEnd - props.nextGameWeekNumber + 1;
        case 'half_season':
            return props.halfSeasonEnd - props.nextGameWeekNumber + 1;
        case 'custom':
            if (form.start_game_week && form.end_game_week) {
                return form.end_game_week - form.start_game_week + 1;
            }
            return 0;
        default:
            return 0;
    }
});

// Calculate what selection strategy will be used
const selectionStrategy = computed(() => {
    switch (form.tournament_mode) {
        case 'full_season':
            return 'home_away_required';
        case 'half_season':
            return 'once_only';
        case 'custom':
            const weeks = totalGameWeeks.value;
            return weeks > 20 ? 'home_away_allowed' : 'once_only';
        default:
            return 'once_only';
    }
});

// Get strategy description
const strategyDescription = computed(() => {
    switch (selectionStrategy.value) {
        case 'home_away_required':
            return 'Each team can be picked twice (once home, once away)';
        case 'home_away_allowed':
            return 'Each team can be picked up to twice (home and/or away)';
        case 'once_only':
        default:
            return 'Each team can only be picked once';
    }
});

// Get available game weeks for custom mode
const customGameWeeks = computed(() => {
    return props.availableGameWeeks;
});

// Update start game week when mode changes
const updateStartGameWeek = () => {
    if (form.tournament_mode === 'full_season') {
        form.start_game_week = props.nextGameWeekNumber;
        form.end_game_week = props.fullSeasonEnd;
    } else if (form.tournament_mode === 'half_season') {
        form.start_game_week = props.nextGameWeekNumber;
        form.end_game_week = props.halfSeasonEnd;
    } else {
        // For custom mode, default to the next available gameweek
        const nextAvailableGameweek = props.availableGameWeeks.length > 0 ? props.availableGameWeeks[0].week_number : props.nextGameWeekNumber;
        form.start_game_week = nextAvailableGameweek;
        form.end_game_week = null;
    }
    
    // Convert to numbers to ensure they're sent as integers
    if (form.start_game_week !== null) {
        form.start_game_week = Number(form.start_game_week);
    }
    if (form.end_game_week !== null) {
        form.end_game_week = Number(form.end_game_week);
    }
};

// Initialize form with default values for full season
onMounted(() => {
    // Wait a tick to ensure props are loaded
    setTimeout(() => {
        updateStartGameWeek();
    }, 0);
});

// Watch for prop changes and update form values
watch(() => [props.nextGameWeekNumber, props.fullSeasonEnd, props.halfSeasonEnd], () => {
    if (form.tournament_mode !== 'custom') {
        updateStartGameWeek();
    }
}, { immediate: false });
</script>

<template>
    <Head title="Create Tournament - PL Tournament" />

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
            <!-- General Error Display -->
            <div v-if="form.errors.tournament || form.errors.tournament_mode" class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-circle text-red-600 mt-1 mr-3"></i>
                    <div>
                        <p v-if="form.errors.tournament" class="text-red-800 font-medium">{{ form.errors.tournament }}</p>
                        <p v-if="form.errors.tournament_mode" class="text-red-800 font-medium">{{ form.errors.tournament_mode }}</p>
                    </div>
                </div>
            </div>
            
            <form @submit.prevent="submit" class="space-y-8">
                <!-- Tournament Details -->
                <div class="bg-white rounded-xl p-6 border border-green-200 shadow-lg">
                    <div class="px-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Tournament Details</h3>
                        <p class="text-gray-600 text-sm mb-6">Basic information about your tournament</p>
                    </div>
                    
                    <div class="space-y-6">
                        <div>
                            <InputLabel for="name" value="Tournament Name" />
                            <TextInput
                                id="name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.name"
                                required
                                autofocus
                                placeholder="e.g., Premier League Full Season Predictions"
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>
                        
                        <div>
                            <InputLabel for="max_participants" value="Maximum Participants" />
                            <TextInput
                                id="max_participants"
                                type="number"
                                class="mt-1 block w-full"
                                v-model="form.max_participants"
                                required
                                min="2"
                                max="20"
                                placeholder="2-20 participants"
                            />
                            <InputError class="mt-2" :message="form.errors.max_participants" />
                        </div>
                        
                        <div>
                            <InputLabel for="description" value="Tournament Description" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                class="mt-1 block w-full bg-white border border-gray-300 text-gray-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 placeholder-gray-600"
                                rows="4"
                                placeholder="e.g., Weekly Premier League predictions with prizes for the top 3 finishers..."
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>
                    </div>
                </div>

                <!-- Tournament Settings -->
                <div class="bg-white rounded-xl p-6 border border-green-200 shadow-lg">
                    <div class="px-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Tournament Settings</h3>
                        <p class="text-gray-600 text-sm mb-6">Choose how long your tournament will run</p>
                    </div>
                    
                    <div class="space-y-6">
                        <!-- Tournament Mode Selection -->
                        <div>
                            <InputLabel value="Tournament Duration" />
                            <InputError class="mt-2" :message="form.errors.tournament_mode" />
                            <div class="mt-3 space-y-3">
                                <label class="flex items-start">
                                    <input
                                        type="radio"
                                        v-model="form.tournament_mode"
                                        value="full_season"
                                        @change="updateStartGameWeek"
                                        class="mt-1 h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500"
                                    />
                                    <div class="ml-3">
                                        <span class="text-gray-900 font-medium">Full Season</span>
                                        <p class="text-gray-600 text-sm">From Game Week {{ nextGameWeekNumber }} to {{ fullSeasonEnd }} ({{ fullSeasonEnd - nextGameWeekNumber + 1 }} gameweeks)</p>
                                        <p class="text-blue-600 text-xs mt-1">Teams can be picked twice: once home, once away</p>
                                    </div>
                                </label>
                                
                                <label class="flex items-start">
                                    <input
                                        type="radio"
                                        v-model="form.tournament_mode"
                                        value="half_season"
                                        @change="updateStartGameWeek"
                                        class="mt-1 h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500"
                                    />
                                    <div class="ml-3">
                                        <span class="text-gray-900 font-medium">Half Season</span>
                                        <p class="text-gray-600 text-sm">From Game Week {{ nextGameWeekNumber }} to {{ halfSeasonEnd }} ({{ halfSeasonEnd - nextGameWeekNumber + 1 }} gameweeks)</p>
                                        <p class="text-orange-600 text-xs mt-1">Each team can only be picked once</p>
                                    </div>
                                </label>
                                
                                <label class="flex items-start">
                                    <input
                                        type="radio"
                                        v-model="form.tournament_mode"
                                        value="custom"
                                        @change="updateStartGameWeek"
                                        class="mt-1 h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500"
                                    />
                                    <div class="ml-3">
                                        <span class="text-gray-900 font-medium">Custom Range</span>
                                        <p class="text-gray-600 text-sm">Choose specific gameweeks to include</p>
                                        <p class="text-purple-600 text-xs mt-1">≤20 weeks: teams once only, >20 weeks: teams twice (home/away)</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Custom Game Week Selection -->
                        <div v-if="form.tournament_mode === 'custom'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="start_game_week" value="Start Game Week" />
                                <select
                                    id="start_game_week"
                                    v-model="form.start_game_week"
                                    class="mt-1 block w-full bg-white border border-gray-300 text-gray-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200"
                                    required
                                >
                                    <option value="">Select start game week</option>
                                    <option v-for="gameweek in customGameWeeks" :key="gameweek.week_number" :value="gameweek.week_number">
                                        Game Week {{ gameweek.week_number }} ({{ new Date(gameweek.start_date).toLocaleDateString() }})
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.start_game_week" />
                            </div>
                            
                            <div>
                                <InputLabel for="end_game_week" value="End Game Week" />
                                <select
                                    id="end_game_week"
                                    v-model="form.end_game_week"
                                    class="mt-1 block w-full bg-white border border-gray-300 text-gray-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200"
                                    required
                                >
                                    <option value="">Select end game week</option>
                                    <option v-for="gameweek in customGameWeeks.filter(gw => gw.week_number >= form.start_game_week)" :key="gameweek.week_number" :value="gameweek.week_number">
                                        Game Week {{ gameweek.week_number }} ({{ new Date(gameweek.end_date).toLocaleDateString() }})
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.end_game_week" />
                            </div>
                        </div>
                        
                        <!-- Tournament Summary -->
                        <div v-if="totalGameWeeks > 0" class="bg-green-50 rounded-lg p-4 border border-green-200">
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <i class="fas fa-info-circle text-green-600 mr-3"></i>
                                    <div>
                                        <span class="text-green-800 font-medium">Tournament Summary:</span>
                                        <span class="text-green-700 ml-2">{{ totalGameWeeks }} gameweeks</span>
                                        <span v-if="form.tournament_mode === 'custom' && form.start_game_week && form.end_game_week" class="text-green-600 ml-2">
                                            (Game Week {{ form.start_game_week }} to {{ form.end_game_week }})
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-users text-green-600 mr-3"></i>
                                    <div>
                                        <span class="text-green-800 font-medium">Team Selection:</span>
                                        <span class="text-green-700 ml-2">{{ strategyDescription }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="flex items-start">
                                <Checkbox v-model:checked="form.is_private" />
                                <div class="ml-3">
                                    <span class="text-gray-700 font-medium">Private Tournament</span>
                                    <p class="text-gray-600 text-sm mt-1">Only people with the join code can participate</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Tournament Rules -->
                <div class="bg-white rounded-xl p-6 border border-green-200 shadow-lg">
                    <div class="px-4">
                        <h3 class="font-semibold text-gray-900 mb-4">How It Works</h3>
                    </div>
                    <div class="text-sm text-gray-600 space-y-3">
                        <div class="flex items-start">
                            <i class="fas fa-check text-green-600 mt-1 mr-3 flex-shrink-0"></i>
                            <span>Each participant picks one Premier League team per gameweek</span>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-check text-green-600 mt-1 mr-3 flex-shrink-0"></i>
                            <span>Win = 3 points, Draw = 1 point, Loss = 0 points</span>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-trophy text-green-600 mt-1 mr-3 flex-shrink-0"></i>
                            <span>{{ strategyDescription }}</span>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-check text-green-600 mt-1 mr-3 flex-shrink-0"></i>
                            <span>Highest total score at the end wins the tournament</span>
                        </div>
                        <div v-if="selectionStrategy !== 'once_only'" class="flex items-start">
                            <i class="fas fa-info text-blue-600 mt-1 mr-3 flex-shrink-0"></i>
                            <span class="text-blue-700">
                                Strategic tip: Choose wisely between home and away games - teams often perform differently!
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <Link
                        :href="route('tournaments.index')"
                        class="bg-white border border-green-200 text-gray-700 px-6 py-3 rounded-lg font-medium transition-all hover:bg-green-50 text-center"
                    >
                        Cancel
                    </Link>
                    <PrimaryButton
                        class="px-6 py-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        <i class="fas fa-plus mr-2"></i>
                        Create Tournament
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </TournamentLayout>
</template> 