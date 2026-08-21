<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ActionBar from '@/components/board/ActionBar.vue';
import BoardColumns from '@/components/board/BoardColumns.vue';
import BoardPage from '@/components/board/BoardPage.vue';
import EventStatusBadge from '@/components/event/EventStatusBadge.vue';
import EventSummary from '@/components/event/EventSummary.vue';
import ActionButton from '@/components/race/ActionButton.vue';
import SeatCounter from '@/components/registration/SeatCounter.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { t } from '@/lib/i18n';
import { can } from '@/lib/permissions';
import { show as showRegistration } from '@/routes/registration';
import type { EventDetails } from '@/types/event';

type Props = {
    event: EventDetails;
    canRegister: boolean;
    isRegistered: boolean;
};

const props = defineProps<Props>();

const isDraft = computed(() => props.event.status === 'draft');
const title = computed(() => props.event.name ?? t('event.public.untitled'));
</script>

<template>
    <Head :title="title" />

    <BoardPage>
        <div class="grid gap-6">
            <Alert v-if="isDraft && can('manage-event')">
                <AlertTitle>
                    {{ t('event.public.draft_notice_title') }}
                </AlertTitle>
                <AlertDescription>
                    {{ t('event.public.draft_notice_description') }}
                </AlertDescription>
            </Alert>

            <BoardColumns>
                <template #lead>
                    <div class="grid justify-items-start gap-2">
                        <h1 class="text-title">{{ title }}</h1>
                        <EventStatusBadge :status="event.status" />
                    </div>

                    <SeatCounter
                        v-if="canRegister || isRegistered"
                        :confirmed="event.confirmed_participants"
                        :capacity="event.max_participants"
                    />
                </template>

                <EventSummary :event="event" />

                <ActionBar v-if="isRegistered || canRegister">
                    <template v-if="canRegister" #note>
                        {{ t('event.public.registrations_open') }}
                    </template>

                    <ActionButton v-if="isRegistered" as-child>
                        <Link :href="showRegistration()">
                            {{ t('registration.show.call_to_action') }}
                        </Link>
                    </ActionButton>
                </ActionBar>
            </BoardColumns>
        </div>
    </BoardPage>
</template>
