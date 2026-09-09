<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Hourglass } from '@lucide/vue';
import { computed, onMounted, onUnmounted } from 'vue';
import ManagePage from '@/components/manage/ManagePage.vue';
import NextRoundDuration from '@/components/race/NextRoundDuration.vue';
import RoundBoard from '@/components/race/RoundBoard.vue';
import EmptyState from '@/components/state/EmptyState.vue';
import { usePolling } from '@/composables/usePolling';
import { t } from '@/lib/i18n';
import {
    raceStandby,
    raceStandbyDescriptionKey,
    raceStandbyTitleKey,
} from '@/lib/raceStandby';
import type { EventStatus } from '@/types/event';
import type { CurrentRound, NextRound, RoundRunner } from '@/types/race';

type Props = {
    eventStatus: EventStatus | null;
    currentRound: CurrentRound | null;
    nextRound: NextRound | null;
    roundRunners: RoundRunner[];
};

const props = defineProps<Props>();

const standby = computed(() => raceStandby(props.eventStatus));

const { start, stop } = usePolling([
    'eventStatus',
    'currentRound',
    'nextRound',
    'roundRunners',
]);

onMounted(start);
onUnmounted(stop);
</script>

<template>
    <Head :title="t('ui.manage.title')" />

    <ManagePage>
        <h1 class="sr-only">{{ t('ui.manage.title') }}</h1>

        <NextRoundDuration v-if="nextRound" :round="nextRound" />

        <RoundBoard v-if="currentRound" :runners="roundRunners" />

        <EmptyState
            v-else
            :icon="Hourglass"
            :title="t(raceStandbyTitleKey(standby))"
            :description="t(raceStandbyDescriptionKey(standby))"
        />
    </ManagePage>
</template>
