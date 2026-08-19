<script setup lang="ts">
import EventField from '@/components/event/EventField.vue';
import EventFieldset from '@/components/event/EventFieldset.vue';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
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
    <EventFieldset :title="t('event.section.identity')">
        <EventField
            name="name"
            :label="t('event.field.name')"
            :error="errors.name"
        >
            <Input
                id="name"
                name="name"
                :default-value="event.name ?? undefined"
                required
                maxlength="120"
            />
        </EventField>

        <EventField
            name="description"
            :label="t('event.field.description')"
            :error="errors.description"
        >
            <Textarea
                id="description"
                name="description"
                rows="4"
                :default-value="event.description ?? undefined"
            />
        </EventField>
    </EventFieldset>

    <EventFieldset :title="t('event.section.place')">
        <EventField
            name="address"
            :label="t('event.field.address')"
            :error="errors.address"
        >
            <Input
                id="address"
                name="address"
                :default-value="event.address ?? undefined"
            />
        </EventField>

        <EventField
            name="latitude"
            :label="t('event.field.latitude')"
            :hint="t('event.hint.coordinates')"
            :error="errors.latitude"
        >
            <Input
                id="latitude"
                type="number"
                inputmode="decimal"
                step="any"
                min="-90"
                max="90"
                name="latitude"
                class="tabular-nums"
                :default-value="event.latitude ?? undefined"
            />
        </EventField>

        <EventField
            name="longitude"
            :label="t('event.field.longitude')"
            :error="errors.longitude"
        >
            <Input
                id="longitude"
                type="number"
                inputmode="decimal"
                step="any"
                min="-180"
                max="180"
                name="longitude"
                class="tabular-nums"
                :default-value="event.longitude ?? undefined"
            />
        </EventField>
    </EventFieldset>

    <EventFieldset :title="t('event.section.capacity')">
        <EventField
            name="max_participants"
            :label="t('event.field.max_participants')"
            :hint="t('event.hint.max_participants')"
            :error="errors.max_participants"
        >
            <Input
                id="max_participants"
                type="number"
                inputmode="numeric"
                min="1"
                step="1"
                name="max_participants"
                class="tabular-nums"
                :default-value="event.max_participants ?? undefined"
            />
        </EventField>
    </EventFieldset>
</template>
