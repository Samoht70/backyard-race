<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Trophy } from '@lucide/vue';
import { computed } from 'vue';
import BoardPage from '@/components/board/BoardPage.vue';
import Heading from '@/components/Heading.vue';
import StatusBadge from '@/components/race/StatusBadge.vue';
import EmptyState from '@/components/state/EmptyState.vue';
import { formatKilometers } from '@/lib/distance';
import { t } from '@/lib/i18n';
import type { Standing } from '@/types/race';

type Props = {
    event: {
        name: string | null;
        finished_at: string | null;
    };
    standings: Standing[];
};

const props = defineProps<Props>();

const EMPTY = '—';

const description = computed(() =>
    props.event.finished_at === null
        ? t('race.standings.no_instant')
        : t('race.standings.description', { instant: props.event.finished_at }),
);

const sharedRanks = computed(() => {
    const seen = new Set<number>();
    const shared = new Set<number>();

    props.standings.forEach((standing) => {
        if (seen.has(standing.rank)) {
            shared.add(standing.rank);
        }

        seen.add(standing.rank);
    });

    return shared;
});

function distance(standing: Standing): string {
    return formatKilometers(standing.covered_meters) ?? EMPTY;
}
</script>

<template>
    <Head :title="t('race.standings.title')" />

    <BoardPage>
        <div class="grid gap-6">
            <Heading
                :title="t('race.standings.title')"
                :description="description"
            />

            <section
                v-if="standings.length"
                class="grid gap-2 rounded-sm border border-border bg-card px-4 py-3 font-mono"
            >
                <table class="w-full text-data tabular-nums">
                    <thead>
                        <tr class="text-label text-muted-foreground uppercase">
                            <th
                                scope="col"
                                class="w-10 border-b border-border pb-1.5 text-left font-normal"
                            >
                                {{ t('race.standings.rank') }}
                            </th>
                            <th
                                scope="col"
                                class="border-b border-border pb-1.5 text-left font-normal"
                            >
                                {{ t('race.standings.runner') }}
                            </th>
                            <th
                                scope="col"
                                class="border-b border-border pb-1.5 text-right font-normal"
                            >
                                {{ t('race.standings.laps') }}
                            </th>
                            <th
                                scope="col"
                                class="border-b border-border pb-1.5 text-right font-normal"
                            >
                                {{ t('event.unit.kilometers') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="standing in standings"
                            :key="`${standing.rank}-${standing.bib_label}-${standing.last_name}`"
                            class="border-b border-border-soft last:border-0"
                        >
                            <th
                                scope="row"
                                class="py-2 text-left align-top font-bold"
                            >
                                {{ standing.rank }}
                                <span
                                    v-if="sharedRanks.has(standing.rank)"
                                    class="sr-only"
                                >
                                    {{
                                        t('race.standings.tied', {
                                            rank: standing.rank,
                                        })
                                    }}
                                </span>
                            </th>
                            <td class="py-2">
                                <span class="font-bold">
                                    {{ standing.first_name }}
                                    {{ standing.last_name }}
                                </span>
                                <span
                                    class="mt-0.5 flex items-center gap-2 text-label text-muted-foreground"
                                >
                                    <span v-if="standing.bib_label">
                                        {{ standing.bib_label }}
                                    </span>
                                    <StatusBadge
                                        :status="standing.status"
                                        size="sm"
                                    />
                                </span>
                            </td>
                            <td class="py-2 text-right align-top font-bold">
                                {{ standing.validated_laps }}
                            </td>
                            <td
                                class="py-2 text-right align-top text-muted-foreground"
                            >
                                {{ distance(standing) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <EmptyState
                v-else
                :icon="Trophy"
                :title="t('race.standings.empty_title')"
                :description="t('race.standings.empty_description')"
            />
        </div>
    </BoardPage>
</template>
