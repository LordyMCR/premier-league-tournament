<script setup>
import { Head, Link } from '@inertiajs/vue3';
import TournamentLayout from '@/Layouts/TournamentLayout.vue';

defineProps({
    myTournaments: Array,
    createdTournaments: Array,
});

const getStatusBadge = (status) => {
    const classes = {
        pending: 'bg-yellow-500/20 text-yellow-200 border-yellow-500/30',
        active: 'bg-green-500/20 text-green-200 border-green-500/30',
        completed: 'bg-gray-500/20 text-gray-200 border-gray-500/30'
    };
    return classes[status] || 'bg-gray-500/20 text-gray-200 border-gray-500/30';
};
</script>

<template>
    <Head title="My Tournaments" />

    <TournamentLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-white">
                        My Tournaments
                    </h2>
                    <p class="text-white/70 mt-2">
                        Manage your Premier League prediction competitions
                    </p>
                </div>
                <div class="flex space-x-3">
                    <Link :href="route('tournaments.join-form')">
                        <button class="inline-flex items-center px-4 py-2 bg-white/20 border border-white/30 rounded-lg text-white hover:bg-white/30 transition-all">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 515.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 919.288 0M15 7a3 3 0 11-6 0 3 3 0 616 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            Join Tournament
                        </button>
                    </Link>
                    <Link :href="route('tournaments.create')">
                        <button class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-emerald-500 to-green-600 rounded-lg text-white hover:from-emerald-600 hover:to-green-700 transition-all">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Create Tournament
                        </button>
                    </Link>
                </div>
            </div>
        </template>

        <!-- My Tournaments -->
        <div class="space-y-8">
            <div>
                <h3 class="text-xl font-semibold text-white mb-6">Tournaments I'm In</h3>
                
                <div v-if="myTournaments.length === 0" class="bg-white/10 backdrop-blur-md rounded-xl p-8 border border-white/20 text-center">
                    <svg class="mx-auto h-16 w-16 text-white/40 mb-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 515.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 919.288 0M15 7a3 3 0 11-6 0 3 3 0 616 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h4 class="text-xl font-semibold text-white mb-3">No tournaments yet!</h4>
                    <p class="text-white/70 mb-6 max-w-md mx-auto">
                        Join an existing tournament or create your own to get started with Premier League predictions.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <Link :href="route('tournaments.join-form')">
                            <button class="inline-flex items-center px-4 py-2 bg-white/20 border border-white/30 rounded-lg text-white hover:bg-white/30 transition-all">
                                Join Tournament
                            </button>
                        </Link>
                        <Link :href="route('tournaments.create')">
                            <button class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-emerald-500 to-green-600 rounded-lg text-white hover:from-emerald-600 hover:to-green-700 transition-all">
                                Create Tournament
                            </button>
                        </Link>
                    </div>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div 
                        v-for="tournament in myTournaments" 
                        :key="tournament.id"
                        class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20 hover:bg-white/15 transition-all"
                    >
                        <div class="flex justify-between items-start mb-4">
                            <h4 class="text-lg font-semibold text-white">{{ tournament.name }}</h4>
                            <span 
                                :class="getStatusBadge(tournament.status)"
                                class="px-3 py-1 text-xs font-medium rounded-full border"
                            >
                                {{ tournament.status.charAt(0).toUpperCase() + tournament.status.slice(1) }}
                            </span>
                        </div>
                        
                        <p class="text-white/70 text-sm mb-4">{{ tournament.description || 'No description' }}</p>
                        
                        <div class="text-sm text-white/60 mb-6 space-y-2">
                            <div class="flex justify-between">
                                <span>Creator:</span>
                                <span class="text-white/80">{{ tournament.creator.name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Participants:</span>
                                <span class="text-white/80">{{ tournament.participants_count }}/{{ tournament.max_participants }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Game Week:</span>
                                <span class="text-white/80">{{ tournament.current_game_week }}/20</span>
                            </div>
                        </div>

                        <Link :href="route('tournaments.show', tournament.id)" class="block w-full">
                            <button class="w-full bg-gradient-to-r from-emerald-500 to-green-600 text-white py-2 px-4 rounded-lg hover:from-emerald-600 hover:to-green-700 transition-all font-medium">
                                View Tournament
                            </button>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Created Tournaments -->
            <div v-if="createdTournaments.length > 0">
                <h3 class="text-xl font-semibold text-white mb-6">Tournaments I Created</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div 
                        v-for="tournament in createdTournaments" 
                        :key="tournament.id"
                        class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-emerald-500/30 hover:bg-white/15 transition-all relative"
                    >
                        <div class="absolute top-4 right-4">
                            <div class="w-3 h-3 bg-emerald-500 rounded-full animate-pulse"></div>
                        </div>
                        
                        <div class="flex justify-between items-start mb-4 pr-6">
                            <h4 class="text-lg font-semibold text-white">{{ tournament.name }}</h4>
                            <span 
                                :class="getStatusBadge(tournament.status)"
                                class="px-3 py-1 text-xs font-medium rounded-full border"
                            >
                                {{ tournament.status.charAt(0).toUpperCase() + tournament.status.slice(1) }}
                            </span>
                        </div>
                        
                        <p class="text-white/70 text-sm mb-4">{{ tournament.description || 'No description' }}</p>
                        
                        <div class="text-sm text-white/60 mb-6 space-y-2">
                            <div class="flex justify-between">
                                <span>Join Code:</span>
                                <span class="font-mono font-bold text-emerald-400">{{ tournament.join_code }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Participants:</span>
                                <span class="text-white/80">{{ tournament.participants_count }}/{{ tournament.max_participants }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Game Week:</span>
                                <span class="text-white/80">{{ tournament.current_game_week }}/20</span>
                            </div>
                        </div>

                        <div class="flex space-x-2">
                            <Link :href="route('tournaments.show', tournament.id)" class="flex-1">
                                <button class="w-full bg-gradient-to-r from-emerald-500 to-green-600 text-white py-2 px-3 rounded-lg hover:from-emerald-600 hover:to-green-700 transition-all text-sm font-medium">
                                    Manage
                                </button>
                            </Link>
                            <Link :href="route('tournaments.leaderboard', tournament.id)" class="flex-1">
                                <button class="w-full bg-white/20 border border-white/30 text-white py-2 px-3 rounded-lg hover:bg-white/30 transition-all text-sm font-medium">
                                    Leaderboard
                                </button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TournamentLayout>
</template> 