<script setup>
import { Head, Link } from '@inertiajs/vue3';
import TournamentLayout from '@/Layouts/TournamentLayout.vue';

const props = defineProps({
    team: Object,
    completedGames: Array,
    upcomingGames: Array,
    stats: Object,
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
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getGameResult = (game, team) => {
    if (game.status !== 'FINISHED') return null;
    
    const isHome = game.home_team_id === team.id;
    const teamScore = isHome ? (game.home_score || 0) : (game.away_score || 0);
    const opponentScore = isHome ? (game.away_score || 0) : (game.home_score || 0);
    
    if (teamScore > opponentScore) return { result: 'W', class: 'bg-green-500' };
    if (teamScore < opponentScore) return { result: 'L', class: 'bg-red-500' };
    return { result: 'D', class: 'bg-yellow-500' };
};

const getOpponent = (game, team) => {
    return game.home_team_id === team.id ? game.away_team : game.home_team;
};

const getScoreDisplay = (game, team) => {
    if (game.status !== 'FINISHED') return 'vs';
    
    const isHome = game.home_team_id === team.id;
    const teamScore = isHome ? (game.home_score || 0) : (game.away_score || 0);
    const opponentScore = isHome ? (game.away_score || 0) : (game.home_score || 0);
    
    return `${teamScore} - ${opponentScore}`;
};
</script>

<template>
    <Head :title="`${team.name} - Fixtures & Results`" />

    <TournamentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <img :src="team.logo_url" 
                         :alt="team.name"
                         class="w-16 h-16 rounded-full object-cover"
                         @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(team.short_name)}&background=random&color=fff&size=64`">
                    <div>
                        <h2 class="text-3xl font-bold text-white">
                            {{ team.name }}
                        </h2>
                        <p class="text-white/70 mt-1">
                            {{ team.venue || 'Premier League' }}
                        </p>
                    </div>
                </div>
                <Link :href="route('schedule.index')" 
                      class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all">
                    ← Back to Schedule
                </Link>
            </div>
        </template>

        <div class="space-y-8">
            <!-- Team Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20 text-center">
                    <p class="text-white/60 text-sm">Played</p>
                    <p class="text-white font-bold text-xl">{{ stats.games_played }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20 text-center">
                    <p class="text-white/60 text-sm">Won</p>
                    <p class="text-green-400 font-bold text-xl">{{ stats.wins }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20 text-center">
                    <p class="text-white/60 text-sm">Drawn</p>
                    <p class="text-yellow-400 font-bold text-xl">{{ stats.draws }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20 text-center">
                    <p class="text-white/60 text-sm">Lost</p>
                    <p class="text-red-400 font-bold text-xl">{{ stats.losses }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20 text-center">
                    <p class="text-white/60 text-sm">Points</p>
                    <p class="text-white font-bold text-xl">{{ stats.points }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20 text-center">
                    <p class="text-white/60 text-sm">Goals For</p>
                    <p class="text-white font-bold text-xl">{{ stats.goals_for }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20 text-center">
                    <p class="text-white/60 text-sm">Goal Diff</p>
                    <p class="text-white font-bold text-xl" :class="stats.goal_difference >= 0 ? 'text-green-400' : 'text-red-400'">
                        {{ stats.goal_difference >= 0 ? '+' : '' }}{{ stats.goal_difference }}
                    </p>
                </div>
            </div>

            <!-- Upcoming Fixtures -->
            <div v-if="upcomingGames.length > 0" class="bg-white/10 backdrop-blur-md rounded-xl border border-white/20 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600/20 to-purple-600/20 p-6 border-b border-white/20">
                    <h3 class="text-xl font-semibold text-white">Upcoming Fixtures</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div v-for="game in upcomingGames.slice(0, 5)" :key="game.id"
                             class="flex items-center justify-between p-4 bg-white/5 rounded-lg hover:bg-white/10 transition-all">
                            <div class="flex items-center space-x-4">
                                <span class="text-white/60 text-sm font-medium w-24">
                                    {{ game.game_week.name }}
                                </span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-white/60 text-sm">vs</span>
                                    <img :src="getOpponent(game, team).logo_url" 
                                         :alt="getOpponent(game, team).name"
                                         class="w-8 h-8 rounded-full object-cover"
                                         @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(getOpponent(game, team).short_name)}&background=random&color=fff&size=32`">
                                    <span class="text-white font-medium">{{ getOpponent(game, team).name }}</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-white/60 text-sm">{{ formatDateTime(game.kick_off_time) }}</span>
                                <p class="text-white/40 text-xs">{{ game.home_team_id === team.id ? 'Home' : 'Away' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Results -->
            <div v-if="completedGames.length > 0" class="bg-white/10 backdrop-blur-md rounded-xl border border-white/20 overflow-hidden">
                <div class="bg-gradient-to-r from-green-600/20 to-blue-600/20 p-6 border-b border-white/20">
                    <h3 class="text-xl font-semibold text-white">Recent Results</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div v-for="game in completedGames.slice(-10).reverse()" :key="game.id"
                             class="flex items-center justify-between p-4 bg-white/5 rounded-lg hover:bg-white/10 transition-all">
                            <div class="flex items-center space-x-4">
                                <span class="text-white/60 text-sm font-medium w-24">
                                    {{ game.game_week.name }}
                                </span>
                                <div class="flex items-center space-x-2">
                                    <div v-if="getGameResult(game, team)" 
                                         :class="getGameResult(game, team).class"
                                         class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                        {{ getGameResult(game, team).result }}
                                    </div>
                                    <img :src="getOpponent(game, team).logo_url" 
                                         :alt="getOpponent(game, team).name"
                                         class="w-8 h-8 rounded-full object-cover"
                                         @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(getOpponent(game, team).short_name)}&background=random&color=fff&size=32`">
                                    <span class="text-white font-medium">{{ getOpponent(game, team).name }}</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-white font-bold">{{ getScoreDisplay(game, team) }}</span>
                                <p class="text-white/60 text-sm">{{ formatDate(game.kick_off_time) }}</p>
                                <p class="text-white/40 text-xs">{{ game.home_team_id === team.id ? 'Home' : 'Away' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TournamentLayout>
</template>
