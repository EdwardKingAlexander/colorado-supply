{{-- Inline styles: the admin panel uses Filament's precompiled CSS (no custom
     theme), so arbitrary Tailwind utilities here would not be generated. --}}
<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-building-storefront">
        <x-slot name="heading">
            Store Status
        </x-slot>

        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
            <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                <x-filament::toggle
                    state="$wire.$entangle('storeEnabled', true)"
                    on-color="success"
                    off-color="danger"
                    on-icon="heroicon-m-check"
                    off-icon="heroicon-m-x-mark"
                    aria-label="Store open"
                />

                <span style="display: flex; flex-direction: column; gap: 0.125rem;">
                    <span style="font-size: 0.875rem; font-weight: 600;">
                        {{ $storeEnabled ? 'Open' : 'Closed — Under Maintenance' }}
                    </span>
                    <span style="font-size: 0.875rem; opacity: 0.7;">
                        {{ $storeEnabled
                            ? 'Customers can browse the store and place orders.'
                            : 'Customers see the Under Maintenance page. Admins can still browse the store.' }}
                    </span>
                </span>
            </label>

            <x-filament::link
                :href="route('store.maintenance-preview')"
                target="_blank"
                icon="heroicon-m-arrow-top-right-on-square"
                icon-position="after"
                size="sm"
            >
                Preview maintenance page
            </x-filament::link>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
