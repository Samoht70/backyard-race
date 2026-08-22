import {
    Check,
    CircleCheckBig,
    CircleSlash,
    Hourglass,
    Undo2,
} from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import { cva } from 'class-variance-authority';
import type {
    RegistrationStatus,
    RegistrationTransition,
} from '@/types/registration';

export const registrationStatusIcons = {
    pending: Hourglass,
    confirmed: CircleCheckBig,
    cancelled: CircleSlash,
} satisfies Record<RegistrationStatus, LucideIcon>;

export const registrationStatusVariants = cva(
    'inline-flex shrink-0 items-center gap-1.5 rounded-sm border font-medium',
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

export const registrationStatusBar = {
    pending: 'bg-muted-foreground',
    confirmed: 'bg-status-running',
    cancelled: 'bg-status-eliminated',
} satisfies Record<RegistrationStatus, string>;

export const registrationStatusTone = {
    pending: 'text-muted-foreground',
    confirmed: 'text-status-running',
    cancelled: 'text-status-eliminated',
} satisfies Record<RegistrationStatus, string>;

type RegistrationTransitionPresentation = {
    icon: LucideIcon;
    tone: 'primary' | 'danger' | 'quiet';
    needsConfirmation: boolean;
};

export const registrationTransitions = {
    confirm: { icon: Check, tone: 'primary', needsConfirmation: false },
    cancel: { icon: CircleSlash, tone: 'danger', needsConfirmation: true },
    reopen: { icon: Undo2, tone: 'quiet', needsConfirmation: false },
} satisfies Record<RegistrationTransition, RegistrationTransitionPresentation>;

export function registrationTransitionLabelKey(
    transition: RegistrationTransition,
): string {
    return `registration.transition.${transition}`;
}

export function registrationTransitionAriaKey(
    transition: RegistrationTransition,
): string {
    return `registration.transition.aria_${transition}`;
}
