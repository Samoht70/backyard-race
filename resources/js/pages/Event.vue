<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarOff } from '@lucide/vue';
import { computed } from 'vue';
import ActionButton from '@/components/ActionButton.vue';
import ActionBar from '@/components/board/ActionBar.vue';
import BoardColumns from '@/components/board/BoardColumns.vue';
import BoardPage from '@/components/board/BoardPage.vue';
import EventStatusBadge from '@/components/event/EventStatusBadge.vue';
import EventSummary from '@/components/event/EventSummary.vue';
import Notice from '@/components/Notice.vue';
import SeatCounter from '@/components/registration/SeatCounter.vue';
import EmptyState from '@/components/state/EmptyState.vue';
import { isAuthenticated } from '@/lib/auth';
import { t } from '@/lib/i18n';
import { can } from '@/lib/permissions';
import { create as createAccount } from '@/routes/account';
import { show as showRegistration } from '@/routes/registration';
import type { EventDetails } from '@/types/event';

type Props = {
    event: EventDetails | null;
    canRegister: boolean;
    isRegistered: boolean;
};

const props = defineProps<Props>();

const isDraft = computed(() => props.event?.status === 'draft');
const canJoin = computed(() => props.canRegister && !isAuthenticated());
const title = computed(() => {
    if (props.event === null) {
        return t('event.public.empty_title');
    }

    return props.event.name ?? t('event.public.untitled');
});
</script>

<template>
    <Head :title="title" />

    <BoardPage>
        <EmptyState
            v-if="event === null"
            :icon="CalendarOff"
            :title="t('event.public.empty_title')"
            :description="t('event.public.empty_description')"
        />

        <div v-else class="grid gap-6">
            <Notice
                v-if="isDraft && can('manage-event')"
                :title="t('event.public.draft_notice_title')"
            >
                {{ t('event.public.draft_notice_description') }}
            </Notice>

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

                    <ActionButton v-if="canJoin" as-child>
                        <Link :href="createAccount()">
                            {{ t('event.public.register') }}
                        </Link>
                    </ActionButton>
                </ActionBar>
            </BoardColumns>
        </div>
    </BoardPage>
</template>
