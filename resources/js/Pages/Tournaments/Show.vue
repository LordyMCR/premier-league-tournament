<script setup>
import { Head, Link } from '@inertiajs/vue3';
import TournamentLayout from '@/Layouts/TournamentLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    tournament: Object,
    isParticipant: Boolean,
    leaderboard: Array,
    currentGameweek: Object,
    selectionGameweek: Object,
    userPicks: Array,
    currentPick: Object,
});

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};

const copyJoinCode = () => {
    navigator.clipboard.writeText(props.tournament.join_code).then(() => {
        // TODO: Show success message
        console.log('Join code copied to clipboard');
    });
};
</script>

<template>
    <Head :title="tournament.name" />

    <TournamentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-white">
                        {{ tournament.name }}
                    </h2>
                    <p class="text-white/70 mt-2">
                        {{ tournament.description || 'Premier League prediction tournament' }}
                    </p>
                </div>
                <div class="text-right">
                    <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2 border border-white/30">
                        <p class="text-white/60 text-sm">Join Code</p>
                        <p class="text-white font-mono text-lg">{{ tournament.join_code }}</p>
                    </div>
                </div>
            </div>
        </template>

        <div class="max-w-6xl mx-auto space-y-8">
            <!-- Tournament Info Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Status -->
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white/60 text-sm">Status</p>
                            <p class="text-white font-semibold capitalize">{{ tournament.status }}</p>
                        </div>
                    </div>
                </div>

                <!-- Participants -->
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white/60 text-sm">Participants</p>
                            <p class="text-white font-semibold">{{ tournament.participants_count || tournament.participants?.length || 0 }}/{{ tournament.max_participants }}</p>
                        </div>
                    </div>
                </div>

                <!-- Current Gameweek -->
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white/60 text-sm">Current Week</p>
                            <p class="text-white font-semibold">Gameweek {{ tournament.current_game_week }}</p>
                        </div>
                    </div>
                </div>

                <!-- Tournament Length -->
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-orange-500 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white/60 text-sm">Duration</p>
                            <p class="text-white font-semibold">{{ tournament.total_game_weeks }} Gameweeks</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success Message -->
            <div v-if="$page.props.flash?.success" class="bg-green-500/20 border border-green-500/30 rounded-xl p-4">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <p class="text-white">{{ $page.props.flash.success }}</p>
                </div>
            </div>

            <!-- Join Tournament Button (if not participant) -->
            <div v-if="!isParticipant" class="bg-white/10 backdrop-blur-md rounded-xl p-8 border border-white/20 text-center">
                <h3 class="text-xl font-semibold text-white mb-4">Join This Tournament</h3>
                <p class="text-white/70 mb-6">Start making your weekly predictions and compete with other participants!</p>
                <button 
                    @click="joinTournament"
                    class="inline-flex items-center px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg transition-colors"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Join Tournament
                </button>
            </div>

            <!-- Tournament Details -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Current Gameweek Info -->
                    <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                        <h3 class="text-lg font-semibold text-white mb-4">Tournament Schedule</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center py-2 border-b border-white/10">
                                <span class="text-white/70">Start Gameweek:</span>
                                <span class="text-white font-medium">Gameweek {{ tournament.start_game_week }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-white/10">
                                <span class="text-white/70">End Gameweek:</span>
                                <span class="text-white font-medium">Gameweek {{ tournament.start_game_week + tournament.total_game_weeks - 1 }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-white/70">Total Duration:</span>
                                <span class="text-white font-medium">{{ tournament.total_game_weeks }} gameweeks</span>
                            </div>
                        </div>
                    </div>

                    <!-- Current Gameweek & Picks -->
                    <div v-if="isParticipant && tournament.status === 'active'" class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                        <h3 class="text-lg font-semibold text-white mb-4">Team Selection</h3>
                        
                        <div v-if="selectionGameweek" class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-white font-medium">Selection for {{ selectionGameweek.name }}</h4>
                                    <p class="text-white/60 text-sm">
                                        Deadline: {{ formatDateTime(selectionGameweek.selection_deadline) }}
                                    </p>
                                    <p class="text-white/60 text-xs">
                                        Games start: {{ formatDateTime(selectionGameweek.gameweek_start_time) }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span v-if="isSelectionOpen(selectionGameweek)" class="text-xs px-2 py-1 rounded-full bg-green-500 text-white">
                                        Selection Open
                                    </span>
                                    <span v-else class="text-xs px-2 py-1 rounded-full bg-red-500 text-white">
                                        Selection Closed
                                    </span>
                                </div>
                            </div>

                            <!-- Current Pick Status -->
                            <div v-if="currentPick" class="bg-green-500/20 border border-green-500/30 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center"
                                             :style="{ backgroundColor: currentPick.team.primary_color }">
                                            <span class="font-bold text-white text-xs">
                                                {{ currentPick.team.short_name }}
                                            </span>
                                        </div>
                                        <div>
                                            <p class="text-white font-medium">{{ currentPick.team.name }}</p>
                                            <p class="text-white/60 text-xs">Your pick for {{ selectionGameweek.name }}</p>
                                        </div>
                                    </div>
                                    <div v-if="currentPick.points_earned !== null" class="text-right">
                                        <span class="text-white font-bold">{{ currentPick.points_earned }} pts</span>
                                        <p class="text-white/60 text-xs capitalize">{{ currentPick.result }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Make Pick Button -->
                            <div v-else-if="isSelectionOpen(selectionGameweek)" class="text-center">
                                <Link :href="route('tournaments.gameweeks.picks.create', [tournament.id, selectionGameweek.id])"
                                      class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Make Your Pick for {{ selectionGameweek.name }}
                                </Link>
                            </div>

                            <!-- Selection Closed -->
                            <div v-else class="text-center py-4">
                                <p class="text-white/60">Selection window for {{ selectionGameweek.name }} has closed</p>
                                <p class="text-white/40 text-sm">
                                    Deadline was: {{ formatDateTime(selectionGameweek.selection_deadline) }}
                                </p>
                            </div>
                        </div>

                        <div v-else class="text-center py-4">
                            <p class="text-white/60">No selection window currently open</p>
                        </div>
                    </div>

                    <!-- Recent Picks -->
                    <div v-if="isParticipant && userPicks && userPicks.length > 0" class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                        <h3 class="text-lg font-semibold text-white mb-4">Your Recent Picks</h3>
                        <div class="space-y-3">
                            <div v-for="pick in userPicks.slice(0, 5)" :key="pick.id"
                                 class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center"
                                         :style="{ backgroundColor: pick.team.primary_color }">
                                        <span class="font-bold text-white text-xs">
                                            {{ pick.team.short_name }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-white font-medium">{{ pick.team.name }}</p>
                                        <p class="text-white/60 text-xs">{{ pick.game_week.name }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div v-if="pick.points_earned !== null" class="flex items-center space-x-2">
                                        <span class="text-white font-bold">{{ pick.points_earned }} pts</span>
                                        <span class="text-xs px-2 py-1 rounded-full capitalize"
                                              :class="{
                                                  'bg-green-500 text-white': pick.result === 'win',
                                                  'bg-yellow-500 text-white': pick.result === 'draw',
                                                  'bg-red-500 text-white': pick.result === 'loss'
                                              }">
                                            {{ pick.result }}
                                        </span>
                                    </div>
                                    <div v-else class="text-white/60 text-xs">Pending</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Leaderboard -->
                    <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                        <h3 class="text-lg font-semibold text-white mb-4">Leaderboard</h3>
                        <div v-if="leaderboard && leaderboard.length > 0" class="space-y-3">
                            <div 
                                v-for="(participant, index) in leaderboard" 
                                :key="participant.id"
                                class="flex items-center justify-between p-3 bg-white/5 rounded-lg"
                            >
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                        {{ index + 1 }}
                                    </div>
                                    <span class="text-white font-medium">{{ participant.user?.name || 'Unknown' }}</span>
                                </div>
                                <span class="text-emerald-400 font-semibold">{{ participant.total_points }} pts</span>
                            </div>
                        </div>
                        <div v-else class="text-center py-8">
                            <p class="text-white/60">No participants yet</p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Tournament Creator -->
                    <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                        <h3 class="text-lg font-semibold text-white mb-4">Tournament Creator</h3>
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold">
                                {{ tournament.creator?.name?.charAt(0)?.toUpperCase() || '?' }}
                            </div>
                            <div>
                                <p class="text-white font-medium">{{ tournament.creator?.name || 'Unknown' }}</p>
                                <p class="text-white/60 text-sm">Tournament Host</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                        <h3 class="text-lg font-semibold text-white mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            <a 
                                :href="route('tournaments.index')"
                                class="flex items-center justify-center w-full px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition-colors"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Back to Tournaments
                            </a>
                            <button 
                                @click="copyJoinCode"
                                class="flex items-center justify-center w-full px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                Copy Join Code
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TournamentLayout>
</template>

<script>
export default {
    methods: {
        joinTournament() {
            // TODO: Implement join tournament functionality
            console.log('Join tournament clicked');
        },
        copyJoinCode() {
            navigator.clipboard.writeText(this.tournament.join_code).then(() => {
                // TODO: Show success message
                console.log('Join code copied to clipboard');
            });
        },
        // Method to check if selection is open for a gameweek
        isSelectionOpen(gameweek) {
            if (!gameweek || !gameweek.selection_opens || !gameweek.selection_deadline) {
                return false;
            }
            
            const now = new Date();
            const opensAt = new Date(gameweek.selection_opens);
            const deadline = new Date(gameweek.selection_deadline);
            
            return now >= opensAt && now <= deadline;
        },
        // Format date and time for display
        formatDateTime(dateString) {
            if (!dateString) return 'N/A';
            
            const date = new Date(dateString);
            return date.toLocaleDateString('en-GB', {
                weekday: 'short',
                day: 'numeric',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }
}
</script>
