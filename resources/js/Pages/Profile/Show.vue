<template>
    <TournamentLayout>
        <div class="min-h-screen py-8">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Profile Header -->
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl border border-white/20 p-8 mb-8">
                    <div class="flex flex-col md:flex-row items-start gap-8">
                        <!-- Avatar and Basic Info -->
                        <div class="flex flex-col items-center text-center md:text-left">
                            <div class="relative">
                                <img 
                                    :src="profileUser.avatar_url" 
                                    :alt="profileUser.name"
                                    class="w-32 h-32 rounded-full border-4 border-emerald-400/50 shadow-2xl"
                                    @error="handleImageError"
                                    @load="handleImageLoad"
                                >
                                <div v-if="profileUser.favorite_team" 
                                     class="absolute -bottom-2 -right-2 w-12 h-12 rounded-full border-2 border-white shadow-lg flex items-center justify-center text-xs font-bold"
                                     :style="{ backgroundColor: profileUser.favorite_team.primary_color || '#10B981', color: '#fff' }">
                                    {{ profileUser.favorite_team.short_name }}
                                </div>
                            </div>
                            
                            <h1 class="text-3xl font-bold text-white mt-4">
                                {{ profileUser.display_name || profileUser.name }}
                            </h1>
                            
                            <p v-if="profileUser.show_real_name && profileUser.display_name && profileUser.display_name !== profileUser.name" 
                               class="text-gray-300 text-sm">
                                {{ profileUser.name }}
                            </p>
                            
                            <div class="flex flex-wrap gap-2 mt-3">
                                <span v-if="profileUser.show_location && profileUser.location" 
                                      class="px-3 py-1 bg-blue-500/20 text-blue-300 rounded-full text-sm flex items-center gap-1">
                                    <i class="fas fa-map-marker-alt"></i>
                                    {{ profileUser.location }}
                                </span>
                                
                                <span v-if="profileUser.show_join_date" 
                                      class="px-3 py-1 bg-emerald-500/20 text-emerald-300 rounded-full text-sm flex items-center gap-1">
                                    <i class="fas fa-calendar"></i>
                                    Member since {{ new Date(profileUser.created_at).getFullYear() }}
                                </span>
                                
                                <span v-if="profileUser.show_last_active && profileUser.last_active_at" 
                                      class="px-3 py-1 bg-purple-500/20 text-purple-300 rounded-full text-sm flex items-center gap-1">
                                    <i class="fas fa-clock"></i>
                                    Last active {{ formatDate(profileUser.last_active_at) }}
                                </span>
                            </div>
                        </div>
                        
                        <!-- Profile Details -->
                        <div class="flex-1 space-y-6">
                            <!-- Bio -->
                            <div v-if="profileUser.bio">
                                <h3 class="text-lg font-semibold text-white mb-2">About</h3>
                                <p class="text-gray-300 leading-relaxed">{{ profileUser.bio }}</p>
                            </div>
                            
                            <!-- Favorite Team Info -->
                            <div v-if="profileUser.show_favorite_team && profileUser.favorite_team" class="space-y-2">
                                <h3 class="text-lg font-semibold text-white">Favorite Team</h3>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold"
                                         :style="{ backgroundColor: profileUser.favorite_team.primary_color || '#10B981', color: '#fff' }">
                                        {{ profileUser.favorite_team.short_name }}
                                    </div>
                                    <span class="text-gray-300">{{ profileUser.favorite_team.name }}</span>
                                    <span v-if="profileUser.show_supporter_since && profileUser.supporter_since" 
                                          class="text-sm text-gray-400">
                                        (Supporting since {{ profileUser.supporter_since }})
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Social Links -->
                            <div v-if="profileUser.show_social_links && (profileUser.twitter_handle || profileUser.instagram_handle)" 
                                 class="space-y-2">
                                <h3 class="text-lg font-semibold text-white">Social Media</h3>
                                <div class="flex gap-4">
                                    <a v-if="profileUser.twitter_handle" 
                                       :href="`https://twitter.com/${profileUser.twitter_handle}`"
                                       target="_blank"
                                       class="flex items-center gap-2 text-blue-400 hover:text-blue-300 transition-colors">
                                        <i class="fab fa-twitter"></i>
                                        @{{ profileUser.twitter_handle }}
                                    </a>
                                    <a v-if="profileUser.instagram_handle" 
                                       :href="`https://instagram.com/${profileUser.instagram_handle}`"
                                       target="_blank"
                                       class="flex items-center gap-2 text-pink-400 hover:text-pink-300 transition-colors">
                                        <i class="fab fa-instagram"></i>
                                        @{{ profileUser.instagram_handle }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Stats -->
                        <div v-if="profileUser.show_statistics && profileUser.statistics" 
                             class="bg-white/5 rounded-xl p-4 min-w-64">
                            <h3 class="text-lg font-semibold text-white mb-4">Quick Stats</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-300">Tournaments</span>
                                    <span class="text-white font-semibold">{{ profileUser.statistics.total_tournaments }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-300">Wins</span>
                                    <span class="text-emerald-400 font-semibold">{{ profileUser.statistics.tournaments_won }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-300">Total Points</span>
                                    <span class="text-blue-400 font-semibold">{{ profileUser.statistics.total_points }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-300">Win Rate</span>
                                    <span class="text-purple-400 font-semibold">{{ winRate }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div v-if="canEdit" class="mt-6 flex gap-3">
                        <Link :href="route('profile.edit')" 
                              class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition-colors">
                            <i class="fas fa-edit mr-2"></i>
                            Edit Profile
                        </Link>
                    </div>
                </div>
                
                <!-- Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Featured Achievements -->
                    <div v-if="profileUser.show_achievements && profileUser.achievements?.length" 
                         class="bg-white/10 backdrop-blur-lg rounded-2xl border border-white/20 p-6">
                        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                            <i class="fas fa-trophy text-yellow-400"></i>
                            Featured Achievements
                        </h2>
                        
                        <div class="space-y-3">
                            <div v-for="achievement in profileUser.achievements.slice(0, 3)" 
                                 :key="achievement.id"
                                 class="flex items-center gap-3 p-3 bg-white/5 rounded-lg">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm"
                                     :style="{ backgroundColor: achievement.color }">
                                    <i :class="achievement.icon || 'fas fa-star'" class="text-white"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-white font-medium">{{ achievement.name }}</h3>
                                    <p class="text-gray-400 text-sm">{{ achievement.description }}</p>
                                    <span class="text-xs text-gray-500">
                                        Earned {{ formatDate(achievement.pivot.earned_at) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Tournaments -->
                    <div v-if="profileUser.show_tournament_history && recentTournaments?.length" 
                         class="bg-white/10 backdrop-blur-lg rounded-2xl border border-white/20 p-6">
                        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                            <i class="fas fa-futbol text-emerald-400"></i>
                            Recent Tournaments
                        </h2>
                        
                        <div class="space-y-3">
                            <div v-for="tournament in recentTournaments.slice(0, 5)" 
                                 :key="tournament.id"
                                 class="p-3 bg-white/5 rounded-lg">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h3 class="text-white font-medium">{{ tournament.name }}</h3>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="px-2 py-1 rounded text-xs"
                                                  :class="getStatusClass(tournament.status)">
                                                {{ tournament.status }}
                                            </span>
                                            <span class="text-gray-400 text-sm">
                                                {{ formatDate(tournament.created_at) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-emerald-400 font-bold">{{ tournament.points }}</div>
                                        <div class="text-gray-400 text-xs">points</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Detailed Statistics -->
                    <div v-if="profileUser.show_statistics && profileUser.statistics" 
                         class="bg-white/10 backdrop-blur-lg rounded-2xl border border-white/20 p-6">
                        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                            <i class="fas fa-chart-bar text-blue-400"></i>
                            Statistics
                        </h2>
                        
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="text-center p-3 bg-white/5 rounded-lg">
                                    <div class="text-2xl font-bold text-emerald-400">{{ profileUser.statistics.total_picks }}</div>
                                    <div class="text-gray-400 text-sm">Total Picks</div>
                                </div>
                                <div class="text-center p-3 bg-white/5 rounded-lg">
                                    <div class="text-2xl font-bold text-blue-400">{{ profileUser.statistics.average_points_per_tournament }}</div>
                                    <div class="text-gray-400 text-sm">Avg Points</div>
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-300">Best Tournament Score</span>
                                    <span class="text-yellow-400 font-semibold">{{ profileUser.statistics.highest_tournament_score }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-300">Current Win Streak</span>
                                    <span class="text-emerald-400 font-semibold">{{ profileUser.statistics.current_win_streak }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-300">Longest Win Streak</span>
                                    <span class="text-purple-400 font-semibold">{{ profileUser.statistics.longest_win_streak }}</span>
                                </div>
                            </div>
                            
                            <!-- Most Picked Team -->
                            <div v-if="profileUser.statistics.most_picked_team" class="pt-4 border-t border-white/10">
                                <h4 class="text-white font-medium mb-2">Most Picked Team</h4>
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                                         :style="{ backgroundColor: profileUser.statistics.most_picked_team.primary_color || '#10B981', color: '#fff' }">
                                        {{ profileUser.statistics.most_picked_team.short_name }}
                                    </div>
                                    <span class="text-gray-300">{{ profileUser.statistics.most_picked_team.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TournamentLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import TournamentLayout from '@/Layouts/TournamentLayout.vue'

const props = defineProps({
    profileUser: Object,
    recentTournaments: Array,
    isOwnProfile: Boolean,
    canEdit: Boolean,
})

const winRate = computed(() => {
    if (!props.profileUser.statistics || props.profileUser.statistics.total_tournaments === 0) {
        return 0
    }
    return Math.round((props.profileUser.statistics.tournaments_won / props.profileUser.statistics.total_tournaments) * 100)
})

const formatDate = (dateString) => {
    const date = new Date(dateString)
    const now = new Date()
    const diffTime = Math.abs(now - date)
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    
    if (diffDays < 1) {
        return 'Today'
    } else if (diffDays < 7) {
        return `${diffDays} days ago`
    } else if (diffDays < 30) {
        return `${Math.ceil(diffDays / 7)} weeks ago`
    } else if (diffDays < 365) {
        return `${Math.ceil(diffDays / 30)} months ago`
    } else {
        return `${Math.ceil(diffDays / 365)} years ago`
    }
}

const getStatusClass = (status) => {
    switch (status) {
        case 'active':
            return 'bg-emerald-500/20 text-emerald-300'
        case 'completed':
            return 'bg-blue-500/20 text-blue-300'
        case 'pending':
            return 'bg-yellow-500/20 text-yellow-300'
        default:
            return 'bg-gray-500/20 text-gray-300'
    }
}

const handleImageError = (event) => {
    console.error('Avatar image failed to load:', event.target.src)
    // Fallback to default avatar URL generator
    event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(props.profileUser.name)}&background=10B981&color=fff&size=150`
}

const handleImageLoad = (event) => {
    console.log('Avatar image loaded successfully:', event.target.src)
}
</script> 