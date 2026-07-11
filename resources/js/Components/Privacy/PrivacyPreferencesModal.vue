<script setup>
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue';
import { reactive, watch } from 'vue';

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    categories: {
        type: Object,
        required: true,
    },
    selectedCategories: {
        type: Array,
        default: () => ['essential'],
    },
    saving: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'save']);
const selections = reactive({});

const resetSelections = () => {
    for (const [key, category] of Object.entries(props.categories)) {
        selections[key] = category.locked || props.selectedCategories.includes(key);
    }
};

watch(() => props.open, (open) => {
    if (open) {
        resetSelections();
    }
}, { immediate: true });

const save = () => {
    emit('save', Object.keys(selections).filter((key) => selections[key]));
};
</script>

<template>
    <Dialog :open="open" class="relative z-[110]" @close="saving ? null : emit('close')">
        <div class="fixed inset-0 bg-gray-950/60" aria-hidden="true" />

        <div class="safe-y fixed inset-0 overflow-y-auto px-4 py-6 sm:px-6">
            <div class="flex min-h-full items-center justify-center">
                <DialogPanel class="w-full max-w-xl rounded-xl bg-white p-5 text-gray-900 shadow-2xl sm:p-7">
                    <DialogTitle class="text-2xl font-bold">Privacy preferences</DialogTitle>
                    <p class="mt-2 text-base leading-6 text-gray-600">
                        Choose which optional technologies may run. Strictly necessary services are always active.
                    </p>

                    <div class="mt-6 divide-y divide-gray-200 rounded-lg border border-gray-200">
                        <label
                            v-for="(category, key) in categories"
                            :key="key"
                            class="flex min-h-20 gap-4 p-4"
                            :class="category.locked ? 'cursor-not-allowed bg-gray-50' : 'cursor-pointer'"
                        >
                            <span class="min-w-0 flex-1">
                                <span class="block text-base font-semibold text-gray-900">{{ category.label }}</span>
                                <span class="mt-1 block text-sm leading-5 text-gray-600">{{ category.description }}</span>
                            </span>
                            <input
                                v-model="selections[key]"
                                type="checkbox"
                                :disabled="category.locked"
                                class="mt-1 h-6 w-6 rounded border-gray-300 text-indigo-700 focus:ring-indigo-600 disabled:opacity-60"
                            />
                        </label>
                    </div>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        <button
                            type="button"
                            class="inline-flex min-h-12 items-center justify-center rounded-md border border-gray-300 bg-white px-4 text-base font-semibold text-gray-800 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2"
                            :disabled="saving"
                            @click="emit('close')"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="inline-flex min-h-12 items-center justify-center rounded-md border border-indigo-800 bg-indigo-800 px-4 text-base font-semibold text-white hover:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 disabled:opacity-60"
                            :disabled="saving"
                            @click="save"
                        >
                            {{ saving ? 'Saving…' : 'Save preferences' }}
                        </button>
                    </div>
                </DialogPanel>
            </div>
        </div>
    </Dialog>
</template>
