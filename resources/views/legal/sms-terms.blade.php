<!DOCTYPE html>
@php($cspNonce = request()->attributes->get('csp_nonce', ''))
@php(\Illuminate\Support\Facades\Vite::useCspNonce($cspNonce))
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Text Message Terms - Colorado Supply &amp; Procurement</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css'])
</head>
{{-- Text message program terms (ai/modules/will-call-notifications, phase 6). Reviewed by carriers for A2P 10DLC. --}}
<body class="bg-white text-gray-900 antialiased dark:bg-gray-900 dark:text-gray-100">
    <main class="mx-auto max-w-2xl space-y-6 px-4 py-10 text-base leading-7">
        <a href="{{ url('/') }}" class="text-sm font-semibold text-blue-700 underline dark:text-blue-300">Colorado Supply &amp; Procurement</a>
        <h1 class="text-3xl font-bold">Text message terms</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">Last updated {{ \Illuminate\Support\Carbon::parse(config('sms.consent_version'))->format('F j, Y') }}</p>

        <section class="space-y-2">
            <h2 class="text-xl font-semibold">What you'll receive</h2>
            <p>Colorado Supply &amp; Procurement LLC sends text messages only to people who turn them on in their account and agree to these terms:</p>
            <ul class="list-disc space-y-1 pl-6">
                <li><strong>Customers:</strong> a text when a will call (counter pickup) order is ready, with its pickup code, and reminders if it hasn't been collected.</li>
                <li><strong>Our staff:</strong> alerts about new will call orders.</li>
            </ul>
            <p>We never send marketing or promotional texts through this program. Message frequency varies with your orders. Message and data rates may apply.</p>
        </section>

        <section class="space-y-2">
            <h2 class="text-xl font-semibold">How to opt in</h2>
            <p>Enter your mobile number on your Notifications page, tick the consent box, and confirm the 6-digit code we text you. Consent is not a condition of purchase.</p>
        </section>

        <section class="space-y-2">
            <h2 class="text-xl font-semibold">How to stop or get help</h2>
            <p>Reply <strong>STOP</strong> to any message to stop all texts; we'll confirm and send nothing more. Reply <strong>START</strong> to turn them back on. Reply <strong>HELP</strong> for help, or contact us through our website. You can also turn texts off on your Notifications page at any time.</p>
        </section>

        <section class="space-y-2">
            <h2 class="text-xl font-semibold">Quiet hours</h2>
            <p>Texts that would arrive between {{ \Illuminate\Support\Carbon::parse(config('sms.quiet_hours.start'))->format('g:i A') }} and {{ \Illuminate\Support\Carbon::parse(config('sms.quiet_hours.end'))->format('g:i A') }} Mountain Time are held until morning.</p>
        </section>

        <section class="space-y-2">
            <h2 class="text-xl font-semibold">Your privacy</h2>
            <p>Your mobile number and consent are used only to send the messages described above. We do not sell, rent or share mobile numbers or text opt-in information with third parties or affiliates for marketing purposes. Messages are delivered through our messaging provider, Twilio.</p>
            <p>Carriers are not liable for delayed or undelivered messages.</p>
        </section>
    </main>
</body>
</html>
