import { SearchX, ServerCrash, ShieldOff, TimerOff } from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import { isAuthenticated } from '@/lib/auth';
import { t } from '@/lib/i18n';
import { dashboard, home } from '@/routes';
import type { ErrorSituation } from '@/types/error';
import type { NavItem } from '@/types/navigation';

export const errorSituationIcons = {
    not_found: SearchX,
    forbidden: ShieldOff,
    expired: TimerOff,
    server: ServerCrash,
} satisfies Record<ErrorSituation, LucideIcon>;

const situationsByStatus: Record<number, ErrorSituation> = {
    403: 'forbidden',
    404: 'not_found',
    419: 'expired',
    500: 'server',
};

export function errorSituation(status: number): ErrorSituation {
    return situationsByStatus[status] ?? 'server';
}

export function errorTitleKey(situation: ErrorSituation): string {
    return `error.${situation}.title`;
}

export function errorDescriptionKey(situation: ErrorSituation): string {
    return `error.${situation}.description`;
}

export function errorReturnItem(): NavItem {
    return isAuthenticated()
        ? { title: t('error.back_dashboard'), href: dashboard() }
        : { title: t('error.back_home'), href: home() };
}
