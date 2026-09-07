<script setup lang="ts">
import { computed } from 'vue';
import { formatKilometers } from '@/lib/distance';
import { t } from '@/lib/i18n';
import { formatLapDuration, formatSpeed } from '@/lib/lapReadout';
import type { RunnerLap } from '@/types/race';

type Props = {
    laps: RunnerLap[];
};

const props = defineProps<Props>();

const EMPTY = '—';

const hasCorrection = computed(() => props.laps.some((lap) => lap.corrected));

function duration(lap: RunnerLap): string {
    return lap.duration_seconds === null
        ? t('race.detail.pending')
        : formatLapDuration(lap.duration_seconds);
}

function distance(lap: RunnerLap): string {
    return formatKilometers(lap.distance_meters) ?? EMPTY;
}

function speed(lap: RunnerLap): string {
    return lap.speed_kmh === null ? EMPTY : formatSpeed(lap.speed_kmh);
}
</script>

<template>
    <section
        class="grid gap-2 rounded-sm border border-border bg-card px-4 py-3 font-mono"
    >
        <h2 class="text-label text-muted-foreground uppercase">
            {{ t('race.detail.title') }}
        </h2>

        <p v-if="!laps.length" class="text-sm text-muted-foreground">
            {{ t('race.detail.empty') }}
        </p>

        <table v-else class="w-full text-data tabular-nums">
            <thead>
                <tr class="text-label text-muted-foreground uppercase">
                    <th
                        scope="col"
                        class="w-10 border-b border-border pb-1.5 text-left font-normal"
                    >
                        {{ t('race.round.number') }}
                    </th>
                    <th
                        scope="col"
                        class="border-b border-border pb-1.5 text-right font-normal"
                    >
                        {{ t('race.lap.time') }}
                    </th>
                    <th
                        scope="col"
                        class="border-b border-border pb-1.5 text-right font-normal"
                    >
                        {{ t('event.unit.kilometers') }}
                    </th>
                    <th
                        scope="col"
                        class="border-b border-border pb-1.5 text-right font-normal"
                    >
                        {{ t('race.lap.speed_unit') }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="lap in laps"
                    :key="lap.round_number"
                    class="border-b border-border-soft last:border-0"
                >
                    <th scope="row" class="py-1.5 text-left font-bold">
                        {{ lap.round_number }}
                        <template v-if="lap.corrected">
                            <span aria-hidden="true">*</span>
                            <span class="sr-only">
                                {{ t('race.correction.marker') }}
                            </span>
                        </template>
                    </th>
                    <td
                        class="py-1.5 text-right"
                        :class="
                            lap.duration_seconds === null
                                ? 'text-muted-foreground'
                                : 'font-bold'
                        "
                    >
                        {{ duration(lap) }}
                    </td>
                    <td class="py-1.5 text-right text-muted-foreground">
                        {{ distance(lap) }}
                    </td>
                    <td class="py-1.5 text-right text-muted-foreground">
                        {{ speed(lap) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <p v-if="hasCorrection" class="text-label text-muted-foreground">
            * {{ t('race.correction.marker') }}
        </p>
    </section>
</template>
