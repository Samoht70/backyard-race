import { CircleCheckBig, CircleSlash, Hourglass } from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import { cva } from 'class-variance-authority';
import type { RegistrationStatus } from '@/types/registration';

export const registrationStatusIcons = {
    pending: Hourglass,
    confirmed: CircleCheckBig,
    cancelled: CircleSlash,
} satisfies Record<RegistrationStatus, LucideIcon>;

export const registrationStatusVariants = cva(
    'inline-flex shrink-0 items-center gap-1.5 rounded-md border font-medium',
    {
        variants: {
            status: {
                pending: 'border-border bg-muted text-muted-foreground',
                confirmed:
                    'border-status-running/20 bg-status-running-surface text-status-running',
                cancelled:
                    'border-status-eliminated/20 bg-status-eliminated-surface text-status-eliminated',
            },
            size: {
                sm: 'px-1.5 py-0.5 text-xs',
                md: 'px-2 py-1 text-sm',
            },
        },
        defaultVariants: {
            status: 'pending',
            size: 'md',
        },
    },
);

export function registrationStatusLabelKey(status: RegistrationStatus): string {
    return `registration.status.${status}`;
}
