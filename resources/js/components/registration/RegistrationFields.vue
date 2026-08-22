<script setup lang="ts">
import DateField from '@/components/form/DateField.vue';
import FormField from '@/components/form/FormField.vue';
import FormFieldset from '@/components/form/FormFieldset.vue';
import TextAreaField from '@/components/form/TextAreaField.vue';
import TextField from '@/components/form/TextField.vue';
import { t } from '@/lib/i18n';
import type {
    RegistrationDetails,
    RegistrationSection,
} from '@/types/registration';

type Props = {
    registration?: RegistrationDetails;
    errors: Record<string, string>;
    lockPps?: boolean;
    section?: RegistrationSection;
};

const props = withDefaults(defineProps<Props>(), {
    lockPps: false,
});

function shows(section: RegistrationSection): boolean {
    return props.section === undefined || props.section === section;
}
</script>

<template>
    <FormFieldset
        v-if="shows('runner')"
        :title="t('registration.section.runner')"
    >
        <FormField
            name="phone"
            :span="2"
            :label="t('registration.field.phone')"
            :hint="t('registration.hint.phone')"
            :error="errors.phone"
        >
            <TextField
                id="phone"
                type="tel"
                name="phone"
                autocomplete="tel"
                required
                maxlength="40"
                :default-value="registration?.phone"
            />
        </FormField>

        <FormField
            name="birth_date"
            :span="2"
            :label="t('registration.field.birth_date')"
            :hint="t('registration.hint.birth_date')"
            :error="errors.birth_date"
        >
            <DateField
                id="birth_date"
                name="birth_date"
                required
                :default-value="registration?.birth_date"
            />
        </FormField>

        <FormField
            name="pps_number"
            :span="2"
            :label="t('registration.field.pps_number')"
            :hint="t('registration.hint.pps_number')"
            :error="errors.pps_number"
            :locked="lockPps"
            :locked-reason="t('registration.locked.pps_number')"
            :value="registration?.pps_number ?? undefined"
        >
            <TextField
                id="pps_number"
                name="pps_number"
                autocomplete="off"
                maxlength="16"
                placeholder="PPS12345678"
                class="tabular-nums"
                :default-value="registration?.pps_number ?? undefined"
            />
        </FormField>
    </FormFieldset>

    <FormFieldset
        v-if="shows('emergency')"
        :title="t('registration.section.emergency')"
    >
        <FormField
            name="emergency_contact_name"
            :span="3"
            :label="t('registration.field.emergency_contact_name')"
            :error="errors.emergency_contact_name"
        >
            <TextField
                id="emergency_contact_name"
                name="emergency_contact_name"
                required
                maxlength="120"
                :default-value="registration?.emergency_contact_name"
            />
        </FormField>

        <FormField
            name="emergency_contact_phone"
            :span="3"
            :label="t('registration.field.emergency_contact_phone')"
            :error="errors.emergency_contact_phone"
        >
            <TextField
                id="emergency_contact_phone"
                type="tel"
                name="emergency_contact_phone"
                required
                maxlength="40"
                :default-value="registration?.emergency_contact_phone"
            />
        </FormField>
    </FormFieldset>

    <FormFieldset
        v-if="shows('notes')"
        :title="t('registration.section.notes')"
    >
        <FormField
            name="notes"
            :label="t('registration.field.notes')"
            :hint="t('registration.hint.notes')"
            :error="errors.notes"
        >
            <TextAreaField
                id="notes"
                name="notes"
                rows="4"
                :default-value="registration?.notes ?? undefined"
            />
        </FormField>
    </FormFieldset>
</template>
