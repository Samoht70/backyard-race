import { describe, expect, it } from 'vitest';
import { toCalendarDate, toTime } from '@/lib/temporal';

describe('toCalendarDate', () => {
    it('reads the date the server sends', () => {
        const date = toCalendarDate('2026-09-12');

        expect(date?.year).toBe(2026);
        expect(date?.month).toBe(9);
        expect(date?.day).toBe(12);
    });

    it('submits back the format it was given', () => {
        expect(toCalendarDate('2026-09-12')?.toString()).toBe('2026-09-12');
    });

    it('returns nothing for an absent or unreadable value', () => {
        expect(toCalendarDate(null)).toBeUndefined();
        expect(toCalendarDate(undefined)).toBeUndefined();
        expect(toCalendarDate('')).toBeUndefined();
        expect(toCalendarDate('12/09/2026')).toBeUndefined();
        expect(toCalendarDate('2026-09-12 13:00:00')).toBeUndefined();
    });
});

describe('toTime', () => {
    it('reads a time with or without seconds', () => {
        expect(toTime('13:00')?.hour).toBe(13);
        expect(toTime('13:00:00')?.minute).toBe(0);
        expect(toTime('08:30')?.hour).toBe(8);
    });

    it('drops the seconds it was given', () => {
        expect(toTime('13:45:59')?.second).toBe(0);
    });

    it('returns nothing for an absent or unreadable value', () => {
        expect(toTime(null)).toBeUndefined();
        expect(toTime('')).toBeUndefined();
        expect(toTime('1:00')).toBeUndefined();
        expect(toTime('13h00')).toBeUndefined();
    });
});
