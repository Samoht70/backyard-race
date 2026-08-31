import { CircleArrowRight, Skull, TimerOff, Trophy } from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import { cva } from 'class-variance-authority';
import type { RunnerStatus } from '@/types/race';

export const runnerStatusIcons = {
    running: CircleArrowRight,
    eliminated: TimerOff,
    withdrawn: Skull,
    finished: Trophy,
} satisfies Record<RunnerStatus, LucideIcon>;

export const runnerStatusTone = {
    running: 'text-status-running',
    eliminated: 'text-status-eliminated',
    withdrawn: 'text-status-abandoned',
    finished: 'text-status-finished',
} satisfies Record<RunnerStatus, string>;

export const runnerStatusBar = {
    running: 'bg-status-running',
    eliminated: 'bg-status-eliminated',
    withdrawn: 'bg-status-abandoned',
    finished: 'bg-status-finished',
} satisfies Record<RunnerStatus, string>;

export const runnerStatusVariants = cva(
    'inline-flex shrink-0 items-center gap-1.5 rounded-sm font-bold tracking-wide uppercase',
    {
        variants: {
            status: {
                running: 'bg-status-running-surface text-status-running',
                eliminated:
                    'bg-status-eliminated-surface text-status-eliminated',
                withdrawn: 'bg-status-abandoned-surface text-status-abandoned',
                finished: 'bg-status-finished-surface text-status-finished',
            },
            size: {
                sm: 'px-1.5 py-0.5 text-[0.625rem]',
                md: 'px-2.5 py-1 text-[0.6875rem]',
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
