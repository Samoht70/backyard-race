<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ClipboardList,
    Files,
    ScrollText,
    SlidersHorizontal,
} from '@lucide/vue';
import { computed } from 'vue';
import BoardPage from '@/components/board/BoardPage.vue';
import NextRoundDuration from '@/components/race/NextRoundDuration.vue';
import RoundBoard from '@/components/race/RoundBoard.vue';
import RoundHeader from '@/components/race/RoundHeader.vue';
import { t } from '@/lib/i18n';
import { can } from '@/lib/permissions';
import { edit as editBriefing } from '@/routes/manage/briefing';
import { index as documents } from '@/routes/manage/documents';
import { edit as editEvent } from '@/routes/manage/event';
import { index as registrations } from '@/routes/manage/registrations';
import type { CurrentRound, NextRound, RoundRunner } from '@/types/race';

type Props = {
    currentRound: CurrentRound | null;
    nextRound: NextRound | null;
    roundRunners: RoundRunner[];
};

defineProps<Props>();

const desks = computed(() =>
    [
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

    <RoundHeader
        v-if="currentRound"
        :round="currentRound.number"
        :start-at="currentRound.starts_at"
        :deadline-at="currentRound.deadline_at"
    />

    <BoardPage>
        <div class="grid gap-6">
            <h1 class="text-title">{{ t('ui.manage.title') }}</h1>

            <nav class="grid gap-1.5 sm:grid-cols-2 xl:grid-cols-4">
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

            <RoundBoard v-if="currentRound" :runners="roundRunners" />

            <NextRoundDuration v-if="nextRound" :round="nextRound" />
        </div>
    </BoardPage>
</template>
