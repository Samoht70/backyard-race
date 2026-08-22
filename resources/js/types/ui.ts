import type { InertiaLinkProps } from '@inertiajs/vue3';

export type Href = NonNullable<InertiaLinkProps['href']>;

export type Appearance = 'light' | 'dark' | 'system';
export type ResolvedAppearance = 'light' | 'dark';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
};

export type BoardFilterOption = {
    value: string | null;
    label: string;
    href: Href;
    count: number;
};

export type Pagination = {
    current_page: number;
    last_page: number;
};

export type PageLink = {
    page: number;
    href: Href;
    current: boolean;
};
