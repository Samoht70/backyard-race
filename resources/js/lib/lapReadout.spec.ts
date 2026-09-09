import { describe, expect, it } from 'vitest';
import {
    averageSpeedKmh,
    formatLapDuration,
    formatSpeed,
    lastTimedLap,
} from '@/lib/lapReadout';
import type { RunnerLap } from '@/types/race';

function lap(roundNumber: number, durationSeconds: number | null): RunnerLap {
    return {
        round_number: roundNumber,
        corrected: false,
        duration_seconds: durationSeconds,
        distance_meters: durationSeconds === null ? null : 6000,
        speed_kmh: durationSeconds === null ? null : 7.57,
    };
}

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

describe('lastTimedLap', () => {
    it('holds the last lap that carries a time', () => {
        expect(lastTimedLap([lap(1, 3120), lap(2, 2852)])?.round_number).toBe(
            2,
        );
    });

    it('skips the lap still under way', () => {
        expect(lastTimedLap([lap(1, 3120), lap(2, null)])?.round_number).toBe(
            1,
        );
    });

    it('reads nothing from a runner who has validated no lap', () => {
        expect(lastTimedLap([lap(1, null)])).toBeNull();
        expect(lastTimedLap([])).toBeNull();
    });
});

describe('averageSpeedKmh', () => {
    it('weighs the whole distance against the whole running time', () => {
        expect(averageSpeedKmh([lap(1, 3600), lap(2, 1800)])).toBeCloseTo(8, 5);
    });

    it('leaves out the lap still under way', () => {
        expect(averageSpeedKmh([lap(1, 3600), lap(2, null)])).toBeCloseTo(6, 5);
    });

    it('reads nothing when no lap carries both a time and a distance', () => {
        expect(averageSpeedKmh([lap(1, null)])).toBeNull();
        expect(averageSpeedKmh([])).toBeNull();
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
