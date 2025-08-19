<script setup>
import TournamentLayout from '@/Layouts/TournamentLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  stats: Object,
  upcomingFixtures: Array,
  favoriteFixtures: Array,
  standings: Array,
});

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
                Welcome to Tournament Central
            </h2>
            <p class="text-gray-600 mt-2">
                Your hub for Premier League tournament action
            </p>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
            <!-- Quick Actions Card -->
            <div class="bg-white rounded-xl p-4 lg:p-6 border border-green-200 hover:shadow-lg transition-all shadow-md">
                <div class="flex items-center justify-between mb-3 lg:mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                    <div class="w-8 h-8 lg:w-10 lg:h-10 bg-green-600 rounded-lg flex items-center justify-center shadow-md">
                        <i class="fas fa-bolt text-white text-sm lg:text-lg"></i>
                    </div>
                </div>
                <div class="space-y-2 lg:space-y-3">
                    <Link :href="route('tournaments.index')" class="block">
                        <div class="bg-gray-50 rounded-lg p-2 lg:p-3 hover:bg-green-50 transition-all">
                            <div class="flex items-center space-x-2 lg:space-x-3">
                                <div class="w-4 lg:w-5 flex justify-center">
                                    <i class="fas fa-trophy text-green-600 text-sm lg:text-lg"></i>
                                </div>
                                <span class="text-gray-900 font-medium text-sm lg:text-base">My Tournaments</span>
                            </div>
                        </div>
                    </Link>
                    <Link :href="route('tournaments.create')" class="block">
                        <div class="bg-gray-50 rounded-lg p-2 lg:p-3 hover:bg-green-50 transition-all">
                            <div class="flex items-center space-x-2 lg:space-x-3">
                                <div class="w-4 lg:w-5 flex justify-center">
                                    <i class="fas fa-plus text-green-600 text-sm lg:text-lg"></i>
                                </div>
                                <span class="text-gray-900 font-medium text-sm lg:text-base">Create Tournament</span>
                            </div>
                        </div>
                    </Link>
                    <Link :href="route('tournaments.join-form')" class="block">
                        <div class="bg-gray-50 rounded-lg p-2 lg:p-3 hover:bg-green-50 transition-all">
                            <div class="flex items-center space-x-2 lg:space-x-3">
                                <div class="w-4 lg:w-5 flex justify-center">
                                    <i class="fas fa-users text-green-600 text-sm lg:text-lg"></i>
                                </div>
                                <span class="text-gray-900 font-medium text-sm lg:text-base">Join Tournament</span>
                            </div>
                        </div>
                    </Link>
                    <Link :href="route('schedule.index')" class="block">
                        <div class="bg-gray-50 rounded-lg p-2 lg:p-3 hover:bg-green-50 transition-all">
                            <div class="flex items-center space-x-2 lg:space-x-3">
                                <div class="w-4 lg:w-5 flex justify-center">
                                    <i class="fas fa-calendar text-green-600 text-sm lg:text-lg"></i>
                                </div>
                                <span class="text-gray-900 font-medium text-sm lg:text-base">Premier League Schedule</span>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Game Rules Card -->
            <div class="bg-white rounded-xl p-4 lg:p-6 border border-green-200 shadow-md">
                <div class="flex items-center justify-between mb-3 lg:mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">How to Play</h3>
                    <div class="w-8 h-8 lg:w-10 lg:h-10 bg-green-600 rounded-lg flex items-center justify-center shadow-md">
                        <i class="fas fa-info-circle text-white text-sm lg:text-lg"></i>
                    </div>
                </div>
                <div class="text-gray-600 text-sm space-y-2 lg:space-y-3">
                    <div class="flex items-start">
                        <i class="fas fa-futbol text-green-600 mt-1 mr-2 lg:mr-3 flex-shrink-0 text-xs lg:text-sm"></i>
                        <span>Pick one Premier League team per gameweek</span>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-chart-line text-blue-600 mt-1 mr-2 lg:mr-3 flex-shrink-0 text-xs lg:text-sm"></i>
                        <span>Win = 3 points, Draw = 1 point, Loss = 0 points</span>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-trophy text-green-600 mt-1 mr-2 lg:mr-3 flex-shrink-0 text-xs lg:text-sm"></i>
                        <span>Full Season: pick each team twice (home & away)</span>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-user-check text-orange-600 mt-1 mr-2 lg:mr-3 flex-shrink-0 text-xs lg:text-sm"></i>
                        <span>Half Season or Custom ≤20 weeks: pick each team once</span>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-crown text-yellow-500 mt-1 mr-2 lg:mr-3 flex-shrink-0 text-xs lg:text-sm"></i>
                        <span>Highest total score at the end wins</span>
                    </div>
                </div>
            </div>

            <!-- Statistics Card -->
            <div class="bg-white rounded-xl p-4 lg:p-6 border border-green-200 shadow-md">
                <div class="flex items-center justify-between mb-3 lg:mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Your Stats</h3>
                    <div class="w-8 h-8 lg:w-10 lg:h-10 bg-green-600 rounded-lg flex items-center justify-center shadow-md">
                        <i class="fas fa-chart-bar text-white text-sm lg:text-lg"></i>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 lg:gap-4">
                    <!-- dynamic stats -->
                    <div class="bg-gray-50 rounded-lg p-3 lg:p-4 text-center border border-gray-200">
                        <div class="text-xl lg:text-2xl font-bold text-green-600">{{ statsSafe.tournaments }}</div>
                        <div class="text-gray-600 text-xs lg:text-sm">Tournaments</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3 lg:p-4 text-center border border-gray-200">
                        <div class="text-xl lg:text-2xl font-bold text-blue-600">{{ statsSafe.wins }}</div>
                        <div class="text-gray-600 text-xs lg:text-sm">Wins</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3 lg:p-4 text-center border border-gray-200">
                        <div class="text-xl lg:text-2xl font-bold text-yellow-600">{{ statsSafe.total_points }}</div>
                        <div class="text-gray-600 text-xs lg:text-sm">Total Points</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3 lg:p-4 text-center border border-gray-200">
                        <div class="text-xl lg:text-2xl font-bold text-purple-600">{{ statsSafe.win_rate }}</div>
                        <div class="text-gray-600 text-xs lg:text-sm">Win Rate</div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Fixtures Card -->
            <div class="bg-white rounded-xl p-4 lg:p-6 border border-green-200 shadow-md">
                <div class="flex items-center justify-between mb-3 lg:mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Upcoming Fixtures</h3>
                    <div class="w-8 h-8 lg:w-10 lg:h-10 bg-green-600 rounded-lg flex items-center justify-center shadow-md">
                        <i class="fas fa-futbol text-white text-sm lg:text-lg"></i>
                    </div>
                </div>
                <div class="space-y-2 lg:space-y-3">
                    <div v-for="(fixture, idx) in props.upcomingFixtures" :key="idx" class="bg-gray-50 rounded-lg p-3 lg:p-4">
                        <div class="text-gray-900 text-sm font-medium break-words">
                            {{ fixture.home }} vs {{ fixture.away }}
                        </div>
                        <div class="text-green-600 text-xs">
                            {{ fixture.when }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Favorite Team Fixtures Card -->
            <div class="bg-white rounded-xl p-4 lg:p-6 border border-green-200 shadow-md">
                <div class="flex items-center justify-between mb-3 lg:mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Your Team Fixtures</h3>
                    <div class="w-8 h-8 lg:w-10 lg:h-10 bg-green-600 rounded-lg flex items-center justify-center shadow-md">
                        <i class="fas fa-star text-white text-sm lg:text-lg"></i>
                    </div>
                </div>
                <div class="space-y-2 lg:space-y-3">
                    <template v-if="props.favoriteFixtures.length">
                        <div v-for="(fixture, idx) in props.favoriteFixtures" :key="`fav-${idx}`" class="bg-gray-50 rounded-lg p-3 lg:p-4">
                            <div class="text-gray-900 text-sm font-medium break-words">
                                {{ fixture.home }} vs {{ fixture.away }}
                            </div>
                            <div class="text-green-600 text-xs">
                                {{ fixture.when }}
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <div class="text-gray-500 text-sm italic">
                            No upcoming fixtures for your favorite team. Please set a favorite team in your profile.
                        </div>
                    </template>
                </div>
            </div>

            <!-- Premier League Standings Card -->
            <div class="bg-white rounded-xl p-4 lg:p-6 border border-green-200 shadow-md md:col-span-2 lg:col-span-3">
                <div class="flex items-center justify-between mb-3 lg:mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Premier League Table</h3>
                    <div class="w-8 h-8 lg:w-10 lg:h-10 bg-green-600 rounded-lg flex items-center justify-center shadow-md">
                        <i class="fas fa-trophy text-white text-sm lg:text-lg"></i>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-500 uppercase border-b">
                                <th class="text-left py-2 px-1">#</th>
                                <th class="text-left py-2 px-2">Team</th>
                                <th class="text-center py-2 px-1">P</th>
                                <th class="text-center py-2 px-1">W</th>
                                <th class="text-center py-2 px-1">D</th>
                                <th class="text-center py-2 px-1">L</th>
                                <th class="text-center py-2 px-1 hidden sm:table-cell">GF</th>
                                <th class="text-center py-2 px-1 hidden sm:table-cell">GA</th>
                                <th class="text-center py-2 px-1">GD</th>
                                <th class="text-center py-2 px-1 font-semibold">Pts</th>
                                <th class="text-center py-2 px-1">Form</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="team in props.standings" :key="team.position" 
                                class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-2 px-1 font-semibold text-gray-600">{{ team.position }}</td>
                                <td class="py-2 px-2 font-medium">
                                    <Link :href="route('schedule.team', team.team_id)" 
                                          class="text-gray-900 hover:text-green-600 transition-colors">
                                        <span class="hidden sm:inline">{{ team.team }}</span>
                                        <span class="sm:hidden">{{ team.team_short }}</span>
                                    </Link>
                                </td>
                                <td class="py-2 px-1 text-center text-gray-600">{{ team.played }}</td>
                                <td class="py-2 px-1 text-center text-green-600 font-medium">{{ team.wins }}</td>
                                <td class="py-2 px-1 text-center text-yellow-600">{{ team.draws }}</td>
                                <td class="py-2 px-1 text-center text-red-600">{{ team.losses }}</td>
                                <td class="py-2 px-1 text-center text-gray-600 hidden sm:table-cell">{{ team.goals_for }}</td>
                                <td class="py-2 px-1 text-center text-gray-600 hidden sm:table-cell">{{ team.goals_against }}</td>
                                <td class="py-2 px-1 text-center" :class="team.goal_difference >= 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ team.goal_difference >= 0 ? '+' : '' }}{{ team.goal_difference }}
                                </td>
                                <td class="py-2 px-1 text-center font-bold text-green-600">{{ team.points }}</td>
                                <td class="py-2 px-1 text-center">
                                    <div class="flex justify-center space-x-0.5" v-if="team.form">
                                        <div v-for="(result, index) in team.form.slice(0, 5)" :key="index" 
                                             class="w-3 h-3 sm:w-4 sm:h-4 rounded text-xs font-bold flex items-center justify-center text-white"
                                             :class="getFormClass(result)">
                                            <span class="hidden sm:inline">{{ result || '' }}</span>
                                            <span class="sm:hidden text-xs leading-none">{{ result ? result.charAt(0) : '' }}</span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-2 lg:mt-3 text-center">
                    <Link :href="route('schedule.standings')" class="text-green-600 hover:text-green-700 text-xs font-medium">
                        View Full Table & Fixtures →
                    </Link>
                </div>
            </div>

            <!-- end grid of cards -->
        </div>
    </TournamentLayout>
</template>
