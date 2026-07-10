<script setup>
import BrandedAuthLayout from '@/Layouts/BrandedAuthLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <BrandedAuthLayout
        title="Create your account"
        subtitle="Set up access for quotes, ordering, and customer purchasing workflows with Colorado Supply."
        secondary-action-label="Log in"
        :secondary-action-href="route('login')"
    >
        <Head title="Register" />

        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Name" class="text-gray-800" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-2 block w-full border-gray-300 bg-white px-3 py-2.5 text-gray-950 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" class="text-gray-800" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-2 block w-full border-gray-300 bg-white px-3 py-2.5 text-gray-950 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                    v-model="form.email"
                    required
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
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel
                    for="password_confirmation"
                    value="Confirm Password"
                    class="text-gray-800"
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-2 block w-full border-gray-300 bg-white px-3 py-2.5 text-gray-950 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="flex flex-col gap-4 pt-2 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-base leading-6 text-gray-600">
                    Already have an account?
                    <Link :href="route('login')" class="font-semibold text-amber-700 hover:text-amber-800">
                        Log in
                    </Link>
                </p>
                <PrimaryButton
                    class="w-full justify-center bg-primary-700 px-5 py-3 text-base hover:bg-primary-600 focus:bg-primary-600 focus:ring-amber-500 active:bg-primary-800 sm:w-auto"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Register
                </PrimaryButton>
            </div>
        </form>
    </BrandedAuthLayout>
</template>
