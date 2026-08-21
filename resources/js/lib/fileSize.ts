const KILOBYTE = 1024;
const UNITS = ['o', 'ko', 'Mo'] as const;

export function formatFileSize(bytes: number): string {
    let size = bytes;
    let unit = 0;

    while (size >= KILOBYTE && unit < UNITS.length - 1) {
        size /= KILOBYTE;
        unit += 1;
    }

    const rounded = unit === 0 ? size : Math.round(size * 10) / 10;

    return `${rounded.toLocaleString('fr-FR')} ${UNITS[unit]}`;
}
