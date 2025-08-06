<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-green-50 via-white to-green-100 relative">
        <!-- Football pitch pattern overlay -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: linear-gradient(90deg, transparent 0%, transparent 49%, rgba(34, 197, 94, 0.1) 49%, rgba(34, 197, 94, 0.1) 51%, transparent 51%, transparent 100%); background-size: 100px 100px;"></div>
        </div>
        
        <!-- Header -->
        <header class="bg-white/90 backdrop-blur-md border-b border-green-200 relative z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center">
                        <Link :href="route('welcome')" class="flex items-center space-x-3 group">
                            <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center shadow-md group-hover:shadow-lg transition-all duration-300">
                                <i class="fas fa-futbol text-white text-lg"></i>
                            </div>
                            <div class="flex flex-col">
                                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">PL Tournament</h1>
                                <span class="text-xs text-green-600 font-medium">Premier League</span>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex items-center justify-center min-h-[calc(100vh-120px)] px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="w-full max-w-md">
                <!-- Card Container -->
                <div class="bg-white/90 backdrop-blur-md rounded-2xl p-8 border border-green-200 shadow-xl">
                    <slot />
                </div>

                <!-- Back to Home Link -->
                <div class="text-center mt-6">
                    <Link 
                        :href="route('welcome')" 
                        class="text-gray-600 hover:text-green-600 text-sm transition-colors flex items-center justify-center gap-2"
                    >
                        <i class="fas fa-arrow-left"></i>
                        Back to Home
                    </Link>
                </div>
            </div>
        </main>
    </div>
</template>
