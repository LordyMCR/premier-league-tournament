<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const isLocal = computed(() => page.props.isLocal === true)
</script>

<template>
    <div
        v-if="isLocal"
        class="bg-amber-400 text-amber-950 text-center text-sm font-semibold tracking-wide py-1.5 px-4 border-b border-amber-500"
        role="status"
    >
        LOCAL DEVELOPMENT — not production
    </div>
</template>
