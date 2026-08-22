<script setup lang="ts">
import FormField from '@/components/form/FormField.vue';
import FormFieldset from '@/components/form/FormFieldset.vue';
import NumberField from '@/components/form/NumberField.vue';
import TextAreaField from '@/components/form/TextAreaField.vue';
import TextField from '@/components/form/TextField.vue';
import { t } from '@/lib/i18n';
import type { EventDetails } from '@/types/event';

/**
 * What the event says about itself. None of it drives the race, so none of it
 * ever freezes while the race is on.
 */
type Props = {
    event: EventDetails;
    errors: Record<string, string>;
};

defineProps<Props>();
</script>

<template>
    <FormFieldset :title="t('event.section.identity')">
        <FormField
            name="name"
            :label="t('event.field.name')"
            :error="errors.name"
        >
            <TextField
                id="name"
                name="name"
                :default-value="event.name ?? undefined"
                required
                maxlength="120"
            />
        </FormField>

        <FormField
            name="description"
            :label="t('event.field.description')"
            :error="errors.description"
        >
            <TextAreaField
                id="description"
                name="description"
                rows="4"
                :default-value="event.description ?? undefined"
            />
        </FormField>
    </FormFieldset>

    <FormFieldset :title="t('event.section.place')">
        <FormField
            name="address"
            :label="t('event.field.address')"
            :error="errors.address"
        >
            <TextField
                id="address"
                name="address"
                :default-value="event.address ?? undefined"
            />
        </FormField>

        <FormField
            name="latitude"
            :label="t('event.field.latitude')"
            :hint="t('event.hint.coordinates')"
            :error="errors.latitude"
        >
            <NumberField
                id="latitude"
                name="latitude"
                :min="-90"
                :max="90"
                :step="0.000001"
                :fraction-digits="6"
                :default-value="event.latitude ?? undefined"
            />
        </FormField>

        <FormField
            name="longitude"
            :label="t('event.field.longitude')"
            :error="errors.longitude"
        >
            <NumberField
                id="longitude"
                name="longitude"
                :min="-180"
                :max="180"
                :step="0.000001"
                :fraction-digits="6"
                :default-value="event.longitude ?? undefined"
            />
        </FormField>
    </FormFieldset>

    <FormFieldset :title="t('event.section.capacity')">
        <FormField
            name="max_participants"
            :label="t('event.field.max_participants')"
            :hint="t('event.hint.max_participants')"
            :error="errors.max_participants"
        >
            <NumberField
                id="max_participants"
                name="max_participants"
                :min="1"
                :max="1000"
                :default-value="event.max_participants ?? undefined"
            />
        </FormField>
    </FormFieldset>
</template>
