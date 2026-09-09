const SECONDS_PER_MINUTE = 60;
const MINUTES_PER_HOUR = 60;
const SECONDS_PER_HOUR = SECONDS_PER_MINUTE * MINUTES_PER_HOUR;

export function formatRaceDuration(seconds: number | null): string | null {
    if (seconds === null || seconds < SECONDS_PER_MINUTE) {
        return null;
    }

    const hours = Math.floor(seconds / SECONDS_PER_HOUR);
    const minutes = Math.floor(seconds / SECONDS_PER_MINUTE) % MINUTES_PER_HOUR;

    return hours === 0
        ? `${minutes} min`
        : `${hours} h ${String(minutes).padStart(2, '0')}`;
}
