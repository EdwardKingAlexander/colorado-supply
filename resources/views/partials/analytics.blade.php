@php($measurementId = 'G-RZ06XS51X0')
<script nonce="{{ $cspNonce }}">
    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };

    window.gtag('consent', 'default', {
        analytics_storage: 'denied',
        ad_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
        wait_for_update: 500,
    });

    (function () {
        const cookieName = @json(config('privacy.consent_cookie.name'));
        const policyVersion = @json(config('privacy.policy_version'));
        const serverGpc = @json(request()->headers->get('Sec-GPC') === '1');
        const browserGpc = typeof navigator !== 'undefined' && navigator.globalPrivacyControl === true;
        const gpc = serverGpc || browserGpc;
        let consent = null;

        try {
            const prefix = `${encodeURIComponent(cookieName)}=`;
            const value = document.cookie
                .split('; ')
                .find((entry) => entry.startsWith(prefix))
                ?.slice(prefix.length);

            consent = value ? JSON.parse(decodeURIComponent(value)) : null;
        } catch (error) {
            consent = null;
        }

        if (!gpc && consent?.version === policyVersion && consent?.categories?.includes('analytics')) {
            window.gtag('consent', 'update', {
                analytics_storage: 'granted',
                ad_storage: 'denied',
                ad_user_data: 'denied',
                ad_personalization: 'denied',
            });
        }

        window.gtag('js', new Date());
        window.gtag('config', @json($measurementId), {
            anonymize_ip: true,
            allow_google_signals: false,
            allow_ad_personalization_signals: false,
        });
    })();
</script>
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $measurementId }}"></script>
