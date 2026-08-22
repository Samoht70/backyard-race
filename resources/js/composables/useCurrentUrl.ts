import type { InertiaLinkProps } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { toUrl } from '@/lib/utils';

export type UseCurrentUrlReturn = {
    isCurrentUrl: (
        urlToCheck: NonNullable<InertiaLinkProps['href']>,
    ) => boolean;
};

const page = usePage();
const currentUrlReactive = computed(
    () =>
        new URL(
            page.url,
            typeof window !== 'undefined'
                ? window.location.origin
                : 'http://localhost',
        ).pathname,
);

export function useCurrentUrl(): UseCurrentUrlReturn {
    function isCurrentUrl(urlToCheck: NonNullable<InertiaLinkProps['href']>) {
        const urlString = toUrl(urlToCheck);

        if (!urlString.startsWith('http')) {
            return urlString === currentUrlReactive.value;
        }

        try {
            return new URL(urlString).pathname === currentUrlReactive.value;
        } catch {
            return false;
        }
    }

    return { isCurrentUrl };
}
