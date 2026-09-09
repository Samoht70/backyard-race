<script setup lang="ts">
import { computed } from 'vue';
import RunnerLapList from '@/components/race/RunnerLapList.vue';
import { formatKilometers } from '@/lib/distance';
import { t } from '@/lib/i18n';
import {
    averageSpeedKmh,
    formatLapDuration,
    formatSpeed,
    lastTimedLap,
} from '@/lib/lapReadout';
import { runnerStatusLabelKey } from '@/lib/runnerStatus';
import type { NextRound, RunnerSearchResult } from '@/types/race';

type Props = {
    runner: RunnerSearchResult;
    nextRound?: NextRound | null;
};

const props = defineProps<Props>();

const EMPTY = '—';

const distance = computed(() => {
    const kilometers = formatKilometers(props.runner.covered_meters);

    return kilometers === null
        ? EMPTY
        : `${kilometers} ${t('event.unit.kilometers')}`;
});

const lastLap = computed(() => lastTimedLap(props.runner.laps));

const lastLapDuration = computed(() => {
    const seconds = lastLap.value?.duration_seconds ?? null;

    return seconds === null ? EMPTY : formatLapDuration(seconds);
});

const lastLapSpeed = computed(() => {
    const speed = lastLap.value?.speed_kmh ?? null;

    return speed === null
        ? EMPTY
        : `${formatSpeed(speed)} ${t('race.lap.speed_unit')}`;
});

const averageSpeed = computed(() => {
    const speed = averageSpeedKmh(props.runner.laps);

    return speed === null
        ? EMPTY
        : `${formatSpeed(speed)} ${t('race.lap.speed_unit')}`;
});

const isRunning = computed(() => props.runner.status === 'running');

const nextStart = computed(() => {
    const round = props.nextRound ?? null;

    return round === null
        ? null
        : `${t('race.round.short', { number: round.number })} · ${round.starts_at}`;
});

const exitLabel = computed(() => t(runnerStatusLabelKey(props.runner.status)));
</script>

<template>
    <div class="grid gap-3">
        <dl
            class="grid grid-cols-2 gap-x-4 gap-y-3 rounded-sm border border-border bg-card px-4 py-3 font-mono"
        >
            <div class="flex flex-col gap-0.5">
                <dt class="text-label text-muted-foreground uppercase">
                    {{ t('race.runner.last_lap') }}
                </dt>
                <dd class="text-sm font-bold tabular-nums">
                    {{ lastLapDuration }}
                </dd>
            </div>
            <div class="flex flex-col gap-0.5">
                <dt class="text-label text-muted-foreground uppercase">
                    {{ t('race.runner.last_speed') }}
                </dt>
                <dd class="text-sm font-bold tabular-nums">
                    {{ lastLapSpeed }}
                </dd>
            </div>
            <div class="flex flex-col gap-0.5">
                <dt class="text-label text-muted-foreground uppercase">
                    {{ t('race.search.total_distance') }}
                </dt>
                <dd class="text-sm font-bold tabular-nums">{{ distance }}</dd>
            </div>
            <div class="flex flex-col gap-0.5">
                <dt class="text-label text-muted-foreground uppercase">
                    {{ t('race.runner.average_speed') }}
                </dt>
                <dd class="text-sm font-bold tabular-nums">
                    {{ averageSpeed }}
                </dd>
            </div>
            <div
                v-if="isRunning && nextStart !== null"
                class="col-span-2 flex flex-col gap-0.5"
            >
                <dt class="text-label text-muted-foreground uppercase">
                    {{ t('race.runner.next_start') }}
                </dt>
                <dd class="text-sm font-bold tabular-nums">{{ nextStart }}</dd>
            </div>
            <div v-if="!isRunning" class="col-span-2 flex flex-col gap-0.5">
                <dt class="text-label text-muted-foreground uppercase">
                    {{ exitLabel }}
                </dt>
                <dd class="text-sm font-bold tabular-nums">
                    {{ runner.exited_at ?? EMPTY }}
                </dd>
            </div>
        </dl>

        <RunnerLapList :laps="runner.laps" />
    </div>
</template>
