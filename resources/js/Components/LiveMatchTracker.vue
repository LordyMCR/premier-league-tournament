<template>
    <div class="space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Live Matches
                </h3>
                <span 
                    v-if="hasLiveMatches" 
                    class="flex items-center gap-1.5 px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-full text-xs font-medium"
                >
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                    </span>
                    LIVE
                </span>
            </div>
            
            <div v-if="lastUpdated" class="text-xs text-gray-500 dark:text-gray-400">
                Updated {{ formatTimeAgo(lastUpdated) }}
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
        <div v-else-if="!hasLiveMatches" class="text-center py-12 px-4 bg-gradient-to-br from-gray-100 to-gray-50 dark:bg-gradient-to-br dark:from-gray-800 dark:to-gray-700 rounded-lg border-2 border-gray-300 dark:border-gray-600">
            <div class="text-4xl mb-4">⚽</div>
            <div class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                No matches currently live
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-300">
                Check back during match days
            </div>
        </div>

        <!-- Live Matches Grid -->
        <div v-else class="space-y-3">
            <div 
                v-for="match in liveMatches" 
                :key="match.id"
                class="relative bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow"
            >
                <!-- Match Status Banner -->
                <div 
                    class="absolute top-0 left-0 right-0 h-1"
                    :class="{
                        'bg-red-500': match.status === 'LIVE' || match.status === 'IN_PLAY',
                        'bg-yellow-500': match.status === 'PAUSED',
                        'bg-green-500': match.status === 'FINISHED'
                    }"
                ></div>

                <div class="p-4">
                    <!-- Match Info -->
                    <div class="flex items-center justify-between mb-3">
                        <!-- Home Team -->
                        <div class="flex items-center gap-2 flex-1">
                            <img 
                                v-if="match.home_team?.logo_url" 
                                :src="match.home_team.logo_url" 
                                :alt="match.home_team.name"
                                class="w-8 h-8 object-contain"
                            />
                            <span class="font-medium text-gray-900 dark:text-white truncate">
                                {{ getShortTeamName(match.home_team?.name) }}
                            </span>
                        </div>

                        <!-- Score & Status -->
                        <div class="px-4 flex flex-col items-center gap-1 min-w-[80px]">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ match.live_event?.home_score ?? match.home_score ?? 0 }}
                                <span class="text-gray-400 mx-1">-</span>
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
                        <div class="flex items-center gap-2 flex-1 justify-end">
                            <span class="font-medium text-gray-900 dark:text-white truncate">
                                {{ getShortTeamName(match.away_team?.name) }}
                            </span>
                            <img 
                                v-if="match.away_team?.logo_url" 
                                :src="match.away_team.logo_url" 
                                :alt="match.away_team.name"
                                class="w-8 h-8 object-contain"
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
        <div v-if="stats && hasLiveMatches" class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ stats.total_picks }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Your Active Picks</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                        {{ stats.winning_picks }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Winning</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                        {{ stats.drawing_picks }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Drawing</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ stats.projected_points }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Projected Points</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const liveMatches = ref([]);
const userPicksStatus = ref([]);
const stats = ref(null);
const lastUpdated = ref(null);
const loading = ref(true);
let pollingInterval = null;

const hasLiveMatches = computed(() => {
    return liveMatches.value && liveMatches.value.length > 0;
});

// Fetch live match data
async function fetchLiveMatches() {
    try {
        const response = await axios.get('/api/live-matches');
        liveMatches.value = response.data.live_matches || [];
        userPicksStatus.value = response.data.user_picks || [];
        stats.value = response.data.stats || null;
        lastUpdated.value = new Date();
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

// Format time ago
function formatTimeAgo(date) {
    const seconds = Math.floor((new Date() - date) / 1000);
    
    if (seconds < 60) return 'just now';
    if (seconds < 120) return '1 min ago';
    if (seconds < 3600) return `${Math.floor(seconds / 60)} mins ago`;
    
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
    
    // Poll every 60 seconds
    pollingInterval = setInterval(() => {
        fetchLiveMatches();
    }, 60000);
});

// Cleanup
onUnmounted(() => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});
</script>
