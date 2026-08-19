export const REGISTRATION_STATUSES = [
    'pending',
    'confirmed',
    'cancelled',
] as const;

export type RegistrationStatus = (typeof REGISTRATION_STATUSES)[number];
