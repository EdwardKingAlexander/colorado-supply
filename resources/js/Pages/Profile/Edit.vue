<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
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

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Profile
            </h2>
        </template>

        <div class="py-8 sm:py-12">
            <div class="mobile-page-gutter mx-auto max-w-7xl space-y-6 lg:px-8">
                <div
                    class="rounded-lg bg-white p-4 shadow sm:p-8"
                >
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div
                    class="rounded-lg bg-white p-4 shadow sm:p-8"
                >
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div
                    class="rounded-lg bg-white p-4 shadow sm:p-8"
                >
                    <TwoFactorAuthenticationForm
                        class="max-w-xl"
                        :enabled="twoFactorEnabled"
                        :method="twoFactorMethod"
                        :setup="twoFactorSetup"
                        :recovery-codes="mfaRecoveryCodes"
                    />
                </div>

                <div
                    class="rounded-lg bg-white p-4 shadow sm:p-8"
                >
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
