<script setup>
import TournamentLayout from '@/Layouts/TournamentLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Tournament Dashboard" />

    <TournamentLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-white">
                Welcome to Tournament Central
            </h2>
            <p class="text-white/70 mt-2">
                Your hub for Premier League tournament action
            </p>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Quick Actions Card -->
            <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20 hover:bg-white/15 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-white">Quick Actions</h3>
                    <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-green-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                </div>
                <div class="space-y-3">
                    <Link :href="route('tournaments.index')" class="block">
                        <div class="bg-white/10 rounded-lg p-3 hover:bg-white/20 transition-all">
                            <div class="flex items-center space-x-3">
                                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                                <span class="text-white font-medium">My Tournaments</span>
                            </div>
                        </div>
                    </Link>
                    <Link :href="route('tournaments.create')" class="block">
                        <div class="bg-white/10 rounded-lg p-3 hover:bg-white/20 transition-all">
                            <div class="flex items-center space-x-3">
                                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                <span class="text-white font-medium">Create Tournament</span>
                            </div>
                        </div>
                    </Link>
                    <Link :href="route('tournaments.join-form')" class="block">
                        <div class="bg-white/10 rounded-lg p-3 hover:bg-white/20 transition-all">
                            <div class="flex items-center space-x-3">
                                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                <span class="text-white font-medium">Join Tournament</span>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Game Rules Card -->
            <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-white">How to Play</h3>
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                </div>
                <div class="text-white/80 text-sm space-y-2">
                    <div class="flex items-start space-x-2">
                        <span class="font-semibold text-emerald-400">1.</span>
                        <span>Pick one Premier League team per game week</span>
                    </div>
                    <div class="flex items-start space-x-2">
                        <span class="font-semibold text-emerald-400">2.</span>
                        <span>Win = 3pts, Draw = 1pt, Loss = 0pts</span>
                    </div>
                    <div class="flex items-start space-x-2">
                        <span class="font-semibold text-emerald-400">3.</span>
                        <span>Once picked, that team is eliminated</span>
                    </div>
                    <div class="flex items-start space-x-2">
                        <span class="font-semibold text-emerald-400">4.</span>
                        <span>Highest score after 20 weeks wins!</span>
                    </div>
                </div>
            </div>

            <!-- Stats Card -->
            <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-white">Your Stats</h3>
                    <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4"></path>
                        </svg>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-white/70">Tournaments</span>
                        <span class="text-white font-semibold">0</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-white/70">Total Points</span>
                        <span class="text-white font-semibold">0</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-white/70">Best Finish</span>
                        <span class="text-white font-semibold">-</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Get Started Section -->
        <div class="mt-8 bg-gradient-to-r from-emerald-500/20 to-green-600/20 backdrop-blur-md rounded-xl p-8 border border-emerald-500/30 text-center">
            <h2 class="text-2xl font-bold text-white mb-4">Ready to Start Your Tournament Journey?</h2>
            <p class="text-white/80 mb-6 max-w-2xl mx-auto">
                Join the ultimate Premier League prediction game where strategy meets football knowledge. 
                Pick teams wisely – you only get one shot with each!
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <Link :href="route('tournaments.create')" class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-emerald-500 to-green-600 text-white font-semibold rounded-lg hover:from-emerald-600 hover:to-green-700 transition-all transform hover:scale-105">
                    🏆 Create Your Tournament
                </Link>
                <Link :href="route('tournaments.join-form')" class="inline-flex items-center justify-center px-6 py-3 bg-white/20 text-white font-semibold rounded-lg hover:bg-white/30 transition-all border border-white/30">
                    🎯 Join Existing Tournament
                </Link>
            </div>
        </div>
    </TournamentLayout>
</template>
