<script setup>
import { computed, ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Dialog, DialogPanel } from '@headlessui/vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const page = usePage();

const accountName = computed(() => {
    return page.props.auth?.user?.name
        ?? page.props.auth?.admin?.name
        ?? 'Account';
});

const accountEmail = computed(() => {
    return page.props.auth?.user?.email
        ?? page.props.auth?.admin?.email
        ?? null;
});

const showProfileLink = computed(() => Boolean(page.props.auth?.user));
const showAdminPanelLink = computed(() => Boolean(page.props.auth?.admin));
const canLogout = computed(() => showProfileLink.value || showAdminPanelLink.value);
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100">
            <nav
                class="safe-top relative z-50 border-b border-gray-200 bg-white"
            >
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')" class="flex min-h-12 items-center rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <ApplicationLogo
                                        class="block h-9 w-auto fill-current text-gray-800"
                                    />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div
                                class="hidden space-x-4 md:-my-px md:ms-8 md:flex lg:space-x-8"
                            >
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                >
                                    Dashboard
                                </NavLink>
                                <NavLink
                                    :href="route('store.index')"
                                    :active="route().current('store.index')"
                                >
                                    Store
                                </NavLink>
                                <NavLink
                                    v-if="showProfileLink"
                                    :href="route('dashboard.reports')"
                                    :active="route().current('dashboard.reports')"
                                >
                                    Reports
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden md:ms-6 md:flex md:items-center">
                            <!-- Settings Dropdown -->
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex min-h-12 items-center rounded-md border border-transparent bg-white px-3 py-2 text-base font-medium leading-6 text-gray-600 transition duration-150 ease-in-out hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                            >
                                                {{ accountName }}

                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink
                                            v-if="showProfileLink"
                                            :href="route('profile.edit')"
                                        >
                                            Profile
                                        </DropdownLink>
                                        <DropdownLink
                                            v-if="showAdminPanelLink"
                                            :href="route('filament.admin.pages.dashboard')"
                                            external
                                        >
                                            Admin Panel
                                        </DropdownLink>
                                        <DropdownLink
                                            v-if="canLogout"
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="flex items-center md:hidden">
                            <button
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                                class="inline-flex h-12 w-12 items-center justify-center rounded-md text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                :aria-expanded="showingNavigationDropdown"
                                aria-controls="customer-mobile-menu"
                                aria-label="Toggle account navigation"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <Dialog class="md:hidden" :open="showingNavigationDropdown" @close="showingNavigationDropdown = false">
                  <div class="fixed inset-0 z-40 bg-gray-950/55" aria-hidden="true" />
                  <DialogPanel id="customer-mobile-menu" class="safe-y fixed inset-y-0 right-0 z-50 w-full max-w-drawer overflow-y-auto bg-white pb-6 shadow-2xl ring-1 ring-gray-900/10">
                    <div class="flex h-16 items-center justify-between border-b border-gray-200 px-4">
                      <p class="text-base font-semibold text-gray-900">Account navigation</p>
                      <button type="button" class="inline-flex h-12 w-12 items-center justify-center rounded-md text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500" aria-label="Close account navigation" @click="showingNavigationDropdown = false">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" /></svg>
                      </button>
                    </div>
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                            @click="showingNavigationDropdown = false"
                        >
                            Dashboard
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('store.index')"
                            :active="route().current('store.index')"
                            @click="showingNavigationDropdown = false"
                        >
                            Store
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-if="showProfileLink"
                            :href="route('dashboard.reports')"
                            :active="route().current('dashboard.reports')"
                            @click="showingNavigationDropdown = false"
                        >
                            Reports
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div
                        class="border-t border-gray-200 pb-1 pt-4"
                    >
                        <div class="px-4 pb-2">
                            <div
                                class="text-base font-medium text-gray-800"
                            >
                                {{ accountName }}
                            </div>
                            <div
                                v-if="accountEmail"
                                class="text-sm font-medium text-gray-500"
                            >
                                {{ accountEmail }}
                            </div>
                        </div>

                        <div
                            v-if="canLogout"
                            class="mt-3 space-y-1"
                        >
                            <ResponsiveNavLink
                                v-if="showProfileLink"
                                :href="route('profile.edit')"
                                @click="showingNavigationDropdown = false"
                            >
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                v-if="showAdminPanelLink"
                                :href="route('filament.admin.pages.dashboard')"
                                external
                                @click="showingNavigationDropdown = false"
                            >
                                Admin Panel
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                                @click="showingNavigationDropdown = false"
                            >
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                  </DialogPanel>
                </Dialog>
            </nav>

            <!-- Page Heading -->
            <header
                class="bg-white shadow"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
