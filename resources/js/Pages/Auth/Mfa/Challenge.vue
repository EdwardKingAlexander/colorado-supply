<script setup>
import BrandedAuthLayout from '@/Layouts/BrandedAuthLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    method: { type: String, default: null },
    status: { type: String, default: null },
});

const useRecovery = ref(false);

const form = useForm({
    code: '',
    recovery_code: '',
});

const submit = () => {
    form.transform((data) =>
        useRecovery.value
            ? { recovery_code: data.recovery_code }
            : { code: data.code },
    ).post(route('mfa.challenge.verify'), {
        onFinish: () => form.reset('code', 'recovery_code'),
    });
};

const emailForm = useForm({});
const sendEmailCode = () => {
    emailForm.post(route('mfa.challenge.email'), { preserveScroll: true });
};
</script>

<template>
    <BrandedAuthLayout
        title="Two-step verification"
        subtitle="Confirm it's you to finish signing in to your Colorado Supply account."
    >
        <Head title="Two-step verification" />

        <div
            v-if="status"
            class="mb-6 border-l-4 border-green-500 bg-green-50 px-4 py-3 text-base font-medium leading-6 text-green-800"
            role="status"
        >
            {{ status }}
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <template v-if="!useRecovery">
                <div>
                    <InputLabel for="code" value="Verification code" class="text-gray-800" />
                    <p class="mt-1 text-sm text-gray-600">
                        {{
                            method === 'totp'
                                ? 'Enter the 6-digit code from your authenticator app.'
                                : 'Enter the 6-digit code we emailed to you.'
                        }}
                    </p>
                    <TextInput
                        id="code"
                        type="text"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        autofocus
                        class="mt-2 block w-full border-gray-300 bg-white px-3 py-2.5 text-gray-950 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                        v-model="form.code"
                    />
                    <InputError class="mt-2" :message="form.errors.code" />
                </div>

                <button
                    type="button"
                    class="text-sm font-semibold text-amber-700 hover:text-amber-800"
                    @click="sendEmailCode"
                >
                    Email me a code
                </button>
            </template>

            <template v-else>
                <div>
                    <InputLabel for="recovery_code" value="Recovery code" class="text-gray-800" />
                    <p class="mt-1 text-sm text-gray-600">
                        Enter one of the recovery codes you saved when you set up
                        two-factor authentication.
                    </p>
                    <TextInput
                        id="recovery_code"
                        type="text"
                        autocomplete="one-time-code"
                        class="mt-2 block w-full border-gray-300 bg-white px-3 py-2.5 text-gray-950 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                        v-model="form.recovery_code"
                    />
                    <InputError class="mt-2" :message="form.errors.recovery_code" />
                </div>
            </template>

            <div class="flex items-center justify-between pt-2">
                <button
                    type="button"
                    class="text-sm font-semibold text-amber-700 hover:text-amber-800"
                    @click="useRecovery = !useRecovery"
                >
                    {{ useRecovery ? 'Use a verification code' : 'Use a recovery code' }}
                </button>

                <PrimaryButton
                    class="justify-center bg-primary-700 px-5 py-3 text-base hover:bg-primary-600 focus:ring-amber-500"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Verify
                </PrimaryButton>
            </div>
        </form>

        <div class="mt-6 text-center">
            <Link :href="route('logout')" as="button" method="post" class="text-sm text-gray-500 hover:text-gray-700">
                Log out
            </Link>
        </div>
    </BrandedAuthLayout>
</template>
