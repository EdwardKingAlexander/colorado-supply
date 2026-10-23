<script setup>
import AccountLayout from '@/Layouts/AccountLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import TwoFactorAuthenticationForm from './Partials/TwoFactorAuthenticationForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    twoFactorEnabled: {
        type: Boolean,
        default: false,
    },
    twoFactorMethod: {
        type: String,
        default: null,
    },
    twoFactorSetup: {
        type: Object,
        default: null,
    },
    mfaRecoveryCodes: {
        type: Array,
        default: null,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AccountLayout
        title="Profile"
        description="Your sign-in details, password and two-factor authentication."
        narrow
    >
        <div class="space-y-6">
            <div class="st-panel p-5 sm:p-8">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-xl"
                />
            </div>

            <div class="st-panel p-5 sm:p-8">
                <UpdatePasswordForm class="max-w-xl" />
            </div>

            <div class="st-panel p-5 sm:p-8">
                <TwoFactorAuthenticationForm
                    class="max-w-xl"
                    :enabled="twoFactorEnabled"
                    :method="twoFactorMethod"
                    :setup="twoFactorSetup"
                    :recovery-codes="mfaRecoveryCodes"
                />
            </div>

            <div class="st-panel border-accent-600/40 p-5 sm:p-8">
                <DeleteUserForm class="max-w-xl" />
            </div>
        </div>
    </AccountLayout>
</template>
