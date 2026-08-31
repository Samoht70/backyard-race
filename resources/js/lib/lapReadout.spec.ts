import { describe, expect, it } from 'vitest';
import { formatLapDuration, formatSpeed } from '@/lib/lapReadout';

describe('formatLapDuration', () => {
    it('reads a lap shorter than an hour in minutes and seconds', () => {
        expect(formatLapDuration(2852)).toBe('47:32');
    });

    it('pads a lap of a few seconds', () => {
        expect(formatLapDuration(7)).toBe('00:07');
    });

    it('spells out the hour once the lap passes it', () => {
        expect(formatLapDuration(3735)).toBe('1:02:15');
    });
});

describe('formatSpeed', () => {
    it('keeps two decimals behind a comma', () => {
        expect(formatSpeed(7.57)).toBe('7,57');
    });

    it('holds the decimals of a round speed', () => {
        expect(formatSpeed(8)).toBe('8,00');
    });
});
