import type { RunnerLap } from '@/types/race';

const SECONDS_PER_MINUTE = 60;
const MINUTES_PER_HOUR = 60;
const SECONDS_PER_HOUR = SECONDS_PER_MINUTE * MINUTES_PER_HOUR;
const METERS_PER_KILOMETER = 1000;
const SPEED_DECIMALS = 2;

function padded(value: number): string {
    return String(value).padStart(2, '0');
}

export function formatLapDuration(seconds: number): string {
    const hours = Math.floor(seconds / SECONDS_PER_HOUR);
    const minutes = Math.floor(seconds / SECONDS_PER_MINUTE) % MINUTES_PER_HOUR;
    const remainder = seconds % SECONDS_PER_MINUTE;

    const withoutHours = `${padded(minutes)}:${padded(remainder)}`;

    return hours === 0 ? withoutHours : `${hours}:${withoutHours}`;
}

export function lastTimedLap(laps: RunnerLap[]): RunnerLap | null {
    const timed = laps.filter((lap) => lap.duration_seconds !== null);

    return timed.at(-1) ?? null;
}

export function averageSpeedKmh(laps: RunnerLap[]): number | null {
    const measured = laps.filter(
        (lap) => lap.duration_seconds !== null && lap.distance_meters !== null,
    );

    const seconds = measured.reduce(
        (total, lap) => total + (lap.duration_seconds ?? 0),
        0,
    );
    const meters = measured.reduce(
        (total, lap) => total + (lap.distance_meters ?? 0),
        0,
    );

    if (seconds < 1 || meters < 1) {
        return null;
    }

    return meters / METERS_PER_KILOMETER / (seconds / SECONDS_PER_HOUR);
}

export function formatSpeed(kilometersPerHour: number): string {
    return kilometersPerHour.toLocaleString('fr-FR', {
        minimumFractionDigits: SPEED_DECIMALS,
        maximumFractionDigits: SPEED_DECIMALS,
    });
}
