<template>
    <div class="space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <h3 class="text-xl font-bold !text-gray-900">
                    Live Matches
                </h3>
                <span 
                    v-if="hasLiveMatches" 
                    class="flex items-center gap-1.5 px-3 py-1.5 bg-red-500 text-white rounded-full text-xs font-bold shadow-lg"
                >
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-300 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                    </span>
                    LIVE
                </span>
            </div>
            
            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 font-medium">
                <span v-if="isUpdating" class="inline-flex items-center gap-1">
                    <svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Updating...
                </span>
                <span v-else-if="lastUpdated">
                    Updated {{ timeAgo }}
                </span>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading && !liveMatches.length" class="text-center py-8">
            <div class="inline-flex items-center gap-2 text-gray-500 dark:text-gray-400">
                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Loading live matches...</span>
            </div>
        </div>

        <!-- No Live Matches -->
        <div v-else-if="!hasLiveMatches" class="text-center py-16 px-6 bg-gradient-to-br from-gray-50 to-gray-100 dark:bg-gradient-to-br dark:from-gray-800 dark:to-gray-700 rounded-xl border-2 border-gray-200 dark:border-gray-600 shadow-lg">
            <div class="text-6xl mb-6 opacity-60">⚽</div>
            <div class="text-xl font-bold text-gray-900 dark:text-white mb-3">
                No matches currently live
            </div>
            <div class="text-base text-gray-600 dark:text-gray-300">
                Check back during match days for live updates
            </div>
        </div>

        <!-- Live Matches Grid -->
        <div v-else class="space-y-3">
            <div 
                v-for="match in liveMatches" 
                :key="match.id"
                class="relative bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-lg hover:shadow-xl transition-all duration-300"
            >
                <!-- Match Status Banner -->
                <div 
                    class="absolute top-0 left-0 right-0 h-2"
                    :class="{
                        'bg-red-500': match.status === 'LIVE' || match.status === 'IN_PLAY',
                        'bg-yellow-500': match.status === 'PAUSED',
                        'bg-green-500': match.status === 'FINISHED'
                    }"
                ></div>

                <div class="p-4 md:p-6">
                    <!-- Match Info -->
                    <div class="flex items-center justify-between">
                        <!-- Home Team -->
                        <div class="flex items-center gap-2 md:gap-3 flex-1 min-w-0">
                            <img 
                                v-if="match.home_team?.logo_url" 
                                :src="match.home_team.logo_url" 
                                :alt="match.home_team.name"
                                class="w-8 h-8 md:w-10 md:h-10 object-contain flex-shrink-0"
                            />
                            <div class="min-w-0">
                                <div class="font-semibold text-gray-900 dark:text-white text-xs md:text-sm truncate">
                                    <span class="md:hidden">{{ match.home_team?.short_name || getShortTeamName(match.home_team?.name) }}</span>
                                    <span class="hidden md:inline">{{ match.home_team?.name || 'Unknown' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Score & Status -->
                        <div class="px-2 md:px-6 flex flex-col items-center gap-1 md:gap-2 min-w-[80px] md:min-w-[100px]">
                            <!-- Score -->
                            <div class="flex items-center gap-1 md:gap-3">
                                <div class="text-xl md:text-3xl font-bold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 px-2 md:px-4 py-1 md:py-2 rounded-lg">
                                    {{ match.live_event?.home_score ?? match.home_score ?? 0 }}
                                </div>
                                <div class="text-gray-400 text-lg md:text-xl font-medium">-</div>
                                <div class="text-xl md:text-3xl font-bold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 px-2 md:px-4 py-1 md:py-2 rounded-lg">
                                    {{ match.live_event?.away_score ?? match.away_score ?? 0 }}
                                </div>
                            </div>
                            
                            <!-- Match Status -->
                            <div class="flex items-center gap-2">
                                <span 
                                    v-if="match.status === 'LIVE' || match.status === 'IN_PLAY'"
                                    class="inline-flex items-center gap-1 px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full text-xs font-semibold"
                                >
                                    <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                                    {{ match.live_event?.minute || 'LIVE' }}'
                                </span>
                                <span 
                                    v-else-if="match.status === 'PAUSED'"
                                    class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-full text-xs font-semibold"
                                >
                                    HT
                                </span>
                                <span 
                                    v-else-if="match.status === 'FINISHED'"
                                    class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full text-xs font-semibold"
                                >
                                    FT
                                </span>
                            </div>
                        </div>

                        <!-- Away Team -->
                        <div class="flex items-center gap-2 md:gap-3 flex-1 justify-end min-w-0">
                            <div class="min-w-0 text-right">
                                <div class="font-semibold text-gray-900 dark:text-white text-xs md:text-sm truncate">
                                    <span class="md:hidden">{{ match.away_team?.short_name || getShortTeamName(match.away_team?.name) }}</span>
                                    <span class="hidden md:inline">{{ match.away_team?.name || 'Unknown' }}</span>
                                </div>
                            </div>
                            <img 
                                v-if="match.away_team?.logo_url" 
                                :src="match.away_team.logo_url" 
                                :alt="match.away_team.name"
                                class="w-8 h-8 md:w-10 md:h-10 object-contain flex-shrink-0"
                            />
                        </div>
                    </div>

                    <!-- User's Pick Status -->
                    <div v-if="match.user_pick" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2">
                                <span class="text-gray-600 dark:text-gray-400">Your pick:</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ getShortTeamName(match.user_pick.team_name) }}
                                </span>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <!-- Status Indicator -->
                                <span 
                                    class="px-2 py-1 rounded text-xs font-medium"
                                    :class="{
                                        'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400': match.user_pick.status === 'winning',
                                        'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400': match.user_pick.status === 'drawing',
                                        'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400': match.user_pick.status === 'losing'
                                    }"
                                >
                                    {{ match.user_pick.status === 'winning' ? '✓ Winning' : 
                                       match.user_pick.status === 'drawing' ? '= Drawing' : 
                                       '✗ Losing' }}
                                </span>
                                
                                <!-- Projected Points -->
                                <span 
                                    class="px-2 py-1 rounded text-xs font-bold"
                                    :class="{
                                        'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400': match.user_pick.projected_points === 3,
                                        'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400': match.user_pick.projected_points === 1,
                                        'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400': match.user_pick.projected_points === 0
                                    }"
                                >
                                    {{ match.user_pick.projected_points }} pts
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Events -->
                    <div v-if="match.live_event?.events && match.live_event.events.length > 0" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex gap-2 overflow-x-auto pb-1">
                            <div 
                                v-for="(event, idx) in getRecentEvents(match.live_event.events)" 
                                :key="idx"
                                class="flex-shrink-0 flex items-center gap-1 px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs"
                            >
                                <span>{{ event.minute }}'</span>
                                <span>{{ getEventIcon(event.type) }}</span>
                                <span class="text-gray-600 dark:text-gray-300">{{ event.player }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Stats -->
        <div v-if="!hideStats && stats && hasLiveMatches" class="mt-4 md:mt-6 pt-3 md:pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
                <div class="text-center">
                    <div class="text-lg md:text-2xl font-bold !text-gray-900">
                        {{ stats.user_picks_live }}
                    </div>
                    <div class="text-xs !text-gray-600">Your Active Picks</div>
                </div>
                <div class="text-center">
                    <div class="text-lg md:text-2xl font-bold !text-green-600">
                        {{ stats.winning_picks }}
                    </div>
                    <div class="text-xs !text-gray-600">Winning</div>
                </div>
                <div class="text-center">
                    <div class="text-lg md:text-2xl font-bold !text-yellow-600">
                        {{ stats.drawing_picks }}
                    </div>
                    <div class="text-xs !text-gray-600">Drawing</div>
                </div>
                <div class="text-center">
                    <div class="text-lg md:text-2xl font-bold !text-gray-900">
                        {{ stats.projected_points }}
                    </div>
                    <div class="text-xs !text-gray-600">Projected Points</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    hideStats: {
        type: Boolean,
        default: false
    }
});

