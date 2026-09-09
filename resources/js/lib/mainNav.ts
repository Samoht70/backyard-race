import { canReach } from '@/lib/access';
import { isAuthenticated } from '@/lib/auth';
import { t } from '@/lib/i18n';
import { can } from '@/lib/permissions';
import { dashboard, home, login } from '@/routes';
import { create as createAccount } from '@/routes/account';
import { show as briefing } from '@/routes/briefing';
import { index as manage } from '@/routes/manage';
import { show as registration } from '@/routes/registration';
import { index as standings } from '@/routes/standings';
import type { NavItem } from '@/types';

export function mainNavItems(): NavItem[] {
    return isAuthenticated() ? memberNavItems() : guestNavItems();
}

function homeEntry(): NavItem {
    return {
        title: canReach('results') ? t('ui.nav.results') : t('ui.nav.event'),
        href: home(),
    };
}

function guestNavItems(): NavItem[] {
    const entries: NavItem[] = [homeEntry()];

    if (canReach('event')) {
        entries.push({ title: t('ui.nav.briefing'), href: briefing() });
    }

    if (canReach('standings')) {
        entries.push({ title: t('ui.nav.standings'), href: standings() });
    }

    entries.push({ title: t('ui.nav.registration'), href: login() });

    if (canReach('register')) {
        entries.push({ title: t('ui.nav.register'), href: createAccount() });
    }

    return entries;
}

function memberNavItems(): NavItem[] {
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

    if (canReach('standings')) {
        entries.push({ title: t('ui.nav.standings'), href: standings() });
    }

    if (canReach('event')) {
        entries.push(homeEntry());
    }

    return entries;
}
