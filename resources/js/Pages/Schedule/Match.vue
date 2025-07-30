<script setup>
import { Head, Link } from '@inertiajs/vue3';
import TournamentLayout from '@/Layouts/TournamentLayout.vue';

const props = defineProps({
    game: Object,
    homeTeamStats: Object,
    awayTeamStats: Object,
    headToHead: Object,
    homeTeamForm: Array,
    awayTeamForm: Array,
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
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getMatchStatus = () => {
    if (props.game.status === 'FINISHED') {
        return {
            text: 'Full Time',
            class: 'bg-gray-500'
        };
    } else if (props.game.status === 'LIVE') {
        return {
            text: 'Live',
            class: 'bg-red-500 animate-pulse'
        };
    } else {
        return {
            text: formatDateTime(props.game.kick_off_time),
            class: 'bg-blue-500'
        };
    }
};
</script>

<template>
    <Head :title="`${game.home_team.name} vs ${game.away_team.name} - ${game.game_week.name}`" />

    <TournamentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="text-center">
                        <h2 class="text-3xl font-bold text-white mb-2">
                            {{ game.game_week.name }}
                        </h2>
                        <p class="text-white/70">
                            {{ getMatchStatus().text }}
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
            <!-- Match Overview -->
            <div class="bg-white/10 backdrop-blur-md rounded-xl border border-white/20 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600/20 to-purple-600/20 p-8 border-b border-white/20">
                    <div class="flex items-center justify-between">
                        <!-- Home Team -->
                        <Link :href="route('schedule.team', game.home_team.id)" 
                              class="flex flex-col items-center space-y-3 hover:opacity-80 transition-all">
                            <img :src="game.home_team.logo_url" 
                                 :alt="game.home_team.name"
                                 class="w-20 h-20 rounded-full object-cover"
                                 @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(game.home_team.short_name)}&background=random&color=fff&size=80`">
                            <div class="text-center">
                                <h3 class="text-xl font-bold text-white">{{ game.home_team.name }}</h3>
                                <p class="text-white/60 text-sm">Home</p>
                            </div>
                        </Link>

                        <!-- Match Info -->
                        <div class="text-center space-y-4">
                            <div v-if="game.status === 'FINISHED'" class="text-6xl font-bold text-white">
                                {{ game.home_score }} - {{ game.away_score }}
                            </div>
                            <div v-else class="text-4xl font-bold text-white/70">
                                VS
                            </div>
                            <div :class="getMatchStatus().class" 
                                 class="px-4 py-2 rounded-full text-white text-sm font-medium inline-block">
                                {{ getMatchStatus().text }}
                            </div>
                        </div>

                        <!-- Away Team -->
                        <Link :href="route('schedule.team', game.away_team.id)" 
                              class="flex flex-col items-center space-y-3 hover:opacity-80 transition-all">
                            <img :src="game.away_team.logo_url" 
                                 :alt="game.away_team.name"
                                 class="w-20 h-20 rounded-full object-cover"
                                 @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(game.away_team.short_name)}&background=random&color=fff&size=80`">
                            <div class="text-center">
                                <h3 class="text-xl font-bold text-white">{{ game.away_team.name }}</h3>
                                <p class="text-white/60 text-sm">Away</p>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Team Comparison -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Home Team Stats -->
                <div class="bg-white/10 backdrop-blur-md rounded-xl border border-white/20 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-600/20 to-blue-600/20 p-6 border-b border-white/20">
                        <div class="flex items-center space-x-3">
                            <img :src="game.home_team.logo_url" 
                                 :alt="game.home_team.name"
                                 class="w-8 h-8 rounded-full object-cover">
                            <h3 class="text-xl font-semibold text-white">{{ game.home_team.name }}</h3>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <!-- Season Stats -->
                            <div>
                                <h4 class="text-white/70 font-medium mb-3">Season Record</h4>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-white/60">Played:</span>
                                        <span class="text-white">{{ homeTeamStats.played }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-white/60">Points:</span>
                                        <span class="text-white">{{ homeTeamStats.points }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-white/60">W-D-L:</span>
                                        <span class="text-white">{{ homeTeamStats.wins }}-{{ homeTeamStats.draws }}-{{ homeTeamStats.losses }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-white/60">Goals:</span>
                                        <span class="text-white">{{ homeTeamStats.goals_for }}:{{ homeTeamStats.goals_against }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Home/Away Split -->
                            <div>
                                <h4 class="text-white/70 font-medium mb-3">Home Record</h4>
                                <div class="text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-white/60">Home:</span>
                                        <span class="text-white">{{ homeTeamStats.home_record.wins }}-{{ homeTeamStats.home_record.draws }}-{{ homeTeamStats.home_record.losses }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Form -->
                            <div>
                                <h4 class="text-white/70 font-medium mb-3">Recent Form</h4>
                                <div class="flex space-x-1">
                                    <div v-for="(form, index) in homeTeamForm" :key="index"
                                         :class="form.class"
                                         class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                        {{ form.result }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Away Team Stats -->
                <div class="bg-white/10 backdrop-blur-md rounded-xl border border-white/20 overflow-hidden">
                    <div class="bg-gradient-to-r from-red-600/20 to-orange-600/20 p-6 border-b border-white/20">
                        <div class="flex items-center space-x-3">
                            <img :src="game.away_team.logo_url" 
                                 :alt="game.away_team.name"
                                 class="w-8 h-8 rounded-full object-cover">
                            <h3 class="text-xl font-semibold text-white">{{ game.away_team.name }}</h3>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <!-- Season Stats -->
                            <div>
                                <h4 class="text-white/70 font-medium mb-3">Season Record</h4>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-white/60">Played:</span>
                                        <span class="text-white">{{ awayTeamStats.played }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-white/60">Points:</span>
                                        <span class="text-white">{{ awayTeamStats.points }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-white/60">W-D-L:</span>
                                        <span class="text-white">{{ awayTeamStats.wins }}-{{ awayTeamStats.draws }}-{{ awayTeamStats.losses }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-white/60">Goals:</span>
                                        <span class="text-white">{{ awayTeamStats.goals_for }}:{{ awayTeamStats.goals_against }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Home/Away Split -->
                            <div>
                                <h4 class="text-white/70 font-medium mb-3">Away Record</h4>
                                <div class="text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-white/60">Away:</span>
                                        <span class="text-white">{{ awayTeamStats.away_record.wins }}-{{ awayTeamStats.away_record.draws }}-{{ awayTeamStats.away_record.losses }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Form -->
                            <div>
                                <h4 class="text-white/70 font-medium mb-3">Recent Form</h4>
                                <div class="flex space-x-1">
                                    <div v-for="(form, index) in awayTeamForm" :key="index"
                                         :class="form.class"
                                         class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                        {{ form.result }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Head-to-Head Record -->
            <div v-if="headToHead.recent_meetings.length > 0" class="bg-white/10 backdrop-blur-md rounded-xl border border-white/20 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-600/20 to-pink-600/20 p-6 border-b border-white/20">
                    <h3 class="text-xl font-semibold text-white">Head-to-Head Record</h3>
                    <p class="text-white/60 text-sm mt-1">Last 5 meetings between these teams</p>
                </div>
                <div class="p-6">
                    <div class="mb-6 text-center">
                        <div class="inline-flex items-center space-x-6">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-green-400">{{ headToHead.wins }}</p>
                                <p class="text-white/60 text-sm">{{ game.home_team.short_name }} Wins</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-yellow-400">{{ headToHead.draws }}</p>
                                <p class="text-white/60 text-sm">Draws</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-red-400">{{ headToHead.losses }}</p>
                                <p class="text-white/60 text-sm">{{ game.away_team.short_name }} Wins</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <h4 class="text-white/70 font-medium">Recent Meetings</h4>
                        <div v-for="meeting in headToHead.recent_meetings" :key="meeting.date"
                             class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                            <span class="text-white/60 text-sm">{{ formatDate(meeting.date) }}</span>
                            <span class="text-white font-medium">{{ meeting.score }}</span>
                            <span class="text-white/60 text-sm">{{ meeting.venue === 'H' ? 'Home' : 'Away' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TournamentLayout>
</template>
