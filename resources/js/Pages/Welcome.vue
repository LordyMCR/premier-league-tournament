<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import AppHeader from '@/Components/AppHeader.vue';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});

const page = usePage();
</script>

<template>
    <Head title="Premier League Tournament Host" />
    <div class="min-h-screen bg-gradient-to-br from-purple-900 via-blue-900 to-indigo-900">
        <!-- Shared Header -->
        <AppHeader />

        <!-- Hero Section -->
        <main>
            <div class="relative overflow-hidden">
                <!-- Background Pattern -->
                <div class="absolute inset-0 opacity-20"></div>
                
                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
                    <div class="text-center">
                        <!-- Hero Title -->
                        <h1 class="text-5xl md:text-7xl font-bold text-white mb-6">
                            <span class="bg-gradient-to-r from-yellow-400 via-red-500 to-purple-600 bg-clip-text text-transparent">
                                Premier League
                            </span>
                            <br>
                            <span class="text-white">Tournament Host</span>
                        </h1>
                        
                        <!-- Hero Subtitle -->
                        <p class="text-xl md:text-2xl text-white/80 mb-8 max-w-3xl mx-auto leading-relaxed">
                            Create and compete in thrilling Premier League prediction tournaments. 
                            Pick your teams, climb the leaderboard, and prove you're the ultimate football predictor.
                        </p>
                        
                        <!-- Hero Buttons -->
                        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                            <a
                                href="#how-it-works"
                                class="bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white px-8 py-4 rounded-xl text-lg font-semibold transition-all transform hover:scale-105 shadow-2xl"
                            >
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                Start Playing
                            </a>
                            <a
                                href="#features"
                                class="bg-white/10 backdrop-blur-md border border-white/20 text-white px-8 py-4 rounded-xl text-lg font-semibold transition-all hover:bg-white/20"
                            >
                                Learn More
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- How It Works Section -->
            <section id="how-it-works" class="py-20">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-16">
                        <h2 class="text-4xl md:text-5xl font-bold text-white mb-4">How It Works</h2>
                        <p class="text-xl text-white/70 max-w-2xl mx-auto">
                            Simple rules, unlimited excitement. Here's how to dominate the leaderboard.
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <!-- Step 1 -->
                        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-8 border border-white/20 text-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-6">
                                <span class="text-2xl font-bold text-white">1</span>
                            </div>
                            <h3 class="text-2xl font-bold text-white mb-4">Pick Your Teams</h3>
                            <p class="text-white/70 leading-relaxed">
                                Each gameweek, select one Premier League team to back. 
                                Choose wisely - you can only use each team once per tournament!
                            </p>
                        </div>
                        
                        <!-- Step 2 -->
                        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-8 border border-white/20 text-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                                <span class="text-2xl font-bold text-white">2</span>
                            </div>
                            <h3 class="text-2xl font-bold text-white mb-4">Earn Points</h3>
                            <p class="text-white/70 leading-relaxed">
                                Win = 3 points, Draw = 1 point, Loss = 0 points. 
                                Strategic team selection is key to maximizing your score.
                            </p>
                        </div>
                        
                        <!-- Step 3 -->
                        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-8 border border-white/20 text-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-full flex items-center justify-center mx-auto mb-6">
                                <span class="text-2xl font-bold text-white">3</span>
                            </div>
                            <h3 class="text-2xl font-bold text-white mb-4">Climb the Leaderboard</h3>
                            <p class="text-white/70 leading-relaxed">
                                Compete with friends and football fans worldwide. 
                                Track your progress and aim for tournament glory!
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Features Section -->
            <section id="features" class="py-20 bg-white/5">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-16">
                        <h2 class="text-4xl md:text-5xl font-bold text-white mb-4">Tournament Features</h2>
                        <p class="text-xl text-white/70 max-w-2xl mx-auto">
                            Everything you need for the ultimate Premier League prediction experience.
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <!-- Feature 1 -->
                        <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">Private Tournaments</h3>
                            <p class="text-white/70">Create private tournaments for friends, family, or colleagues with custom join codes.</p>
                        </div>
                        
                        <!-- Feature 2 -->
                        <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                            <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-green-600 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">Live Leaderboards</h3>
                            <p class="text-white/70">Real-time rankings and detailed statistics to track your performance.</p>
                        </div>
                        
                        <!-- Feature 3 -->
                        <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                            <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">Season-Long</h3>
                            <p class="text-white/70">Full Premier League season tournaments spanning all 38 gameweeks.</p>
                        </div>
                        
                        <!-- Feature 4 -->
                        <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                            <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-pink-600 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">Strategic Gameplay</h3>
                            <p class="text-white/70">One team per gameweek rule adds strategy - save the best teams for crucial moments.</p>
                        </div>
                        
                        <!-- Feature 5 -->
                        <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                            <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">Easy to Use</h3>
                            <p class="text-white/70">Intuitive interface makes it simple to join tournaments and make your picks.</p>
                        </div>
                        
                        <!-- Feature 6 -->
                        <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                            <div class="w-12 h-12 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">Detailed Stats</h3>
                            <p class="text-white/70">Track your picks, view team performance, and analyze your tournament history.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA Section -->
            <section v-if="!page.props.auth.user" class="py-20">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <div class="bg-gradient-to-r from-emerald-500/20 to-blue-600/20 backdrop-blur-md rounded-3xl p-12 border border-white/20">
                        <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">
                            Ready to Start Your Tournament Journey?
                        </h2>
                        <p class="text-xl text-white/80 mb-8 max-w-2xl mx-auto">
                            Join thousands of football fans already competing in exciting Premier League prediction tournaments.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <a
                                href="/register"
                                class="bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white px-8 py-4 rounded-xl text-lg font-semibold transition-all transform hover:scale-105 shadow-2xl"
                            >
                                Create Account
                            </a>
                            <a
                                href="/login"
                                class="bg-white/10 backdrop-blur-md border border-white/20 text-white px-8 py-4 rounded-xl text-lg font-semibold transition-all hover:bg-white/20"
                            >
                                Sign In
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="py-12 border-t border-white/20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <div class="flex items-center justify-center space-x-2 mb-4">
                        <div class="w-8 h-8 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white">PL Tournament</h3>
                    </div>
                    <p class="text-white/60">
                        © 2025 Premier League Tournament Host. Create amazing football prediction tournaments.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
