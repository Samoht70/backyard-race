import { describe, expect, it } from 'vitest';
import {
    firstFieldInError,
    firstStepInError,
    REGISTRATION_STEP_COUNT,
} from '@/lib/registrationSteps';

describe('firstStepInError', () => {
    it('sends a rejected birth date back to the runner step', () => {
        expect(firstStepInError({ birth_date: 'Trop jeune.' })).toBe(1);
    });

    it('sends the runner to the earliest step in error', () => {
        const step = firstStepInError({
            notes: 'Trop long.',
            birth_date: 'Trop jeune.',
        });

        expect(step).toBe(1);
    });

    it('keeps the runner where they are when the event itself refuses', () => {
        expect(firstStepInError({ event: 'Complet.' })).toBeNull();
    });

    it('keeps the runner where they are on an unknown key', () => {
        expect(firstStepInError({ bib_number: 'Déjà pris.' })).toBeNull();
    });

    it('covers every submitted field across its steps', () => {
        expect(REGISTRATION_STEP_COUNT).toBe(4);
        expect(firstStepInError({ first_name: '' })).toBe(0);
        expect(firstStepInError({ last_name: '' })).toBe(0);
        expect(firstStepInError({ phone: '' })).toBe(1);
        expect(firstStepInError({ pps_number: '' })).toBe(1);
        expect(firstStepInError({ emergency_contact_name: '' })).toBe(2);
        expect(firstStepInError({ emergency_contact_phone: '' })).toBe(2);
        expect(firstStepInError({ notes: '' })).toBe(3);
    });
});

describe('firstFieldInError', () => {
    it('names the field to focus inside the step', () => {
        const errors = { phone: 'Obligatoire.', birth_date: 'Trop jeune.' };

        expect(firstFieldInError(errors, 1)).toBe('phone');
    });

    it('follows the declared field order, not the error order', () => {
        const errors = { birth_date: 'Trop jeune.', phone: 'Obligatoire.' };

        expect(firstFieldInError(errors, 1)).toBe('phone');
    });

    it('names nothing when the step is clean', () => {
        expect(firstFieldInError({ notes: 'Trop long.' }, 1)).toBeNull();
    });

    it('names nothing outside the declared steps', () => {
        expect(firstFieldInError({ notes: 'Trop long.' }, 9)).toBeNull();
    });
});
