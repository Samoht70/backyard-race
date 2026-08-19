<script setup lang="ts">
import { computed } from 'vue';
import EventField from '@/components/event/EventField.vue';
import EventFieldset from '@/components/event/EventFieldset.vue';
import { Input } from '@/components/ui/input';
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
    <EventFieldset :title="t('event.section.schedule')">
        <EventField
            name="start_date"
            :label="t('event.field.start_date')"
            :error="errors.start_date"
            :locked="isScheduleFrozen"
            :locked-reason="t('event.locked.running')"
            :value="event.start_date ?? undefined"
        >
            <Input
                id="start_date"
                type="date"
                name="start_date"
                class="tabular-nums"
                :default-value="event.start_date ?? undefined"
            />
        </EventField>

        <EventField
            name="start_time"
            :label="t('event.field.start_time')"
            :error="errors.first_start_at"
            :locked="isScheduleFrozen"
            :locked-reason="t('event.locked.running')"
            :value="event.start_time ?? undefined"
        >
            <Input
                id="start_time"
                type="time"
                name="start_time"
                class="tabular-nums"
                :default-value="event.start_time ?? undefined"
            />
        </EventField>
    </EventFieldset>

    <EventFieldset :title="t('event.section.loop')">
        <EventField
            name="lap_distance_meters"
            :label="t('event.field.lap_distance_meters')"
            :hint="t('event.hint.lap_distance_meters')"
            :unit="t('event.unit.meters')"
            :error="errors.lap_distance_meters"
        >
            <Input
                id="lap_distance_meters"
                type="number"
                inputmode="numeric"
                min="1"
                step="1"
                name="lap_distance_meters"
                class="tabular-nums"
                :default-value="event.lap_distance_meters ?? undefined"
            />
        </EventField>

        <EventField
            name="lap_duration_minutes"
            :label="t('event.field.lap_duration_minutes')"
            :hint="t('event.hint.lap_duration_minutes')"
            :unit="t('event.unit.minutes')"
            :error="errors.lap_duration_minutes"
            :locked="isDurationFrozen"
            :locked-reason="t('event.locked.running')"
            :value="duration"
        >
            <Input
                id="lap_duration_minutes"
                type="number"
                inputmode="numeric"
                min="1"
                step="1"
                name="lap_duration_minutes"
                class="tabular-nums"
                :default-value="event.lap_duration_minutes ?? undefined"
            />
        </EventField>
    </EventFieldset>
</template>
