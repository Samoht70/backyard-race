import { usePage } from '@inertiajs/vue3';

const page = usePage();

export function isAuthenticated(): boolean {
    return (page.props.auth?.user ?? null) !== null;
}
