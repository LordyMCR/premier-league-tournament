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
    nextSelectionGameweek: Object,
    userPicks: Array,
    currentPick: Object,
    allParticipantPicks: Object,
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

// Computed property to get time until next selection window
const timeUntilNextSelection = computed(() => {
    if (!props.nextSelectionGameweek?.selection_opens) return null;
    
    const now = new Date();
    const opensAt = new Date(props.nextSelectionGameweek.selection_opens);
    const diffInMs = opensAt - now;
    
    if (diffInMs <= 0) return null;
    
    const days = Math.floor(diffInMs / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diffInMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diffInMs % (1000 * 60 * 60)) / (1000 * 60));
    
    if (days > 0) {
        return `${days}d ${hours}h`;
    } else if (hours > 0) {
        return `${hours}h ${minutes}m`;
    } else {
        return `${minutes}m`;
    }
});
</script>

<template>
    <Head :title="tournament.name" />

    <TournamentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">
                        {{ tournament.name }}
                    </h2>
                    <p class="text-gray-600 mt-2">
                        {{ tournament.description || 'Premier League prediction tournament' }}
                    </p>
                </div>
                <div class="text-right">
                    <div class="bg-green-50 rounded-lg px-4 py-2 border border-green-200 shadow-md">
                        <p class="text-gray-600 text-sm">Join Code</p>
                        <p class="text-gray-900 font-mono text-lg">{{ tournament.join_code }}</p>
                    </div>
                </div>
            </div>
        </template>

        <div class="max-w-6xl mx-auto space-y-8">
            <!-- Tournament Info Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Status -->
                <div class="bg-white rounded-xl p-6 border border-green-200 shadow-lg">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center shadow-md">
                            <i class="fas fa-info-circle text-white text-lg"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Status</p>
                            <p class="text-gray-900 font-semibold capitalize">{{ tournament.status }}</p>
                        </div>
                    </div>
                </div>

                <!-- Participants -->
                <div class="bg-white rounded-xl p-6 border border-green-200 shadow-lg">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center shadow-md">
                            <i class="fas fa-users text-white text-lg"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Participants</p>
                            <p class="text-gray-900 font-semibold">{{ tournament.participants_count }}</p>
                        </div>
                    </div>
                </div>

                <!-- Current Gameweek -->
                <div class="bg-white rounded-xl p-6 border border-green-200 shadow-lg">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center shadow-md">
                            <i class="fas fa-calendar text-white text-lg"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Current Gameweek</p>
                            <p class="text-gray-900 font-semibold">{{ currentGameweek?.week_number || 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Created -->
                <div class="bg-white rounded-xl p-6 border border-green-200 shadow-lg">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center shadow-md">
                            <i class="fas fa-clock text-white text-lg"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Created</p>
                            <p class="text-gray-900 font-semibold">{{ formatDate(tournament.created_at) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-4" v-if="!isParticipant">
                <button @click="copyJoinCode" 
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-all shadow-md hover:shadow-lg">
                    <i class="fas fa-copy mr-2"></i>
                    Copy Join Code
                </button>
                <Link :href="route('tournaments.join-form')" 
                      class="bg-white border border-green-200 text-gray-700 px-6 py-3 rounded-lg font-medium transition-all hover:bg-green-50">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Join Tournament
                </Link>
            </div>

            <!-- Selection Window Open -->
            <div v-if="isParticipant && selectionGameweek" class="bg-white rounded-xl p-6 border border-green-200 shadow-lg">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Selection Window Open</h3>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600">Gameweek {{ selectionGameweek.week_number }}</p>
                        <p class="text-sm text-gray-500">Closes {{ formatDate(selectionGameweek.selection_deadline) }}</p>
                    </div>
                    <Link :href="route('tournaments.gameweeks.picks.create', { tournament: tournament.id, gameWeek: selectionGameweek.id })"
                          class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg font-medium transition-all shadow-md hover:shadow-lg">
                        Make Your Pick
                    </Link>
                </div>
            </div>

            <!-- Next Selection Window -->
            <div v-else-if="nextSelectionGameweek" class="bg-white rounded-xl p-6 border border-green-200 shadow-lg">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Next Selection Window</h3>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600">Gameweek {{ nextSelectionGameweek.week_number }}</p>
                        <p class="text-sm text-gray-500">Opens {{ formatDate(nextSelectionGameweek.selection_opens) }}</p>
                    </div>
                    <div v-if="timeUntilNextSelection" class="text-right">
                        <p class="text-gray-600 text-sm">Time Remaining</p>
                        <p class="text-green-600 font-bold">{{ timeUntilNextSelection }}</p>
                    </div>
                </div>
            </div>

            <!-- Current Pick -->
            <div v-if="currentPick" class="bg-white rounded-xl p-6 border border-green-200 shadow-lg">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Current Pick</h3>
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-full overflow-hidden flex items-center justify-center border-2 border-green-200"
                         :style="{ backgroundColor: currentPick.team.primary_color || '#22C55E' }">
                        <img v-if="currentPick.team.logo_url"
                             :src="currentPick.team.logo_url"
                             :alt="currentPick.team.name"
                             class="w-full h-full object-contain bg-white" />
                        <span v-else class="text-white text-sm font-bold">{{ currentPick.team.short_name }}</span>
                    </div>
                    <div>
                        <p class="text-gray-900 font-medium">{{ currentPick.team.name }}</p>
                        <p class="text-gray-500 text-sm">Gameweek {{ currentPick.gameweek?.week_number || 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Leaderboard -->
            <div class="bg-white rounded-xl border border-green-200 shadow-lg">
                <div class="p-6 border-b border-green-200">
                    <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                        <i class="fas fa-trophy text-yellow-500"></i>
                        Leaderboard
                    </h3>
                </div>
                <!-- Mobile card list -->
                <div class="sm:hidden p-4 space-y-2">
                    <div v-for="(participant, index) in leaderboard" :key="participant.id" class="bg-gray-50 rounded-lg p-3 flex items-center justify-between">
                        <div class="flex items-center min-w-0 space-x-3">
                            <span v-if="index === 0" class="text-xl">🥇</span>
                            <span v-else-if="index === 1" class="text-xl">🥈</span>
                            <span v-else-if="index === 2" class="text-xl">🥉</span>
                            <span v-else class="w-5 text-center text-gray-500 text-sm font-medium">{{ index + 1 }}</span>
                            <div class="w-8 h-8 rounded-full overflow-hidden flex items-center justify-center border-2 border-green-200 flex-shrink-0">
                                <img :src="participant.user.avatar_url" :alt="participant.user.name" class="w-full h-full object-cover" @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(participant.user.name)}&background=22C55E&color=fff&size=32`" />
                            </div>
                            <span class="text-sm text-gray-900 font-medium truncate max-w-[10rem]">{{ participant.user.name }}</span>
                        </div>
                        <span class="text-green-600 font-bold flex-shrink-0">{{ participant.points }}</span>
                    </div>
                </div>
                <!-- Desktop/Table view -->
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full min-w-[36rem]">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Player</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Points</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Picks</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-green-200">
                            <tr v-for="(participant, index) in leaderboard" :key="participant.id" 
                                class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span v-if="index === 0" class="text-2xl mr-2">🥇</span>
                                        <span v-else-if="index === 1" class="text-2xl mr-2">🥈</span>
                                        <span v-else-if="index === 2" class="text-2xl mr-2">🥉</span>
                                        <span v-else class="text-gray-500 font-medium mr-2">{{ index + 1 }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full overflow-hidden flex items-center justify-center border-2 border-green-200">
                                            <img 
                                                :src="participant.user.avatar_url" 
                                                :alt="participant.user.name"
                                                class="w-full h-full object-cover"
                                                @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(participant.user.name)}&background=22C55E&color=fff&size=32`"
                                            />
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">{{ participant.user.name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-green-600 font-bold">{{ participant.points }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ participant.picks_count }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- All Participants' Picks -->
            <div v-if="Object.keys(allParticipantPicks).length > 0" class="bg-white rounded-xl border border-green-200 shadow-lg">
                <div class="p-6 border-b border-green-200">
                    <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                        <i class="fas fa-users text-blue-500"></i>
                        All Participants' Picks
                    </h3>
                    <p class="text-gray-600 text-sm mt-1">See what teams everyone has selected</p>
                </div>
                
                <div class="max-h-[34rem] overflow-y-auto">
                    <div v-for="(picks, gameweekId) in allParticipantPicks" :key="gameweekId" class="border-b border-gray-100 last:border-b-0">
                        <div class="p-4 bg-gray-50 border-b border-gray-200">
                            <h4 class="font-semibold text-gray-900">
                                {{ picks[0]?.gameweek?.name || `Gameweek ${picks[0]?.gameweek?.week_number}` }}
                            </h4>
                        </div>
                        <div class="p-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                <div v-for="pick in picks" :key="pick.id" 
                                     class="flex flex-col gap-2 p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                     <div class="flex items-center space-x-3">
                                        <!-- User Avatar -->
                                        <div class="w-8 h-8 rounded-full overflow-hidden flex items-center justify-center border-2 border-green-200">
                                            <img 
                                                :src="pick.user.avatar_url" 
                                                :alt="pick.user.name"
                                                class="w-full h-full object-cover"
                                                @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(pick.user.name)}&background=22C55E&color=fff&size=32`"
                                            />
                                        </div>
                                        
                                        <!-- User Name -->
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900 truncate">{{ pick.user.name }}</p>
                                        </div>
                                    </div>
                                    
                                     <div class="flex items-center flex-wrap gap-x-3 gap-y-1 w-full justify-start md:justify-end">
                                        <!-- Team Badge + Name -->
                                        <div class="flex items-center space-x-2 min-w-0">
                                            <div class="w-7 h-7 rounded-full overflow-hidden flex items-center justify-center border border-green-200"
                                                 :style="{ backgroundColor: pick.team.primary_color || '#22C55E' }"
                                                 :title="pick.team.name">
                                                <img v-if="pick.team.logo_url" :src="pick.team.logo_url" :alt="pick.team.name" class="w-full h-full object-contain bg-white" />
                                                <span v-else class="text-white text-xs font-bold">{{ pick.team.short_name }}</span>
                                            </div>
                                            <span class="text-sm text-gray-900 font-medium truncate leading-tight max-w-[7rem] sm:max-w-[12rem]" :title="pick.team.name">
                                                <span class="sm:hidden">{{ pick.team.short_name }}</span>
                                                <span class="hidden sm:inline">{{ pick.team.name }}</span>
                                            </span>
                                        </div>
                                        
                                        <!-- Home/Away indicator if applicable -->
                                         <span v-if="pick.home_away" 
                                              class="text-xs px-1.5 py-0.5 rounded text-gray-600 bg-gray-200 flex-shrink-0"
                                               :title="pick.home_away === 'home' ? 'Home fixture' : 'Away fixture'">{{ pick.home_away === 'home' ? 'H' : 'A' }}</span>
                                        
                                        <!-- Result indicator -->
                                         <div class="flex items-center space-x-1 flex-shrink-0">
                                            <span v-if="pick.result === 'win'" class="w-2 h-2 bg-green-500 rounded-full" title="Win"></span>
                                            <span v-else-if="pick.result === 'draw'" class="w-2 h-2 bg-yellow-500 rounded-full" title="Draw"></span>
                                            <span v-else-if="pick.result === 'loss'" class="w-2 h-2 bg-red-500 rounded-full" title="Loss"></span>
                                            <span v-else class="w-2 h-2 bg-gray-300 rounded-full" title="Pending"></span>
                                            
                                            <!-- Points -->
                                            <span class="text-xs font-medium text-gray-600">{{ pick.points || 0 }}pts</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Picks History -->
            <div v-if="userPicks.length" class="bg-white rounded-xl border border-green-200 shadow-lg">
                <div class="p-6 border-b border-green-200">
                    <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                        <i class="fas fa-history text-blue-500"></i>
                        Your Picks History
                    </h3>
                </div>
                <!-- Mobile card list -->
                <div class="sm:hidden p-4 space-y-2">
                    <div v-for="pick in userPicks" :key="pick.id" class="bg-gray-50 rounded-lg p-3 flex items-center justify-between">
                        <div class="flex items-center min-w-0 space-x-3">
                            <span class="text-xs text-gray-500 font-medium flex-shrink-0">GW {{ pick.gameweek?.week_number || 'N/A' }}</span>
                            <div class="w-6 h-6 rounded-full overflow-hidden flex items-center justify-center border border-green-200 flex-shrink-0"
                                 :style="{ backgroundColor: pick.team.primary_color || '#22C55E' }">
                                <img v-if="pick.team.logo_url" :src="pick.team.logo_url" :alt="pick.team.name" class="w-full h-full object-contain bg-white" />
                                <span v-else class="text-white text-xs font-bold">{{ pick.team.short_name }}</span>
                            </div>
                            <span class="text-sm text-gray-900 font-medium truncate" :title="pick.team.name">{{ pick.team.short_name }}</span>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <div>
                                <span v-if="pick.result === 'win'" class="text-green-600 text-sm font-medium">Win</span>
                                <span v-else-if="pick.result === 'draw'" class="text-yellow-600 text-sm font-medium">Draw</span>
                                <span v-else-if="pick.result === 'loss'" class="text-red-600 text-sm font-medium">Loss</span>
                                <span v-else class="text-gray-500 text-sm">Pending</span>
                            </div>
                            <div class="text-xs text-gray-600">{{ pick.points }} pts</div>
                        </div>
                    </div>
                </div>

                <!-- Desktop/Table view -->
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full min-w-[40rem]">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gameweek</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Result</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Points</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-green-200">
                            <tr v-for="pick in userPicks" :key="pick.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ pick.gameweek?.week_number || 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center min-w-0">
                                        <div class="w-6 h-6 rounded-full overflow-hidden flex items-center justify-center border border-green-200"
                                             :style="{ backgroundColor: pick.team.primary_color || '#22C55E' }">
                                            <img v-if="pick.team.logo_url" :src="pick.team.logo_url" :alt="pick.team.name" class="w-full h-full object-contain bg-white" />
                                            <span v-else class="text-white text-xs font-bold">{{ pick.team.short_name }}</span>
                                        </div>
                                        <span class="ml-2 text-sm text-gray-900 truncate" :title="pick.team.name">
                                            <span class="sm:hidden">{{ pick.team.short_name }}</span>
                                            <span class="hidden sm:inline">{{ pick.team.name }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="pick.result === 'win'" class="text-green-600 font-medium">Win</span>
                                    <span v-else-if="pick.result === 'draw'" class="text-yellow-600 font-medium">Draw</span>
                                    <span v-else-if="pick.result === 'loss'" class="text-red-600 font-medium">Loss</span>
                                    <span v-else class="text-gray-500">Pending</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-green-600 font-bold">{{ pick.points }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
            if (!gameweek) return false;
            const now = new Date();
            if (gameweek.selection_opens && gameweek.selection_deadline) {
                return now >= new Date(gameweek.selection_opens) && now <= new Date(gameweek.selection_deadline);
            }
            if (gameweek.selection_deadline) {
                return now <= new Date(gameweek.selection_deadline);
            }
            if (gameweek.gameweek_start_time) {
                return now < new Date(gameweek.gameweek_start_time);
            }
            return true;
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
