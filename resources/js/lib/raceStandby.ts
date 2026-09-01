import type { EventStatus } from '@/types/event';

export const RACE_STANDBYS = [
    'no_event',
    'draft',
    'registration',
    'between_rounds',
    'finished',
] as const;

export type RaceStandby = (typeof RACE_STANDBYS)[number];

const standbysByStatus: Record<EventStatus, RaceStandby> = {
    draft: 'draft',
    registration: 'registration',
    running: 'between_rounds',
    finished: 'finished',
};

export function raceStandby(status: EventStatus | null): RaceStandby {
    return status === null ? 'no_event' : standbysByStatus[status];
}

export function raceStandbyTitleKey(standby: RaceStandby): string {
    return `race.standby.${standby}.title`;
}

export function raceStandbyDescriptionKey(standby: RaceStandby): string {
    return `race.standby.${standby}.description`;
}
