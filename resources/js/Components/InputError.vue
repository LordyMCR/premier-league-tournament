<script setup>
defineProps({
    message: {
        type: String,
    },
});
</script>

<template>
    <div v-show="message">
        <p class="text-sm text-red-300 mt-1">
            {{ message }}
        </p>
    </div>
</template>