const liveMatches = ref([]);
const userPicksStatus = ref([]);
const stats = ref(null);
const lastUpdated = ref(null);
const loading = ref(true);
const isUpdating = ref(false);
const timeAgo = ref('0s ago');
let pollingInterval = null;
let timeUpdateInterval = null;

const hasLiveMatches = computed(() => {
    return liveMatches.value && liveMatches.value.length > 0;
});

// Update time ago display
function updateTimeAgo() {
    if (lastUpdated.value) {
        timeAgo.value = formatTimeAgo(lastUpdated.value);
    }
}

// Fetch live match data
async function fetchLiveMatches() {
    if (isUpdating.value) return; // Prevent concurrent requests
    
    try {
        isUpdating.value = true;
        const response = await axios.get('/api/live-matches');
        liveMatches.value = response.data.live_matches || [];
        userPicksStatus.value = response.data.user_picks || [];
        stats.value = response.data.stats || null;
        lastUpdated.value = new Date();
        timeAgo.value = '0s ago'; // Reset to 0 when new data arrives
        loading.value = false;
        
        // Map user picks to matches for easy display
        if (liveMatches.value.length > 0) {
            liveMatches.value.forEach(match => {
                const userPick = userPicksStatus.value.find(pick => pick.game_id === match.id);
                if (userPick) {
                    match.user_pick = userPick;
                }
            });
        }
    } catch (error) {
        console.error('Error fetching live matches:', error);
        loading.value = false;
    } finally {
        isUpdating.value = false;
    }
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

// Format time ago with counting system
function formatTimeAgo(date) {
    const seconds = Math.floor((new Date() - date) / 1000);
    
    if (seconds < 60) return `${seconds}s ago`;
    if (seconds < 120) return '1m ago';
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
    
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

// Get event icon
function getEventIcon(type) {
    const icons = {
        'GOAL': '⚽',
        'YELLOW_CARD': '🟨',
        'RED_CARD': '🟥',
        'SUBSTITUTION': '🔄',
        'PENALTY': '🎯',
        'OWN_GOAL': '⚽ (OG)'
    };
    
    return icons[type] || '•';
}

// Get recent events (last 5)
function getRecentEvents(events) {
    if (!events || !Array.isArray(events)) return [];
    return events.slice(-5).reverse();
}

// Initialize
onMounted(() => {
    fetchLiveMatches();
    
    // Start time update interval (every second)
    timeUpdateInterval = setInterval(() => {
        updateTimeAgo();
    }, 1000);
    
    // Smart polling: more frequent during live matches
    const startPolling = () => {
        if (pollingInterval) clearInterval(pollingInterval);
        
        // Poll every 2 minutes for both live and non-live matches to reduce API usage
        const interval = 120000; // 2 minutes
        
        pollingInterval = setInterval(() => {
            fetchLiveMatches();
            // Restart polling with potentially different interval
            startPolling();
        }, interval);
    };
    
    startPolling();
});

// Cleanup
onUnmounted(() => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
    if (timeUpdateInterval) {
        clearInterval(timeUpdateInterval);
    }
});
</script>
