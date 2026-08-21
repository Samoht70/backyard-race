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

export function mainNavItems(): NavItem[] {
    const entries: NavItem[] = [{ title: t('ui.nav.home'), href: dashboard() }];

    if (can('manage-event')) {
        entries.push({ title: t('ui.nav.manage'), href: manage() });
    }

    if (canReach('registration')) {
        entries.push({ title: t('ui.nav.registration'), href: registration() });
    }

    if (canReach('event')) {
        entries.push({ title: t('ui.nav.briefing'), href: briefing() });
    }

    if (canReach('documents')) {
        entries.push({ title: t('ui.nav.documents'), href: documents() });
    }

    if (canReach('event')) {
        entries.push({ title: t('ui.nav.event'), href: event() });
    }

    return entries;
}
