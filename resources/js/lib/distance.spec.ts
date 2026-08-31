import { describe, expect, it } from 'vitest';
import { formatKilometers } from '@/lib/distance';

describe('formatKilometers', () => {
    it('turns metres into kilometres', () => {
        expect(formatKilometers(6706)).toBe('6,706');
    });

    it('drops the decimals of a round distance', () => {
        expect(formatKilometers(5000)).toBe('5');
    });

    it('keeps a distance shorter than a kilometre readable', () => {
        expect(formatKilometers(400)).toBe('0,4');
    });

    it('leaves an unset distance unset', () => {
        expect(formatKilometers(null)).toBeNull();
    });
});
