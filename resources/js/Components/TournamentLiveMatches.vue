<template>
    <div class="space-y-3">
        <!-- Header -->
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <h4 class="text-base font-semibold text-gray-900 dark:text-white">
                    Live Matches
                </h4>
                <span 
                    v-if="hasLiveMatches" 
                    class="flex items-center gap-1.5 px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-full text-xs font-medium"
                >
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                    </span>
                    LIVE
                </span>
            </div>
            
            <div v-if="lastUpdated" class="text-xs text-gray-500 dark:text-gray-400">
                {{ formatTimeAgo(lastUpdated) }}
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading && !relevantMatches.length" class="text-center py-6">
            <div class="inline-flex items-center gap-2 text-gray-500 dark:text-gray-400 text-sm">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Checking for live matches...</span>
            </div>
        </div>

        <!-- No Live Matches -->
        <div v-else-if="!hasLiveMatches" class="text-center py-8 px-4 bg-gradient-to-br from-gray-100 to-gray-50 dark:bg-gradient-to-br dark:from-gray-800 dark:to-gray-700 rounded-lg border-2 border-gray-300 dark:border-gray-600">
            <div class="text-3xl mb-3">⚽</div>
            <div class="text-sm font-semibold text-gray-900 dark:text-white mb-1">
                No matches currently live
            </div>
            <div class="text-xs text-gray-600 dark:text-gray-300">
                Check back during match days
            </div>
        </div>

        <!-- Relevant Live Matches (Compact Tournament View) -->
        <div v-else class="space-y-2">
            <div 
                v-for="match in relevantMatches" 
                :key="match.id"
                class="bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-sm transition-shadow"
            >
                <!-- Match Status Banner -->
                <div 
                    class="h-0.5"
                    :class="{
                        'bg-red-500': match.status === 'LIVE' || match.status === 'IN_PLAY',
                        'bg-yellow-500': match.status === 'PAUSED',
                        'bg-green-500': match.status === 'FINISHED'
                    }"
                ></div>

                <div class="p-3">
                    <!-- Match Info (Compact) -->
                    <div class="flex items-center justify-between mb-2">
                        <!-- Home Team -->
                        <div class="flex items-center gap-1.5 flex-1 min-w-0">
                            <img 
                                v-if="match.home_team?.logo_url" 
                                :src="match.home_team.logo_url" 
                                :alt="match.home_team.name"
                                class="w-5 h-5 object-contain flex-shrink-0"
                            />
                            <span class="text-xs font-medium text-gray-900 dark:text-white truncate">
                                {{ getShortTeamName(match.home_team?.name) }}
                            </span>
                        </div>

                        <!-- Score & Status (Compact) -->
                        <div class="px-2 flex flex-col items-center gap-0.5 min-w-[60px]">
                            <div class="text-base font-bold text-gray-900 dark:text-white">
                                {{ match.live_event?.home_score ?? match.home_score ?? 0 }}
                                <span class="text-gray-400 mx-0.5">-</span>
                                {{ match.live_event?.away_score ?? match.away_score ?? 0 }}
                            </div>
                            <div class="text-xs font-medium">
                                <span 
                                    v-if="match.status === 'LIVE' || match.status === 'IN_PLAY'"
                                    class="text-red-600 dark:text-red-400"
                                >
                                    {{ match.live_event?.minute }}'
                                </span>
                                <span 
                                    v-else-if="match.status === 'PAUSED'"
                                    class="text-yellow-600 dark:text-yellow-400"
                                >
                                    HT
                                </span>
                                <span 
                                    v-else-if="match.status === 'FINISHED'"
                                    class="text-green-600 dark:text-green-400"
                                >
                                    FT
                                </span>
                            </div>
                        </div>

                        <!-- Away Team -->
                        <div class="flex items-center gap-1.5 flex-1 justify-end min-w-0">
                            <span class="text-xs font-medium text-gray-900 dark:text-white truncate">
                                {{ getShortTeamName(match.away_team?.name) }}
                            </span>
                            <img 
                                v-if="match.away_team?.logo_url" 
                                :src="match.away_team.logo_url" 
                                :alt="match.away_team.name"
                                class="w-5 h-5 object-contain flex-shrink-0"
                            />
                        </div>
                    </div>

                    <!-- Tournament Participants' Picks for This Match -->
                    <div v-if="match.tournament_picks && match.tournament_picks.length > 0" class="pt-2 border-t border-gray-200 dark:border-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">
                            {{ match.tournament_picks.length }} pick{{ match.tournament_picks.length !== 1 ? 's' : '' }}:
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <div 
                                v-for="pick in match.tournament_picks" 
                                :key="pick.user_id"
                                class="flex items-center gap-1 px-2 py-1 rounded text-xs"
                                :class="{
                                    'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400': pick.status === 'winning',
                                    'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400': pick.status === 'drawing',
                                    'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400': pick.status === 'losing'
                                }"
                            >
                                <span class="font-medium truncate max-w-[80px]">{{ pick.user_name }}</span>
                                <span class="text-xs opacity-75">→</span>
                                <span class="font-semibold">{{ getShortTeamName(pick.team_name) }}</span>
                                <span class="ml-0.5" :title="pick.status">
                                    {{ pick.status === 'winning' ? '✓' : pick.status === 'drawing' ? '=' : '✗' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Stats (if user has picks) -->
            <div v-if="tournamentStats.total_picks > 0" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div>
                        <div class="text-sm font-bold text-green-600 dark:text-green-400">
                            {{ tournamentStats.winning_picks }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Winning</div>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-yellow-600 dark:text-yellow-400">
                            {{ tournamentStats.drawing_picks }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Drawing</div>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ tournamentStats.projected_points }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Proj. Pts</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    tournamentId: {
        type: Number,
        required: true
    }
});

const liveMatches = ref([]);
const allTournamentPicks = ref([]); // All picks for this tournament's current gameweek
const lastUpdated = ref(null);
const loading = ref(true);
let pollingInterval = null;

const hasLiveMatches = computed(() => {
    return relevantMatches.value && relevantMatches.value.length > 0;
});

// Filter to show only matches where tournament participants have picks
const relevantMatches = computed(() => {
    if (!liveMatches.value.length) return [];
    
    return liveMatches.value.map(match => {
        // Find all tournament participants who picked teams in this match
        const picksForThisMatch = allTournamentPicks.value.filter(pick => {
            return pick.game_id === match.id;
        });
        
        if (picksForThisMatch.length === 0) return null;
        
        // Add tournament picks data to match
        return {
            ...match,
            tournament_picks: picksForThisMatch.map(pick => ({
                user_id: pick.user_id,
                user_name: pick.user_name,
                team_name: pick.team_name,
                status: calculatePickStatus(match, pick.team_id),
                projected_points: calculateProjectedPoints(match, pick.team_id)
            }))
        };
    }).filter(match => match !== null);
});

// Calculate stats for tournament participants
const tournamentStats = computed(() => {
    let winning = 0;
    let drawing = 0;
    let losing = 0;
    let projectedPoints = 0;
    
    relevantMatches.value.forEach(match => {
        match.tournament_picks?.forEach(pick => {
            if (pick.status === 'winning') {
                winning++;
                projectedPoints += 3;
            } else if (pick.status === 'drawing') {
                drawing++;
                projectedPoints += 1;
            } else if (pick.status === 'losing') {
                losing++;
            }
        });
    });
    
    return {
        total_picks: winning + drawing + losing,
        winning_picks: winning,
        drawing_picks: drawing,
        losing_picks: losing,
        projected_points: projectedPoints
    };
});

// Fetch live match data + tournament picks
async function fetchData() {
    try {
        // Fetch live matches from existing endpoint (no extra API calls!)
        const liveResponse = await axios.get('/api/live-matches');
        liveMatches.value = liveResponse.data.live_matches || [];
        
        // Fetch tournament participants' picks for current gameweek
        const picksResponse = await axios.get(`/api/tournaments/${props.tournamentId}/live-picks`);
        allTournamentPicks.value = picksResponse.data.picks || [];
        
        lastUpdated.value = new Date();
        loading.value = false;
    } catch (error) {
        console.error('Error fetching tournament live data:', error);
        loading.value = false;
    }
}

// Calculate if a pick is winning/drawing/losing
function calculatePickStatus(match, pickedTeamId) {
    if (match.status !== 'IN_PLAY' && match.status !== 'LIVE' && match.status !== 'PAUSED' && match.status !== 'FINISHED') {
        return 'pending';
    }
    
    const homeScore = match.live_event?.home_score ?? match.home_score ?? 0;
    const awayScore = match.live_event?.away_score ?? match.away_score ?? 0;
    
    const isHome = match.home_team?.id === pickedTeamId;
    
    if (homeScore > awayScore) {
        return isHome ? 'winning' : 'losing';
    } else if (awayScore > homeScore) {
        return isHome ? 'losing' : 'winning';
    } else {
        return 'drawing';
    }
}

// Calculate projected points
function calculateProjectedPoints(match, pickedTeamId) {
    const status = calculatePickStatus(match, pickedTeamId);
    if (status === 'winning') return 3;
    if (status === 'drawing') return 1;
    return 0;
}

// Get short team name for mobile display
function getShortTeamName(name) {
    if (!name) return '';
    
    const shortNames = {
        'Manchester United': 'Man Utd',
        'Manchester City': 'Man City',
        'Newcastle United': 'Newcastle',
        'Tottenham Hotspur': 'Tottenham',
        'West Ham United': 'West Ham',
        'Brighton & Hove Albion': 'Brighton',
        'Nottingham Forest': "Nott'm Forest",
        'Wolverhampton Wanderers': 'Wolves',
        'Leicester City': 'Leicester',
        'Sheffield United': 'Sheffield Utd',
        'Luton Town': 'Luton',
    };
    
    return shortNames[name] || name;
}

// Format time ago
function formatTimeAgo(date) {
    const seconds = Math.floor((new Date() - date) / 1000);
    
    if (seconds < 60) return 'just now';
    if (seconds < 120) return '1 min ago';
    if (seconds < 3600) return `${Math.floor(seconds / 60)} mins ago`;
    
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

// Initialize
onMounted(() => {
    fetchData();
    
    // Poll every 60 seconds
    pollingInterval = setInterval(() => {
        fetchData();
    }, 60000);
});

// Cleanup
onUnmounted(() => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});
</script>
