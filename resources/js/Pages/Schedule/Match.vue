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
    const isMobile = window.innerWidth < 640;
    
    if (isMobile) {
        return date.toLocaleDateString('en-GB', {
            weekday: 'short',
            day: 'numeric',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit'
        });
    } else {
        return date.toLocaleDateString('en-GB', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
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

const getFormResult = (form) => {
    if (typeof form === 'object' && form.result) {
        return form.result;
    }
    return form;
};

const getFormClass = (form) => {
    if (typeof form === 'object' && form.class) {
        return form.class;
    }
    
    const result = typeof form === 'object' ? form.result : form;
    switch (result) {
        case 'W':
            return 'bg-green-500';
        case 'L':
            return 'bg-red-500';
        case 'D':
            return 'bg-yellow-500';
        default:
            return 'bg-gray-500';
    }
};
</script>

<template>
    <Head :title="`${game.home_team.name} vs ${game.away_team.name} - ${game.game_week.name} - PL Tournament`" />

    <TournamentLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
                <div class="flex items-center space-x-3 lg:space-x-4">
                    <div class="text-center">
                        <h2 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-2">
                            {{ game.game_week.name }}
                        </h2>
                        <p class="text-gray-600 text-sm lg:text-base">
                            {{ getMatchStatus().text }}
                        </p>
                    </div>
                </div>
                <Link :href="route('schedule.index')" 
                      class="bg-white border border-green-200 text-gray-700 px-3 py-2 lg:px-4 lg:py-2 rounded-lg text-sm font-medium transition-all hover:bg-green-50 self-start sm:self-auto">
                    ← Back to Schedule
                </Link>
            </div>
        </template>

        <div class="space-y-6 lg:space-y-8">
            <!-- Match Overview -->
            <div class="bg-white rounded-xl border border-green-200 shadow-lg overflow-hidden">
                <div class="bg-green-50 p-4 lg:p-8 border-b border-green-200">
                    <div class="grid grid-cols-3 gap-4 lg:gap-8">
                        <!-- Home Team -->
                        <div class="flex flex-col items-center justify-center">
                            <Link :href="route('schedule.team', game.home_team.id)" 
                                  class="flex flex-col items-center space-y-3 hover:opacity-80 transition-all">
                                <img :src="game.home_team.logo_url" 
                                     :alt="game.home_team.name"
                                     class="w-16 h-16 lg:w-20 lg:h-20 object-contain"
                                     @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(game.home_team.short_name)}&background=${encodeURIComponent(game.home_team.primary_color || '#22C55E')}&color=fff&size=80`">
                                <div class="text-center">
                                    <h3 class="text-lg lg:text-xl font-bold text-gray-900">
                                        <span class="sm:hidden tracking-widest">{{ game.home_team.short_name }}</span>
                                        <span class="hidden sm:inline">{{ game.home_team.name }}</span>
                                    </h3>
                                    <p class="text-gray-600 text-sm">Home</p>
                                </div>
                            </Link>
                        </div>

                        <!-- Match Info -->
                        <div class="flex flex-col items-center justify-center space-y-3 lg:space-y-4">
                            <div v-if="game.status === 'FINISHED'" class="text-4xl lg:text-6xl font-bold text-gray-900">
                                {{ game.home_score }} - {{ game.away_score }}
                            </div>
                            <div v-else class="text-center">
                                <div class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-700">
                                    VS
                                </div>
                            </div>
                            <div :class="getMatchStatus().class" 
                                 class="px-2.5 py-1.5 sm:px-4 sm:py-2 rounded-full text-white text-xs sm:text-sm font-medium inline-block text-center">
                                {{ getMatchStatus().text }}
                            </div>
                        </div>

                        <!-- Away Team -->
                        <div class="flex flex-col items-center justify-center">
                            <Link :href="route('schedule.team', game.away_team.id)" 
                                  class="flex flex-col items-center space-y-3 hover:opacity-80 transition-all">
                                <img :src="game.away_team.logo_url" 
                                     :alt="game.away_team.name"
                                     class="w-16 h-16 lg:w-20 lg:h-20 object-contain"
                                     @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(game.away_team.short_name)}&background=${encodeURIComponent(game.away_team.primary_color || '#22C55E')}&color=fff&size=80`">
                                <div class="text-center">
                                    <h3 class="text-lg lg:text-xl font-bold text-gray-900">
                                        <span class="sm:hidden tracking-widest">{{ game.away_team.short_name }}</span>
                                        <span class="hidden sm:inline">{{ game.away_team.name }}</span>
                                    </h3>
                                    <p class="text-gray-600 text-sm">Away</p>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team Comparison -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6">
                <!-- Home Team Stats -->
                <div class="bg-white rounded-xl border border-green-200 shadow-lg overflow-hidden">
                    <div class="bg-green-50 p-4 lg:p-6 border-b border-green-200">
                        <div class="flex items-center space-x-3">
                            <img :src="game.home_team.logo_url" 
                                 :alt="game.home_team.name"
                                 class="w-6 h-6 lg:w-8 lg:h-8 object-contain">
                            <h3 class="text-lg lg:text-xl font-semibold text-gray-900">{{ game.home_team.name }}</h3>
                        </div>
                    </div>
                    <div class="p-4 lg:p-6">
                        <div class="space-y-4">
                            <!-- Season Stats -->
                            <div>
                                <h4 class="text-gray-700 font-medium mb-3 text-sm lg:text-base">Season Record</h4>
                                <div class="grid grid-cols-2 gap-3 lg:gap-4 text-xs lg:text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Played:</span>
                                        <span class="text-gray-900">{{ homeTeamStats.played || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Points:</span>
                                        <span class="text-gray-900">{{ homeTeamStats.points || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">W-D-L:</span>
                                        <span class="text-gray-900">{{ homeTeamStats.wins || 0 }}-{{ homeTeamStats.draws || 0 }}-{{ homeTeamStats.losses || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Goals:</span>
                                        <span class="text-gray-900">{{ homeTeamStats.goals_for || 0 }}:{{ homeTeamStats.goals_against || 0 }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Home/Away Split -->
                            <div v-if="homeTeamStats.home_record">
                                <h4 class="text-gray-700 font-medium mb-3 text-sm lg:text-base">Home Record</h4>
                                <div class="text-xs lg:text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Home:</span>
                                        <span class="text-gray-900">{{ homeTeamStats.home_record.wins || 0 }}-{{ homeTeamStats.home_record.draws || 0 }}-{{ homeTeamStats.home_record.losses || 0 }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Form -->
                            <div v-if="homeTeamForm && homeTeamForm.length > 0">
                                <h4 class="text-gray-700 font-medium mb-3 text-sm lg:text-base">Recent Form</h4>
                                <div class="flex space-x-1">
                                    <div v-for="(form, index) in homeTeamForm" :key="index"
                                         :class="form.class"
                                         class="w-5 h-5 lg:w-6 lg:h-6 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                        {{ form.result }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Away Team Stats -->
                <div class="bg-white rounded-xl border border-green-200 shadow-lg overflow-hidden">
                    <div class="bg-green-50 p-4 lg:p-6 border-b border-green-200">
                        <div class="flex items-center space-x-3">
                            <img :src="game.away_team.logo_url" 
                                 :alt="game.away_team.name"
                                 class="w-6 h-6 lg:w-8 lg:h-8 object-contain">
                            <h3 class="text-lg lg:text-xl font-semibold text-gray-900">{{ game.away_team.name }}</h3>
                        </div>
                    </div>
                    <div class="p-4 lg:p-6">
                        <div class="space-y-4">
                            <!-- Season Stats -->
                            <div>
                                <h4 class="text-gray-700 font-medium mb-3 text-sm lg:text-base">Season Record</h4>
                                <div class="grid grid-cols-2 gap-3 lg:gap-4 text-xs lg:text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Played:</span>
                                        <span class="text-gray-900">{{ awayTeamStats.played || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Points:</span>
                                        <span class="text-gray-900">{{ awayTeamStats.points || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">W-D-L:</span>
                                        <span class="text-gray-900">{{ awayTeamStats.wins || 0 }}-{{ awayTeamStats.draws || 0 }}-{{ awayTeamStats.losses || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Goals:</span>
                                        <span class="text-gray-900">{{ awayTeamStats.goals_for || 0 }}:{{ awayTeamStats.goals_against || 0 }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Home/Away Split -->
                            <div v-if="awayTeamStats.away_record">
                                <h4 class="text-gray-700 font-medium mb-3 text-sm lg:text-base">Away Record</h4>
                                <div class="text-xs lg:text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Away:</span>
                                        <span class="text-gray-900">{{ awayTeamStats.away_record.wins || 0 }}-{{ awayTeamStats.away_record.draws || 0 }}-{{ awayTeamStats.away_record.losses || 0 }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Form -->
                            <div v-if="awayTeamForm && awayTeamForm.length > 0">
                                <h4 class="text-gray-700 font-medium mb-3 text-sm lg:text-base">Recent Form</h4>
                                <div class="flex space-x-1">
                                    <div v-for="(form, index) in awayTeamForm" :key="index"
                                         :class="form.class"
                                         class="w-5 h-5 lg:w-6 lg:h-6 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                        {{ form.result }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Head-to-Head Record -->
            <div v-if="headToHead && (headToHead.total_games > 0 || (headToHead.recent_meetings && headToHead.recent_meetings.length > 0))" 
                 class="bg-white rounded-xl border border-green-200 shadow-lg overflow-hidden">
                <div class="bg-green-50 p-4 lg:p-6 border-b border-green-200">
                    <h3 class="text-lg lg:text-xl font-semibold text-gray-900">Head-to-Head Record</h3>
                    <p class="text-gray-600 text-sm mt-1">
                        {{ headToHead.data_source === 'historical' ? 'Historical record between these teams' : 'Recent meetings this season' }}
                    </p>
                </div>
                <div class="p-4 lg:p-6">
                    <!-- Overall Record -->
                    <div v-if="headToHead.total_games > 0" class="mb-6">
                        <div class="grid grid-cols-3 gap-6 justify-items-center sm:flex sm:flex-row sm:items-center sm:justify-center sm:space-x-12 sm:space-y-0 space-y-0">
                            <div class="text-center">
                                <p class="text-3xl lg:text-4xl font-bold text-green-600 mb-1">{{ headToHead.wins || 0 }}</p>
                                <p class="text-gray-800 text-sm lg:text-base font-medium">{{ game.home_team.short_name }}</p>
                                <p class="text-gray-600 text-xs">Wins</p>
                            </div>
                            <div class="text-center">
                                <p class="text-3xl lg:text-4xl font-bold text-yellow-600 mb-1">{{ headToHead.draws || 0 }}</p>
                                <p class="text-gray-800 text-sm lg:text-base font-medium">Draws</p>
                                <p class="text-gray-600 text-xs">&nbsp;</p>
                            </div>
                            <div class="text-center">
                                <p class="text-3xl lg:text-4xl font-bold text-red-600 mb-1">{{ headToHead.losses || 0 }}</p>
                                <p class="text-gray-800 text-sm lg:text-base font-medium">{{ game.away_team.short_name }}</p>
                                <p class="text-gray-600 text-xs">Wins</p>
                            </div>
                        </div>
                        <div class="text-center mt-4">
                            <p class="text-gray-700 text-sm">Total: {{ headToHead.total_games }} games played</p>
                            <div v-if="headToHead.data_source === 'historical'" class="mt-2">
                                <span class="inline-block bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs">
                                    Including historical data
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Meetings -->
                    <div v-if="headToHead.recent_meetings && headToHead.recent_meetings.length > 0" class="space-y-3">
                        <h4 class="text-gray-700 font-medium text-sm lg:text-base">Recent Meetings</h4>
                        <div class="space-y-2">
                            <div v-for="meeting in headToHead.recent_meetings" :key="meeting.date"
                                 class="grid grid-cols-3 items-center gap-4 p-3 bg-gray-50 rounded-lg">
                                <span class="text-gray-600 text-sm">{{ formatDate(meeting.date) }}</span>
                                <div class="flex items-center justify-center space-x-2 flex-nowrap">
                                    <span class="text-gray-900 font-medium text-sm text-right whitespace-nowrap">{{ meeting.venue === 'H' ? game.home_team.short_name : game.away_team.short_name }}</span>
                                    <span class="text-gray-900 font-bold text-lg text-center whitespace-nowrap">{{ meeting.score }}</span>
                                    <span class="text-gray-900 font-medium text-sm text-left whitespace-nowrap">{{ meeting.venue === 'H' ? game.away_team.short_name : game.home_team.short_name }}</span>
                                </div>
                                <div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Record (if no recent_meetings but has recent_record) -->
                    <div v-else-if="headToHead.recent_record && headToHead.recent_record.length > 0" class="space-y-3">
                        <h4 class="text-gray-700 font-medium text-sm lg:text-base">Recent Record</h4>
                        <div class="space-y-2">
                            <div v-for="record in headToHead.recent_record" :key="record.date"
                                 class="grid grid-cols-3 items-center gap-4 p-3 bg-gray-50 rounded-lg">
                                <span class="text-gray-600 text-sm">{{ formatDate(record.date) }}</span>
                                <div class="flex items-center justify-center space-x-2 flex-nowrap">
                                    <span class="text-gray-900 font-medium text-sm text-right whitespace-nowrap">{{ record.venue === 'H' ? game.home_team.short_name : game.away_team.short_name }}</span>
                                    <span class="text-gray-900 font-bold text-lg text-center whitespace-nowrap">{{ record.score }}</span>
                                    <span class="text-gray-900 font-medium text-sm text-left whitespace-nowrap">{{ record.venue === 'H' ? game.away_team.short_name : game.home_team.short_name }}</span>
                                </div>
                                <div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Historical Note -->
                    <div v-if="headToHead.historical_note" class="mt-4 p-3 bg-blue-50 rounded-lg">
                        <p class="text-blue-700 text-xs lg:text-sm italic">{{ headToHead.historical_note }}</p>
                    </div>
                </div>
            </div>

            <!-- No Head-to-Head Data -->
            <div v-else class="bg-white rounded-xl border border-green-200 shadow-lg overflow-hidden">
                <div class="bg-green-50 p-4 lg:p-6 border-b border-green-200">
                    <h3 class="text-lg lg:text-xl font-semibold text-gray-900">Head-to-Head Record</h3>
                </div>
                <div class="p-4 lg:p-6">
                    <div class="text-center text-gray-600">
                        <p class="text-sm lg:text-base">No previous meetings found between these teams this season.</p>
                    </div>
                </div>
            </div>
        </div>
    </TournamentLayout>
</template>
