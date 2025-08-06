<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        type="button"
        class="bg-white border border-green-200 text-gray-700 font-medium py-2 px-4 rounded-lg transition-all duration-200 hover:bg-green-50 hover:shadow-md"
    >
        <slot />
    </button>
</template>
