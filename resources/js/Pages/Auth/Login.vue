<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import BrandedAuthLayout from '@/Layouts/BrandedAuthLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <BrandedAuthLayout
        title="Sign in to your account"
        subtitle="Access quotes, order history, and procurement support from your Colorado Supply account."
        secondary-action-label="Create account"
        :secondary-action-href="route('register')"
    >
        <Head title="Log in" />

        <div
            v-if="status"
            class="mb-6 border-l-4 border-green-500 bg-green-50 px-4 py-3 text-sm font-medium text-green-800"
        >
            {{ status }}
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" class="text-gray-800" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-2 block w-full border-gray-300 bg-white px-3 py-2.5 text-gray-950 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Password" class="text-gray-800" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-2 block w-full border-gray-300 bg-white px-3 py-2.5 text-gray-950 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between gap-4">
                <label class="flex items-center">
                    <Checkbox
                        name="remember"
                        v-model:checked="form.remember"
                        class="border-gray-300 text-amber-600 focus:ring-amber-500"
                    />
                    <span class="ms-2 text-sm text-gray-700"
                        >Remember me</span
                    >
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm font-semibold text-amber-700 hover:text-amber-800 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                >
                    Forgot your password?
                </Link>
            </div>

            <div class="flex flex-col gap-4 pt-2 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-600">
                    New to Colorado Supply?
                    <Link :href="route('register')" class="font-semibold text-amber-700 hover:text-amber-800">
                        Create an account
                    </Link>
                </p>
                <PrimaryButton
                    class="justify-center bg-primary-700 px-5 py-3 text-sm hover:bg-primary-600 focus:bg-primary-600 focus:ring-amber-500 active:bg-primary-800"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Log in
                </PrimaryButton>
            </div>
        </form>
    </BrandedAuthLayout>
</template>
