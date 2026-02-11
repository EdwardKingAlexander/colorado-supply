{{--
    Terminal Page Layout Component
    Wraps <x-filament-panels::page> with the shared terminal chrome.

    Usage:
        <x-terminal-page>
            <x-slot:banner>SYSTEM NAME // MODULE NAME</x-slot:banner>
            ... page content ...
        </x-terminal-page>

    Props:
        - banner (slot): Text for the classification bar. Omit to hide the bar.
        - grid (bool): Show the ambient grid background. Default: true.
        - scanlines (bool): Add scan line effect on the main panel. Default: false.
        - footer-left (string): Left footer text.
        - footer-center (string): Center footer text.
        - footer-right (string): Right footer text.
--}}

@props([
    'grid' => true,
    'scanlines' => false,
    'footerLeft' => null,
    'footerCenter' => null,
    'footerRight' => null,
])

<x-filament-panels::page>
    @include('filament.pages.partials.terminal-theme')

    <div class="t-terminal">
        {{-- Ambient grid background --}}
        @if($grid)
            <div class="t-grid-bg"></div>
        @endif

        {{-- Classification banner --}}
        @if(isset($banner))
            <div class="t-classification-bar">
                <span class="t-classification-dot"></span>
                {{ $banner }}
                <span class="t-classification-dot"></span>
            </div>
        @endif

        {{-- Page content --}}
        {{ $slot }}

        {{-- System info footer --}}
        @if($footerLeft || $footerCenter || $footerRight)
            <div class="t-sys-footer">
                @if($footerLeft)
                    <span>{{ $footerLeft }}</span>
                @endif
                @if($footerLeft && ($footerCenter || $footerRight))
                    <span class="t-sep">&bull;</span>
                @endif
                @if($footerCenter)
                    <span>{{ $footerCenter }}</span>
                @endif
                @if($footerCenter && $footerRight)
                    <span class="t-sep">&bull;</span>
                @endif
                @if($footerRight)
                    <span>{{ $footerRight }}</span>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
