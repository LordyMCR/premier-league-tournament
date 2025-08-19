<template>
    <Head title="Premier League Table - PL Tournament" />
    
    <TournamentLayout>
        <div class="min-h-screen bg-gray-50 py-8">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Header -->
                <div class="bg-white rounded-xl p-6 mb-6 border border-green-200 shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Premier League Table</h1>
                            <p class="text-gray-600 mt-1">2025-26 Season Standings</p>
                        </div>
                        <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center shadow-md">
                            <i class="fas fa-trophy text-white text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <div class="bg-white rounded-xl mb-6 border border-green-200 shadow-md overflow-hidden">
                    <div class="flex border-b border-gray-200">
                        <Link :href="route('schedule.index')" 
                              class="flex-1 py-4 px-6 text-center font-medium text-gray-600 hover:text-green-600 hover:bg-gray-50 transition-colors">
                            <i class="fas fa-calendar mr-2"></i>
                            Fixtures
                        </Link>
                        <div class="flex-1 py-4 px-6 text-center font-medium text-green-600 bg-green-50 border-b-2 border-green-600">
                            <i class="fas fa-trophy mr-2"></i>
                            Table
                        </div>
                    </div>
                </div>

                <!-- Standings Table -->
                <div class="bg-white rounded-xl p-6 border border-green-200 shadow-md">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-500 uppercase border-b border-gray-200">
                                    <th class="text-left py-3 px-2 font-semibold">Pos</th>
                                    <th class="text-left py-3 px-4 font-semibold">Club</th>
                                    <th class="text-center py-3 px-2 font-semibold">Pl</th>
                                    <th class="text-center py-3 px-2 font-semibold">W</th>
                                    <th class="text-center py-3 px-2 font-semibold">D</th>
                                    <th class="text-center py-3 px-2 font-semibold">L</th>
                                    <th class="text-center py-3 px-2 font-semibold">GF</th>
                                    <th class="text-center py-3 px-2 font-semibold">GA</th>
                                    <th class="text-center py-3 px-2 font-semibold">GD</th>
                                    <th class="text-center py-3 px-2 font-semibold">Pts</th>
                                    <th class="text-center py-3 px-2 font-semibold hidden sm:table-cell">Form</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="team in standings" :key="team.position" 
                                    class="border-b border-gray-100 hover:bg-gray-50 transition-colors"
                                    :class="getPositionClass(team.position)">
                                    <td class="py-4 px-2">
                                        <div class="flex items-center">
                                            <span class="font-bold text-gray-700">{{ team.position }}</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex items-center">
                                            <Link :href="route('schedule.team', team.team_id)" 
                                                  class="font-semibold text-gray-900 hover:text-green-600 transition-colors">
                                                <span class="hidden sm:inline">{{ team.team }}</span>
                                                <span class="sm:hidden">{{ team.team_short }}</span>
                                            </Link>
                                        </div>
                                    </td>
                                    <td class="py-4 px-2 text-center text-gray-600 font-medium">{{ team.played }}</td>
                                    <td class="py-4 px-2 text-center text-green-600 font-semibold">{{ team.wins }}</td>
                                    <td class="py-4 px-2 text-center text-yellow-600 font-semibold">{{ team.draws }}</td>
                                    <td class="py-4 px-2 text-center text-red-600 font-semibold">{{ team.losses }}</td>
                                    <td class="py-4 px-2 text-center text-gray-700 font-medium">{{ team.goals_for }}</td>
                                    <td class="py-4 px-2 text-center text-gray-700 font-medium">{{ team.goals_against }}</td>
                                    <td class="py-4 px-2 text-center font-semibold" :class="team.goal_difference >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ team.goal_difference >= 0 ? '+' : '' }}{{ team.goal_difference }}
                                    </td>
                                    <td class="py-4 px-2 text-center font-bold text-lg text-gray-900">{{ team.points }}</td>
                                    <td class="py-4 px-2 text-center hidden sm:table-cell">
                                        <div class="flex justify-center space-x-1">
                                            <div v-for="(result, index) in team.form" :key="index" 
                                                 class="w-6 h-6 rounded text-xs font-bold flex items-center justify-center text-white"
                                                 :class="getFormClass(result)">
                                                {{ result || '' }}
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Legend -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-blue-500 rounded mr-2"></div>
                                <span class="text-gray-600">Champions League</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-orange-500 rounded mr-2"></div>
                                <span class="text-gray-600">Europa League</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-green-500 rounded mr-2"></div>
                                <span class="text-gray-600">Conference League</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-red-500 rounded mr-2"></div>
                                <span class="text-gray-600">Relegation</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </TournamentLayout>
</template>

<script setup>
import TournamentLayout from '@/Layouts/TournamentLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    standings: Array,
});

const getPositionClass = (position) => {
    if (position <= 4) return 'border-l-4 border-l-blue-500';
    if (position === 5 || position === 6) return 'border-l-4 border-l-orange-500';
    if (position === 7) return 'border-l-4 border-l-green-500';
    if (position >= 18) return 'border-l-4 border-l-red-500';
    return '';
};

const getFormClass = (result) => {
    if (result === 'W') return 'bg-green-500';
    if (result === 'D') return 'bg-yellow-500';
    if (result === 'L') return 'bg-red-500';
    return 'bg-gray-200'; // No game played yet
};
</script>
