<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});
</script>

<template>
    <Head title="Premier League Tournament Host" />
    <div class="min-h-screen bg-gradient-to-br from-purple-900 via-blue-900 to-indigo-900">
        <!-- Header -->
        <header class="bg-white/10 backdrop-blur-md border-b border-white/20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div class="flex items-center">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                            </div>
                            <h1 class="text-2xl font-bold text-white">PL Tournament</h1>
                        </div>
                    </div>
                    <nav v-if="canLogin" class="flex space-x-4">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('tournaments.index')"
                            class="text-white/90 hover:text-white px-3 py-2 rounded-md text-sm font-medium"
                        >
                            My Tournaments
                        </Link>
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="text-white/90 hover:text-white px-3 py-2 rounded-md text-sm font-medium"
                            >
                                Login
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="bg-gradient-to-r from-green-500 to-emerald-600 text-white hover:from-green-600 hover:to-emerald-700 px-4 py-2 rounded-md text-sm font-medium transition-all"
                            >
                                Join Now
                            </Link>
                        </template>
                    </nav>
                </div>
            </div>
                </header>

        <!-- Hero Section -->
        <main>
            <div class="relative">
                <div class="max-w-7xl mx-auto py-16 px-4 sm:py-24 sm:px-6 lg:px-8">
                    <div class="text-center">
                        <h1 class="text-4xl font-extrabold text-white sm:text-5xl md:text-6xl">
                            <span class="block">The Ultimate</span>
                            <span class="block text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-orange-500">
                                Premier League
                            </span>
                            <span class="block">Prediction Game</span>
                        </h1>
                        <p class="mt-3 max-w-md mx-auto text-base text-gray-300 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
                            Pick winning teams across 20 game weeks. But here's the twist: once you pick a team, they're gone forever. Strategy meets football in the most challenging prediction game.
                        </p>
                        
                        <!-- CTA Buttons -->
                        <div class="mt-5 max-w-4xl mx-auto sm:flex sm:justify-center md:mt-8">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 w-full">
                                <Link :href="route('tournaments.index')" class="rounded-md shadow-lg">
                                    <div class="w-full flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 md:py-4 md:text-lg transition-all transform hover:scale-105">
                                        🏆 My Tournaments
                            </div>
                                </Link>
                                <Link :href="route('tournaments.create')" class="rounded-md shadow-lg">
                                    <div class="w-full flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700 md:py-4 md:text-lg transition-all transform hover:scale-105">
                                        ⚽ Create Tournament
                                    </div>
                                </Link>
                                <Link :href="route('tournaments.join-form')" class="rounded-md shadow-lg">
                                    <div class="w-full flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 md:py-4 md:text-lg transition-all transform hover:scale-105">
                                        🎯 Join Tournament
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- How It Works Section -->
            <div class="py-16 bg-white/5 backdrop-blur-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="lg:text-center">
                        <h2 class="text-base text-yellow-400 font-semibold tracking-wide uppercase">How It Works</h2>
                        <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-white sm:text-4xl">
                            Simple Rules, Strategic Depth
                        </p>
                        <p class="mt-4 max-w-2xl text-xl text-gray-300 lg:mx-auto">
                            Think you know football? Prove it with strategy that goes beyond just picking winners.
                        </p>
                    </div>

                    <div class="mt-16">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                            <div class="text-center">
                                <div class="flex items-center justify-center h-16 w-16 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 text-white mx-auto mb-4">
                                    <span class="text-2xl font-bold">1</span>
                                </div>
                                <h3 class="text-lg font-semibold text-white mb-2">Pick Your Team</h3>
                                <p class="text-gray-300">Each game week, choose one Premier League team you think will win their match.</p>
                            </div>

                            <div class="text-center">
                                <div class="flex items-center justify-center h-16 w-16 rounded-full bg-gradient-to-r from-green-500 to-blue-600 text-white mx-auto mb-4">
                                    <span class="text-2xl font-bold">2</span>
                                </div>
                                <h3 class="text-lg font-semibold text-white mb-2">Earn Points</h3>
                                <p class="text-gray-300">Win = 3 points, Draw = 1 point, Loss = 0 points. Simple scoring, maximum tension.</p>
                            </div>

                            <div class="text-center">
                                <div class="flex items-center justify-center h-16 w-16 rounded-full bg-gradient-to-r from-yellow-500 to-red-600 text-white mx-auto mb-4">
                                    <span class="text-2xl font-bold">3</span>
                                </div>
                                <h3 class="text-lg font-semibold text-white mb-2">No Second Chances</h3>
                                <p class="text-gray-300">Once you pick a team, they're eliminated from your options. Choose wisely!</p>
                            </div>

                            <div class="text-center">
                                <div class="flex items-center justify-center h-16 w-16 rounded-full bg-gradient-to-r from-purple-500 to-pink-600 text-white mx-auto mb-4">
                                    <span class="text-2xl font-bold">4</span>
                                </div>
                                <h3 class="text-lg font-semibold text-white mb-2">Win the League</h3>
                                <p class="text-gray-300">After 20 game weeks, highest total points wins. Strategy beats luck.</p>
                            </div>
                        </div>
                    </div>
                </div>
                            </div>

            <!-- Strategy Section -->
            <div class="py-16 bg-gradient-to-r from-indigo-900 to-purple-900">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="lg:grid lg:grid-cols-2 lg:gap-8 lg:items-center">
                        <div>
                            <h2 class="text-3xl font-extrabold text-white sm:text-4xl">
                                More Than Just Predictions
                                </h2>
                            <p class="mt-3 text-lg text-gray-300">
                                This isn't your typical fantasy league. Every pick matters because every pick is final. Do you use Manchester City early for an "easy" win, or save them for a crucial later match?
                            </p>
                            <div class="mt-8 space-y-4">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <div class="flex items-center justify-center h-8 w-8 rounded-md bg-yellow-500 text-white">
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-lg font-medium text-white">
                                            Fixture Analysis Matters
                                        </h3>
                                        <p class="mt-1 text-gray-300">
                                            Study upcoming fixtures, form, injuries. When is the best time to use each team?
                                </p>
                            </div>
                                </div>
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <div class="flex items-center justify-center h-8 w-8 rounded-md bg-green-500 text-white">
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/>
                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-lg font-medium text-white">
                                            Psychological Warfare
                                        </h3>
                                        <p class="mt-1 text-gray-300">
                                            Watch opponents' picks. Did they waste Arsenal on a tough fixture? Exploit their mistakes.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-8 lg:mt-0">
                            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6 border border-white/20">
                                <h3 class="text-lg font-semibold text-white mb-4">Sample Tournament Progress</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-gray-300">GW1: Liverpool vs Bournemouth</span>
                                        <span class="text-green-400 font-semibold">✓ 3pts</span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-gray-300">GW2: Brighton vs Man Utd</span>
                                        <span class="text-yellow-400 font-semibold">≈ 1pt</span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-gray-300">GW3: Arsenal vs ...</span>
                                        <span class="text-gray-500">Available</span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-gray-500">Man City, Chelsea, Tottenham...</span>
                                        <span class="text-gray-500">Remaining</span>
                                    </div>
                                </div>
                                <div class="mt-4 pt-4 border-t border-white/20">
                                    <div class="flex justify-between items-center">
                                        <span class="text-white font-medium">Current Total:</span>
                                        <span class="text-yellow-400 font-bold text-lg">4 points</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="bg-gradient-to-r from-green-600 to-blue-600">
                <div class="max-w-2xl mx-auto text-center py-16 px-4 sm:py-20 sm:px-6 lg:px-8">
                    <h2 class="text-3xl font-extrabold text-white sm:text-4xl">
                        <span class="block">Ready to Test Your</span>
                        <span class="block">Football Knowledge?</span>
                    </h2>
                    <p class="mt-4 text-lg leading-6 text-green-100">
                        Join thousands of football fans competing in the most strategic prediction game. Create private leagues with friends or join public tournaments.
                    </p>
                    <Link
                        v-if="canRegister && !$page.props.auth.user"
                        :href="route('register')"
                        class="mt-8 w-full inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-green-600 bg-white hover:bg-green-50 sm:w-auto transform transition-all hover:scale-105"
                    >
                        🚀 Start Your Tournament Journey
                    </Link>
                    <Link
                        v-else-if="!$page.props.auth.user"
                        :href="route('login')"
                        class="mt-8 w-full inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-green-600 bg-white hover:bg-green-50 sm:w-auto transform transition-all hover:scale-105"
                    >
                        🏆 Enter Tournament Arena
                    </Link>
                    <Link
                        v-else
                        :href="route('tournaments.index')"
                        class="mt-8 w-full inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-green-600 bg-white hover:bg-green-50 sm:w-auto transform transition-all hover:scale-105"
                    >
                        🎯 View My Tournaments
                    </Link>
                        </div>
                    </div>
                </main>

        <!-- Footer -->
        <footer class="bg-gray-900">
            <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                <div class="text-center text-gray-400">
                    <p>&copy; 2024 Premier League Tournament Host. The ultimate strategy game for football fans.</p>
                    <p class="mt-2 text-sm text-gray-500">Not affiliated with the Premier League. All team names and logos are trademarks of their respective owners.</p>
                </div>
            </div>
        </footer>
    </div>
</template>
