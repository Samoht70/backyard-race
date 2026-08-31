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

export type NextRound = {
    number: number;
    starts_at: string;
    lap_duration_minutes: number;
};

export const LAP_STATUSES = ['pending', 'validated', 'eliminated'] as const;

export type LapStatus = (typeof LAP_STATUSES)[number];

export type RoundRunner = {
    runner_id: number;
    lap_id: number;
    lap_status: LapStatus;
    bib_label: string | null;
    first_name: string;
    last_name: string;
    status: RunnerStatus;
    validated_laps: number;
    covered_meters: number | null;
    validated_at: string | null;
    duration_seconds: number | null;
    distance_meters: number | null;
    speed_kmh: number | null;
};
