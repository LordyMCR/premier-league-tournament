<script setup>
import TournamentLayout from '@/Layouts/TournamentLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  stats: Object,
  upcomingFixtures: Array,
  favoriteFixtures: Array,
});

// Provide robust defaults so layout never looks odd for new accounts
const statsSafe = computed(() => ({
  tournaments: props.stats?.tournaments ?? 0,
  wins: props.stats?.wins ?? 0,
  total_points: props.stats?.total_points ?? 0,
  win_rate: props.stats?.win_rate ?? '0%',
}));
</script>

<template>
    <Head title="Dashboard" />

    <TournamentLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-gray-900">
                Welcome to Tournament Central
            </h2>
            <p class="text-gray-600 mt-2">
                Your hub for Premier League tournament action
            </p>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Quick Actions Card -->
            <div class="bg-white rounded-xl p-6 border border-green-200 hover:shadow-lg transition-all shadow-md">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                    <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center shadow-md">
                        <i class="fas fa-bolt text-white text-lg"></i>
                    </div>
                </div>
                <div class="space-y-3">
                    <Link :href="route('tournaments.index')" class="block">
                        <div class="bg-gray-50 rounded-lg p-3 hover:bg-green-50 transition-all">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-trophy text-green-600 text-lg"></i>
                                <span class="text-gray-900 font-medium">My Tournaments</span>
                            </div>
                        </div>
                    </Link>
                    <Link :href="route('tournaments.create')" class="block">
                        <div class="bg-gray-50 rounded-lg p-3 hover:bg-green-50 transition-all">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-plus text-green-600 text-lg"></i>
                                <span class="text-gray-900 font-medium">Create Tournament</span>
                            </div>
                        </div>
                    </Link>
                    <Link :href="route('tournaments.join-form')" class="block">
                        <div class="bg-gray-50 rounded-lg p-3 hover:bg-green-50 transition-all">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-users text-green-600 text-lg"></i>
                                <span class="text-gray-900 font-medium">Join Tournament</span>
                            </div>
                        </div>
                    </Link>
                    <Link :href="route('schedule.index')" class="block">
                        <div class="bg-gray-50 rounded-lg p-3 hover:bg-green-50 transition-all">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-calendar text-green-600 text-lg"></i>
                                <span class="text-gray-900 font-medium">Premier League Schedule</span>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Game Rules Card -->
            <div class="bg-white rounded-xl p-6 border border-green-200 shadow-md">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">How to Play</h3>
                    <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center shadow-md">
                        <i class="fas fa-info-circle text-white text-lg"></i>
                    </div>
                </div>
                <div class="text-gray-600 text-sm space-y-3">
                    <div class="flex items-start">
                        <i class="fas fa-futbol text-green-600 mt-1 mr-3 flex-shrink-0"></i>
                        <span>Pick one Premier League team per gameweek</span>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-chart-line text-blue-600 mt-1 mr-3 flex-shrink-0"></i>
                        <span>Win = 3 points, Draw = 1 point, Loss = 0 points</span>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-trophy text-green-600 mt-1 mr-3 flex-shrink-0"></i>
                        <span>Full Season: pick each team twice (home & away)</span>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-user-check text-orange-600 mt-1 mr-3 flex-shrink-0"></i>
                        <span>Half Season or Custom ≤20 weeks: pick each team once</span>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-crown text-yellow-500 mt-1 mr-3 flex-shrink-0"></i>
                        <span>Highest total score at the end wins</span>
                    </div>
                </div>
            </div>

            <!-- Statistics Card -->
            <div class="bg-white rounded-xl p-6 border border-green-200 shadow-md">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Your Stats</h3>
                    <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center shadow-md">
                        <i class="fas fa-chart-bar text-white text-lg"></i>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <!-- dynamic stats -->
                    <div class="bg-gray-50 rounded-lg p-4 text-center border border-gray-200">
                        <div class="text-2xl font-bold text-green-600">{{ statsSafe.tournaments }}</div>
                        <div class="text-gray-600 text-sm">Tournaments</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 text-center border border-gray-200">
                        <div class="text-2xl font-bold text-blue-600">{{ statsSafe.wins }}</div>
                        <div class="text-gray-600 text-sm">Wins</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 text-center border border-gray-200">
                        <div class="text-2xl font-bold text-yellow-600">{{ statsSafe.total_points }}</div>
                        <div class="text-gray-600 text-sm">Total Points</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 text-center border border-gray-200">
                        <div class="text-2xl font-bold text-purple-600">{{ statsSafe.win_rate }}</div>
                        <div class="text-gray-600 text-sm">Win Rate</div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Fixtures Card -->
            <div class="bg-white rounded-xl p-6 border border-green-200 shadow-md">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Upcoming Fixtures</h3>
                    <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center shadow-md">
                        <i class="fas fa-futbol text-white text-lg"></i>
                    </div>
                </div>
                <div class="space-y-3">
                    <div v-for="(fixture, idx) in props.upcomingFixtures" :key="idx" class="bg-gray-50 rounded-lg p-4">
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
            <div class="bg-white rounded-xl p-6 border border-green-200 shadow-md">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Your Team Fixtures</h3>
                    <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center shadow-md">
                        <i class="fas fa-star text-white text-lg"></i>
                    </div>
                </div>
                <div class="space-y-3">
                    <template v-if="props.favoriteFixtures.length">
                        <div v-for="(fixture, idx) in props.favoriteFixtures" :key="`fav-${idx}`" class="bg-gray-50 rounded-lg p-4">
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

            <!-- end grid of cards -->
        </div>
    </TournamentLayout>
</template>
