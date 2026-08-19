import { usePage } from '@inertiajs/vue3';

const page = usePage();

export function t(
    key: string,
    replacements: Record<string, string | number> = {},
): string {
    const translations = page.props.translations as
        Record<string, string> | undefined;

    return Object.entries(replacements).reduce(
        (line, [token, value]) => line.replaceAll(`:${token}`, String(value)),
        translations?.[key] ?? key,
    );
}
