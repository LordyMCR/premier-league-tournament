<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-purple-900 via-blue-900 to-indigo-900">
        <!-- Header -->
        <header class="bg-white/10 backdrop-blur-md border-b border-white/20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
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
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex items-center justify-center min-h-[calc(100vh-120px)] px-4 sm:px-6 lg:px-8">
            <div class="w-full max-w-md">
                <!-- Card Container -->
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-8 border border-white/20 shadow-2xl">
                    <slot />
                </div>

                <!-- Back to Home Link -->
                <div class="text-center mt-6">
                    <Link 
                        :href="route('welcome')" 
                        class="text-white/70 hover:text-white text-sm transition-colors"
                    >
                        ← Back to Home
                    </Link>
                </div>
            </div>
        </main>
    </div>
</template>
