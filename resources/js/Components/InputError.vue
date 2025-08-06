<script setup>
defineProps(['message']);
</script>

<template>
    <div v-show="message" class="text-red-600 text-sm mt-1">
        {{ message }}
    </div>
</template>
