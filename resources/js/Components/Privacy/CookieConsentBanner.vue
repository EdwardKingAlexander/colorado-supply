<script setup>
import PrivacyPreferencesModal from '@/Components/Privacy/PrivacyPreferencesModal.vue';
import { applyConsent, saveConsent } from '@/Utils/consent.js';
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const page = usePage();
const privacy = computed(() => page.props.privacy ?? {});
const consent = ref(privacy.value.consent ?? null);
const browserGpc = ref(false);
const gpcDismissed = ref(false);
const preferencesOpen = ref(false);
const saving = ref(false);
const errorMessage = ref('');

const effectiveGpc = computed(() => Boolean(privacy.value.gpc || browserGpc.value));
const currentConsent = computed(() => consent.value?.version === privacy.value.policyVersion ? consent.value : null);
const categories = computed(() => privacy.value.categories ?? {});
const allCategories = computed(() => Object.keys(categories.value));
const selectedCategories = computed(() => currentConsent.value?.categories ?? ['essential']);
const showBanner = computed(() => !effectiveGpc.value && !currentConsent.value);
const showGpcNotice = computed(() => effectiveGpc.value && !gpcDismissed.value);

const persist = async (selected, gpc = false) => {
    saving.value = true;
    errorMessage.value = '';

    try {
        const data = await saveConsent(selected, gpc);
        consent.value = data.consent;
        preferencesOpen.value = false;
    } catch {
        errorMessage.value = 'We could not save your privacy choice. Please try again.';
    } finally {
        saving.value = false;
    }
};

const dismissGpc = () => {
    gpcDismissed.value = true;

    if (typeof sessionStorage !== 'undefined') {
        sessionStorage.setItem('cs-gpc-notice-dismissed', '1');
    }
};

onMounted(async () => {
    browserGpc.value = navigator.globalPrivacyControl === true;
    gpcDismissed.value = sessionStorage.getItem('cs-gpc-notice-dismissed') === '1';

    if (!effectiveGpc.value) {
        return;
    }

    applyConsent(['essential'], true);

    const needsGpcReceipt = !currentConsent.value
        || currentConsent.value.categories.some((category) => category !== 'essential');

    if (needsGpcReceipt) {
        await persist(['essential'], true);
    }
});
</script>

<template>
    <PrivacyPreferencesModal
        :open="preferencesOpen"
        :categories="categories"
        :selected-categories="selectedCategories"
        :saving="saving"
        @close="preferencesOpen = false"
        @save="persist($event)"
    />

    <aside
        v-if="showBanner"
        class="safe-bottom fixed inset-x-0 bottom-0 z-[100] border-t border-gray-300 bg-white p-4 text-gray-900 shadow-[0_-8px_30px_rgba(15,23,42,0.18)] sm:p-5"
        aria-label="Cookie consent"
    >
        <div class="mx-auto max-w-6xl lg:flex lg:items-center lg:gap-8">
            <div class="min-w-0 flex-1">
                <h2 class="text-xl font-bold">Your privacy choices</h2>
                <p class="mt-2 text-base leading-6 text-gray-600">
                    We use strictly necessary technologies to operate this site. With your permission, analytics helps us improve it. Marketing cookies are not currently used.
                </p>
                <p v-if="errorMessage" class="mt-2 text-sm font-semibold text-red-700" role="alert">{{ errorMessage }}</p>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-3 lg:mt-0 lg:w-[34rem]">
                <button
                    type="button"
                    class="inline-flex min-h-12 items-center justify-center rounded-md border border-gray-400 bg-white px-4 text-base font-semibold text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 disabled:opacity-60"
                    :disabled="saving"
                    @click="persist(allCategories)"
                >
                    Accept all
                </button>
                <button
                    type="button"
                    class="inline-flex min-h-12 items-center justify-center rounded-md border border-gray-400 bg-white px-4 text-base font-semibold text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 disabled:opacity-60"
                    :disabled="saving"
                    @click="persist(['essential'])"
                >
                    Essential only
                </button>
                <button
                    type="button"
                    class="inline-flex min-h-12 items-center justify-center rounded-md border border-gray-400 bg-white px-4 text-base font-semibold text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 disabled:opacity-60"
                    :disabled="saving"
                    @click="preferencesOpen = true"
                >
                    Preferences
                </button>
            </div>
        </div>
    </aside>

    <aside
        v-else-if="showGpcNotice"
        class="safe-bottom fixed inset-x-0 bottom-0 z-[100] border-t border-emerald-300 bg-emerald-50 p-4 text-emerald-950 shadow-[0_-8px_30px_rgba(15,23,42,0.16)] sm:p-5"
        aria-label="Global Privacy Control honored"
    >
        <div class="mx-auto flex max-w-6xl flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-bold">Global Privacy Control honored</h2>
                <p class="mt-1 text-base leading-6">Optional analytics and advertising storage are disabled for this browser.</p>
                <p v-if="errorMessage" class="mt-2 text-sm font-semibold text-red-700" role="alert">{{ errorMessage }}</p>
            </div>
            <button
                type="button"
                class="inline-flex min-h-12 shrink-0 items-center justify-center rounded-md border border-emerald-800 bg-white px-5 text-base font-semibold text-emerald-950 hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2"
                @click="dismissGpc"
            >
                Dismiss
            </button>
        </div>
    </aside>

    <button
        v-else-if="currentConsent && !effectiveGpc"
        type="button"
        class="safe-bottom fixed bottom-3 left-3 z-[90] inline-flex min-h-11 items-center justify-center rounded-full border border-gray-400 bg-white px-4 text-sm font-semibold text-gray-800 shadow-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 sm:bottom-4 sm:left-4"
        @click="preferencesOpen = true"
    >
        Privacy choices
    </button>
</template>
