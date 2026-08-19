export const EVENT_STATUSES = [
    'draft',
    'registration',
    'running',
    'finished',
] as const;

export type EventStatus = (typeof EVENT_STATUSES)[number];

export const EVENT_FIELDS = [
    'name',
    'description',
    'first_start_at',
    'lap_distance_meters',
    'lap_duration_minutes',
    'address',
    'latitude',
    'longitude',
    'max_participants',
] as const;

export type EventFieldName = (typeof EVENT_FIELDS)[number];

export type EventDetails = {
    name: string | null;
    description: string | null;
    status: EventStatus;
    start_date: string | null;
    start_time: string | null;
    lap_distance_meters: number | null;
    lap_duration_minutes: number | null;
    address: string | null;
    latitude: number | null;
    longitude: number | null;
    max_participants: number | null;
};

export type EventTransition = {
    current: EventStatus;
    next: EventStatus | null;
    refusals: string[];
};
