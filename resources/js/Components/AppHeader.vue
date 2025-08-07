<script setup>
import { ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const showDropdown = ref(false)
const showMobileMenu = ref(false)
const page = usePage()

const closeDropdown = () => {
    showDropdown.value = false
}

const toggleDropdown = () => {
    showDropdown.value = !showDropdown.value
}

const logout = () => {
    if (confirm('Are you sure you want to log out?')) {
        window.location.href = '/logout'
    }
}
</script>

<template>
    <header class="bg-white/90 backdrop-blur-md border-b border-green-200 relative z-[9999] shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <!-- Logo -->
                <div class="flex items-center">
                    <Link :href="route('welcome')" class="flex items-center space-x-3 group">
                        <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center shadow-md group-hover:shadow-lg transition-all duration-300">
                            <i class="fas fa-futbol text-white text-lg"></i>
                        </div>
                        <div class="flex flex-col">
                            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">PL Tournament</h1>
                            <span class="text-xs text-green-600 font-medium">Premier League Predictions</span>
                        </div>
                    </Link>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-6" v-if="page.props.auth.user">
                    <!-- Quick Actions -->
                    <Link
                        :href="route('tournaments.index')"
                        class="text-gray-700 hover:text-green-600 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 hover:bg-green-50"
                    >
                        <i class="fas fa-trophy mr-2"></i>
                        My Tournaments
                    </Link>
                    <Link
                        :href="route('schedule.index')"
                        class="text-gray-700 hover:text-green-600 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 hover:bg-green-50"
                    >
                        <i class="fas fa-calendar mr-2"></i>
                        Schedule
                    </Link>
                    <Link
                        :href="route('tournaments.create')"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 shadow-md hover:shadow-lg transform hover:scale-105"
                    >
                        <i class="fas fa-plus mr-2"></i>
                        Create Tournament
                    </Link>
                    
                    <!-- User Dropdown -->
                    <div class="relative">
                        <button 
                            @click="toggleDropdown"
                            class="flex items-center space-x-3 text-gray-700 hover:text-green-600 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 hover:bg-green-50"
                        >
                            <div class="w-8 h-8 rounded-full overflow-hidden flex items-center justify-center border-2 border-green-200">
                                <img 
                                    :src="page.props.auth.user.avatar_url" 
                                    :alt="page.props.auth.user.name"
                                    class="w-full h-full object-cover"
                                    @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(page.props.auth.user.name)}&background=22C55E&color=fff&size=32`"
                                />
                            </div>
                            <span>{{ page.props.auth.user.name }}</span>
                            <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': showDropdown }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <div 
                            v-show="showDropdown"
                            class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl py-2 z-[10000] border border-green-200 backdrop-blur-md"
                            @click="closeDropdown"
                        >
                            <div class="px-4 py-3 border-b border-green-200">
                                <p class="text-sm text-gray-900 font-medium">{{ page.props.auth.user.name }}</p>
                                <p class="text-sm text-green-600">{{ page.props.auth.user.email }}</p>
                            </div>
                            
                            <Link 
                                :href="route('profile.show', { user: page.props.auth.user.id })"
                                class="flex items-center px-4 py-3 text-sm text-gray-700 hover:text-green-600 hover:bg-green-50 cursor-pointer transition-colors duration-200"
                            >
                                <i class="fas fa-user mr-3 text-green-500"></i>
                                Profile
                            </Link>
                            
                            <Link 
                                :href="route('profile.edit')"
                                class="flex items-center px-4 py-3 text-sm text-gray-700 hover:text-green-600 hover:bg-green-50 cursor-pointer transition-colors duration-200"
                            >
                                <i class="fas fa-cog mr-3 text-green-500"></i>
                                Settings
                            </Link>
                            
                            <div class="border-t border-green-200 my-2"></div>
                            
                            <button 
                                @click="logout"
                                class="w-full flex items-center px-4 py-3 text-sm text-red-600 hover:text-red-700 hover:bg-red-50 cursor-pointer transition-colors duration-200"
                            >
                                <i class="fas fa-sign-out-alt mr-3"></i>
                                Logout
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Guest Navigation -->
                <div class="hidden md:flex items-center space-x-4" v-else>
                    <Link
                        :href="route('login')"
                        class="text-gray-700 hover:text-green-600 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 hover:bg-green-50"
                    >
                        Login
                    </Link>
                    <Link
                        :href="route('register')"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg text-sm font-medium transition-all duration-200 shadow-md hover:shadow-lg transform hover:scale-105"
                    >
                        Join Now
                    </Link>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button 
                        @click="showMobileMenu = !showMobileMenu"
                        class="text-gray-700 hover:text-green-600 p-2 rounded-lg transition-colors duration-200"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-if="!showMobileMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation -->
            <div v-show="showMobileMenu" class="md:hidden py-4 border-t border-green-200">
                <div class="space-y-2" v-if="page.props.auth.user">
                    <Link
                        :href="route('tournaments.index')"
                        class="block text-gray-700 hover:text-green-600 px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 hover:bg-green-50"
                    >
                        <i class="fas fa-trophy mr-2"></i>
                        My Tournaments
                    </Link>
                    <Link
                        :href="route('schedule.index')"
                        class="block text-gray-700 hover:text-green-600 px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 hover:bg-green-50"
                    >
                        <i class="fas fa-calendar mr-2"></i>
                        Schedule
                    </Link>
                    <Link
                        :href="route('tournaments.create')"
                        class="block bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium"
                    >
                        <i class="fas fa-plus mr-2"></i>
                        Create Tournament
                    </Link>
                    <Link
                        :href="route('profile.show', { user: page.props.auth.user.id })"
                        class="block text-gray-700 hover:text-green-600 px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 hover:bg-green-50"
                    >
                        <i class="fas fa-user mr-2"></i>
                        Profile
                    </Link>
                    <button 
                        @click="logout"
                        class="block w-full text-left text-red-600 hover:text-red-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 hover:bg-red-50"
                    >
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        Logout
                    </button>
                </div>
                <div class="space-y-2" v-else>
                    <Link
                        :href="route('login')"
                        class="block text-gray-700 hover:text-green-600 px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 hover:bg-green-50"
                    >
                        Login
                    </Link>
                    <Link
                        :href="route('register')"
                        class="block bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium"
                    >
                        Join Now
                    </Link>
                </div>
            </div>
        </div>
    </header>
</template> 