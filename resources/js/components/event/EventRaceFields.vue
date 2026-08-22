<script setup lang="ts">
import { computed } from 'vue';
import DateField from '@/components/form/DateField.vue';
import FormField from '@/components/form/FormField.vue';
import FormFieldset from '@/components/form/FormFieldset.vue';
import NumberField from '@/components/form/NumberField.vue';
import TimeField from '@/components/form/TimeField.vue';
import { t } from '@/lib/i18n';
import type { EventDetails, EventFieldName } from '@/types/event';

/**
 * The parameters the race runs on. Two of them freeze once it starts: BR-04
 * derives every lap start from them.
 */
type Props = {
    event: EventDetails;
    errors: Record<string, string>;
    frozenFields: EventFieldName[];
};

const props = defineProps<Props>();

const frozen = computed(() => new Set<EventFieldName>(props.frozenFields));

const isScheduleFrozen = computed(() => frozen.value.has('first_start_at'));
const isDurationFrozen = computed(() =>
    frozen.value.has('lap_duration_minutes'),
);
const duration = computed(() =>
    props.event.lap_duration_minutes === null
        ? undefined
        : String(props.event.lap_duration_minutes),
);
</script>

<template>
    <FormFieldset :title="t('event.section.schedule')">
        <FormField
            name="start_date"
            :label="t('event.field.start_date')"
            :error="errors.start_date"
            :locked="isScheduleFrozen"
            :locked-reason="t('event.locked.running')"
            :value="event.start_date ?? undefined"
        >
            <DateField
                id="start_date"
                name="start_date"
                :default-value="event.start_date ?? undefined"
            />
        </FormField>

        <FormField
            name="start_time"
            :label="t('event.field.start_time')"
            :error="errors.first_start_at"
            :locked="isScheduleFrozen"
            :locked-reason="t('event.locked.running')"
            :value="event.start_time ?? undefined"
        >
            <TimeField
                id="start_time"
                name="start_time"
                :default-value="event.start_time ?? undefined"
            />
        </FormField>
    </FormFieldset>

    <FormFieldset :title="t('event.section.loop')">
        <FormField
            name="lap_distance_meters"
            :label="t('event.field.lap_distance_meters')"
            :hint="t('event.hint.lap_distance_meters')"
            :unit="t('event.unit.meters')"
            :error="errors.lap_distance_meters"
        >
            <NumberField
                id="lap_distance_meters"
                name="lap_distance_meters"
                :min="1"
                :max="100000"
                :default-value="event.lap_distance_meters ?? undefined"
            />
        </FormField>

        <FormField
            name="lap_duration_minutes"
            :label="t('event.field.lap_duration_minutes')"
            :hint="t('event.hint.lap_duration_minutes')"
            :unit="t('event.unit.minutes')"
            :error="errors.lap_duration_minutes"
            :locked="isDurationFrozen"
            :locked-reason="t('event.locked.running')"
            :value="duration"
        >
            <NumberField
                id="lap_duration_minutes"
                name="lap_duration_minutes"
                :min="1"
                :max="1440"
                :default-value="event.lap_duration_minutes ?? undefined"
            />
        </FormField>
    </FormFieldset>
</template>
