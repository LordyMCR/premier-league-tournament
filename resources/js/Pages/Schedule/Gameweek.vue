<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import TournamentLayout from '@/Layouts/TournamentLayout.vue';

const props = defineProps({
    gameweek: Object,
    previousGameweek: Object,
    nextGameweek: Object,
});

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
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
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

const viewMatch = (game) => {
    router.visit(route('schedule.match', { game: game.id }));
};

const getGamesByDate = () => {
    const gamesByDate = {};
    
    props.gameweek.games.forEach(game => {
        const dateKey = new Date(game.kick_off_time).toDateString();
        if (!gamesByDate[dateKey]) {
            gamesByDate[dateKey] = [];
        }
        gamesByDate[dateKey].push(game);
    });
    
    return gamesByDate;
};

const gamesByDate = getGamesByDate();
</script>

<template>
    <Head :title="`${gameweek.name} - Premier League`" />

    <TournamentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900">
                        {{ gameweek.name }}
                    </h2>
                    <p class="text-gray-600 mt-2">
                        {{ formatDate(gameweek.start_date) }} - {{ formatDate(gameweek.end_date) }}
                    </p>
                </div>
                <div class="flex items-center space-x-4">
                    <div v-if="gameweek.is_completed" class="bg-green-100 text-green-700 px-4 py-2 rounded-full text-sm font-medium">
                        Completed
                    </div>
                    <div v-else class="bg-blue-100 text-blue-700 px-4 py-2 rounded-full text-sm font-medium">
                        Upcoming
                    </div>
                    <Link :href="route('schedule.index')" 
                          class="bg-white border border-green-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-all hover:bg-green-50">
                        ← Back to Schedule
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-8">
            <!-- Navigation -->
            <div class="flex items-center justify-between">
                <div>
                    <Link v-if="previousGameweek" 
                          :href="route('schedule.gameweek', previousGameweek.id)"
                          class="flex items-center space-x-2 bg-white border border-green-200 text-gray-700 px-4 py-2 rounded-lg transition-all hover:bg-green-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        <span>{{ previousGameweek.name }}</span>
                    </Link>
                </div>
                <div>
                    <Link v-if="nextGameweek" 
                          :href="route('schedule.gameweek', nextGameweek.id)"
                          class="flex items-center space-x-2 bg-white border border-green-200 text-gray-700 px-4 py-2 rounded-lg transition-all hover:bg-green-50">
                        <span>{{ nextGameweek.name }}</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </Link>
                </div>
            </div>

            <!-- Gameweek Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl p-6 border border-green-200 shadow-lg">
                    <h4 class="text-gray-900 font-semibold mb-2">Selection Window</h4>
                    <div class="space-y-1 text-gray-600 text-sm">
                        <p>Opens: {{ formatDateTime(gameweek.selection_opens) }}</p>
                        <p>Deadline: {{ formatDateTime(gameweek.selection_deadline) }}</p>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl p-6 border border-green-200 shadow-lg">
                    <h4 class="text-gray-900 font-semibold mb-2">Match Period</h4>
                    <div class="space-y-1 text-gray-600 text-sm">
                        <p>First Kick-off: {{ formatDateTime(gameweek.gameweek_start_time) }}</p>
                        <p>Last Match: {{ formatDateTime(gameweek.gameweek_end_time) }}</p>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl p-6 border border-green-200 shadow-lg">
                    <h4 class="text-gray-900 font-semibold mb-2">Status</h4>
                    <div class="space-y-1 text-gray-600 text-sm">
                        <p>Week Number: {{ gameweek.week_number }}</p>
                        <p>Status: {{ gameweek.is_completed ? 'Completed' : 'Upcoming' }}</p>
                    </div>
                </div>
            </div>

            <!-- Fixtures by Date -->
            <div class="space-y-6">
                <div v-for="(games, dateKey) in gamesByDate" :key="dateKey"
                     class="bg-white rounded-xl border border-green-200 shadow-lg overflow-hidden">
                    
                    <!-- Date Header -->
                    <div class="bg-green-50 p-4 border-b border-green-200">
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ new Date(dateKey).toLocaleDateString('en-GB', { 
                                weekday: 'long', 
                                day: 'numeric', 
                                month: 'long',
                                year: 'numeric'
                            }) }}
                        </h3>
                    </div>

                    <!-- Games for this date -->
                    <div class="p-6">
                        <div class="space-y-4">
                            <div v-for="game in games" :key="game.id"
                                 @click="viewMatch(game)"
                                 class="bg-gray-50 rounded-lg p-6 hover:bg-gray-100 transition-all cursor-pointer">
                                
                                <!-- Game Time and Status -->
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-gray-600 font-medium">
                                        {{ new Date(game.kick_off_time).toLocaleTimeString('en-GB', { 
                                            hour: '2-digit', 
                                            minute: '2-digit' 
                                        }) }}
                                    </span>
                                    <span :class="getGameStatusBadge(game).class" class="px-3 py-1 rounded-full text-sm font-medium">
                                        {{ getGameStatusBadge(game).text }}
                                    </span>
                                </div>
                                
                                <!-- Teams and Score -->
                                <div class="flex items-center justify-between">
                                    <!-- Home Team -->
                                    <div class="flex items-center space-x-4 flex-1">
                                        <img :src="game.home_team.logo_url" 
                                             :alt="game.home_team.name"
                                             class="w-12 h-12 object-contain"
                                             @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(game.home_team.short_name)}&background=${encodeURIComponent(game.home_team.primary_color || '#22C55E')}&color=fff&size=48`">
                                        <div>
                                            <h4 class="text-gray-900 font-semibold">{{ game.home_team.name }}</h4>
                                            <p class="text-gray-600 text-sm">{{ game.home_team.venue || 'Home' }}</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Score -->
                                    <div class="text-center px-8 flex-shrink-0">
                                        <div class="text-2xl font-bold text-gray-900">
                                            {{ formatScore(game) }}
                                        </div>
                                        <div v-if="game.status === 'FINISHED' && game.home_score !== null" class="text-gray-600 text-sm mt-1">
                                            Full Time
                                        </div>
                                    </div>
                                    
                                    <!-- Away Team -->
                                    <div class="flex items-center space-x-4 flex-1 justify-end">
                                        <div class="text-right">
                                            <h4 class="text-gray-900 font-semibold">{{ game.away_team.name }}</h4>
                                            <p class="text-gray-600 text-sm">Away</p>
                                        </div>
                                        <img :src="game.away_team.logo_url" 
                                             :alt="game.away_team.name"
                                             class="w-12 h-12 object-contain"
                                             @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(game.away_team.short_name)}&background=${encodeURIComponent(game.away_team.primary_color || '#22C55E')}&color=fff&size=48`">
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
