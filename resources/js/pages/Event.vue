<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import EventStatusBadge from '@/components/event/EventStatusBadge.vue';
import EventSummary from '@/components/event/EventSummary.vue';
import FestoonDivider from '@/components/race/FestoonDivider.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { t } from '@/lib/i18n';
import { can } from '@/lib/permissions';
import { show } from '@/routes/event';
import type { EventDetails } from '@/types/event';

type Props = {
    event: EventDetails;
    canRegister: boolean;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Événement',
                href: show(),
            },
        ],
    },
});

const isDraft = computed(() => props.event.status === 'draft');
</script>

<template>
    <Head :title="event.name" />

    <div class="flex flex-col gap-6 p-4">
        <Alert v-if="isDraft && can('manage-event')">
            <AlertTitle>{{ t('event.public.draft_notice_title') }}</AlertTitle>
            <AlertDescription>
                {{ t('event.public.draft_notice_description') }}
            </AlertDescription>
        </Alert>

        <header class="flex flex-col items-center gap-2 text-center">
            <h1 class="font-display text-lap">{{ event.name }}</h1>
            <EventStatusBadge :status="event.status" />
        </header>

        <FestoonDivider />

        <p v-if="canRegister" class="text-center text-sm text-muted-foreground">
            {{ t('event.public.registrations_open') }}
        </p>

        <EventSummary :event="event" />
    </div>
</template>
