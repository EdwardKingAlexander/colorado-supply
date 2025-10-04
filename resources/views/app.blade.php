<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark"> {{-- dY`^ enable dark mode --}}
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/svg+xml" href="{{ asset("favicon.svg") }}">

        <title inertia>{{ config('app.name', 'Colorado Supply & Procurement LLC') }}</title>

        <!-- Fonts: Inter -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" media="all" />

        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-RZ06XS51X0"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', 'G-RZ06XS51X0');
        </script>

        @if (config('services.google.recaptcha.site_key'))
            <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.google.recaptcha.site_key') }}" async defer></script>
            <script>
                window.googleRecaptchaSiteKey = "{{ config('services.google.recaptcha.site_key') }}";
            </script>
        @endif

        <!-- Scripts -->
        @routes
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-white dark:bg-gray-900 scroll-smooth scroll-pt-16" >
        @inertia
    </body>
</html>


