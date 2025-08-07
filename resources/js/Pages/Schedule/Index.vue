<script setup>
import { Head, Link } from '@inertiajs/vue3';
import TournamentLayout from '@/Layouts/TournamentLayout.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    gameweeks: Array,
    currentGameweek: Object,
    nextGameweek: Object,
    teams: Array,
    stats: Object,
    recentGames: Array,
    upcomingHighlights: Array,
});

const selectedFilter = ref('all');
const selectedTeam = ref('');

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};

const formatDateTime = (dateString) => {
    if (!dateString) return 'TBD';
    
    const date = new Date(dateString);
    
    // Check if the date is valid
    if (isNaN(date.getTime())) {
        return 'TBD';
    }
    
    return date.toLocaleDateString('en-GB', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatScore = (game) => {
    if (game.status === 'FINISHED') {
        return `${game.home_score} - ${game.away_score}`;
    }
    return 'vs';
};

const getGameStatusBadge = (game) => {
    switch (game.status) {
        case 'FINISHED':
            return { class: 'bg-green-100 text-green-700', text: 'FT' };
        case 'SCHEDULED':
            return { class: 'bg-blue-100 text-blue-700', text: 'Scheduled' };
        case 'LIVE':
            return { class: 'bg-red-100 text-red-700 animate-pulse', text: 'LIVE' };
        default:
            return { class: 'bg-gray-100 text-gray-700', text: game.status };
    }
};

const filteredGameweeks = computed(() => {
    let gameweeks = props.gameweeks;
    
    // Filter by completion status
    if (selectedFilter.value === 'completed') {
        gameweeks = gameweeks.filter(gw => gw.is_completed);
    } else if (selectedFilter.value === 'upcoming') {
        gameweeks = gameweeks.filter(gw => !gw.is_completed);
    }
    
    // Filter by team if selected
    if (selectedTeam.value && selectedTeam.value !== '') {
        gameweeks = gameweeks.map(gameweek => ({
            ...gameweek,
            games: gameweek.games.filter(game => 
                game.home_team.id == selectedTeam.value || 
                game.away_team.id == selectedTeam.value
            )
        })).filter(gameweek => gameweek.games.length > 0);
    }
    
    return gameweeks;
});
</script>

<template>
    <Head title="Premier League Schedule" />

    <TournamentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900">
                        Premier League Schedule
                    </h2>
                    <p class="text-gray-600 mt-2">
                        Complete fixture list for the 2024/25 season
                    </p>
                </div>
                <div class="text-right">
                    <div class="bg-green-50 rounded-lg px-4 py-2 border border-green-200 shadow-md">
                        <p class="text-gray-600 text-sm">Current Gameweek</p>
                        <p class="text-gray-900 font-bold text-lg">{{ currentGameweek?.week_number || 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </template>

        <!-- Filters -->
        <div class="bg-white rounded-xl p-6 border border-green-200 shadow-lg mb-8">
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Status</label>
                    <select 
                        v-model="selectedFilter"
                        class="w-full bg-white border border-gray-300 text-gray-900 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    >
                        <option value="all">All Gameweeks</option>
                        <option value="completed">Completed</option>
                        <option value="upcoming">Upcoming</option>
                    </select>
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Team</label>
                    <select 
                        v-model="selectedTeam"
                        class="w-full bg-white border border-gray-300 text-gray-900 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    >
                        <option value="">All Teams</option>
                        <option v-for="team in teams" :key="team.id" :value="team.id">
                            {{ team.name }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Gameweeks Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div v-for="gameweek in filteredGameweeks" :key="gameweek.id" 
                 class="bg-white rounded-xl border border-green-200 shadow-lg hover:shadow-xl transition-all duration-300">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <Link :href="route('schedule.gameweek', gameweek.id)" 
                              class="text-xl font-bold text-gray-900 hover:text-green-600 transition-colors">
                            {{ gameweek.name }}
                        </Link>
                        <span class="px-3 py-1 rounded-full text-xs font-medium"
                              :class="gameweek.is_completed ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'">
                            {{ gameweek.is_completed ? 'Completed' : 'Upcoming' }}
                        </span>
                    </div>
                    
                    <div class="space-y-3">
                        <div v-for="game in gameweek.games" :key="game.id" 
                             class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center space-x-3 flex-1 min-w-0">
                                <Link :href="route('schedule.team', game.home_team.id)" 
                                      class="w-10 h-10 flex items-center justify-center overflow-hidden hover:opacity-80 transition-opacity flex-shrink-0">
                                    <img :src="game.home_team.logo_url" 
                                         :alt="game.home_team.name"
                                         class="w-full h-full object-contain"
                                         @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(game.home_team.short_name)}&background=${encodeURIComponent(game.home_team.primary_color || '#22C55E')}&color=fff&size=40`">
                                </Link>
                                <Link :href="route('schedule.team', game.home_team.id)" 
                                      class="text-gray-900 font-medium hover:text-green-600 transition-colors truncate">
                                    {{ game.home_team.name }}
                                </Link>
                            </div>
                            
                            <Link :href="route('schedule.match', game.id)" 
                                  class="text-center mx-4 hover:bg-white rounded-lg px-3 py-1 transition-colors flex-shrink-0">
                                <div class="text-gray-900 font-bold">{{ formatScore(game) }}</div>
                                <div class="text-xs text-gray-500">{{ formatDateTime(game.kick_off_time) }}</div>
                            </Link>
                            
                            <div class="flex items-center space-x-3 flex-1 justify-end min-w-0">
                                <Link :href="route('schedule.team', game.away_team.id)" 
                                      class="text-gray-900 font-medium hover:text-green-600 transition-colors truncate text-right">
                                    {{ game.away_team.name }}
                                </Link>
                                <Link :href="route('schedule.team', game.away_team.id)" 
                                      class="w-10 h-10 flex items-center justify-center overflow-hidden hover:opacity-80 transition-opacity flex-shrink-0">
                                    <img :src="game.away_team.logo_url" 
                                         :alt="game.away_team.name"
                                         class="w-full h-full object-contain"
                                         @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(game.away_team.short_name)}&background=${encodeURIComponent(game.away_team.primary_color || '#22C55E')}&color=fff&size=40`">
                                </Link>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-green-200">
                        <Link :href="route('schedule.gameweek', gameweek.id)" 
                              class="text-green-600 hover:text-green-700 text-sm font-medium transition-colors">
                            View Details →
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </TournamentLayout>
</template>
