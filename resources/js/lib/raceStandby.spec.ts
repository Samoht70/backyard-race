import { describe, expect, it } from 'vitest';
import {
    RACE_STANDBYS,
    raceStandby,
    raceStandbyDescriptionKey,
    raceStandbyTitleKey,
} from '@/lib/raceStandby';
import { EVENT_STATUSES } from '@/types/event';

describe('raceStandby', () => {
    it('names what the manager sees instead of a board', () => {
        expect(raceStandby(null)).toBe('no_event');
        expect(raceStandby('draft')).toBe('draft');
        expect(raceStandby('registration')).toBe('registration');
        expect(raceStandby('finished')).toBe('finished');
    });

    it('separates a race with no open round from a race not started', () => {
        expect(raceStandby('running')).toBe('between_rounds');
    });

    it('answers every event status the server can send', () => {
        for (const status of EVENT_STATUSES) {
            expect(RACE_STANDBYS).toContain(raceStandby(status));
        }
    });

    it('reads its wording from the race translations', () => {
        expect(raceStandbyTitleKey('draft')).toBe('race.standby.draft.title');
        expect(raceStandbyDescriptionKey('between_rounds')).toBe(
            'race.standby.between_rounds.description',
        );
    });
});
