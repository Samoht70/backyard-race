<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import EventStatusBadge from '@/components/event/EventStatusBadge.vue';
import EventSummary from '@/components/event/EventSummary.vue';
import SeatCounter from '@/components/registration/SeatCounter.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { t } from '@/lib/i18n';
import { can } from '@/lib/permissions';
import { show } from '@/routes/event';
import { show as showRegistration } from '@/routes/registration';
import type { EventDetails } from '@/types/event';

type Props = {
    event: EventDetails;
    canRegister: boolean;
    isRegistered: boolean;
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

const registrationLinkClasses =
    'inline-flex min-h-11 w-full touch-manipulation items-center justify-center border border-primary bg-primary text-xs font-bold tracking-widest text-primary-foreground uppercase';

const isDraft = computed(() => props.event.status === 'draft');
const title = computed(() => props.event.name ?? t('event.public.untitled'));
</script>

<template>
    <Head :title="title" />

    <div class="flex flex-col gap-6 p-4">
        <Alert v-if="isDraft && can('manage-event')">
            <AlertTitle>{{ t('event.public.draft_notice_title') }}</AlertTitle>
            <AlertDescription>
                {{ t('event.public.draft_notice_description') }}
            </AlertDescription>
        </Alert>

        <header class="flex flex-col items-center gap-2 text-center">
            <h1 class="text-title">{{ title }}</h1>
            <EventStatusBadge :status="event.status" />
        </header>

        <p v-if="canRegister" class="text-center text-sm text-muted-foreground">
            {{ t('event.public.registrations_open') }}
        </p>

        <SeatCounter
            v-if="canRegister || isRegistered"
            :confirmed="event.confirmed_participants"
            :capacity="event.max_participants"
        />

        <Link
            v-if="isRegistered"
            :href="showRegistration()"
            :class="registrationLinkClasses"
        >
            {{ t('registration.show.call_to_action') }}
        </Link>

        <EventSummary :event="event" />
    </div>
</template>
