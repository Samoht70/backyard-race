<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import EventController from '@/actions/App/Http/Controllers/Manage/EventController';
import BoardPage from '@/components/board/BoardPage.vue';
import EventDetailsFields from '@/components/event/EventDetailsFields.vue';
import EventRaceFields from '@/components/event/EventRaceFields.vue';
import EventStatusPanel from '@/components/event/EventStatusPanel.vue';
import EventSummary from '@/components/event/EventSummary.vue';
import Heading from '@/components/Heading.vue';
import ActionButton from '@/components/race/ActionButton.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { t } from '@/lib/i18n';
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

defineProps<Props>();
</script>

<template>
    <Head :title="t('event.manage.title')" />

    <BoardPage>
        <div class="grid max-w-4xl gap-6">
            <Heading
                :title="t('event.manage.title')"
                :description="t('event.manage.description')"
            />

            <EventStatusPanel :transition="transition" />

            <template v-if="!isEditable">
                <Alert>
                    <AlertTitle>{{
                        t('event.manage.readonly_title')
                    }}</AlertTitle>
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
                <EventDetailsFields :event="event" :errors="errors" />

                <EventRaceFields
                    :event="event"
                    :errors="errors"
                    :frozen-fields="frozenFields"
                />

                <div
                    class="sticky bottom-0 -mx-4 border-t border-border bg-background/95 px-4 py-3 backdrop-blur"
                >
                    <ActionButton type="submit" :loading="processing">
                        {{ t('event.manage.save') }}
                    </ActionButton>
                </div>
            </Form>
        </div>
    </BoardPage>
</template>
