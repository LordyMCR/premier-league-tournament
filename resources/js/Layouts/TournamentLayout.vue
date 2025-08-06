<script setup>
import AppHeader from '@/Components/AppHeader.vue';
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-green-50 via-white to-green-100 relative">
        <!-- Football pitch pattern overlay -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: linear-gradient(90deg, transparent 0%, transparent 49%, rgba(34, 197, 94, 0.1) 49%, rgba(34, 197, 94, 0.1) 51%, transparent 51%, transparent 100%); background-size: 100px 100px;"></div>
        </div>
        
        <!-- Shared Header -->
        <AppHeader />

        <!-- Page Content -->
        <main class="relative z-10 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Page Header Slot -->
                <div v-if="$slots.header" class="mb-8">
                    <div class="bg-white/80 backdrop-blur-md rounded-xl p-6 border border-green-200 shadow-lg">
                        <slot name="header" />
                    </div>
                </div>

                <!-- Main Content -->
                <div class="space-y-6">
                    <slot />
                </div>
            </div>
        </main>
    </div>
</template> 