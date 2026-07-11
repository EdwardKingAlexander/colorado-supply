<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    enabled: Boolean,
    method: { type: String, default: null },
    setup: { type: Object, default: null },
    recoveryCodes: { type: Array, default: null },
});

// Which enrollment step we are on is driven by the server: `setup` present
// means enrollment is in progress; `enabled` means a confirmed factor exists.
const enrolling = computed(() => props.setup !== null);

const enableForm = useForm({ method: 'totp' });
const confirmForm = useForm({ code: '' });
const disableForm = useForm({ password: '' });
const recoveryForm = useForm({});

const showDisable = ref(false);

const startEnroll = (method) => {
    enableForm.method = method;
    enableForm.post(route('mfa.enable'), { preserveScroll: true });
};

const confirm = () => {
    confirmForm.post(route('mfa.confirm'), {
        preserveScroll: true,
        onSuccess: () => confirmForm.reset(),
    });
};

const regenerate = () => {
    recoveryForm.post(route('mfa.recovery-codes'), { preserveScroll: true });
};

const disable = () => {
    disableForm.delete(route('mfa.disable'), {
        preserveScroll: true,
        onSuccess: () => {
            disableForm.reset();
            showDisable.value = false;
        },
    });
};

const copyRecoveryCodes = () => {
    if (props.recoveryCodes) {
        navigator.clipboard?.writeText(props.recoveryCodes.join('\n'));
    }
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Two-Factor Authentication
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Add a second step to sign-in using an authenticator app or a
                code emailed to you. This is required for accessing government
                and CUI-related data.
            </p>
        </header>

        <!-- Freshly generated recovery codes (shown once) -->
        <div
            v-if="recoveryCodes"
            class="rounded-md border border-amber-300 bg-amber-50 p-4"
        >
            <p class="text-sm font-medium text-amber-800">
                Save your recovery codes
            </p>
            <p class="mt-1 text-sm text-amber-700">
                Store these somewhere safe. Each code can be used once to sign
                in if you lose access to your authenticator or email. They will
                not be shown again.
            </p>
            <ul
                class="mt-3 grid grid-cols-2 gap-1 font-mono text-sm text-amber-900"
            >
                <li v-for="code in recoveryCodes" :key="code">{{ code }}</li>
            </ul>
            <SecondaryButton class="mt-3" @click="copyRecoveryCodes">
                Copy codes
            </SecondaryButton>
        </div>

        <!-- Status: enabled -->
        <div v-if="enabled && !enrolling" class="space-y-4">
            <p class="text-sm text-green-700">
                Two-factor authentication is <strong>enabled</strong> using
                {{ method === 'totp' ? 'an authenticator app' : 'email codes' }}.
            </p>

            <div class="flex flex-wrap gap-3">
                <SecondaryButton
                    :disabled="recoveryForm.processing"
                    @click="regenerate"
                >
                    Regenerate recovery codes
                </SecondaryButton>
                <DangerButton
                    v-if="!showDisable"
                    @click="showDisable = true"
                >
                    Disable
                </DangerButton>
            </div>

            <form
                v-if="showDisable"
                class="max-w-md space-y-3"
                @submit.prevent="disable"
            >
                <InputLabel for="disable_password" value="Confirm your password" />
                <TextInput
                    id="disable_password"
                    v-model="disableForm.password"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                />
                <InputError :message="disableForm.errors.password" />
                <div class="flex gap-3">
                    <DangerButton :disabled="disableForm.processing">
                        Disable two-factor
                    </DangerButton>
                    <SecondaryButton type="button" @click="showDisable = false">
                        Cancel
                    </SecondaryButton>
                </div>
            </form>
        </div>

        <!-- Enrollment in progress -->
        <div v-else-if="enrolling" class="space-y-4">
            <div v-if="setup.method === 'totp'" class="space-y-3">
                <p class="text-sm text-gray-600">
                    Scan this QR code with your authenticator app (Google
                    Authenticator, Authy, 1Password, …), then enter the 6-digit
                    code it shows.
                </p>
                <div class="inline-block rounded-md bg-white p-2 ring-1 ring-gray-200" v-html="setup.qr" />
                <p class="text-xs text-gray-500">
                    Can’t scan? Enter this key manually:
                    <span class="font-mono">{{ setup.secret }}</span>
                </p>
            </div>
            <p v-else class="text-sm text-gray-600">
                We emailed a 6-digit code to your address. Enter it below to turn
                on email-based two-factor authentication.
            </p>

            <form class="max-w-xs space-y-3" @submit.prevent="confirm">
                <InputLabel for="mfa_code" value="Verification code" />
                <TextInput
                    id="mfa_code"
                    v-model="confirmForm.code"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    class="mt-1 block w-full"
                />
                <InputError :message="confirmForm.errors.code" />
                <PrimaryButton :disabled="confirmForm.processing">
                    Confirm & enable
                </PrimaryButton>
            </form>
        </div>

        <!-- Not enrolled: choose a method -->
        <div v-else class="flex flex-wrap gap-3">
            <PrimaryButton
                :disabled="enableForm.processing"
                @click="startEnroll('totp')"
            >
                Set up authenticator app
            </PrimaryButton>
            <SecondaryButton
                :disabled="enableForm.processing"
                @click="startEnroll('email')"
            >
                Use email codes
            </SecondaryButton>
            <InputError :message="enableForm.errors.method" class="w-full" />
        </div>
    </section>
</template>
