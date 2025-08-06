<script setup>
import TournamentLayout from '@/Layouts/TournamentLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
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
                <div class="text-gray-600 text-sm space-y-2">
                    <div class="flex items-start space-x-2">
                        <span class="font-semibold text-green-600">1.</span>
                        <span>Pick one Premier League team per game week</span>
                    </div>
                    <div class="flex items-start space-x-2">
                        <span class="font-semibold text-green-600">2.</span>
                        <span>Win = 3pts, Draw = 1pt, Loss = 0pts</span>
                    </div>
                    <div class="flex items-start space-x-2">
                        <span class="font-semibold text-green-600">3.</span>
                        <span>Once picked, that team is eliminated</span>
                    </div>
                    <div class="flex items-start space-x-2">
                        <span class="font-semibold text-green-600">4.</span>
                        <span>Highest score after 20 weeks wins!</span>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Card -->
            <div class="bg-white rounded-xl p-6 border border-green-200 shadow-md">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
                    <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center shadow-md">
                        <i class="fas fa-clock text-white text-lg"></i>
                    </div>
                </div>
                <div class="text-gray-600 text-sm space-y-3">
                    <div class="flex items-center space-x-3">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span>Tournament "Premier League 2024" started</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                        <span>You joined "Champions League Challenge"</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span>Your pick for Arsenal won 3 points</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                        <span>New achievement unlocked: "First Win"</span>
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
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">12</div>
                        <div class="text-gray-500 text-sm">Tournaments</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">8</div>
                        <div class="text-gray-500 text-sm">Wins</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-600">156</div>
                        <div class="text-gray-500 text-sm">Total Points</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">67%</div>
                        <div class="text-gray-500 text-sm">Win Rate</div>
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
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                        <div class="flex items-center space-x-2">
                            <span class="text-gray-900 text-sm">Arsenal</span>
                            <span class="text-gray-500 text-xs">vs</span>
                            <span class="text-gray-900 text-sm">Chelsea</span>
                        </div>
                        <span class="text-green-600 text-xs">Tomorrow</span>
                    </div>
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                        <div class="flex items-center space-x-2">
                            <span class="text-gray-900 text-sm">Liverpool</span>
                            <span class="text-gray-500 text-xs">vs</span>
                            <span class="text-gray-900 text-sm">Man City</span>
                        </div>
                        <span class="text-green-600 text-xs">Saturday</span>
                    </div>
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                        <div class="flex items-center space-x-2">
                            <span class="text-gray-900 text-sm">Man United</span>
                            <span class="text-gray-500 text-xs">vs</span>
                            <span class="text-gray-900 text-sm">Tottenham</span>
                        </div>
                        <span class="text-green-600 text-xs">Sunday</span>
                    </div>
                </div>
            </div>

            <!-- Leaderboard Preview Card -->
            <div class="bg-white rounded-xl p-6 border border-green-200 shadow-md">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Top Players</h3>
                    <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center shadow-md">
                        <i class="fas fa-crown text-white text-lg"></i>
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                        <div class="flex items-center space-x-2">
                            <span class="text-yellow-600 text-sm font-bold">1</span>
                            <span class="text-gray-900 text-sm">John Smith</span>
                        </div>
                        <span class="text-green-600 text-sm font-bold">45 pts</span>
                    </div>
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                        <div class="flex items-center space-x-2">
                            <span class="text-gray-400 text-sm font-bold">2</span>
                            <span class="text-gray-900 text-sm">Sarah Johnson</span>
                        </div>
                        <span class="text-green-600 text-sm font-bold">42 pts</span>
                    </div>
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                        <div class="flex items-center space-x-2">
                            <span class="text-yellow-600 text-sm font-bold">3</span>
                            <span class="text-gray-900 text-sm">Mike Wilson</span>
                        </div>
                        <span class="text-green-600 text-sm font-bold">38 pts</span>
                    </div>
                </div>
            </div>
        </div>
    </TournamentLayout>
</template>
