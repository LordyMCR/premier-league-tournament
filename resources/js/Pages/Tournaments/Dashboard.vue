<script setup>
import { Head, Link } from '@inertiajs/vue3';
import TournamentLayout from '@/Layouts/TournamentLayout.vue';

defineProps({
    tournaments: Array,
    createdTournaments: Array,
});
</script>

<template>
    <Head title="My Tournaments - PL Tournament" />

    <TournamentLayout>
        <template #header>
            <div class="space-y-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">
                        My Tournaments
                    </h2>
                    <p class="text-gray-600 mt-2">
                        Manage your tournaments and track your progress
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <Link
                        :href="route('tournaments.join-form')"
                        class="bg-white border border-green-200 text-gray-700 px-4 py-3 rounded-lg font-medium transition-all hover:bg-green-50 text-center flex items-center justify-center"
                    >
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Join Tournament
                    </Link>
                    <Link
                        :href="route('tournaments.create')"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg font-medium transition-all shadow-md hover:shadow-lg text-center flex items-center justify-center"
                    >
                        <i class="fas fa-plus mr-2"></i>
                        Create Tournament
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Tournaments I'm In -->
            <div class="bg-white rounded-xl border border-green-200 shadow-lg">
                <div class="p-4 sm:p-6 border-b border-green-200">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Tournaments I'm In</h3>
                    <p class="text-gray-600 text-sm">Tournaments you're participating in</p>
                </div>
                
                <div class="p-4 sm:p-6">
                    <div v-if="tournaments.length === 0" class="text-center py-8 sm:py-12">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-trophy text-gray-400 text-2xl"></i>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">No tournaments yet!</h4>
                        <p class="text-gray-600 mb-6 max-w-md mx-auto px-4">
                            Join a tournament to start competing with other football fans.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-3 justify-center px-4">
                            <Link
                                :href="route('tournaments.join-form')"
                                class="bg-white border border-green-200 text-gray-700 px-4 py-3 rounded-lg font-medium transition-all hover:bg-green-50 text-center flex items-center justify-center"
                            >
                                <i class="fas fa-sign-in-alt mr-2"></i>
                                Join Tournament
                            </Link>
                            <Link
                                :href="route('tournaments.create')"
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg font-medium transition-all shadow-md hover:shadow-lg text-center flex items-center justify-center"
                            >
                                <i class="fas fa-plus mr-2"></i>
                                Create Tournament
                            </Link>
                        </div>
                    </div>
                    
                    <div v-else class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-6">
                        <div v-for="tournament in tournaments" :key="tournament.id" 
                             class="bg-gray-50 rounded-lg p-4 border border-green-200 hover:shadow-md transition-all h-full flex flex-col min-h-[280px]">
                            <div class="flex items-start justify-between mb-3">
                                <h4 class="text-lg font-semibold text-gray-900 flex-1 mr-2 leading-tight">{{ tournament.name }}</h4>
                                <span class="px-2 py-1 rounded text-xs font-medium flex-shrink-0"
                                      :class="tournament.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'">
                                    {{ tournament.status }}
                                </span>
                            </div>
                            
                            <p class="text-gray-600 text-sm mb-4 flex-1 line-clamp-3">{{ tournament.description || 'No description' }}</p>
                            
                            <div class="text-sm text-gray-500 mb-4 space-y-2 mt-auto">
                                <div class="flex justify-between">
                                    <span>Creator:</span>
                                    <span class="text-gray-700 font-medium">{{ tournament.creator.name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Participants:</span>
                                    <span class="text-gray-700 font-medium">{{ tournament.participants_count }}/{{ tournament.max_participants }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Progress:</span>
                                    <span class="text-gray-700 font-medium">{{ tournament.current_game_week }}/20</span>
                                </div>
                            </div>
                            
                            <Link :href="route('tournaments.show', tournament.id)"
                                  class="w-full bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 transition-all font-medium text-center block mt-auto">
                                View Tournament
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tournaments I Created -->
            <div class="bg-white rounded-xl border border-green-200 shadow-lg">
                <div class="p-4 sm:p-6 border-b border-green-200">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Tournaments I Created</h3>
                    <p class="text-gray-600 text-sm">Tournaments you've created and manage</p>
                </div>
                
                <div class="p-4 sm:p-6">
                    <div v-if="createdTournaments.length === 0" class="text-center py-8 sm:py-12">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-crown text-gray-400 text-2xl"></i>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">No tournaments created yet!</h4>
                        <p class="text-gray-600 mb-6 max-w-md mx-auto px-4">
                            Create your first tournament and invite friends to compete.
                        </p>
                        <Link
                            :href="route('tournaments.create')"
                            class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-all shadow-md hover:shadow-lg inline-block"
                        >
                            <i class="fas fa-plus mr-2"></i>
                            Create Tournament
                        </Link>
                    </div>
                    
                    <div v-else class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-6">
                        <div v-for="tournament in createdTournaments" :key="tournament.id" 
                             class="bg-gray-50 rounded-lg p-4 border border-green-200 hover:shadow-md transition-all h-full flex flex-col min-h-[280px]">
                            <div class="flex items-start justify-between mb-3">
                                <h4 class="text-lg font-semibold text-gray-900 flex-1 mr-2 leading-tight">{{ tournament.name }}</h4>
                                <span class="px-2 py-1 rounded text-xs font-medium flex-shrink-0"
                                      :class="tournament.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'">
                                    {{ tournament.status }}
                                </span>
                            </div>
                            
                            <p class="text-gray-600 text-sm mb-4 flex-1 line-clamp-3">{{ tournament.description || 'No description' }}</p>
                            
                            <div class="text-sm text-gray-500 mb-4 space-y-2 mt-auto">
                                <div class="flex justify-between">
                                    <span>Participants:</span>
                                    <span class="text-gray-700 font-medium">{{ tournament.participants_count }}/{{ tournament.max_participants }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Progress:</span>
                                    <span class="text-gray-700 font-medium">{{ tournament.current_game_week }}/20</span>
                                </div>
                            </div>
                            
                            <div class="flex flex-col sm:flex-row gap-2 mt-auto">
                                <Link :href="route('tournaments.show', tournament.id)"
                                      class="flex-1 bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 transition-all text-sm font-medium text-center">
                                    View
                                </Link>
                                <button class="flex-1 bg-white border border-green-200 text-gray-700 py-3 px-4 rounded-lg hover:bg-green-50 transition-all text-sm font-medium">
                                    Manage
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TournamentLayout>
</template> 