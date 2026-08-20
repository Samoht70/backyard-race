export type RegistrationStep = {
    label: string;
    fields: readonly string[];
};

export const REGISTRATION_STEPS: readonly RegistrationStep[] = [
    {
        label: 'auth.register.complete.step.identity',
        fields: ['first_name', 'last_name'],
    },
    {
        label: 'auth.register.complete.step.runner',
        fields: ['phone', 'birth_date', 'pps_number'],
    },
    {
        label: 'auth.register.complete.step.emergency',
        fields: ['emergency_contact_name', 'emergency_contact_phone'],
    },
    {
        label: 'auth.register.complete.step.notes',
        fields: ['notes'],
    },
];

export const REGISTRATION_STEP_COUNT = REGISTRATION_STEPS.length;

export function firstStepInError(
    errors: Record<string, string>,
): number | null {
    const step = REGISTRATION_STEPS.findIndex((registrationStep) =>
        registrationStep.fields.some((field) => field in errors),
    );

    return step === -1 ? null : step;
}

export function firstFieldInError(
    errors: Record<string, string>,
    step: number,
): string | null {
    const fields = REGISTRATION_STEPS[step]?.fields ?? [];

    return fields.find((field) => field in errors) ?? null;
}
