<script setup>
import { Head, Link } from '@inertiajs/vue3';
import TournamentLayout from '@/Layouts/TournamentLayout.vue';

const props = defineProps({
    team: Object,
    completedGames: Array,
    upcomingGames: Array,
    stats: Object,
    analytics: Object,
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
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
                <div class="flex items-center space-x-3 lg:space-x-4">
                    <img :src="team.logo_url" 
                         :alt="team.name"
                         class="w-12 h-12 lg:w-16 lg:h-16 rounded-full object-cover"
                         @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(team.short_name)}&background=random&color=fff&size=64`">
                    <div>
                        <h2 class="text-2xl lg:text-3xl font-bold text-white">
                            {{ team.name }}
                        </h2>
                        <p class="text-white/70 mt-1 text-sm lg:text-base">
                            {{ team.venue || 'Premier League' }}
                        </p>
                    </div>
                </div>
                <Link :href="route('schedule.index')" 
                      class="bg-white/20 hover:bg-white/30 text-white px-3 py-2 lg:px-4 lg:py-2 rounded-lg text-sm font-medium transition-all self-start sm:self-auto">
                    ← Back to Schedule
                </Link>
            </div>
        </template>

        <div class="space-y-6 lg:space-y-8">
            <!-- Team Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-3 lg:gap-4">
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-3 lg:p-4 border border-white/20 text-center">
                    <p class="text-white/60 text-xs lg:text-sm">Played</p>
                    <p class="text-white font-bold text-lg lg:text-xl">{{ stats.games_played }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-3 lg:p-4 border border-white/20 text-center">
                    <p class="text-white/60 text-xs lg:text-sm">Won</p>
                    <p class="text-green-400 font-bold text-lg lg:text-xl">{{ stats.wins }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-3 lg:p-4 border border-white/20 text-center">
                    <p class="text-white/60 text-xs lg:text-sm">Drawn</p>
                    <p class="text-yellow-400 font-bold text-lg lg:text-xl">{{ stats.draws }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-3 lg:p-4 border border-white/20 text-center">
                    <p class="text-white/60 text-xs lg:text-sm">Lost</p>
                    <p class="text-red-400 font-bold text-lg lg:text-xl">{{ stats.losses }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-3 lg:p-4 border border-white/20 text-center">
                    <p class="text-white/60 text-xs lg:text-sm">Points</p>
                    <p class="text-white font-bold text-lg lg:text-xl">{{ stats.points }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-3 lg:p-4 border border-white/20 text-center">
                    <p class="text-white/60 text-xs lg:text-sm">Goals For</p>
                    <p class="text-white font-bold text-lg lg:text-xl">{{ stats.goals_for }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-3 lg:p-4 border border-white/20 text-center">
                    <p class="text-white/60 text-xs lg:text-sm">Goal Diff</p>
                    <p class="text-white font-bold text-lg lg:text-xl" :class="stats.goal_difference >= 0 ? 'text-green-400' : 'text-red-400'">
                        {{ stats.goal_difference >= 0 ? '+' : '' }}{{ stats.goal_difference }}
                    </p>
                </div>
            </div>

            <!-- Recent Form & Advanced Stats -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6">
                <!-- Recent Form -->
                <div v-if="analytics?.recent_form?.length > 0" class="bg-white/10 backdrop-blur-md rounded-xl border border-white/20 overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-600/20 to-pink-600/20 p-4 lg:p-6 border-b border-white/20">
                        <h3 class="text-lg lg:text-xl font-semibold text-white">Recent Form</h3>
                        <p class="text-white/60 text-sm mt-1">Last {{ analytics.recent_form.length }} games</p>
                    </div>
                    <div class="p-4 lg:p-6">
                        <div class="flex items-center space-x-2 justify-center">
                            <div v-for="(form, index) in analytics.recent_form" :key="index"
                                 :class="form.class"
                                 class="w-8 h-8 lg:w-10 lg:h-10 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                {{ form.result }}
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-3 gap-4 text-center">
                            <div>
                                <p class="text-white/60 text-xs">Avg Goals</p>
                                <p class="text-white font-semibold text-sm lg:text-base">{{ analytics.average_goals_per_game }}</p>
                            </div>
                            <div>
                                <p class="text-white/60 text-xs">Clean Sheets</p>
                                <p class="text-white font-semibold text-sm lg:text-base">{{ analytics.clean_sheets }}</p>
                            </div>
                            <div>
                                <p class="text-white/60 text-xs">Avg Conceded</p>
                                <p class="text-white font-semibold text-sm lg:text-base">{{ analytics.average_goals_conceded }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Home vs Away Performance -->
                <div v-if="analytics" class="bg-white/10 backdrop-blur-md rounded-xl border border-white/20 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-600/20 to-blue-600/20 p-4 lg:p-6 border-b border-white/20">
                        <h3 class="text-lg lg:text-xl font-semibold text-white">Home vs Away</h3>
                        <p class="text-white/60 text-sm mt-1">Performance breakdown</p>
                    </div>
                    <div class="p-4 lg:p-6">
                        <div class="grid grid-cols-2 gap-4 lg:gap-6">
                            <!-- Home Stats -->
                            <div class="text-center">
                                <h4 class="text-white/70 font-medium mb-3 text-sm lg:text-base">🏠 Home</h4>
                                <div class="space-y-2">
                                    <div class="flex justify-between text-xs lg:text-sm">
                                        <span class="text-white/60">Played:</span>
                                        <span class="text-white">{{ analytics.home_stats.played }}</span>
                                    </div>
                                    <div class="flex justify-between text-xs lg:text-sm">
                                        <span class="text-white/60">Points:</span>
                                        <span class="text-white font-semibold">{{ analytics.home_stats.points }}</span>
                                    </div>
                                    <div class="flex justify-between text-xs lg:text-sm">
                                        <span class="text-white/60">W-D-L:</span>
                                        <span class="text-white">{{ analytics.home_stats.wins }}-{{ analytics.home_stats.draws }}-{{ analytics.home_stats.losses }}</span>
                                    </div>
                                    <div class="flex justify-between text-xs lg:text-sm">
                                        <span class="text-white/60">Goals:</span>
                                        <span class="text-white">{{ analytics.home_stats.goals_for }}:{{ analytics.home_stats.goals_against }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Away Stats -->
                            <div class="text-center">
                                <h4 class="text-white/70 font-medium mb-3 text-sm lg:text-base">✈️ Away</h4>
                                <div class="space-y-2">
                                    <div class="flex justify-between text-xs lg:text-sm">
                                        <span class="text-white/60">Played:</span>
                                        <span class="text-white">{{ analytics.away_stats.played }}</span>
                                    </div>
                                    <div class="flex justify-between text-xs lg:text-sm">
                                        <span class="text-white/60">Points:</span>
                                        <span class="text-white font-semibold">{{ analytics.away_stats.points }}</span>
                                    </div>
                                    <div class="flex justify-between text-xs lg:text-sm">
                                        <span class="text-white/60">W-D-L:</span>
                                        <span class="text-white">{{ analytics.away_stats.wins }}-{{ analytics.away_stats.draws }}-{{ analytics.away_stats.losses }}</span>
                                    </div>
                                    <div class="flex justify-between text-xs lg:text-sm">
                                        <span class="text-white/60">Goals:</span>
                                        <span class="text-white">{{ analytics.away_stats.goals_for }}:{{ analytics.away_stats.goals_against }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Goal Trends Chart -->
            <div v-if="analytics?.goal_trends?.length > 0" class="bg-white/10 backdrop-blur-md rounded-xl border border-white/20 overflow-hidden">
                <div class="bg-gradient-to-r from-yellow-600/20 to-orange-600/20 p-6 border-b border-white/20">
                    <h3 class="text-xl font-semibold text-white">Goal Trends</h3>
                    <p class="text-white/60 text-sm mt-1">Goals scored and conceded by gameweek</p>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <div v-for="trend in analytics.goal_trends.slice(-8)" :key="trend.gameweek"
                             class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <span class="text-white/60 text-sm font-medium w-20">
                                    GW{{ trend.gameweek }}
                                </span>
                                <div class="flex items-center space-x-2">
                                    <div class="flex items-center space-x-1">
                                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                        <span class="text-white text-sm">{{ trend.goals_scored }}</span>
                                    </div>
                                    <span class="text-white/40">-</span>
                                    <div class="flex items-center space-x-1">
                                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                        <span class="text-white text-sm">{{ trend.goals_conceded }}</span>
                                    </div>
                                </div>
                            </div>
                            <span class="text-white/60 text-xs">{{ formatDate(trend.date) }}</span>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-center space-x-6 text-sm">
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            <span class="text-white/70">Goals Scored</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                            <span class="text-white/70">Goals Conceded</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Performance -->
            <div v-if="analytics?.monthly_performance?.length > 0" class="bg-white/10 backdrop-blur-md rounded-xl border border-white/20 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-600/20 to-cyan-600/20 p-4 lg:p-6 border-b border-white/20">
                    <h3 class="text-lg lg:text-xl font-semibold text-white">Monthly Performance</h3>
                    <p class="text-white/60 text-sm mt-1">Performance breakdown by month</p>
                </div>
                <div class="p-4 lg:p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs lg:text-sm">
                            <thead>
                                <tr class="text-white/60 border-b border-white/10">
                                    <th class="text-left py-2 min-w-[4rem]">Month</th>
                                    <th class="text-center py-2 min-w-[3rem]">Played</th>
                                    <th class="text-center py-2 min-w-[4rem]">W-D-L</th>
                                    <th class="text-center py-2 min-w-[3rem]">Goals</th>
                                    <th class="text-center py-2 min-w-[3rem]">Points</th>
                                    <th class="text-center py-2 min-w-[3rem]">Win %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="month in analytics.monthly_performance" :key="month.month"
                                    class="text-white border-b border-white/5 hover:bg-white/5">
                                    <td class="py-3 font-medium">{{ month.month_name }}</td>
                                    <td class="text-center">{{ month.played }}</td>
                                    <td class="text-center">{{ month.wins }}-{{ month.draws }}-{{ month.losses }}</td>
                                    <td class="text-center">{{ month.goals_for }}:{{ month.goals_against }}</td>
                                    <td class="text-center font-semibold">{{ month.points }}</td>
                                    <td class="text-center" :class="month.win_percentage >= 50 ? 'text-green-400' : month.win_percentage >= 30 ? 'text-yellow-400' : 'text-red-400'">
                                        {{ month.win_percentage }}%
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Upcoming Fixtures -->
            <div v-if="upcomingGames.length > 0" class="bg-white/10 backdrop-blur-md rounded-xl border border-white/20 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600/20 to-purple-600/20 p-4 lg:p-6 border-b border-white/20">
                    <h3 class="text-lg lg:text-xl font-semibold text-white">Upcoming Fixtures</h3>
                </div>
                <div class="p-4 lg:p-6">
                    <div class="space-y-3 lg:space-y-4">
                        <div v-for="game in upcomingGames.slice(0, 5)" :key="game.id"
                             class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-3 lg:p-4 bg-white/5 rounded-lg hover:bg-white/10 transition-all cursor-pointer space-y-2 sm:space-y-0"
                             @click="$inertia.visit(route('schedule.match', game.id))">
                            <div class="flex items-center space-x-3 lg:space-x-4">
                                <span class="text-white/60 text-sm font-medium min-w-[4rem] lg:min-w-[6rem]">
                                    {{ game.game_week.name }}
                                </span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-white/60 text-sm">vs</span>
                                    <img :src="getOpponent(game, team).logo_url" 
                                         :alt="getOpponent(game, team).name"
                                         class="w-6 h-6 lg:w-8 lg:h-8 rounded-full object-cover"
                                         @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(getOpponent(game, team).short_name)}&background=random&color=fff&size=32`">
                                    <span class="text-white font-medium text-sm lg:text-base">{{ getOpponent(game, team).name }}</span>
                                </div>
                            </div>
                            <div class="text-left sm:text-right">
                                <span class="text-white/60 text-sm">{{ formatDateTime(game.kick_off_time) }}</span>
                                <p class="text-white/40 text-xs">{{ game.home_team_id === team.id ? 'Home' : 'Away' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fixture Difficulty Analysis -->
            <div v-if="analytics?.upcoming_fixtures_analysis?.length > 0" class="bg-white/10 backdrop-blur-md rounded-xl border border-white/20 overflow-hidden">
                <div class="bg-gradient-to-r from-orange-600/20 to-red-600/20 p-4 lg:p-6 border-b border-white/20">
                    <h3 class="text-lg lg:text-xl font-semibold text-white">Fixture Difficulty Analysis</h3>
                    <p class="text-white/60 text-sm mt-1">Next 5 fixtures with opponent form and difficulty rating</p>
                </div>
                <div class="p-4 lg:p-6">
                    <div class="space-y-4 lg:space-y-6">
                        <div v-for="fixture in analytics.upcoming_fixtures_analysis" :key="fixture.game.id"
                             class="p-4 bg-white/5 rounded-lg hover:bg-white/10 transition-all cursor-pointer"
                             @click="$inertia.visit(route('schedule.match', fixture.game.id))">
                            
                            <!-- Game Header -->
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 space-y-2 sm:space-y-0">
                                <div class="flex items-center space-x-3">
                                    <span class="text-white/60 text-sm font-medium min-w-[4rem]">{{ fixture.game.game_week.name }}</span>
                                    <div class="flex items-center space-x-2">
                                        <img :src="fixture.opponent.logo_url" 
                                             :alt="fixture.opponent.name"
                                             class="w-6 h-6 rounded-full object-cover"
                                             @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(fixture.opponent.short_name)}&background=random&color=fff&size=24`">
                                        <span class="text-white font-medium">{{ fixture.opponent.name }}</span>
                                        <span class="text-white/60 text-xs">({{ fixture.venue }})</span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-2 space-y-1 sm:space-y-0">
                                        <span :class="fixture.difficulty_class" 
                                              class="px-2 py-1 rounded-full text-white text-xs font-medium text-center">
                                            {{ fixture.difficulty }}
                                        </span>
                                        <span v-if="fixture.difficulty_rating" class="text-white/60 text-xs">
                                            ({{ fixture.difficulty_rating }}/100)
                                        </span>
                                    </div>
                                    <span class="text-white/60 text-sm hidden sm:block">{{ formatDateTime(fixture.game.kick_off_time) }}</span>
                                </div>
                            </div>
                            
                            <!-- Mobile date -->
                            <div class="sm:hidden mb-3">
                                <span class="text-white/60 text-sm">{{ formatDateTime(fixture.game.kick_off_time) }}</span>
                            </div>
                            
                            <!-- Stats Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                <!-- Opponent Form -->
                                <div>
                                    <p class="text-white/60 text-xs mb-2">Opponent Recent Form</p>
                                    <div class="flex space-x-1 mb-1">
                                        <div v-for="(formItem, index) in fixture.opponent_stats.recent_form.slice(-5)" :key="index"
                                             :class="formItem.class || (formItem.result === 'W' ? 'bg-green-500' : formItem.result === 'L' ? 'bg-red-500' : 'bg-yellow-500')"
                                             class="w-5 h-5 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                            {{ formItem.result || formItem }}
                                        </div>
                                    </div>
                                    <p v-if="fixture.opponent_stats.has_recent_data" class="text-white/60 text-xs">
                                        Record: {{ fixture.opponent_stats.wins }}-{{ fixture.opponent_stats.draws }}-{{ fixture.opponent_stats.losses }}
                                    </p>
                                    <p v-else class="text-white/60 text-xs italic">No recent data available</p>
                                </div>
                                
                                <!-- Difficulty Breakdown -->
                                <div>
                                    <p class="text-white/60 text-xs mb-2">Difficulty Factors</p>
                                    <div class="space-y-1 text-xs">
                                        <div v-if="fixture.analysis_factors" class="text-white/60">
                                            <span>Historical: {{ fixture.analysis_factors.historical_strength }}/100</span>
                                        </div>
                                        <div v-if="fixture.analysis_factors?.current_form > 0" class="text-white/60">
                                            <span>Current Form: {{ fixture.analysis_factors.current_form }}%</span>
                                        </div>
                                        <div v-if="fixture.analysis_factors?.h2h_factor" class="text-white/60">
                                            <span>vs You: {{ fixture.analysis_factors.h2h_factor }}/100</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Enhanced Head-to-Head Section -->
                            <div class="mt-4 pt-4 border-t border-white/10">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-2 sm:space-y-0">
                                    <div>
                                        <p class="text-white/60 text-xs mb-1">Head-to-Head Record ({{ team.name }} vs {{ fixture.opponent.name }})</p>
                                        <div v-if="fixture.head_to_head.wins + fixture.head_to_head.draws + fixture.head_to_head.losses > 0" 
                                             class="text-sm">
                                            <div class="flex items-center space-x-3">
                                                <span class="text-white font-medium">
                                                    W{{ fixture.head_to_head.wins }} D{{ fixture.head_to_head.draws }} L{{ fixture.head_to_head.losses }}
                                                </span>
                                                <span class="text-white/60 text-xs">
                                                    ({{ fixture.head_to_head.total_games || fixture.head_to_head.total_matches }} games)
                                                </span>
                                            </div>
                                            <div v-if="fixture.head_to_head.has_historical_data" class="text-white/60 text-xs italic mt-1">
                                                {{ fixture.head_to_head.historical_note || 'Includes historical data' }}
                                            </div>
                                        </div>
                                        <div v-else-if="fixture.head_to_head.has_historical_data" class="text-sm">
                                            <div class="text-white/60 italic text-xs">
                                                {{ fixture.head_to_head.historical_note }}
                                            </div>
                                        </div>
                                        <div v-else class="text-white/60 text-xs italic">
                                            No previous meetings this season
                                        </div>
                                    </div>
                                    
                                    <!-- Recent Match Scores -->
                                    <div v-if="(fixture.head_to_head.recent_meetings?.length || fixture.head_to_head.recent_record?.length) > 0" 
                                         class="flex flex-col space-y-1">
                                        <p class="text-white/60 text-xs">Recent Results:</p>
                                        <div class="flex space-x-2">
                                            <span v-for="(meeting, idx) in (fixture.head_to_head.recent_meetings || fixture.head_to_head.recent_record || []).slice(0, 3)" 
                                                  :key="idx"
                                                  class="text-white/80 text-xs bg-white/10 px-2 py-1 rounded">
                                                {{ meeting.score || `${meeting.result}` }}
                                                <span v-if="meeting.venue" class="text-white/60">({{ meeting.venue }})</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
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
