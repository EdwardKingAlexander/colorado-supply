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
        :value="value"
        v-model="proxyChecked"
        class="h-5 w-5 shrink-0 rounded border-gray-300 text-primary-700 shadow-sm focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 [.store-shell_&]:text-st-fill [.store-shell_&]:focus:ring-offset-st-bg"
    />
</template>
