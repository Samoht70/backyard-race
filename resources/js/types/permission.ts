export const PERMISSIONS = [
    'manage-event',
    'manage-participants',
    'manage-laps',
    'validate-laps',
    'manage-documents',
    'manage-route',
    'manage-gallery',
    'view-statistics',
    'finish-event',
] as const;

export type Permission = (typeof PERMISSIONS)[number];
