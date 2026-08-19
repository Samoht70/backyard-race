import type { ComputedRef, Ref } from 'vue';
import { computed, onMounted, ref } from 'vue';
import type { Appearance, ResolvedAppearance } from '@/types';

export type { Appearance, ResolvedAppearance };

export type UseAppearanceReturn = {
    appearance: Ref<Appearance>;
    resolvedAppearance: ComputedRef<ResolvedAppearance>;
    updateAppearance: (value: Appearance) => void;
    toggleAppearance: () => void;
};

const mediaQuery = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.matchMedia('(prefers-color-scheme: dark)');
};

const prefersDark = (): boolean => mediaQuery()?.matches ?? false;

const resolve = (value: Appearance): ResolvedAppearance => {
    if (value === 'system') {
        return prefersDark() ? 'dark' : 'light';
    }

    return value;
};

export function updateTheme(value: Appearance): void {
    if (typeof window === 'undefined') {
        return;
    }

    document.documentElement.classList.toggle(
        'dark',
        resolve(value) === 'dark',
    );
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const getStoredAppearance = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return localStorage.getItem('appearance') as Appearance | null;
};

const handleSystemThemeChange = () => {
    updateTheme(getStoredAppearance() ?? 'system');
};

export function initializeTheme(): void {
    if (typeof window === 'undefined') {
        return;
    }

    updateTheme(getStoredAppearance() ?? 'system');

    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

const appearance = ref<Appearance>('system');

export function useAppearance(): UseAppearanceReturn {
    onMounted(() => {
        const savedAppearance = getStoredAppearance();

        if (savedAppearance) {
            appearance.value = savedAppearance;
        }
    });

    const resolvedAppearance = computed<ResolvedAppearance>(() =>
        resolve(appearance.value),
    );

    function updateAppearance(value: Appearance) {
        appearance.value = value;

        localStorage.setItem('appearance', value);
        setCookie('appearance', value);

        updateTheme(value);
    }

    function toggleAppearance() {
        updateAppearance(
            resolvedAppearance.value === 'dark' ? 'light' : 'dark',
        );
    }

    return {
        appearance,
        resolvedAppearance,
        updateAppearance,
        toggleAppearance,
    };
}
