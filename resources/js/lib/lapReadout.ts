const SECONDS_PER_MINUTE = 60;
const MINUTES_PER_HOUR = 60;
const SECONDS_PER_HOUR = SECONDS_PER_MINUTE * MINUTES_PER_HOUR;
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

export function formatSpeed(kilometersPerHour: number): string {
    return kilometersPerHour.toLocaleString('fr-FR', {
        minimumFractionDigits: SPEED_DECIMALS,
        maximumFractionDigits: SPEED_DECIMALS,
    });
}
