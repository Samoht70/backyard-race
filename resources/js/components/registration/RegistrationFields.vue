<script setup lang="ts">
import EventField from '@/components/event/EventField.vue';
import EventFieldset from '@/components/event/EventFieldset.vue';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
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
    <EventFieldset
        v-if="shows('runner')"
        :title="t('registration.section.runner')"
    >
        <EventField
            name="phone"
            :span="2"
            :label="t('registration.field.phone')"
            :hint="t('registration.hint.phone')"
            :error="errors.phone"
        >
            <Input
                id="phone"
                type="tel"
                name="phone"
                autocomplete="tel"
                required
                maxlength="40"
                :default-value="registration?.phone"
            />
        </EventField>

        <EventField
            name="birth_date"
            :span="2"
            :label="t('registration.field.birth_date')"
            :hint="t('registration.hint.birth_date')"
            :error="errors.birth_date"
        >
            <Input
                id="birth_date"
                type="date"
                name="birth_date"
                autocomplete="bday"
                required
                class="tabular-nums"
                :default-value="registration?.birth_date"
            />
        </EventField>

        <EventField
            name="pps_number"
            :span="2"
            :label="t('registration.field.pps_number')"
            :hint="t('registration.hint.pps_number')"
            :error="errors.pps_number"
            :locked="lockPps"
            :locked-reason="t('registration.locked.pps_number')"
            :value="registration?.pps_number ?? undefined"
        >
            <Input
                id="pps_number"
                name="pps_number"
                autocomplete="off"
                maxlength="16"
                placeholder="PPS12345678"
                class="tabular-nums"
                :default-value="registration?.pps_number ?? undefined"
            />
        </EventField>
    </EventFieldset>

    <EventFieldset
        v-if="shows('emergency')"
        :title="t('registration.section.emergency')"
    >
        <EventField
            name="emergency_contact_name"
            :span="3"
            :label="t('registration.field.emergency_contact_name')"
            :error="errors.emergency_contact_name"
        >
            <Input
                id="emergency_contact_name"
                name="emergency_contact_name"
                required
                maxlength="120"
                :default-value="registration?.emergency_contact_name"
            />
        </EventField>

        <EventField
            name="emergency_contact_phone"
            :span="3"
            :label="t('registration.field.emergency_contact_phone')"
            :error="errors.emergency_contact_phone"
        >
            <Input
                id="emergency_contact_phone"
                type="tel"
                name="emergency_contact_phone"
                required
                maxlength="40"
                :default-value="registration?.emergency_contact_phone"
            />
        </EventField>
    </EventFieldset>

    <EventFieldset
        v-if="shows('notes')"
        :title="t('registration.section.notes')"
    >
        <EventField
            name="notes"
            :label="t('registration.field.notes')"
            :hint="t('registration.hint.notes')"
            :error="errors.notes"
        >
            <Textarea
                id="notes"
                name="notes"
                rows="4"
                :default-value="registration?.notes ?? undefined"
            />
        </EventField>
    </EventFieldset>
</template>
