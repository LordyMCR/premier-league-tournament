<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import AppHeader from '@/Components/AppHeader.vue';
import { Link } from '@inertiajs/vue3';

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
    <Head title="PL Tournament - Premier League Predictions Game | Join Private Tournaments">
        <meta name="description" content="PL Tournament - The ultimate Premier League predictions game. Create private tournaments, make weekly team picks, and compete with friends. Simple rules, strategic gameplay." />
        <meta name="keywords" content="PL Tournament, Premier League predictions, football predictions, soccer picks, tournament game, Premier League game, football tournament" />
        <meta name="author" content="PL Tournament" />
        
        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://www.pl-tournament.com/" />
        <meta property="og:title" content="PL Tournament - Premier League Predictions Game" />
        <meta property="og:description" content="The ultimate Premier League predictions game. Create private tournaments, make weekly team picks, and compete with friends." />
        <meta property="og:image" content="https://www.pl-tournament.com/favicon.ico" />

        <!-- Twitter -->
        <meta property="twitter:card" content="summary_large_image" />
        <meta property="twitter:url" content="https://www.pl-tournament.com/" />
        <meta property="twitter:title" content="PL Tournament - Premier League Predictions Game" />
        <meta property="twitter:description" content="The ultimate Premier League predictions game. Create private tournaments, make weekly team picks, and compete with friends." />
        <meta property="twitter:image" content="https://www.pl-tournament.com/favicon.ico" />
        
        <!-- Structured Data -->
        <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "WebApplication",
          "name": "PL Tournament",
          "url": "https://www.pl-tournament.com",
          "description": "Premier League predictions game with private tournaments and weekly team picks",
          "applicationCategory": "Game",
          "operatingSystem": "Any",
          "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "USD"
          }
        }
        </script>
    </Head>
    <div class="min-h-screen bg-gradient-to-br from-green-50 via-white to-green-100 relative">
        <!-- Football pitch pattern overlay -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: linear-gradient(90deg, transparent 0%, transparent 49%, rgba(34, 197, 94, 0.1) 49%, rgba(34, 197, 94, 0.1) 51%, transparent 51%, transparent 100%); background-size: 100px 100px;"></div>
        </div>
        
        <!-- Shared Header -->
        <AppHeader />

        <!-- Hero Section -->
        <main class="relative z-10">
            <div class="relative overflow-hidden">
                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
                    <div class="text-center">
                        <!-- Hero Title -->
                        <h1 class="text-5xl md:text-7xl font-bold text-gray-900 mb-6 tracking-tight">
                            PL Tournament
                        </h1>
                        
                        <!-- Hero Subtitle -->
                        <p class="text-xl md:text-2xl text-gray-600 mb-8 max-w-3xl mx-auto leading-relaxed">
                            A simple, weekly Premier League picks game. Join or create a private tournament, 
                            make one team pick each gameweek before the deadline, and climb the leaderboard as 
                            results roll in. Once you use a team, you can’t pick them again.
                        </p>
                        
                        <!-- Hero Buttons -->
                        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                            <Link
                                :href="route('register')"
                                class="bg-green-600 hover:bg-green-700 text-white px-8 py-4 rounded-lg text-lg font-semibold transition-all transform hover:scale-105 shadow-lg"
                            >
                                Start Playing
                            </Link>
                            <Link
                                :href="route('login')"
                                class="bg-white border border-green-200 text-gray-700 px-8 py-4 rounded-lg text-lg font-semibold transition-all hover:bg-green-50"
                            >
                                Sign In
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- How It Works Section -->
            <section class="py-20 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-16">
                        <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">How It Works</h2>
                        <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                            Simple rules, strategic gameplay. Here's how to win.
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <!-- Step 1 -->
                        <div class="bg-white rounded-2xl p-8 border border-green-200 text-center shadow-lg hover:shadow-xl transition-all duration-300">
                            <div class="w-16 h-16 bg-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                                <span class="text-2xl font-bold text-white">1</span>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-4">Join or Create</h3>
                            <p class="text-gray-600 leading-relaxed">
                                Start a private tournament for friends or join one with a code. 
                                Your dashboard shows active and upcoming gameweeks.
                            </p>
                        </div>
                        
                        <!-- Step 2 -->
                        <div class="bg-white rounded-2xl p-8 border border-green-200 text-center shadow-lg hover:shadow-xl transition-all duration-300">
                            <div class="w-16 h-16 bg-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                                <span class="text-2xl font-bold text-white">2</span>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-4">Make Your Pick</h3>
                            <p class="text-gray-600 leading-relaxed">
                                Select one club per gameweek before the selection deadline. 
                                Picks lock at the deadline and you can’t reuse a club later.
                            </p>
                        </div>
                        
                        <!-- Step 3 -->
                        <div class="bg-white rounded-2xl p-8 border border-green-200 text-center shadow-lg hover:shadow-xl transition-all duration-300">
                            <div class="w-16 h-16 bg-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                                <span class="text-2xl font-bold text-white">3</span>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-4">Score & Win</h3>
                            <p class="text-gray-600 leading-relaxed">
                                Scoring follows match results: win 3, draw 1, loss 0. 
                                Points are added after games finish; highest total wins the tournament.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Features Section -->
            <section class="py-20 bg-green-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-16">
                        <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">Why PL Tournament?</h2>
                        <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                            Lightweight, private, and designed for weekly picks.
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Feature 1 -->
                        <div class="bg-white rounded-xl p-6 border border-green-200 text-center shadow-lg">
                            <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-trophy text-white text-lg"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Premier League Fixtures</h3>
                            <p class="text-gray-600 text-sm">Schedules and results power gameweeks and scoring</p>
                        </div>
                        
                        <!-- Feature 2 -->
                        <div class="bg-white rounded-xl p-6 border border-green-200 text-center shadow-lg">
                            <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-users text-white text-lg"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Private Tournaments</h3>
                            <p class="text-gray-600 text-sm">Create a tournament and invite others with a join code</p>
                        </div>
                        
                        <!-- Feature 3 -->
                        <div class="bg-white rounded-xl p-6 border border-green-200 text-center shadow-lg">
                            <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-chart-line text-white text-lg"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Automatic Scoring</h3>
                            <p class="text-gray-600 text-sm">Points update after matches finish; view picks history and standings</p>
                        </div>
                        
                        <!-- Feature 4 -->
                        <div class="bg-white rounded-xl p-6 border border-green-200 text-center shadow-lg">
                            <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-mobile-alt text-white text-lg"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Mobile Friendly</h3>
                            <p class="text-gray-600 text-sm">Optimised layouts for phones and tablets with a clean UI</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA Section -->
            <section class="py-20 bg-white">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <div class="bg-white rounded-2xl p-12 border border-green-200 shadow-xl">
                        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">Ready to Start?</h2>
                        <p class="text-xl text-gray-600 mb-8">
                            Be one of the first to join our exciting new Premier League prediction game.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <Link
                                :href="route('register')"
                                class="bg-green-600 hover:bg-green-700 text-white px-8 py-4 rounded-lg text-lg font-semibold transition-all transform hover:scale-105 shadow-lg"
                            >
                                Create Account
                            </Link>
                            <Link
                                :href="route('login')"
                                class="bg-white border border-green-200 text-gray-700 px-8 py-4 rounded-lg text-lg font-semibold transition-all hover:bg-green-50"
                            >
                                Sign In
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
