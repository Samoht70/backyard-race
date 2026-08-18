export const RUNNER_STATUSES = [
    'running',
    'eliminated',
    'withdrawn',
    'finished',
] as const;

export type RunnerStatus = (typeof RUNNER_STATUSES)[number];
