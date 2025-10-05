<script setup>
import TournamentLayout from '@/Layouts/TournamentLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  stats: Object,
  upcomingFixtures: Array,
  favoriteFixtures: Array,
  standings: Array,
});

const page = usePage();
const favoriteTournament = computed(() => page.props.favoriteTournament);

// Provide robust defaults so layout never looks odd for new accounts
const statsSafe = computed(() => ({
  tournaments: props.stats?.tournaments ?? 0,
  wins: props.stats?.wins ?? 0,
  total_points: props.stats?.total_points ?? 0,
  win_rate: props.stats?.win_rate ?? '0%',
}));

// Form class helper function for standings table
const getFormClass = (result) => {
  if (result === 'W') return 'bg-green-500';
  if (result === 'D') return 'bg-yellow-500';
  if (result === 'L') return 'bg-red-500';
  return 'bg-gray-200'; // No game played yet
};
</script>

<template>
    <Head title="Dashboard - PL Tournament" />

    <TournamentLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-gray-900">
                Dashboard
            </h2>
            <p class="text-gray-600 mt-1">
                Welcome back! Ready to make your picks?
            </p>
        </template>

        <!-- Mobile-First Layout: Stack on mobile, responsive grid on desktop -->
        <div class="space-y-4 lg:space-y-6">
            
            <!-- Hero Section: Favorite Tournament (if exists) -->
            <div v-if="favoriteTournament" class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-6 lg:p-8 text-white shadow-lg">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-star text-yellow-300"></i>
                            <span class="text-sm font-medium text-green-100">Your Favorite Tournament</span>
                        </div>
                        <h3 class="text-2xl lg:text-3xl font-bold mb-2">{{ favoriteTournament.name }}</h3>
                        <p class="text-green-100 text-sm lg:text-base">
                            {{ favoriteTournament.participants_count || 0 }} {{ (favoriteTournament.participants_count || 0) === 1 ? 'participant' : 'participants' }}
                        </p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <Link :href="route('tournaments.show', favoriteTournament.id)" 
                              class="bg-white text-green-600 px-6 py-3 rounded-lg font-semibold hover:bg-green-50 transition-all text-center shadow-md">
                            <i class="fas fa-trophy mr-2"></i>Go to Tournament
                        </Link>
                    </div>
                </div>
            </div>

            <!-- No Favorite Tournament CTA -->
            <div v-else class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-6 lg:p-8 text-white shadow-lg">
                <div class="text-center">
                    <i class="fas fa-trophy text-5xl lg:text-6xl mb-4 text-green-200"></i>
                    <h3 class="text-2xl lg:text-3xl font-bold mb-3">Ready to Start Playing?</h3>
                    <p class="text-green-100 mb-6 text-sm lg:text-base">Join or create a tournament to compete with friends!</p>
                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <Link :href="route('tournaments.create')" 
                              class="bg-white text-green-600 px-8 py-3 rounded-lg font-semibold hover:bg-green-50 transition-all shadow-md">
                            <i class="fas fa-plus mr-2"></i>Create Tournament
                        </Link>
                        <Link :href="route('tournaments.join-form')" 
                              class="bg-green-700 text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-800 transition-all shadow-md">
                            <i class="fas fa-users mr-2"></i>Join Tournament
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Your Stats - Enhanced with visual elements -->
            <div class="bg-white rounded-xl p-6 lg:p-8 border border-gray-200 shadow-md">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 lg:w-12 lg:h-12 bg-green-600 rounded-lg flex items-center justify-center shadow-md">
                        <i class="fas fa-chart-line text-white text-lg lg:text-xl"></i>
                    </div>
                    <h3 class="text-xl lg:text-2xl font-bold text-gray-900">Your Performance</h3>
                </div>
                
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
                    <!-- Tournaments -->
                    <div class="text-center p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-xl border border-green-200">
                        <div class="w-12 h-12 lg:w-14 lg:h-14 bg-green-600 rounded-full flex items-center justify-center mx-auto mb-3 shadow-md">
                            <i class="fas fa-trophy text-white text-lg lg:text-xl"></i>
                        </div>
                        <div class="text-3xl lg:text-4xl font-bold text-green-600 mb-1">{{ statsSafe.tournaments }}</div>
                        <div class="text-xs lg:text-sm text-gray-600 font-medium">Tournament{{ statsSafe.tournaments !== 1 ? 's' : '' }}</div>
                    </div>

                    <!-- Wins -->
                    <div class="text-center p-4 bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl border border-yellow-200">
                        <div class="w-12 h-12 lg:w-14 lg:h-14 bg-yellow-500 rounded-full flex items-center justify-center mx-auto mb-3 shadow-md">
                            <i class="fas fa-crown text-white text-lg lg:text-xl"></i>
                        </div>
                        <div class="text-3xl lg:text-4xl font-bold text-yellow-600 mb-1">{{ statsSafe.wins }}</div>
                        <div class="text-xs lg:text-sm text-gray-600 font-medium">Win{{ statsSafe.wins !== 1 ? 's' : '' }}</div>
                    </div>

                    <!-- Total Points -->
                    <div class="text-center p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl border border-blue-200">
                        <div class="w-12 h-12 lg:w-14 lg:h-14 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-3 shadow-md">
                            <i class="fas fa-star text-white text-lg lg:text-xl"></i>
                        </div>
                        <div class="text-3xl lg:text-4xl font-bold text-blue-600 mb-1">{{ statsSafe.total_points }}</div>
                        <div class="text-xs lg:text-sm text-gray-600 font-medium">Total Points</div>
                    </div>

                    <!-- Win Rate -->
                    <div class="text-center p-4 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl border border-purple-200">
                        <div class="w-12 h-12 lg:w-14 lg:h-14 bg-purple-600 rounded-full flex items-center justify-center mx-auto mb-3 shadow-md">
                            <i class="fas fa-percentage text-white text-lg lg:text-xl"></i>
                        </div>
                        <div class="text-3xl lg:text-4xl font-bold text-purple-600 mb-1">{{ statsSafe.win_rate }}</div>
                        <div class="text-xs lg:text-sm text-gray-600 font-medium">Win Rate</div>
                    </div>
                </div>
            </div>

            <!-- Two Column Grid for Fixtures and Standings on Desktop, Stack on Mobile -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6">
                
                <!-- Upcoming Fixtures -->
                <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-md">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center shadow-md">
                            <i class="fas fa-calendar-alt text-white text-lg"></i>
                        </div>
                        <h3 class="text-lg lg:text-xl font-bold text-gray-900">Upcoming Fixtures</h3>
                    </div>
                    
                    <div v-if="upcomingFixtures && upcomingFixtures.length > 0" class="space-y-3">
                        <div v-for="(fixture, idx) in upcomingFixtures" :key="idx" 
                             class="bg-gray-50 rounded-lg p-3 lg:p-4 hover:bg-blue-50 transition-all border border-gray-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs text-green-600 font-medium">
                                    {{ fixture.when }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 flex-1">
                                    <img v-if="fixture.home_crest" :src="fixture.home_crest" :alt="fixture.home" 
                                         class="w-5 h-5 lg:w-6 lg:h-6 object-contain flex-shrink-0" />
                                    <i v-else class="fas fa-shield-alt text-gray-400 text-sm"></i>
                                    <span class="text-sm font-semibold text-gray-900">{{ fixture.home }}</span>
                                </div>
                                <span class="text-xs font-bold text-gray-400 px-2">vs</span>
                                <div class="flex items-center gap-2 flex-1 justify-end">
                                    <span class="text-sm font-semibold text-gray-900">{{ fixture.away }}</span>
                                    <img v-if="fixture.away_crest" :src="fixture.away_crest" :alt="fixture.away" 
                                         class="w-5 h-5 lg:w-6 lg:h-6 object-contain flex-shrink-0" />
                                    <i v-else class="fas fa-shield-alt text-gray-400 text-sm"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8">
                        <i class="fas fa-calendar-times text-gray-300 text-4xl mb-3"></i>
                        <p class="text-gray-500 text-sm">No upcoming fixtures</p>
                    </div>
                    
                    <Link :href="route('schedule.index')" 
                          class="block mt-4 text-center text-green-600 hover:text-green-700 font-semibold text-sm">
                        View Full Schedule <i class="fas fa-arrow-right ml-1"></i>
                    </Link>
                </div>

                <!-- Premier League Standings - Top 6 -->
                <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-md">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center shadow-md">
                            <i class="fas fa-table text-white text-lg"></i>
                        </div>
                        <h3 class="text-lg lg:text-xl font-bold text-gray-900">Premier League Top 6</h3>
                    </div>
                    
                    <div v-if="standings && standings.length > 0" class="space-y-2">
                        <div v-for="team in standings.slice(0, 6)" :key="team.position" 
                             class="flex items-center gap-3 p-2 lg:p-3 bg-gray-50 rounded-lg hover:bg-purple-50 transition-all border border-gray-200">
                            <div class="w-8 h-8 flex items-center justify-center">
                                <span class="font-bold text-gray-700 text-sm lg:text-base">{{ team.position }}</span>
                            </div>
                            <div class="flex items-center gap-2 flex-1 min-w-0">
                                <img v-if="team.team_crest" :src="team.team_crest" :alt="team.team" 
                                     class="w-6 h-6 lg:w-7 lg:h-7 object-contain flex-shrink-0" />
                                <i v-else class="fas fa-shield-alt text-gray-400 text-sm flex-shrink-0"></i>
                                <span class="font-semibold text-gray-900 text-sm lg:text-base truncate">{{ team.team }}</span>
                            </div>
                            <div class="flex items-center gap-3 lg:gap-4 flex-shrink-0">
                                <span class="text-xs lg:text-sm text-gray-600 font-medium w-8 text-center">{{ team.played }}</span>
                                <span class="text-sm lg:text-base font-bold text-gray-900 w-8 text-center">{{ team.points }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8">
                        <i class="fas fa-list text-gray-300 text-4xl mb-3"></i>
                        <p class="text-gray-500 text-sm">No standings available</p>
                    </div>
                    
                    <Link :href="route('schedule.standings')" 
                          class="block mt-4 text-center text-green-600 hover:text-green-700 font-semibold text-sm">
                        View Full Table <i class="fas fa-arrow-right ml-1"></i>
                    </Link>
                </div>

            </div>

            <!-- How to Play - Collapsible on Mobile -->
            <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-6 border border-gray-200 shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-gray-600 rounded-lg flex items-center justify-center shadow-md">
                        <i class="fas fa-question-circle text-white text-lg"></i>
                    </div>
                    <h3 class="text-lg lg:text-xl font-bold text-gray-900">How to Play</h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
                    <div class="flex items-start gap-3 p-3 bg-white rounded-lg border border-gray-200">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                            <span class="text-green-600 font-bold text-sm">1</span>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 text-sm mb-1">Join a Tournament</h4>
                            <p class="text-xs text-gray-600">Create your own or join with a code</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-white rounded-lg border border-gray-200">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                            <span class="text-green-600 font-bold text-sm">2</span>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 text-sm mb-1">Pick Your Team</h4>
                            <p class="text-xs text-gray-600">Choose who you think will win each gameweek</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-white rounded-lg border border-gray-200">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                            <span class="text-green-600 font-bold text-sm">3</span>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 text-sm mb-1">Earn Points</h4>
                            <p class="text-xs text-gray-600">Get 3 points for correct picks</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-white rounded-lg border border-gray-200">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                            <span class="text-green-600 font-bold text-sm">4</span>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 text-sm mb-1">Win the Tournament</h4>
                            <p class="text-xs text-gray-600">Top the leaderboard and claim victory!</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </TournamentLayout>
</template>
