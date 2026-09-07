<script setup lang="ts">
import { t } from '@/lib/i18n';
import type { RoundOutcome } from '@/types/race';

type Props = {
    rounds: RoundOutcome[];
};

defineProps<Props>();
</script>

<template>
    <section
        class="grid gap-2 rounded-sm border border-border bg-card px-4 py-3 font-mono"
    >
        <h2 class="text-label text-muted-foreground uppercase">
            {{ t('race.results.rounds') }}
        </h2>

        <p v-if="!rounds.length" class="text-sm text-muted-foreground">
            {{ t('race.results.rounds_empty') }}
        </p>

        <div v-else class="overflow-x-auto">
            <table class="w-full text-data tabular-nums">
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
                            {{ t('race.results.round_runners') }}
                        </th>
                        <th
                            scope="col"
                            class="border-b border-border pb-1.5 text-right font-normal"
                        >
                            {{ t('race.results.round_completed') }}
                        </th>
                        <th
                            scope="col"
                            class="border-b border-border pb-1.5 text-right font-normal"
                        >
                            {{ t('race.results.round_withdrawals') }}
                        </th>
                        <th
                            scope="col"
                            class="border-b border-border pb-1.5 text-right font-normal"
                        >
                            {{ t('race.results.round_timeouts') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="round in rounds"
                        :key="round.number"
                        class="border-b border-border-soft last:border-0"
                    >
                        <th scope="row" class="py-1.5 text-left font-bold">
                            {{ round.number }}
                        </th>
                        <td class="py-1.5 text-right text-muted-foreground">
                            {{ round.runners }}
                        </td>
                        <td class="py-1.5 text-right font-bold">
                            {{ round.completed_laps }}
                        </td>
                        <td class="py-1.5 text-right text-muted-foreground">
                            {{ round.withdrawals }}
                        </td>
                        <td class="py-1.5 text-right text-muted-foreground">
                            {{ round.timeouts }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
