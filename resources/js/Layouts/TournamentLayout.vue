<script setup>
import AppHeader from '@/Components/AppHeader.vue';
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-purple-900 via-blue-900 to-indigo-900">
        <!-- Shared Header -->
        <AppHeader />

        <!-- Page Content -->
        <main class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Page Header Slot -->
                <div v-if="$slots.header" class="mb-8">
                    <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
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