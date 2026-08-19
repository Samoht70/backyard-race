<script setup lang="ts">
import { computed } from 'vue';
import StatCounter from '@/components/race/StatCounter.vue';
import { t } from '@/lib/i18n';
import type { EventDetails } from '@/types/event';

type Props = {
    event: EventDetails;
};

const props = defineProps<Props>();

const coordinates = computed(() => {
    if (props.event.latitude === null || props.event.longitude === null) {
        return t('event.public.no_coordinates');
    }

    return `${props.event.latitude}, ${props.event.longitude}`;
});

const facts = computed(() => [
    {
        term: t('event.field.start_date'),
        detail: props.event.start_date ?? t('event.public.no_date'),
    },
    {
        term: t('event.field.start_time'),
        detail: props.event.start_time ?? '—',
    },
    {
        term: t('event.field.address'),
        detail: props.event.address ?? '—',
    },
    {
        term: t('event.field.coordinates'),
        detail: coordinates.value,
    },
]);
</script>

<template>
    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-end gap-6">
            <StatCounter
                :value="event.lap_distance_meters"
                :label="t('event.field.lap_distance_meters')"
                :unit="t('event.unit.meters')"
            />
            <StatCounter
                :value="event.lap_duration_minutes"
                :label="t('event.field.lap_duration_minutes')"
                :unit="t('event.unit.minutes')"
            />
            <StatCounter
                :value="event.max_participants"
                :label="t('event.public.seats')"
            />
        </div>

        <dl class="flex flex-col gap-2">
            <div
                v-for="fact in facts"
                :key="fact.term"
                class="flex flex-wrap justify-between gap-2 border-b border-border pb-2"
            >
                <dt
                    class="font-display text-label text-muted-foreground uppercase"
                >
                    {{ fact.term }}
                </dt>
                <dd class="text-sm tabular-nums">{{ fact.detail }}</dd>
            </div>
        </dl>

        <p v-if="event.description" class="text-sm whitespace-pre-line">
            {{ event.description }}
        </p>
    </div>
</template>
