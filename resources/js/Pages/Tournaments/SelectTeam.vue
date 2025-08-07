<template>
    <Head :title="`Select Team - ${tournament.name}`" />

    <TournamentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">
                        Select Your Team
                    </h2>
                    <p class="text-gray-600">
                        Gameweek {{ gameWeek.week_number }} - {{ gameWeek.name }}
                    </p>
                </div>
                <div class="text-right">
                    <div class="bg-green-50 rounded-lg px-4 py-2 border border-green-200 shadow-md">
                        <i class="fas fa-futbol text-green-600 text-lg"></i>
                    </div>
                </div>
            </div>
        </template>

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Current Game Info -->
            <div class="bg-white rounded-xl p-6 border border-green-200 shadow-lg">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Current Game</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="game in gameWeek.games" :key="game.id" 
                         class="bg-gray-50 rounded-lg p-4 border border-green-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold"
                                     :style="{ backgroundColor: game.home_team.primary_color || '#22C55E' }">
                                    <span class="text-xs font-bold text-white">H</span>
                                </div>
                                <span class="text-gray-900 font-medium">{{ game.home_team.name }}</span>
                            </div>
                            <div class="text-center">
                                <span class="text-gray-500 text-sm">vs</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="text-gray-900 font-medium">{{ game.away_team.name }}</span>
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold"
                                     :style="{ backgroundColor: game.away_team.primary_color || '#22C55E' }">
                                    <span class="text-xs font-bold text-white">A</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team Selection -->
            <div class="bg-white rounded-xl p-6 border border-green-200 shadow-lg">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Select Your Team</h3>
                <p class="text-gray-600 mb-6">Choose one Premier League team for this gameweek. Once selected, you cannot use this team again.</p>
                
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <div v-for="team in availableTeams" :key="team.id" 
                             class="relative">
                            <input
                                type="radio"
                                :id="`team-${team.id}`"
                                name="team_id"
                                :value="team.id"
                                v-model="form.team_id"
                                class="sr-only"
                            />
                            <label :for="`team-${team.id}`" 
                                   class="block cursor-pointer">
                                <div class="bg-gray-50 border-2 border-green-200 rounded-lg p-4 text-center hover:bg-green-50 hover:border-green-300 transition-all"
                                     :class="{ 'bg-green-100 border-green-400': form.team_id == team.id }">
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-2"
                                         :style="{ backgroundColor: team.primary_color || '#22C55E' }">
                                        <span class="font-bold text-white text-sm">{{ team.short_name }}</span>
                                    </div>
                                    <div class="font-medium text-gray-900 text-sm">{{ team.name }}</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div v-if="form.team_id" class="text-center">
                        <p class="text-gray-600 mb-4">
                            Selected: <span class="font-semibold text-green-600">{{ availableTeams.find(t => t.id == form.team_id)?.name }}</span>
                        </p>
                        <PrimaryButton
                            class="w-full md:w-auto"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Confirm Selection
                        </PrimaryButton>
                    </div>
                </form>
            </div>

            <!-- Teams Already Used -->
            <div v-if="usedTeams.length > 0" class="bg-white rounded-xl p-6 border border-green-200 shadow-lg">
                <h4 class="text-md font-medium text-gray-900 mb-3">Teams Already Used</h4>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    <div v-for="team in usedTeams" :key="team.id" 
                         class="bg-gray-100 rounded-lg p-3 text-center opacity-60">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center mx-auto mb-1"
                             :style="{ backgroundColor: team.primary_color || '#22C55E' }">
                            <span class="font-bold text-white text-xs">{{ team.short_name }}</span>
                        </div>
                        <div class="text-gray-600 text-xs">{{ team.name }}</div>
                    </div>
                </div>
            </div>
        </div>
    </TournamentLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import TournamentLayout from '@/Layouts/TournamentLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    tournament: Object,
    gameWeek: Object,
    availableTeams: Array,
    usedTeams: Array,
});

const form = useForm({
    team_id: '',
});

const submit = () => {
    form.post(route('tournaments.gameweeks.picks.store', { tournament: props.tournament.id, gameWeek: props.gameWeek.id }));
};
</script>

<style scoped>
.team-card {
    @apply p-4 bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 rounded-lg cursor-pointer transition-all duration-200 flex items-center justify-between;
}

.team-card.selected {
    @apply bg-green-500/20 border-green-500/40;
}
</style>
