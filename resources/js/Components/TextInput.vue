<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
    modelValue: String,
    type: {
        type: String,
        default: 'text'
    },
    placeholder: String,
    id: String,
    required: Boolean,
    autofocus: Boolean,
    autocomplete: String,
    class: String,
    disabled: Boolean,
    readonly: Boolean,
});

defineEmits(['update:modelValue']);

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <div>
        <input
            :type="type"
            :placeholder="placeholder"
            :id="id"
            :required="required"
            :autofocus="autofocus"
            :autocomplete="autocomplete"
            :disabled="disabled"
            :readonly="readonly"
            :class="[
                'w-full border border-gray-300 placeholder-gray-600 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200',
                disabled || readonly
                    ? 'bg-gray-100 text-gray-600 cursor-not-allowed'
                    : 'bg-white text-gray-900',
                props.class,
            ]"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            ref="input"
        />
    </div>
</template>
