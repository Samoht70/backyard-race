const METERS_PER_KILOMETER = 1000;

export function formatKilometers(meters: number | null): string | null {
    if (meters === null) {
        return null;
    }

    return (meters / METERS_PER_KILOMETER).toLocaleString('fr-FR', {
        maximumFractionDigits: 3,
    });
}
