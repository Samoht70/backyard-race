import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

function brandColor(): string {
    if (typeof window === 'undefined') {
        return '#2f43c8';
    }

    const token = getComputedStyle(document.documentElement)
        .getPropertyValue('--primary')
        .trim();

    return token === '' ? '#2f43c8' : token;
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
            case name === 'DesignSystem':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            default:
                return AppLayout;
        }
    },
    progress: {
        color: brandColor(),
        delay: 100,
    },
});

initializeTheme();
initializeFlashToast();
