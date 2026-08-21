import {
    CalendarDays,
    FolderOpen,
    House,
    Megaphone,
    SlidersHorizontal,
    Ticket,
} from '@lucide/vue';
import { canReach } from '@/lib/access';
import { t } from '@/lib/i18n';
import { can } from '@/lib/permissions';
import { dashboard } from '@/routes';
import { show as briefing } from '@/routes/briefing';
import { index as documents } from '@/routes/documents';
import { show as event } from '@/routes/event';
import { index as manage } from '@/routes/manage';
import { show as registration } from '@/routes/registration';
import type { NavItem } from '@/types';

export const BOTTOM_NAV_LIMIT = 4;

export function mainNavItems(): NavItem[] {
    const entries: NavItem[] = [
        { title: t('ui.nav.home'), href: dashboard(), icon: House },
    ];

    if (can('manage-event')) {
        entries.push({
            title: t('ui.nav.manage'),
            href: manage(),
            icon: SlidersHorizontal,
        });
    }

    if (canReach('registration')) {
        entries.push({
            title: t('ui.nav.registration'),
            href: registration(),
            icon: Ticket,
        });
    }

    if (canReach('event')) {
        entries.push({
            title: t('ui.nav.briefing'),
            href: briefing(),
            icon: Megaphone,
        });
    }

    if (canReach('documents')) {
        entries.push({
            title: t('ui.nav.documents'),
            href: documents(),
            icon: FolderOpen,
        });
    }

    if (canReach('event')) {
        entries.push({
            title: t('ui.nav.event'),
            href: event(),
            icon: CalendarDays,
        });
    }

    return entries;
}
