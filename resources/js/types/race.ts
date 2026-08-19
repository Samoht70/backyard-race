export const RUNNER_STATUSES = [
    'running',
    'eliminated',
    'withdrawn',
    'finished',
] as const;

export type RunnerStatus = (typeof RUNNER_STATUSES)[number];

export type CurrentRound = {
    number: number;
    starts_at: string;
    deadline_at: string;
};
