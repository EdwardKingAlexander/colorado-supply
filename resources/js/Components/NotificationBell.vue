<script setup>
import axios from 'axios';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const root = ref(null);
const open = ref(false);
const loading = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);
const loadError = ref(false);

const loadNotifications = async () => {
    loading.value = true;
    loadError.value = false;

    try {
        const { data } = await axios.get(route('notifications.index'));
        notifications.value = data.notifications;
        unreadCount.value = data.unread_count;
    } catch {
        loadError.value = true;
    } finally {
        loading.value = false;
    }
};

const toggle = () => {
    open.value = !open.value;

    if (open.value) {
        loadNotifications();
    }
};

const markAllRead = async () => {
    await axios.post(route('notifications.read'));
    unreadCount.value = 0;
    notifications.value = notifications.value.map((notification) => ({
        ...notification,
        read_at: notification.read_at ?? new Date().toISOString(),
    }));
};

const closeOutside = (event) => {
    if (open.value && root.value && !root.value.contains(event.target)) {
        open.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', closeOutside);
    loadNotifications();
});

onBeforeUnmount(() => document.removeEventListener('click', closeOutside));
</script>

<template>
    <div ref="root" class="relative">
        <button
            type="button"
            class="relative inline-flex h-12 w-12 items-center justify-center rounded-md text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            :aria-expanded="open"
            aria-haspopup="true"
            aria-label="Order notifications"
            @click="toggle"
        >
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.85 23.85 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
            </svg>
            <span
                v-if="unreadCount"
                class="absolute right-1 top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-xs font-bold text-white"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <div
            v-if="open"
            class="absolute right-0 z-50 mt-2 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-lg bg-white shadow-xl ring-1 ring-black/10"
        >
            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                <h2 class="text-base font-semibold text-gray-900">Order notifications</h2>
                <button
                    v-if="unreadCount"
                    type="button"
                    class="text-sm font-semibold text-indigo-700 hover:text-indigo-900 focus:outline-none focus:underline"
                    @click="markAllRead"
                >
                    Mark all read
                </button>
            </div>

            <div v-if="loading && !notifications.length" class="px-4 py-6 text-center text-sm text-gray-500">
                Loading updates…
            </div>
            <div v-else-if="loadError" class="px-4 py-6 text-center text-sm text-red-700">
                Notifications could not be loaded.
            </div>
            <div v-else-if="!notifications.length" class="px-4 py-6 text-center text-sm text-gray-500">
                No order updates yet.
            </div>
            <ul v-else class="max-h-96 divide-y divide-gray-100 overflow-y-auto">
                <li v-for="notification in notifications" :key="notification.id">
                    <a
                        :href="notification.tracker_url || '#'"
                        class="block px-4 py-3 hover:bg-gray-50 focus:bg-gray-50 focus:outline-none"
                        :class="{ 'bg-indigo-50/60': !notification.read_at }"
                    >
                        <p class="text-sm font-medium text-gray-900">{{ notification.label }}</p>
                        <p v-if="notification.order_number" class="mt-0.5 text-sm text-gray-600">
                            {{ notification.order_number }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">{{ notification.created_human }}</p>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</template>
