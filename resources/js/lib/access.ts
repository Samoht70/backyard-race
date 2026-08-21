import { usePage } from '@inertiajs/vue3';
import type { Access } from '@/types/access';

const page = usePage();

export function canReach(area: keyof Access): boolean {
    return page.props.access?.[area] === true;
}
