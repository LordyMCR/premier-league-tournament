<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="w-full rounded-lg border-0 bg-white/20 backdrop-blur-sm px-4 py-3 text-white placeholder-white/60 shadow-sm ring-1 ring-white/30 focus:ring-2 focus:ring-emerald-500 focus:bg-white/30 transition-all"
        v-model="model"
        ref="input"
    />
</template>
