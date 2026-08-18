import { usePage } from '@inertiajs/vue3';

const page = usePage();

export function t(key: string): string {
    const translations = page.props.translations as
        Record<string, string> | undefined;

    return translations?.[key] ?? key;
}
