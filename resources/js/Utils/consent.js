import axios from 'axios';

const deniedConsent = {
    analytics_storage: 'denied',
    ad_storage: 'denied',
    ad_user_data: 'denied',
    ad_personalization: 'denied',
};

export const applyConsent = (categories, gpc = false) => {
    if (typeof window === 'undefined') {
        return;
    }

    const analyticsGranted = !gpc && categories.includes('analytics');

    window.gtag?.('consent', 'update', {
        ...deniedConsent,
        analytics_storage: analyticsGranted ? 'granted' : 'denied',
    });

    if (!analyticsGranted) {
        expireGoogleAnalyticsCookies();
    }
};

export const saveConsent = async (categories, gpc = false) => {
    const { data } = await axios.post(route('privacy.consent.store'), {
        categories,
        gpc,
    });

    applyConsent(data.consent.categories, data.gpc_applied);

    return data;
};

export const expireGoogleAnalyticsCookies = () => {
    if (typeof document === 'undefined') {
        return;
    }

    const names = document.cookie
        .split(';')
        .map((entry) => entry.trim().split('=')[0])
        .filter((name) => name === '_ga' || name.startsWith('_ga_'));

    const hostname = window.location.hostname;
    const domains = ['', hostname, `.${hostname}`];

    for (const name of names) {
        for (const domain of domains) {
            const domainPart = domain ? `; domain=${domain}` : '';
            document.cookie = `${name}=; Max-Age=0; path=/${domainPart}; SameSite=Lax`;
        }
    }
};
