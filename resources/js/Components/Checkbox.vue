<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: {
        type: [Array, Boolean],
        required: true,
    },
    value: {
        default: null,
    },
});

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        emit('update:checked', val);
    },
});
</script>

<template>
    <input
        type="checkbox"
        class="rounded border-gray-300 bg-white text-green-600 shadow-sm focus:border-green-500 focus:ring-green-500"
        :checked="checked"
        @change="$emit('update:checked', $event.target.checked)"
    />
</template>
