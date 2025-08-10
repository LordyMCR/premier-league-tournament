<template>
    <TournamentLayout>
        <!-- Check if profile is visible (ignore when previewing public view as owner) -->
        <div v-if="!settings.profile_visible && !isOwnProfile && !previewPublic" class="min-h-screen py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl border border-gray-200 p-8 text-center shadow-lg">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-lock text-gray-400 text-xl"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">Private Profile</h1>
                    <p class="text-gray-600">This user has chosen to keep their profile private.</p>
                </div>
            </div>
        </div>

        <!-- Show profile if visible or own profile -->
        <div v-else class="min-h-screen py-4 sm:py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Profile Header -->
                <div class="bg-white rounded-xl sm:rounded-2xl border border-green-200 p-4 sm:p-6 lg:p-8 mb-6 sm:mb-8 shadow-lg">
                    <div class="flex flex-col lg:flex-row items-start gap-6 lg:gap-8">
                        <!-- Avatar and Basic Info -->
                        <div class="flex flex-col items-center text-center lg:text-left lg:items-start">
                            <div class="relative">
                                <img 
                                    :src="profileUser.avatar_url" 
                                    :alt="profileUser.name"
                                    class="w-24 h-24 sm:w-32 sm:h-32 rounded-full border-4 border-green-300 shadow-lg"
                                    @error="handleImageError"
                                    @load="handleImageLoad"
                                >
                                <div v-if="settings.show_favorite_team && profileUser.favorite_team" 
                                     class="absolute -bottom-1 -right-1 sm:-bottom-2 sm:-right-2 w-8 h-8 sm:w-12 sm:h-12 rounded-full border-2 border-white shadow-lg overflow-hidden"
                                     :style="{ backgroundColor: profileUser.favorite_team.primary_color || '#22C55E' }">
                                    <img :src="profileUser.favorite_team.logo_url"
                                         :alt="profileUser.favorite_team.name"
                                         class="w-full h-full object-cover" />
                                </div>
                            </div>
                            
                            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mt-4">
                                <template v-if="profileUser.display_name">
                                    {{ profileUser.display_name }}
                                </template>
                                <template v-else-if="settings.show_real_name || isOwnProfile">
                                    {{ profileUser.name }}
                                </template>
                                <template v-else>
                                    Hidden
                                </template>
                            </h1>
                            
                            <p v-if="settings.show_real_name && profileUser.display_name && profileUser.display_name !== profileUser.name" 
                               class="text-gray-500 text-sm">
                                {{ profileUser.name }}
                            </p>
                            
                            <!-- Email Address -->
                            <p v-if="settings.show_email && profileUser.email" 
                               class="text-gray-500 text-sm flex items-center gap-1">
                                <i class="fas fa-envelope"></i>
                                {{ profileUser.email }}
                            </p>
                            
                            <!-- Age -->
                            <p v-if="settings.show_age && profileUser.date_of_birth" 
                               class="text-gray-500 text-sm flex items-center gap-1">
                                <i class="fas fa-birthday-cake"></i>
                                {{ calculateAge(profileUser.date_of_birth) }} years old
                            </p>
                            
                            <div class="flex flex-wrap justify-center lg:justify-start gap-2 mt-3">
                                <span v-if="settings.show_location && profileUser.location" 
                                      class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm flex items-center gap-1">
                                    <i class="fas fa-map-marker-alt"></i>
                                    {{ profileUser.location }}
                                </span>
                                
                                <span v-if="settings.show_join_date" 
                                      class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm flex items-center gap-1">
                                    <i class="fas fa-calendar"></i>
                                    Member since {{ new Date(profileUser.created_at).getFullYear() }}
                                </span>
                                
                                <span v-if="settings.show_last_active && profileUser.last_active_at" 
                                      class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm flex items-center gap-1">
                                    <i class="fas fa-clock"></i>
                                    Last active {{ formatDate(profileUser.last_active_at) }}
                                </span>
                            </div>
                        </div>
                        
                        <!-- Profile Details -->
                        <div class="flex-1 space-y-6">
                            <!-- Bio -->
                            <div v-if="settings.show_bio && profileUser.bio">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">About</h3>
                                <p class="text-gray-600 leading-relaxed">{{ profileUser.bio }}</p>
                            </div>
                            
                            <!-- Favorite Team Info -->
                            <div v-if="settings.show_favorite_team && profileUser.favorite_team" class="space-y-2">
                                <h3 class="text-lg font-semibold text-gray-900">Favorite Team</h3>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg overflow-hidden border border-gray-200 shadow-sm bg-white p-1"
                                         :style="{ borderColor: profileUser.favorite_team.primary_color || '#22C55E' }">
                                        <img :src="profileUser.favorite_team.logo_url"
                                             :alt="profileUser.favorite_team.name"
                                             class="w-full h-full object-contain" />
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900">{{ profileUser.favorite_team.name }}</div>
                                        <span v-if="settings.show_supporter_since && profileUser.supporter_since" 
                                              class="text-sm text-gray-500">
                                            Supporting since {{ profileUser.supporter_since }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Social Links -->
                            <div v-if="settings.show_social_links && (profileUser.twitter_handle || profileUser.instagram_handle)" 
                                 class="space-y-2">
                                <h3 class="text-lg font-semibold text-gray-900">Social Media</h3>
                                <div class="flex gap-4">
                                    <a v-if="profileUser.twitter_handle" 
                                       :href="`https://twitter.com/${profileUser.twitter_handle}`"
                                       target="_blank"
                                       class="flex items-center gap-2 text-blue-600 hover:text-blue-700 transition-colors">
                                        <i class="fab fa-twitter"></i>
                                        @{{ profileUser.twitter_handle }}
                                    </a>
                                    <a v-if="profileUser.instagram_handle" 
                                       :href="`https://instagram.com/${profileUser.instagram_handle}`"
                                       target="_blank"
                                       class="flex items-center gap-2 text-pink-600 hover:text-pink-700 transition-colors">
                                        <i class="fab fa-instagram"></i>
                                        @{{ profileUser.instagram_handle }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Stats - Desktop -->
                        <div v-if="settings.show_statistics && profileUser.statistics" 
                             class="hidden lg:block bg-gray-50 rounded-xl p-4 min-w-72 border border-green-200 self-start">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Stats</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Tournaments</span>
                                    <span class="text-gray-900 font-semibold">{{ profileUser.statistics.total_tournaments }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Wins</span>
                                    <span class="text-green-600 font-semibold">{{ profileUser.statistics.tournaments_won }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Total Points</span>
                                    <span class="text-blue-600 font-semibold">{{ profileUser.statistics.total_points }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Win Rate</span>
                                    <span class="text-yellow-600 font-semibold">{{ winRate }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Stats - Mobile -->
                    <div v-if="settings.show_statistics && profileUser.statistics" 
                         class="lg:hidden mt-6 pt-6 border-t border-green-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Stats</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div class="text-center p-3 bg-gray-50 rounded-lg">
                                <div class="text-xl sm:text-2xl font-bold text-gray-900">{{ profileUser.statistics.total_tournaments }}</div>
                                <div class="text-gray-600 text-xs sm:text-sm">Tournaments</div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 rounded-lg">
                                <div class="text-xl sm:text-2xl font-bold text-green-600">{{ profileUser.statistics.tournaments_won }}</div>
                                <div class="text-gray-600 text-xs sm:text-sm">Wins</div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 rounded-lg">
                                <div class="text-xl sm:text-2xl font-bold text-blue-600">{{ profileUser.statistics.total_points }}</div>
                                <div class="text-gray-600 text-xs sm:text-sm">Total Points</div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 rounded-lg">
                                <div class="text-xl sm:text-2xl font-bold text-yellow-600">{{ winRate }}%</div>
                                <div class="text-gray-600 text-xs sm:text-sm">Win Rate</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div v-if="canEdit" class="mt-6 flex gap-3">
                        <Link :href="route('profile.edit')" 
                              class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-all shadow-md hover:shadow-lg">
                            <i class="fas fa-edit mr-2"></i>
                            Edit Profile
                        </Link>
                    </div>
                </div>
                
                <!-- Content Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 lg:gap-8">
                    <!-- Featured Achievements -->
                    <div v-if="settings.show_achievements && profileUser.achievements?.length" 
                         class="bg-white rounded-xl border border-green-200 p-4 sm:p-6 shadow-lg">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-trophy text-yellow-500"></i>
                            Featured Achievements
                        </h2>
                        
                        <div class="space-y-3">
                            <div v-for="achievement in profileUser.achievements.slice(0, 3)" 
                                 :key="achievement.id"
                                 class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm flex-shrink-0"
                                     :style="{ backgroundColor: achievement.color }">
                                    <i :class="achievement.icon || 'fas fa-star'" class="text-white"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-gray-900 font-medium text-sm sm:text-base">{{ achievement.name }}</h3>
                                    <p class="text-gray-600 text-xs sm:text-sm">{{ achievement.description }}</p>
                                    <span class="text-xs text-gray-500">
                                        Earned {{ formatDate(achievement.pivot.earned_at) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Tournaments -->
                    <div v-if="settings.show_tournament_history && recentTournaments?.length" 
                         class="bg-white rounded-xl border border-green-200 p-4 sm:p-6 shadow-lg">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-futbol text-green-600"></i>
                            Recent Tournaments
                        </h2>
                        
                        <div class="space-y-3">
                            <div v-for="tournament in recentTournaments.slice(0, 5)" 
                                 :key="tournament.id"
                                 class="p-3 bg-gray-50 rounded-lg">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-gray-900 font-medium text-sm sm:text-base truncate">{{ tournament.name }}</h3>
                                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                                            <span class="px-2 py-1 rounded text-xs"
                                                  :class="getStatusClass(tournament.status)">
                                                {{ tournament.status }}
                                            </span>
                                            <span class="text-gray-500 text-xs sm:text-sm">
                                                {{ formatDate(tournament.created_at) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0 ml-2">
                                        <div class="text-green-600 font-bold text-sm sm:text-base">{{ tournament.points }}</div>
                                        <div class="text-gray-500 text-xs">points</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Detailed Statistics -->
                    <div v-if="settings.show_statistics && profileUser.statistics" 
                         class="bg-white rounded-xl border border-green-200 p-4 sm:p-6 shadow-lg md:col-span-2 xl:col-span-1">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-chart-bar text-blue-600"></i>
                            Statistics
                        </h2>
                        
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="text-center p-3 bg-gray-50 rounded-lg">
                                    <div class="text-xl sm:text-2xl font-bold text-green-600">{{ profileUser.statistics.total_picks }}</div>
                                    <div class="text-gray-500 text-xs sm:text-sm">Total Picks</div>
                                </div>
                                <div class="text-center p-3 bg-gray-50 rounded-lg">
                                    <div class="text-xl sm:text-2xl font-bold text-blue-600">{{ profileUser.statistics.average_points_per_tournament }}</div>
                                    <div class="text-gray-500 text-xs sm:text-sm">Avg Points</div>
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 text-sm">Best Tournament Score</span>
                                    <span class="text-yellow-600 font-semibold">{{ profileUser.statistics.highest_tournament_score }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 text-sm">Current Win Streak</span>
                                    <span class="text-green-600 font-semibold">{{ profileUser.statistics.current_win_streak }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 text-sm">Longest Win Streak</span>
                                    <span class="text-purple-600 font-semibold">{{ profileUser.statistics.longest_win_streak }}</span>
                                </div>
                            </div>
                            
                            <!-- Most Picked Team -->
                            <div v-if="profileUser.statistics.most_picked_team" class="pt-4 border-t border-green-200">
                                <h4 class="text-gray-900 font-medium mb-2 text-sm">Most Picked Team</h4>
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full overflow-hidden border border-gray-300"
                                         :style="{ backgroundColor: profileUser.statistics.most_picked_team.primary_color || '#22C55E' }">
                                        <img :src="profileUser.statistics.most_picked_team.logo_url"
                                             :alt="profileUser.statistics.most_picked_team.name"
                                             class="w-full h-full object-cover" />
                                    </div>
                                    <span class="text-gray-700 text-sm">{{ profileUser.statistics.most_picked_team.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Current Tournaments -->
                    <div v-if="settings.show_current_tournaments && profileUser.current_tournaments?.length" 
                         class="bg-white rounded-xl border border-green-200 p-4 sm:p-6 shadow-lg">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-trophy text-amber-600"></i>
                            Current Tournaments
                        </h2>
                        
                        <div class="space-y-3">
                            <div v-for="tournament in profileUser.current_tournaments.slice(0, 5)" 
                                 :key="tournament.id"
                                 class="p-3 bg-gray-50 rounded-lg">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-gray-900 font-medium text-sm sm:text-base truncate">{{ tournament.name }}</h3>
                                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                                            <span class="px-2 py-1 rounded text-xs bg-green-100 text-green-700">
                                                Active
                                            </span>
                                            <span class="text-gray-500 text-xs sm:text-sm">
                                                Gameweek {{ tournament.current_gameweek || 1 }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0 ml-2">
                                        <div class="text-green-600 font-bold text-sm sm:text-base">{{ tournament.current_points || 0 }}</div>
                                        <div class="text-gray-500 text-xs">points</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Team Preferences -->
                    <div v-if="settings.show_team_preferences && profileUser.team_preferences?.length" 
                         class="bg-white rounded-xl border border-green-200 p-4 sm:p-6 shadow-lg">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-heart text-red-500"></i>
                            Team Preferences
                        </h2>
                        
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <div v-for="team in profileUser.team_preferences.slice(0, 6)" 
                                 :key="team.id"
                                 class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg">
                                <div class="w-6 h-6 rounded-full overflow-hidden border border-gray-300"
                                     :style="{ backgroundColor: team.primary_color || '#22C55E' }">
                                    <img :src="team.logo_url"
                                         :alt="team.name"
                                         class="w-full h-full object-cover" />
                                </div>
                                <span class="text-gray-700 text-xs sm:text-sm truncate">{{ team.short_name || team.name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of profile visibility check -->
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
    previewPublic: { type: Boolean, default: false },
})

// Safe wrapper around profile settings with sensible defaults
const settings = computed(() => {
    const s = props.profileUser?.profile_settings || {}
    return {
        profile_visible: s.profile_visible ?? true,
        show_real_name: s.show_real_name ?? false,
        show_email: s.show_email ?? false,
        show_location: s.show_location ?? false,
        show_age: s.show_age ?? false,
        show_bio: s.show_bio ?? true,
        show_favorite_team: s.show_favorite_team ?? true,
        show_supporter_since: s.show_supporter_since ?? true,
        show_social_links: s.show_social_links ?? true,
        show_tournament_history: s.show_tournament_history ?? true,
        show_statistics: s.show_statistics ?? true,
        show_achievements: s.show_achievements ?? true,
        show_current_tournaments: s.show_current_tournaments ?? true,
        show_pick_history: s.show_pick_history ?? true,
        show_team_preferences: s.show_team_preferences ?? true,
        show_last_active: s.show_last_active ?? true,
        show_join_date: s.show_join_date ?? true,
    }
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

const calculateAge = (dateOfBirth) => {
    const today = new Date()
    const birthDate = new Date(dateOfBirth)
    let age = today.getFullYear() - birthDate.getFullYear()
    const monthDiff = today.getMonth() - birthDate.getMonth()
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--
    }
    
    return age
}

const getStatusClass = (status) => {
    switch (status) {
        case 'active':
            return 'bg-green-100 text-green-700'
        case 'completed':
            return 'bg-blue-100 text-blue-700'
        case 'pending':
            return 'bg-yellow-100 text-yellow-700'
        default:
            return 'bg-gray-100 text-gray-700'
    }
}

const handleImageError = (event) => {
    console.error('Avatar image failed to load:', event.target.src)
    // Fallback to default avatar URL generator
    event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(props.profileUser.name)}&background=22C55E&color=fff&size=150`
}

const handleImageLoad = (event) => {
    console.log('Avatar image loaded successfully:', event.target.src)
}
</script> 