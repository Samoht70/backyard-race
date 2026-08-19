import { usePage } from '@inertiajs/vue3';
import type { Permission } from '@/types/permission';

const page = usePage();

export function can(permission: Permission): boolean {
    return page.props.auth?.permissions?.[permission] === true;
}
