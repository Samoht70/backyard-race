import { describe, expect, it } from 'vitest';
import { formatRaceDuration } from '@/lib/raceTotals';

describe('formatRaceDuration', () => {
    it('reads a night of racing in hours and padded minutes', () => {
        expect(formatRaceDuration(54240)).toBe('15 h 04');
    });

    it('drops the hour from a race that never reached one', () => {
        expect(formatRaceDuration(2880)).toBe('48 min');
    });

    it('reads nothing from a race shorter than a minute', () => {
        expect(formatRaceDuration(42)).toBeNull();
        expect(formatRaceDuration(null)).toBeNull();
    });
});
