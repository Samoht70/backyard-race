import { Flag, House } from '@lucide/vue';
import { t } from '@/lib/i18n';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

export function mainNavItems(): NavItem[] {
    return [
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
}
