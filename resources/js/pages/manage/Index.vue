<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ClipboardList,
    Files,
    Hourglass,
    ScrollText,
    SlidersHorizontal,
    Undo2,
} from '@lucide/vue';
import { computed } from 'vue';
import BoardPage from '@/components/board/BoardPage.vue';
import NextRoundDuration from '@/components/race/NextRoundDuration.vue';
import RoundBoard from '@/components/race/RoundBoard.vue';
import RoundHeader from '@/components/race/RoundHeader.vue';
import RoundTally from '@/components/race/RoundTally.vue';
import EmptyState from '@/components/state/EmptyState.vue';
import { t } from '@/lib/i18n';
import { can } from '@/lib/permissions';
import {
    raceStandby,
    raceStandbyDescriptionKey,
    raceStandbyTitleKey,
} from '@/lib/raceStandby';
import { corrections } from '@/routes/manage';
import { edit as editBriefing } from '@/routes/manage/briefing';
import { index as documents } from '@/routes/manage/documents';
import { edit as editEvent } from '@/routes/manage/event';
import { index as registrations } from '@/routes/manage/registrations';
import type { EventStatus } from '@/types/event';
import type {
    CurrentRound,
    NextRound,
    RoundRunner,
    RunnerTally,
} from '@/types/race';

type Props = {
    eventStatus: EventStatus | null;
    currentRound: CurrentRound | null;
    nextRound: NextRound | null;
    tally: RunnerTally | null;
    roundRunners: RoundRunner[];
};

const props = defineProps<Props>();

const counts = computed(() =>
    props.tally === null
        ? []
        : [
              { label: t('race.round.runners_left'), value: props.tally.running },
              { label: t('race.round.runners_out'), value: props.tally.out },
          ],
);

const standby = computed(() => raceStandby(props.eventStatus));

const desks = computed(() =>
    [
        {
            key: 'corrections',
            icon: Undo2,
            label: t('ui.manage.corrections'),
            href: corrections(),
            shown: can('manage-laps') && props.currentRound !== null,
        },
        {
            key: 'event',
            icon: SlidersHorizontal,
            label: t('ui.manage.event'),
            href: editEvent(),
            shown: true,
        },
        {
            key: 'briefing',
            icon: ScrollText,
            label: t('ui.manage.briefing'),
            href: editBriefing(),
            shown: can('manage-documents'),
        },
        {
            key: 'documents',
            icon: Files,
            label: t('ui.manage.documents'),
            href: documents(),
            shown: can('manage-documents'),
        },
        {
            key: 'registrations',
            icon: ClipboardList,
            label: t('ui.manage.registrations'),
            href: registrations(),
            shown: can('manage-participants'),
        },
    ].filter((desk) => desk.shown),
);
</script>

<template>
    <Head :title="t('ui.manage.title')" />

    <div v-if="currentRound" class="sticky top-0 z-10 bg-background">
        <RoundHeader
            :round="currentRound.number"
            :start-at="currentRound.starts_at"
            :deadline-at="currentRound.deadline_at"
        />
        <RoundTally v-if="counts.length" :counts="counts" />
    </div>

    <BoardPage>
        <div class="grid gap-6">
            <h1 class="sr-only">{{ t('ui.manage.title') }}</h1>

            <RoundBoard v-if="currentRound" :runners="roundRunners" />

            <EmptyState
                v-else
                :icon="Hourglass"
                :title="t(raceStandbyTitleKey(standby))"
                :description="t(raceStandbyDescriptionKey(standby))"
            />

            <nav
                class="grid gap-1.5 sm:grid-cols-2 xl:grid-cols-4"
                :aria-label="t('ui.manage.title')"
            >
                <Link
                    v-for="desk in desks"
                    :key="desk.key"
                    :href="desk.href"
                    class="flex min-h-11 items-center gap-3 rounded-sm border border-border bg-card px-3 py-2.5 transition-colors hover:bg-accent"
                >
                    <component
                        :is="desk.icon"
                        class="size-5 shrink-0 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <span class="text-sm font-medium">{{ desk.label }}</span>
                </Link>
            </nav>

            <NextRoundDuration v-if="nextRound" :round="nextRound" />
        </div>
    </BoardPage>
</template>
