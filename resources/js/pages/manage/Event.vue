<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import EventController from '@/actions/App/Http/Controllers/Manage/EventController';
import EventField from '@/components/event/EventField.vue';
import EventStatusPanel from '@/components/event/EventStatusPanel.vue';
import EventSummary from '@/components/event/EventSummary.vue';
import Heading from '@/components/Heading.vue';
import ActionButton from '@/components/race/ActionButton.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { t } from '@/lib/i18n';
import { index as manage } from '@/routes/manage';
import { edit } from '@/routes/manage/event';
import type {
    EventDetails,
    EventFieldName,
    EventTransition,
} from '@/types/event';

type Props = {
    event: EventDetails;
    transition: EventTransition;
    frozenFields: EventFieldName[];
    isEditable: boolean;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Gestion', href: manage() },
            { title: 'Événement', href: edit() },
        ],
    },
});

const frozen = computed(() => new Set<EventFieldName>(props.frozenFields));

function isFrozen(field: EventFieldName): boolean {
    return frozen.value.has(field);
}
</script>

<template>
    <Head :title="t('event.manage.title')" />

    <div class="flex flex-col gap-6 p-4 pb-24">
        <Heading
            :title="t('event.manage.title')"
            :description="t('event.manage.description')"
        />

        <EventStatusPanel :transition="transition" />

        <template v-if="!isEditable">
            <Alert>
                <AlertTitle>{{ t('event.manage.readonly_title') }}</AlertTitle>
                <AlertDescription>
                    {{ t('event.manage.readonly_description') }}
                </AlertDescription>
            </Alert>

            <EventSummary :event="event" />
        </template>

        <Form
            v-else
            v-bind="EventController.update.form()"
            :options="{ preserveScroll: true }"
            class="flex flex-col gap-8"
            v-slot="{ errors, processing }"
        >
            <section class="flex flex-col gap-4">
                <h2
                    class="font-display text-label text-muted-foreground uppercase"
                >
                    {{ t('event.section.identity') }}
                </h2>

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
            </section>

            <section class="flex flex-col gap-4">
                <h2
                    class="font-display text-label text-muted-foreground uppercase"
                >
                    {{ t('event.section.schedule') }}
                </h2>

                <EventField
                    name="start_date"
                    :label="t('event.field.start_date')"
                    :error="errors.start_date"
                    :locked="isFrozen('first_start_at')"
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
                    :locked="isFrozen('first_start_at')"
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
            </section>

            <section class="flex flex-col gap-4">
                <h2
                    class="font-display text-label text-muted-foreground uppercase"
                >
                    {{ t('event.section.loop') }}
                </h2>

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
                    :locked="isFrozen('lap_duration_minutes')"
                    :locked-reason="t('event.locked.running')"
                    :value="
                        event.lap_duration_minutes === null
                            ? undefined
                            : String(event.lap_duration_minutes)
                    "
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
            </section>

            <section class="flex flex-col gap-4">
                <h2
                    class="font-display text-label text-muted-foreground uppercase"
                >
                    {{ t('event.section.place') }}
                </h2>

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
            </section>

            <section class="flex flex-col gap-4">
                <h2
                    class="font-display text-label text-muted-foreground uppercase"
                >
                    {{ t('event.section.capacity') }}
                </h2>

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
            </section>

            <div
                class="sticky bottom-0 -mx-4 border-t border-border bg-background/95 px-4 py-3 backdrop-blur"
            >
                <ActionButton type="submit" :loading="processing">
                    {{ t('event.manage.save') }}
                </ActionButton>
            </div>
        </Form>
    </div>
</template>
