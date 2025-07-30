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
const selectedTeam = ref(null);

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
            return { class: 'bg-green-500 text-white', text: 'FT' };
        case 'SCHEDULED':
            return { class: 'bg-blue-500 text-white', text: 'Scheduled' };
        case 'LIVE':
            return { class: 'bg-red-500 text-white animate-pulse', text: 'LIVE' };
        default:
            return { class: 'bg-gray-500 text-white', text: game.status };
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
    if (selectedTeam.value) {
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
                    <h2 class="text-3xl font-bold text-white">
                        Premier League Schedule
                    </h2>
                    <p class="text-white/70 mt-2">
                        Complete fixture list for the 2024/25 season
                    </p>
                </div>
                <div class="text-right">
                    <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2 border border-white/30">
                        <p class="text-white/60 text-sm">Season Progress</p>
                        <p class="text-white font-semibold">{{ stats.completion_percentage }}% Complete</p>
                    </div>
                </div>
            </div>
        </template>

        <div class="space-y-8">
            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white/60 text-sm">Total Games</p>
                            <p class="text-white font-semibold text-xl">{{ stats.total_games }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white/60 text-sm">Completed</p>
                            <p class="text-white font-semibold text-xl">{{ stats.completed_games }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-orange-500 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white/60 text-sm">Upcoming</p>
                            <p class="text-white font-semibold text-xl">{{ stats.upcoming_games }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white/60 text-sm">Current GW</p>
                            <p class="text-white font-semibold text-xl">{{ currentGameweek ? currentGameweek.week_number : 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Highlights -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Results -->
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                    <h3 class="text-xl font-semibold text-white mb-4">Recent Results</h3>
                    <div class="space-y-3">
                        <div v-for="game in recentGames.slice(0, 5)" :key="game.id"
                             class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="flex items-center space-x-2">
                                    <img :src="game.home_team.logo_url" 
                                         :alt="game.home_team.name"
                                         class="w-6 h-6 rounded-full object-cover"
                                         @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(game.home_team.short_name)}&background=random&color=fff&size=24`">
                                    <Link :href="route('schedule.team', { team: game.home_team.id })" 
                                          class="text-white text-sm hover:text-blue-300 transition-colors">
                                        {{ game.home_team.short_name }}
                                    </Link>
                                </div>
                                <span class="text-white font-bold">{{ formatScore(game) }}</span>
                                <div class="flex items-center space-x-2">
                                    <Link :href="route('schedule.team', { team: game.away_team.id })" 
                                          class="text-white text-sm hover:text-blue-300 transition-colors">
                                        {{ game.away_team.short_name }}
                                    </Link>
                                    <img :src="game.away_team.logo_url" 
                                         :alt="game.away_team.name"
                                         class="w-6 h-6 rounded-full object-cover"
                                         @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(game.away_team.short_name)}&background=random&color=fff&size=24`">
                                </div>
                            </div>
                            <span class="text-white/60 text-xs">{{ formatDate(game.kick_off_time) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Fixtures -->
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                    <h3 class="text-xl font-semibold text-white mb-4">Upcoming Fixtures</h3>
                    <div class="space-y-3">
                        <div v-for="game in upcomingHighlights.slice(0, 5)" :key="game.id"
                             class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="flex items-center space-x-2">
                                    <img :src="game.home_team.logo_url" 
                                         :alt="game.home_team.name"
                                         class="w-6 h-6 rounded-full object-cover"
                                         @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(game.home_team.short_name)}&background=random&color=fff&size=24`">
                                    <Link :href="route('schedule.team', { team: game.home_team.id })" 
                                          class="text-white text-sm hover:text-blue-300 transition-colors">
                                        {{ game.home_team.short_name }}
                                    </Link>
                                </div>
                                <span class="text-white/60">vs</span>
                                <div class="flex items-center space-x-2">
                                    <Link :href="route('schedule.team', { team: game.away_team.id })" 
                                          class="text-white text-sm hover:text-blue-300 transition-colors">
                                        {{ game.away_team.short_name }}
                                    </Link>
                                    <img :src="game.away_team.logo_url" 
                                         :alt="game.away_team.name"
                                         class="w-6 h-6 rounded-full object-cover"
                                         @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(game.away_team.short_name)}&background=random&color=fff&size=24`">
                                </div>
                            </div>
                            <span class="text-white/60 text-xs">{{ formatDateTime(game.kick_off_time) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                <div class="flex flex-wrap items-center gap-4 mb-6">
                    <div class="flex items-center space-x-2">
                        <label class="text-white text-sm font-medium">Filter:</label>
                        <select v-model="selectedFilter" class="bg-white/20 border border-white/30 rounded-lg px-3 py-2 text-white text-sm backdrop-blur-sm focus:bg-white/30 focus:border-white/50 transition-all">
                            <option value="all" class="bg-gray-800 text-white">All Gameweeks</option>
                            <option value="completed" class="bg-gray-800 text-white">Completed</option>
                            <option value="upcoming" class="bg-gray-800 text-white">Upcoming</option>
                        </select>
                    </div>
                    <div class="flex items-center space-x-2">
                        <label class="text-white text-sm font-medium">Team:</label>
                        <select v-model="selectedTeam" class="bg-white/20 border border-white/30 rounded-lg px-3 py-2 text-white text-sm backdrop-blur-sm focus:bg-white/30 focus:border-white/50 transition-all">
                            <option :value="null" class="bg-gray-800 text-white">All Teams</option>
                            <option v-for="team in teams" :key="team.id" :value="team.id" class="bg-gray-800 text-white">{{ team.name }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Gameweeks List -->
            <div class="space-y-6">
                <div v-for="gameweek in filteredGameweeks" :key="gameweek.id"
                     class="bg-white/10 backdrop-blur-md rounded-xl border border-white/20 overflow-hidden">
                    
                    <!-- Gameweek Header -->
                    <div class="bg-gradient-to-r from-purple-600/20 to-blue-600/20 p-6 border-b border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-semibold text-white">{{ gameweek.name }}</h3>
                                <p class="text-white/60 text-sm mt-1">
                                    {{ formatDate(gameweek.start_date) }} - {{ formatDate(gameweek.end_date) }}
                                </p>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div v-if="gameweek.is_completed" class="bg-green-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                                    Completed
                                </div>
                                <div v-else-if="currentGameweek && currentGameweek.id === gameweek.id" class="bg-blue-500 text-white px-3 py-1 rounded-full text-sm font-medium animate-pulse">
                                    Current
                                </div>
                                <div v-else class="bg-gray-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                                    Upcoming
                                </div>
                                <Link :href="route('schedule.gameweek', { gameweek: gameweek.id })" 
                                      class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all">
                                    View Details
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Games -->
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="game in gameweek.games" :key="game.id"
                                 class="bg-white/5 rounded-lg p-4 hover:bg-white/10 transition-all">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-white/60 text-xs">{{ formatDateTime(game.kick_off_time) }}</span>
                                    <span :class="getGameStatusBadge(game).class" class="px-2 py-1 rounded-full text-xs font-medium">
                                        {{ getGameStatusBadge(game).text }}
                                    </span>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <!-- Home Team -->
                                    <div class="flex items-center space-x-2 flex-1">
                                        <img :src="game.home_team.logo_url" 
                                             :alt="game.home_team.name"
                                             class="w-8 h-8 rounded-full object-cover"
                                             @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(game.home_team.short_name)}&background=random&color=fff&size=32`">
                                        <Link :href="route('schedule.team', { team: game.home_team.id })" 
                                              class="text-white text-sm font-medium hover:text-blue-300 transition-colors">
                                            {{ game.home_team.short_name }}
                                        </Link>
                                    </div>
                                    
                                    <!-- Score -->
                                    <div class="px-3">
                                        <span class="text-white font-bold">{{ formatScore(game) }}</span>
                                    </div>
                                    
                                    <!-- Away Team -->
                                    <div class="flex items-center space-x-2 flex-1 justify-end">
                                        <Link :href="route('schedule.team', { team: game.away_team.id })" 
                                              class="text-white text-sm font-medium hover:text-blue-300 transition-colors">
                                            {{ game.away_team.short_name }}
                                        </Link>
                                        <img :src="game.away_team.logo_url" 
                                             :alt="game.away_team.name"
                                             class="w-8 h-8 rounded-full object-cover"
                                             @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(game.away_team.short_name)}&background=random&color=fff&size=32`">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TournamentLayout>
</template>
