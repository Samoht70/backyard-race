import { CircleMinus, CircleX, Footprints, Trophy } from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import { cva } from 'class-variance-authority';
import type { RunnerStatus } from '@/types/race';

/**
 * Icons are structurally distinct silhouettes, not four coloured circles: the
 * pictogram has to carry the status on its own in greyscale.
 */
export const runnerStatusIcons = {
    running: Footprints,
    eliminated: CircleX,
    withdrawn: CircleMinus,
    finished: Trophy,
} satisfies Record<RunnerStatus, LucideIcon>;

export const runnerStatusVariants = cva(
    'inline-flex shrink-0 items-center gap-1.5 rounded-md border font-medium',
    {
        variants: {
            status: {
                running:
                    'border-status-running/20 bg-status-running-surface text-status-running',
                eliminated:
                    'border-status-eliminated/20 bg-status-eliminated-surface text-status-eliminated',
                withdrawn:
                    'border-status-abandoned/20 bg-status-abandoned-surface text-status-abandoned',
                finished:
                    'border-status-finished/20 bg-status-finished-surface text-status-finished',
            },
            size: {
                sm: 'px-1.5 py-0.5 text-xs',
                md: 'px-2 py-1 text-sm',
            },
        },
        defaultVariants: {
            status: 'running',
            size: 'md',
        },
    },
);

export function runnerStatusLabelKey(status: RunnerStatus): string {
    return `race.status.${status}`;
}
