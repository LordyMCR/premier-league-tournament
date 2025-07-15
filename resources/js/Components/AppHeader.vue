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
    <header class="bg-white/10 backdrop-blur-md border-b border-white/20 relative z-[9999]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <!-- Logo -->
                <div class="flex items-center">
                    <Link :href="route('welcome')" class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h1 class="text-2xl font-bold text-white">PL Tournament</h1>
                    </Link>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-6" v-if="page.props.auth.user">
                    <!-- Quick Actions -->
                    <Link
                        :href="route('tournaments.index')"
                        class="text-white/90 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors"
                    >
                        My Tournaments
                    </Link>
                    <Link
                        :href="route('tournaments.create')"
                        class="bg-gradient-to-r from-green-500 to-emerald-600 text-white hover:from-green-600 hover:to-emerald-700 px-3 py-2 rounded-md text-sm font-medium transition-all"
                    >
                        Create Tournament
                    </Link>
                    
                    <!-- User Dropdown -->
                    <div class="relative">
                        <button 
                            @click="toggleDropdown"
                            class="flex items-center space-x-2 text-white/90 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors"
                        >
                            <div class="w-8 h-8 rounded-full overflow-hidden flex items-center justify-center">
                                <img 
                                    :src="page.props.auth.user.avatar_url" 
                                    :alt="page.props.auth.user.name"
                                    class="w-full h-full object-cover"
                                    @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(page.props.auth.user.name)}&background=10B981&color=fff&size=32`"
                                />
                            </div>
                            <span>{{ page.props.auth.user.name }}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <div 
                            v-show="showDropdown"
                            class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-[10000] border"
                            @click="closeDropdown"
                        >
                            <div class="px-4 py-3 border-b border-gray-200">
                                <p class="text-sm text-gray-900 font-medium">{{ page.props.auth.user.name }}</p>
                                <p class="text-sm text-gray-500">{{ page.props.auth.user.email }}</p>
                            </div>
                            
                            <Link 
                                :href="route('profile.show', { user: page.props.auth.user.id })"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Profile
                            </Link>
                            
                            <Link 
                                :href="route('tournaments.index')"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                                My Tournaments
                            </Link>
                            
                            <div class="border-t border-gray-200"></div>
                            
                            <Link 
                                :href="route('logout')" 
                                method="post" 
                                as="button"
                                class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer text-left"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                Log Out
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Auth Links for Guests -->
                <nav v-else class="flex items-center space-x-4">
                    <Link
                        :href="route('login')"
                        class="text-white/90 hover:text-white px-3 py-2 rounded-md text-sm font-medium"
                    >
                        Login
                    </Link>
                    <Link
                        :href="route('register')"
                        class="bg-gradient-to-r from-green-500 to-emerald-600 text-white hover:from-green-600 hover:to-emerald-700 px-4 py-2 rounded-md text-sm font-medium transition-all"
                    >
                        Join Now
                    </Link>
                </nav>

                <!-- Mobile menu button -->
                <div class="md:hidden" v-if="page.props.auth.user">
                    <button
                        @click="showMobileMenu = !showMobileMenu"
                        class="inline-flex items-center justify-center p-2 rounded-md text-white/90 hover:text-white hover:bg-white/10 transition-all"
                    >
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path
                                :class="{'hidden': showMobileMenu, 'inline-flex': !showMobileMenu }"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                            <path
                                :class="{'hidden': !showMobileMenu, 'inline-flex': showMobileMenu }"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation Menu -->
            <div v-if="page.props.auth.user" :class="{'block': showMobileMenu, 'hidden': !showMobileMenu}" class="md:hidden">
                <div class="pt-2 pb-3 space-y-1 border-t border-white/20">
                    <Link
                        :href="route('tournaments.index')"
                        class="block px-3 py-2 text-white/90 hover:text-white hover:bg-white/10 rounded-md text-sm font-medium transition-all"
                    >
                        My Tournaments
                    </Link>
                    <Link
                        :href="route('tournaments.create')"
                        class="block px-3 py-2 text-white/90 hover:text-white hover:bg-white/10 rounded-md text-sm font-medium transition-all"
                    >
                        Create Tournament
                    </Link>
                    <Link
                        :href="route('profile.show', { user: page.props.auth.user.id })"
                        class="block px-3 py-2 text-white/90 hover:text-white hover:bg-white/10 rounded-md text-sm font-medium transition-all"
                    >
                        Profile
                    </Link>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="block w-full text-left px-3 py-2 text-white/90 hover:text-white hover:bg-white/10 rounded-md text-sm font-medium transition-all"
                    >
                        Log Out
                    </Link>
                </div>
            </div>
        </div>

        <!-- Click outside to close dropdown -->
        <div 
            v-show="showDropdown" 
            @click="closeDropdown"
            class="fixed inset-0 z-[9999]"
        ></div>
    </header>
</template> 