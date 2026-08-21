export const REGISTRATION_STATUSES = [
    'pending',
    'confirmed',
    'cancelled',
] as const;

export type RegistrationStatus = (typeof REGISTRATION_STATUSES)[number];

export const REGISTRATION_TRANSITIONS = [
    'confirm',
    'cancel',
    'reopen',
] as const;

export type RegistrationTransition = (typeof REGISTRATION_TRANSITIONS)[number];

export const REGISTRATION_SECTIONS = ['runner', 'emergency', 'notes'] as const;

export type RegistrationSection = (typeof REGISTRATION_SECTIONS)[number];

export type RegistrationDetails = {
    status: RegistrationStatus;
    status_label: string;
    bib_number: number | null;
    bib_label: string | null;
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
    birth_date: string;
    pps_number: string | null;
    emergency_contact_name: string;
    emergency_contact_phone: string;
    notes: string | null;
};

export type ManagedRegistration = RegistrationDetails & {
    id: number;
    allowed_transitions: RegistrationTransition[];
};

export type RegistrationCounts = Record<RegistrationStatus | 'all', number>;

export type RegistrationSeats = {
    confirmed: number;
    capacity: number | null;
};
