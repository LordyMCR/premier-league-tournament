<template>
    <Head :title="`Select Team - ${tournament.name} - PL Tournament`" />

    <TournamentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">
                        Select Your Team
                    </h2>
                    <p class="text-gray-600">
                        {{ gameWeek.name }}
                    </p>
                    <p v-if="gameWeekDateRange" class="text-gray-500 text-sm">
                        {{ gameWeekDateRange }}
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
                <p v-if="!allowsHomeAwayPicks" class="text-gray-600 mb-6">Choose one Premier League team for this gameweek. Once selected, you cannot use this team again.</p>
                <p v-else class="text-gray-600 mb-6">Choose one Premier League team for this gameweek and whether you want them playing at home or away. You can pick each team twice (once home, once away).</p>
                
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- For tournaments that don't allow home/away picks (original logic) -->
                    <div v-if="!allowsHomeAwayPicks" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <div v-for="team in sortedTeams" :key="team.id" 
                             class="relative">
                            <input
                                type="radio"
                                :id="`team-${team.id}`"
                                name="team_id"
                                :value="team.id"
                                v-model="form.team_id"
                                :disabled="team.isDisabled"
                                class="sr-only"
                            />
                            <label :for="`team-${team.id}`" 
                                   class="block h-full"
                                   :class="team.isDisabled ? 'cursor-not-allowed' : 'cursor-pointer'">
                                <div class="border-2 rounded-lg p-4 text-center transition-all h-full flex flex-col justify-between min-h-[140px]"
                                     :class="{
                                         // Selected state
                                         'bg-green-100 border-green-400 ring-2 ring-green-300': form.team_id == team.id && !team.isDisabled,
                                         // Available state
                                         'bg-gray-50 border-green-200 hover:bg-green-50 hover:border-green-300': !team.isDisabled && form.team_id != team.id,
                                         // Disabled state
                                         'bg-gray-100 border-gray-300 opacity-60': team.isDisabled
                                     }">
                                    <div class="flex-1 flex flex-col justify-center">
                                        <div class="w-12 h-12 mx-auto mb-2 flex items-center justify-center">
                                            <img :src="team.logo_url" :alt="team.name" class="w-full h-full object-contain"
                                                 @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(team.short_name)}&background=${encodeURIComponent(team.primary_color || '#22C55E')}&color=fff&size=64`" />
                                        </div>
                                        <div class="font-medium text-sm"
                                             :class="team.isDisabled ? 'text-gray-500' : 'text-gray-900'">
                                            {{ team.name }}
                                        </div>
                                    </div>
                                    <div class="mt-auto">
                                        <div v-if="fixtureMap[team.id]" class="text-xs"
                                             :class="team.isDisabled ? 'text-gray-400' : 'text-gray-600'">
                                            vs <span class="font-medium" :class="team.isDisabled ? 'text-gray-500' : 'text-gray-700'">{{ fixtureMap[team.id].opponent.short_name || fixtureMap[team.id].opponent.name }}</span>
                                            <span class="ml-1 px-1.5 py-0.5 rounded text-[10px]"
                                                  :class="team.isDisabled ? 'bg-gray-200 text-gray-500' : 'bg-gray-200 text-gray-700'">
                                                {{ fixtureMap[team.id].homeAway === 'home' ? 'H' : 'A' }}
                                            </span>
                                        </div>
                                        <div v-if="team.isDisabled" class="text-xs text-red-600 mt-1">
                                            Already picked
                                        </div>
                                        <!-- Spacer for teams without fixture info or disabled state -->
                                        <div v-else-if="!fixtureMap[team.id]" class="text-xs h-4"></div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- For tournaments that allow home/away picks (full season) -->
                    <div v-else class="space-y-6">
                        <!-- Step 1: Select Team -->
                        <div>
                            <h4 class="text-md font-semibold text-gray-900 mb-3">Step 1: Choose Your Team</h4>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                <div v-for="team in uniqueTeams" :key="team.id" 
                                     class="relative">
                                    <input
                                        type="radio"
                                        :id="`team-${team.id}`"
                                        name="team_id"
                                        :value="team.id"
                                        v-model="form.team_id"
                                        @change="form.home_away = ''"
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
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Select Home/Away (only when a team is selected) -->
                        <div v-if="form.team_id" class="border-t pt-6">
                            <h4 class="text-md font-semibold text-gray-900 mb-3">Step 2: Choose Home or Away</h4>
                            <div class="grid grid-cols-2 gap-4 max-w-md">
                                <!-- Home Option -->
                                <div class="relative">
                                    <input
                                        type="radio"
                                        id="home"
                                        name="home_away"
                                        value="home"
                                        v-model="form.home_away"
                                        :disabled="!isHomeAwayAvailable('home')"
                                        class="sr-only"
                                    />
                                    <label for="home" class="block cursor-pointer" :class="{ 'cursor-not-allowed opacity-50': !isHomeAwayAvailable('home') }">
                                        <div class="bg-gray-50 border-2 border-green-200 rounded-lg p-4 text-center hover:bg-green-50 hover:border-green-300 transition-all"
                                             :class="{ 'bg-green-100 border-green-400 ring-2 ring-green-300': form.home_away === 'home', 'bg-red-50 border-red-200': !isHomeAwayAvailable('home') }">
                                            <div class="text-2xl mb-2">🏠</div>
                                            <div class="font-medium text-gray-900">Home</div>
                                            <div v-if="fixtureMap[form.team_id] && fixtureMap[form.team_id].homeAway === 'home'" class="text-xs text-green-600 mt-1">
                                                Playing at home this week
                                            </div>
                                            <div v-if="!isHomeAwayAvailable('home')" class="text-xs text-red-600 mt-1">
                                                {{ getDisabledReason('home') }}
                                            </div>
                                        </div>
                                    </label>
                                </div>

                                <!-- Away Option -->
                                <div class="relative">
                                    <input
                                        type="radio"
                                        id="away"
                                        name="home_away"
                                        value="away"
                                        v-model="form.home_away"
                                        :disabled="!isHomeAwayAvailable('away')"
                                        class="sr-only"
                                    />
                                    <label for="away" class="block cursor-pointer" :class="{ 'cursor-not-allowed opacity-50': !isHomeAwayAvailable('away') }">
                                        <div class="bg-gray-50 border-2 border-green-200 rounded-lg p-4 text-center hover:bg-green-50 hover:border-green-300 transition-all"
                                             :class="{ 'bg-green-100 border-green-400 ring-2 ring-green-300': form.home_away === 'away', 'bg-red-50 border-red-200': !isHomeAwayAvailable('away') }">
                                            <div class="text-2xl mb-2">✈️</div>
                                            <div class="font-medium text-gray-900">Away</div>
                                            <div v-if="fixtureMap[form.team_id] && fixtureMap[form.team_id].homeAway === 'away'" class="text-xs text-green-600 mt-1">
                                                Playing away this week
                                            </div>
                                            <div v-if="!isHomeAwayAvailable('away')" class="text-xs text-red-600 mt-1">
                                                {{ getDisabledReason('away') }}
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="form.team_id && (!allowsHomeAwayPicks || form.home_away)" class="text-center">
                        <div class="flex items-center justify-center mb-4">
                            <div v-if="getSelectedTeam()" class="flex items-center space-x-3">
                                <div class="w-10 h-10 flex items-center justify-center">
                                    <img :src="getSelectedTeam().logo_url" 
                                         :alt="getSelectedTeam().name" 
                                         class="w-full h-full object-contain"
                                         @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(getSelectedTeam().short_name)}&background=${encodeURIComponent(getSelectedTeam().primary_color || '#22C55E')}&color=fff&size=40`" />
                                </div>
                                <div>
                                    <p class="text-gray-600">Selected:</p>
                                    <p class="font-semibold text-green-600 text-lg">
                                        {{ getSelectedTeamName() }}
                                        <span v-if="allowsHomeAwayPicks && form.home_away" class="ml-2 px-2 py-1 rounded text-sm bg-green-100 text-green-700">
                                            {{ form.home_away === 'home' ? '🏠 Home' : '✈️ Away' }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
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
                    <div v-for="team in usedTeams" :key="`${team.id}-${team.home_away || 'once'}`" 
                         class="bg-gray-100 rounded-lg p-3 text-center opacity-60">
                         <div class="w-8 h-8 flex items-center justify-center mx-auto mb-1">
                            <img :src="team.logo_url" 
                                 :alt="team.name" 
                                 class="w-full h-full object-contain"
                                 @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(team.short_name)}&background=${encodeURIComponent(team.primary_color || '#22C55E')}&color=fff&size=32`" />
                        </div>
                        <div class="text-gray-600 text-xs">{{ team.name }}</div>
                        <div v-if="allowsHomeAwayPicks && team.home_away" class="text-[10px] text-gray-500 mt-1">
                            {{ team.home_away === 'home' ? '🏠 Home' : '✈️ Away' }}
                        </div>
                        <div v-if="team.game_week" class="text-[10px] text-gray-400 mt-1">
                            {{ team.game_week }}
                        </div>
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
    actuallyAvailableTeams: Array, // For once_only tournaments: teams that can actually be selected
    usedTeams: Array,
    gameWeekGames: Array,
    allowsHomeAwayPicks: Boolean,
    selectionStrategy: String,
    existingPick: Object,
});

const form = useForm({
    team_id: '',
    home_away: '', // For tournaments that allow home/away picks
});

if (props.existingPick && props.existingPick.team_id) {
    form.team_id = props.existingPick.team_id;
    if (props.allowsHomeAwayPicks && props.existingPick.home_away) {
        form.home_away = props.existingPick.home_away;
    }
}

// For once_only tournaments, we need to show all teams but mark which are disabled
const allTeamsForDisplay = computed(() => {
    if (props.allowsHomeAwayPicks) {
        return [...(props.availableTeams || [])].sort((a, b) => (a.name || '').localeCompare(b.name || ''));
    }
    
    // For once_only tournaments, use availableTeams (which now contains all teams) and determine disabled status
    const actuallyAvailableTeamIds = new Set((props.actuallyAvailableTeams || []).map(team => team.id));
    
    return (props.availableTeams || []).map(team => ({
        ...team,
        isDisabled: !actuallyAvailableTeamIds.has(team.id) && 
                   (!props.existingPick || props.existingPick.team_id != team.id)
    })).sort((a, b) => (a.name || '').localeCompare(b.name || ''));
});

// Teams sorted A→Z by name
const sortedTeams = computed(() => {
    return allTeamsForDisplay.value;
});

// For home/away tournaments, get unique teams (remove duplicates based on team ID)
const uniqueTeams = computed(() => {
    if (!props.allowsHomeAwayPicks) return sortedTeams.value;
    
    const seen = new Set();
    return sortedTeams.value.filter(team => {
        if (seen.has(team.id)) return false;
        seen.add(team.id);
        return true;
    });
});

// Check if a home/away option is available for the selected team
const isHomeAwayAvailable = (homeAway) => {
    if (!form.team_id) return false;
    
    // If we're editing an existing pick with the same team and home_away, allow it
    if (props.existingPick && 
        props.existingPick.team_id == form.team_id && 
        props.existingPick.home_away === homeAway) {
        return true;
    }
    
    // Check what the team is actually playing this week (home or away)
    const fixture = fixtureMap[form.team_id];
    if (fixture) {
        // If team is playing at home this week, only allow "home" selection
        // If team is playing away this week, only allow "away" selection
        if (fixture.homeAway !== homeAway) {
            return false; // Team is not playing in this home/away context this week
        }
    }
    
    // Check if this team+home_away combination has already been used
    const alreadyUsed = props.usedTeams.some(team => 
        team.id == form.team_id && team.home_away === homeAway
    );
    
    return !alreadyUsed;
};

// Get the reason why a home/away option is disabled
const getDisabledReason = (homeAway) => {
    if (!form.team_id) return '';
    
    // Check if already picked
    const alreadyUsed = props.usedTeams.some(team => 
        team.id == form.team_id && team.home_away === homeAway
    );
    
    if (alreadyUsed) {
        return `Already picked ${homeAway}`;
    }
    
    // Check fixture context
    const fixture = fixtureMap[form.team_id];
    if (fixture && fixture.homeAway !== homeAway) {
        return `Playing ${fixture.homeAway} this week`;
    }
    
    return '';
};

// Get the name of the currently selected team
const getSelectedTeamName = () => {
    if (!form.team_id) return '';
    
    const team = props.allowsHomeAwayPicks 
        ? uniqueTeams.value.find(t => t.id == form.team_id)
        : sortedTeams.value.find(t => t.id == form.team_id);
        
    return team?.name || '';
};

// Get the full team object for the currently selected team
const getSelectedTeam = () => {
    if (!form.team_id) return null;
    
    const team = props.allowsHomeAwayPicks 
        ? uniqueTeams.value.find(t => t.id == form.team_id)
        : sortedTeams.value.find(t => t.id == form.team_id);
        
    return team || null;
};

// Build a quick lookup of opponent and home/away per team for this gameweek
const fixtureMap = {};
const gameWeekGames = Array.isArray(props.gameWeekGames) ? props.gameWeekGames : [];
for (const game of gameWeekGames) {
    if (!game?.home_team || !game?.away_team) continue;
    fixtureMap[game.home_team.id] = { opponent: game.away_team, homeAway: 'home' };
    fixtureMap[game.away_team.id] = { opponent: game.home_team, homeAway: 'away' };
}

// Calculate the date range for this gameweek
const gameWeekDateRange = computed(() => {
    if (!gameWeekGames.length) return null;
    
    const dates = gameWeekGames
        .filter(game => game.kick_off_time)
        .map(game => new Date(game.kick_off_time))
        .sort((a, b) => a - b);
    
    if (dates.length === 0) return null;
    
    const firstDate = dates[0];
    const lastDate = dates[dates.length - 1];
    
    const formatDate = (date) => {
        return date.toLocaleDateString('en-GB', { 
            day: 'numeric', 
            month: 'short',
            year: firstDate.getFullYear() !== new Date().getFullYear() ? 'numeric' : undefined
        });
    };
    
    if (firstDate.toDateString() === lastDate.toDateString()) {
        return formatDate(firstDate);
    } else {
        return `${formatDate(firstDate)} - ${formatDate(lastDate)}`;
    }
});

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
