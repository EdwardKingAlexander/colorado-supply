<x-filament-panels::page>
    <p class="text-sm text-gray-600 dark:text-gray-300">
        Choose how you hear about store activity. Everything starts on; turning a channel off here affects only you.
        Push alerts reach only the devices you turn on below.
    </p>

    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit" icon="heroicon-o-check">Save</x-filament::button>
    </form>

    @if ($publicKey = $this->pushPublicKey())
        {{-- Push on this device (ai/modules/will-call-notifications, phase 5). Permission is only asked on click. --}}
        <x-filament::section heading="Push on this device" icon="heroicon-o-device-phone-mobile">
            <div
                x-data="{
                    status: 'checking',
                    busy: false,
                    error: null,
                    key: @js($publicKey),
                    async init() {
                        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                            const ios = /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)
                            this.status = ios ? 'ios-install' : 'unsupported'
                            return
                        }
                        if (Notification.permission === 'denied') { this.status = 'denied'; return }
                        const registration = await navigator.serviceWorker.getRegistration('/sw.js')
                        const subscription = await registration?.pushManager.getSubscription()
                        this.status = subscription && Notification.permission === 'granted' ? 'on' : 'off'
                    },
                    toKey(base64) {
                        const padded = (base64 + '='.repeat((4 - base64.length % 4) % 4)).replace(/-/g, '+').replace(/_/g, '/')
                        return Uint8Array.from(atob(padded), (c) => c.charCodeAt(0))
                    },
                    async turnOn() {
                        this.busy = true; this.error = null
                        try {
                            const permission = await Notification.requestPermission()
                            if (permission !== 'granted') { this.status = permission === 'denied' ? 'denied' : 'off'; return }
                            const registration = await navigator.serviceWorker.register('/sw.js')
                            await navigator.serviceWorker.ready
                            const subscription = (await registration.pushManager.getSubscription())
                                ?? await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: this.toKey(this.key) })
                            const json = subscription.toJSON()
                            await $wire.savePushSubscription({ endpoint: json.endpoint, keys: json.keys, content_encoding: (PushManager.supportedContentEncodings ?? ['aes128gcm'])[0] })
                            this.status = 'on'
                        } catch (e) {
                            this.error = 'Notifications could not be turned on. Please try again.'
                        } finally { this.busy = false }
                    },
                    async turnOff() {
                        this.busy = true; this.error = null
                        try {
                            const registration = await navigator.serviceWorker.getRegistration('/sw.js')
                            const subscription = await registration?.pushManager.getSubscription()
                            if (subscription) {
                                await $wire.removePushDevice(null, subscription.endpoint)
                                await subscription.unsubscribe()
                            }
                            this.status = 'off'
                        } catch (e) {
                            this.error = 'Notifications could not be turned off. Please try again.'
                        } finally { this.busy = false }
                    },
                }"
                class="space-y-3 text-sm"
            >
                <p x-show="status === 'checking'" class="text-gray-500">Checking this browser…</p>
                <p x-show="status === 'unsupported'" x-cloak class="text-gray-600 dark:text-gray-300">This browser doesn't support notifications. Try Chrome, Edge, Firefox or Safari.</p>
                <p x-show="status === 'ios-install'" x-cloak class="text-gray-600 dark:text-gray-300">On iPhone and iPad: tap Share, choose <strong>Add to Home Screen</strong>, open the site from your Home Screen, then turn notifications on here.</p>
                <p x-show="status === 'denied'" x-cloak class="text-gray-600 dark:text-gray-300">Notifications are blocked for this site. Allow them in your browser's site settings, then reload.</p>

                <div x-show="status === 'on' || status === 'off'" x-cloak class="flex flex-wrap items-center gap-3">
                    <span class="font-semibold" x-text="status === 'on' ? 'On for this device' : 'Off for this device'"></span>
                    <x-filament::button x-show="status === 'off'" x-on:click="turnOn" x-bind:disabled="busy" icon="heroicon-o-bell-alert">Turn on notifications</x-filament::button>
                    <x-filament::button x-show="status === 'on'" x-on:click="turnOff" x-bind:disabled="busy" color="gray">Turn off for this device</x-filament::button>
                </div>
                <p x-show="error" x-text="error" x-cloak class="text-danger-600 dark:text-danger-400" role="alert"></p>

                @if ($devices = $this->getPushDevices())
                    <div class="border-t border-gray-200 pt-3 dark:border-white/10">
                        <h3 class="font-semibold">Devices with notifications on</h3>
                        <ul class="mt-1 divide-y divide-gray-200 dark:divide-white/10">
                            @foreach ($devices as $device)
                                <li class="flex items-center justify-between gap-3 py-1" wire:key="push-device-{{ $device['id'] }}">
                                    <span>{{ $device['label'] }} <span class="text-gray-500">· added {{ $device['added'] }}</span></span>
                                    <x-filament::link tag="button" wire:click="removePushDevice({{ $device['id'] }})" color="danger" aria-label="Remove {{ $device['label'] }}">Remove</x-filament::link>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
