<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineProps({
    myTournaments: Array,
    createdTournaments: Array,
});

const getStatusBadge = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        active: 'bg-green-100 text-green-800',
        completed: 'bg-gray-100 text-gray-800'
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="My Tournaments" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    My Tournaments
                </h2>
                <div class="flex space-x-3">
                    <Link :href="route('tournaments.join-form')">
                        <SecondaryButton>Join Tournament</SecondaryButton>
                    </Link>
                    <Link :href="route('tournaments.create')">
                        <PrimaryButton>Create Tournament</PrimaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- My Tournaments -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Tournaments I'm In</h3>
                    
                    <div v-if="myTournaments.length === 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-500 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <p class="text-lg font-medium mb-2">No tournaments yet!</p>
                            <p class="mb-4">Join an existing tournament or create your own to get started.</p>
                            <div class="flex justify-center space-x-3">
                                <Link :href="route('tournaments.join-form')">
                                    <SecondaryButton>Join Tournament</SecondaryButton>
                                </Link>
                                <Link :href="route('tournaments.create')">
                                    <PrimaryButton>Create Tournament</PrimaryButton>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div 
                            v-for="tournament in myTournaments" 
                            :key="tournament.id"
                            class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow"
                        >
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-3">
                                    <h4 class="text-lg font-semibold text-gray-900">{{ tournament.name }}</h4>
                                    <span 
                                        :class="getStatusBadge(tournament.status)"
                                        class="px-2 py-1 text-xs font-medium rounded-full"
                                    >
                                        {{ tournament.status.charAt(0).toUpperCase() + tournament.status.slice(1) }}
                                    </span>
                                </div>
                                
                                <p class="text-gray-600 text-sm mb-3">{{ tournament.description || 'No description' }}</p>
                                
                                <div class="text-sm text-gray-500 mb-4">
                                    <p>Created by: {{ tournament.creator.name }}</p>
                                    <p>Participants: {{ tournament.participants_count }}/{{ tournament.max_participants }}</p>
                                    <p>Game Week: {{ tournament.current_game_week }}/20</p>
                                </div>

                                <Link :href="route('tournaments.show', tournament.id)" class="block w-full">
                                    <PrimaryButton class="w-full justify-center">
                                        View Tournament
                                    </PrimaryButton>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Created Tournaments -->
                <div v-if="createdTournaments.length > 0">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Tournaments I Created</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div 
                            v-for="tournament in createdTournaments" 
                            :key="tournament.id"
                            class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow border-l-4 border-blue-500"
                        >
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-3">
                                    <h4 class="text-lg font-semibold text-gray-900">{{ tournament.name }}</h4>
                                    <span 
                                        :class="getStatusBadge(tournament.status)"
                                        class="px-2 py-1 text-xs font-medium rounded-full"
                                    >
                                        {{ tournament.status.charAt(0).toUpperCase() + tournament.status.slice(1) }}
                                    </span>
                                </div>
                                
                                <p class="text-gray-600 text-sm mb-3">{{ tournament.description || 'No description' }}</p>
                                
                                <div class="text-sm text-gray-500 mb-4">
                                    <p>Join Code: <span class="font-mono font-bold text-gray-900">{{ tournament.join_code }}</span></p>
                                    <p>Participants: {{ tournament.participants_count }}/{{ tournament.max_participants }}</p>
                                    <p>Game Week: {{ tournament.current_game_week }}/20</p>
                                </div>

                                <div class="flex space-x-2">
                                    <Link :href="route('tournaments.show', tournament.id)" class="flex-1">
                                        <PrimaryButton class="w-full justify-center text-sm">
                                            Manage
                                        </PrimaryButton>
                                    </Link>
                                    <Link :href="route('tournaments.leaderboard', tournament.id)" class="flex-1">
                                        <SecondaryButton class="w-full justify-center text-sm">
                                            Leaderboard
                                        </SecondaryButton>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template> 