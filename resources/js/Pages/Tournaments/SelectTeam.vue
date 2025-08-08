<template>
    <Head :title="`Select Team - ${tournament.name}`" />

    <TournamentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">
                        Select Your Team
                    </h2>
                    <p class="text-gray-600">
                        Gameweek {{ gameWeek.week_number }} - {{ gameWeek.name }}
                    </p>
                </div>
                <div class="text-right">
                    <div class="bg-green-50 rounded-lg px-4 py-2 border border-green-200 shadow-md">
                        <i class="fas fa-futbol text-green-600 text-lg"></i>
                    </div>
                </div>
            </div>
        </template>

        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Fixtures for this gameweek -->
            <div class="bg-white rounded-xl p-6 border border-green-200 shadow-lg">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">This Week's Fixtures</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="game in gameWeekGames" :key="game.id" 
                         class="bg-gray-50 rounded-lg p-4 border border-green-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 flex items-center justify-center">
                                    <img :src="game.home_team.logo_url" :alt="game.home_team.name" class="w-full h-full object-contain"
                                         @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(game.home_team.short_name)}&background=${encodeURIComponent(game.home_team.primary_color || '#22C55E')}&color=fff&size=32`" />
                                </div>
                                <span class="text-gray-900 font-medium">
                                    <span class="sm:hidden">{{ game.home_team.short_name || game.home_team.name }}</span>
                                    <span class="hidden sm:inline">{{ game.home_team.name }}</span>
                                </span>
                            </div>
                            <div class="text-center">
                                <span class="text-gray-500 text-sm">vs</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="text-gray-900 font-medium">
                                    <span class="sm:hidden">{{ game.away_team.short_name || game.away_team.name }}</span>
                                    <span class="hidden sm:inline">{{ game.away_team.name }}</span>
                                </span>
                                <div class="w-8 h-8 flex items-center justify-center">
                                    <img :src="game.away_team.logo_url" :alt="game.away_team.name" class="w-full h-full object-contain"
                                         @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(game.away_team.short_name)}&background=${encodeURIComponent(game.away_team.primary_color || '#22C55E')}&color=fff&size=32`" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team Selection -->
            <div class="bg-white rounded-xl p-6 border border-green-200 shadow-lg">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Select Your Team</h3>
                <p class="text-gray-600 mb-6">Choose one Premier League team for this gameweek. Once selected, you cannot use this team again.</p>
                
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <div v-for="team in sortedTeams" :key="team.id" 
                             class="relative">
                            <input
                                type="radio"
                                :id="`team-${team.id}`"
                                name="team_id"
                                :value="team.id"
                                v-model="form.team_id"
                                class="sr-only"
                            />
                            <label :for="`team-${team.id}`" class="block cursor-pointer">
                                <div class="bg-gray-50 border-2 border-green-200 rounded-lg p-4 text-center hover:bg-green-50 hover:border-green-300 transition-all"
                                     :class="{ 'bg-green-100 border-green-400 ring-2 ring-green-300': form.team_id == team.id }">
                                    <div class="w-12 h-12 mx-auto mb-2 flex items-center justify-center">
                                        <img :src="team.logo_url" :alt="team.name" class="w-full h-full object-contain"
                                             @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(team.short_name)}&background=${encodeURIComponent(team.primary_color || '#22C55E')}&color=fff&size=64`" />
                                    </div>
                                    <div class="font-medium text-gray-900 text-sm">{{ team.name }}</div>
                                    <div v-if="fixtureMap[team.id]" class="mt-1 text-xs text-gray-600">
                                        vs <span class="font-medium text-gray-700">{{ fixtureMap[team.id].opponent.short_name || fixtureMap[team.id].opponent.name }}</span>
                                        <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] bg-gray-200 text-gray-700">{{ fixtureMap[team.id].homeAway === 'home' ? 'H' : 'A' }}</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div v-if="form.team_id" class="text-center">
                        <p class="text-gray-600 mb-4">
                            Selected: <span class="font-semibold text-green-600">{{ availableTeams.find(t => t.id == form.team_id)?.name }}</span>
                        </p>
                        <PrimaryButton
                            class="w-full md:w-auto"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            {{ existingPick ? 'Update Selection' : 'Confirm Selection' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>

            <!-- Teams Already Used -->
            <div v-if="usedTeams.length > 0" class="bg-white rounded-xl p-6 border border-green-200 shadow-lg">
                <h4 class="text-md font-medium text-gray-900 mb-3">Teams Already Used</h4>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    <div v-for="team in usedTeams" :key="team.id" 
                         class="bg-gray-100 rounded-lg p-3 text-center opacity-60">
                         <div class="w-8 h-8 flex items-center justify-center mx-auto mb-1"
                             :style="{ backgroundColor: team.primary_color || '#22C55E' }">
                            <span class="font-bold text-white text-xs">{{ team.short_name }}</span>
                        </div>
                        <div class="text-gray-600 text-xs">{{ team.name }}</div>
                    </div>
                </div>
            </div>
        </div>
    </TournamentLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import TournamentLayout from '@/Layouts/TournamentLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    tournament: Object,
    gameWeek: Object,
    availableTeams: Array,
    usedTeams: Array,
    gameWeekGames: Array,
    existingPick: Object,
});

const form = useForm({
    team_id: '',
});

if (props.existingPick && props.existingPick.team_id) {
    form.team_id = props.existingPick.team_id;
}

// Teams sorted A→Z by name
const sortedTeams = computed(() => {
    return [...(props.availableTeams || [])].sort((a, b) => (a.name || '').localeCompare(b.name || ''));
});

// Build a quick lookup of opponent and home/away per team for this gameweek
const fixtureMap = {};
const gameWeekGames = Array.isArray(props.gameWeekGames) ? props.gameWeekGames : [];
for (const game of gameWeekGames) {
    if (!game?.home_team || !game?.away_team) continue;
    fixtureMap[game.home_team.id] = { opponent: game.away_team, homeAway: 'home' };
    fixtureMap[game.away_team.id] = { opponent: game.home_team, homeAway: 'away' };
}

const submit = () => {
    form.post(
        route('tournaments.gameweeks.picks.store', { tournament: props.tournament.id, gameWeek: props.gameWeek.id }),
        {
            onSuccess: () => {
                // After confirming, go back to tournament show which will read flash.success
                window.location = route('tournaments.show', props.tournament.id);
            }
        }
    );
};
</script>

<style scoped>
.team-card {
    @apply p-4 bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 rounded-lg cursor-pointer transition-all duration-200 flex items-center justify-between;
}

.team-card.selected {
    @apply bg-green-500/20 border-green-500/40;
}
</style>
