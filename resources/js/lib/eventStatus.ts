import { CircleCheckBig, ClipboardList, Flag, PencilLine } from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import { cva } from 'class-variance-authority';
import type { EventStatus } from '@/types/event';

/**
 * Silhouettes kept distinct from the runner ones: both sets meet on the same
 * screens, and `running` and `finished` carry both names.
 */
export const eventStatusIcons = {
    draft: PencilLine,
    registration: ClipboardList,
    running: Flag,
    finished: CircleCheckBig,
} satisfies Record<EventStatus, LucideIcon>;

export const eventStatusVariants = cva(
    'inline-flex shrink-0 items-center gap-1.5 rounded-md border font-medium',
    {
        variants: {
            status: {
                draft: 'border-border bg-muted text-muted-foreground',
                registration: 'border-primary/25 bg-primary/10 text-primary',
                running:
                    'border-status-running/20 bg-status-running-surface text-status-running',
                finished:
                    'border-status-finished/20 bg-status-finished-surface text-status-finished',
            },
            size: {
                sm: 'px-1.5 py-0.5 text-xs',
                md: 'px-2 py-1 text-sm',
            },
        },
        defaultVariants: {
            status: 'draft',
            size: 'md',
        },
    },
);

export function eventStatusLabelKey(status: EventStatus): string {
    return `event.status.${status}`;
}

export function eventTransitionLabelKey(status: EventStatus): string {
    return `event.transition.to_${status}`;
}
