<template>
    <Head :title="`Select Team - ${gameWeek.name}`" />
    
    <TournamentLayout :tournament="tournament">
        <div class="space-y-6">
            <!-- Header -->
            <div class="text-center">
                <h2 class="text-2xl font-bold text-white mb-2">
                    Select Your Team for {{ gameWeek.name }}
                </h2>
                <p class="text-gray-300">
                    Choose a team you haven't picked before in this tournament
                </p>
            </div>

            <!-- Games for this gameweek -->
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-white mb-4">
                    This Gameweek's Matches
                </h3>
                <div class="grid gap-3">
                    <div v-for="game in gameWeekGames" :key="game.id" 
                         class="flex items-center justify-between bg-white/5 rounded-lg p-4">
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2">
                                <div class="w-6 h-6 rounded-full border-2 border-white/20 flex items-center justify-center">
                                    <span class="text-xs font-bold text-white">H</span>
                                </div>
                                <span class="text-white font-medium">{{ game.home_team.name }}</span>
                            </div>
                            <span class="text-gray-300 text-sm">vs</span>
                            <div class="flex items-center space-x-2">
                                <span class="text-white font-medium">{{ game.away_team.name }}</span>
                                <div class="w-6 h-6 rounded-full border-2 border-white/20 flex items-center justify-center">
                                    <span class="text-xs font-bold text-white">A</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-300">
                                {{ formatDateTime(game.kick_off_time) }}
                            </div>
                            <div class="text-xs text-gray-400">
                                {{ game.status }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team Selection Form -->
            <form @submit.prevent="submitPick" class="space-y-6">
                <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">
                        Select Your Team
                    </h3>
                    
                    <!-- Available Teams Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="team in availableTeams" :key="team.id"
                             @click="selectTeam(team.id)"
                             class="team-card"
                             :class="{ 'selected': form.team_id === team.id }">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center"
                                     :style="{ backgroundColor: team.primary_color }">
                                    <span class="font-bold text-white text-sm">
                                        {{ team.short_name }}
                                    </span>
                                </div>
                                <div>
                                    <div class="font-medium text-white">{{ team.name }}</div>
                                    <div class="text-xs text-gray-300">
                                        Available to pick
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Selection indicator -->
                            <div v-if="form.team_id === team.id" 
                                 class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Used Teams Display -->
                    <div v-if="usedTeams.length > 0" class="mt-6 pt-6 border-t border-white/10">
                        <h4 class="text-md font-medium text-white mb-3">Teams Already Used</h4>
                        <div class="flex flex-wrap gap-2">
                            <div v-for="team in usedTeams" :key="team.id"
                                 class="flex items-center space-x-2 bg-red-500/20 border border-red-500/30 rounded-lg px-3 py-1">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center"
                                     :style="{ backgroundColor: team.primary_color }">
                                    <span class="font-bold text-white text-xs">
                                        {{ team.short_name }}
                                    </span>
                                </div>
                                <span class="text-sm text-white">{{ team.name }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Error Display -->
                    <div v-if="$page.props.errors.team_id" class="mt-4">
                        <p class="text-red-400 text-sm">{{ $page.props.errors.team_id }}</p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between">
                    <Link :href="route('tournaments.show', tournament.id)"
                          class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                        Cancel
                    </Link>
                    
                    <button type="submit" 
                            :disabled="!form.team_id || form.processing"
                            class="px-6 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-600 disabled:cursor-not-allowed text-white rounded-lg transition-colors">
                        <span v-if="form.processing">Confirming...</span>
                        <span v-else>Confirm Pick</span>
                    </button>
                </div>
            </form>
        </div>
    </TournamentLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import TournamentLayout from '@/Layouts/TournamentLayout.vue'
import { computed } from 'vue'

const props = defineProps({
    tournament: Object,
    gameWeek: Object,
    availableTeams: Array,
    gameWeekGames: {
        type: Array,
        default: () => []
    },
    usedTeams: {
        type: Array,
        default: () => []
    }
})

const form = useForm({
    team_id: null
})

const selectTeam = (teamId) => {
    form.team_id = teamId
}

const submitPick = () => {
    form.post(route('tournaments.gameweeks.picks.store', [props.tournament.id, props.gameWeek.id]))
}

const formatDateTime = (datetime) => {
    return new Date(datetime).toLocaleString('en-GB', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script>

<style scoped>
.team-card {
    @apply p-4 bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 rounded-lg cursor-pointer transition-all duration-200 flex items-center justify-between;
}

.team-card.selected {
    @apply bg-green-500/20 border-green-500/40;
}
</style>
