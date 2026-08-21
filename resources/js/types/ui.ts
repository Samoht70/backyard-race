import type { InertiaLinkProps } from '@inertiajs/vue3';

export type Appearance = 'light' | 'dark' | 'system';
export type ResolvedAppearance = 'light' | 'dark';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
};

export type BoardFilterOption = {
    value: string | null;
    label: string;
    href: NonNullable<InertiaLinkProps['href']>;
    count: number;
};
