import { Flag, House, SlidersHorizontal } from '@lucide/vue';
import { t } from '@/lib/i18n';
import { can } from '@/lib/permissions';
import { dashboard } from '@/routes';
import { index as manage } from '@/routes/manage';
import type { NavItem } from '@/types';

export function mainNavItems(): NavItem[] {
    const entries: NavItem[] = [
        {
            title: t('ui.nav.race'),
            href: dashboard(),
            icon: House,
        },
        {
            title: t('ui.nav.runners'),
            href: dashboard(),
            icon: Flag,
        },
    ];

    if (can('manage-event')) {
        entries.push({
            title: t('ui.nav.manage'),
            href: manage(),
            icon: SlidersHorizontal,
        });
    }

    return entries;
}
