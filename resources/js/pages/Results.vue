<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Trophy } from '@lucide/vue';
import { computed } from 'vue';
import ActionButton from '@/components/ActionButton.vue';
import ActionBar from '@/components/board/ActionBar.vue';
import BoardPage from '@/components/board/BoardPage.vue';
import BoardSection from '@/components/board/BoardSection.vue';
import Heading from '@/components/Heading.vue';
import RoundOutcomeList from '@/components/race/RoundOutcomeList.vue';
import RunnerSlat from '@/components/race/RunnerSlat.vue';
import StatCounter from '@/components/race/StatCounter.vue';
import EmptyState from '@/components/state/EmptyState.vue';
import { formatKilometers } from '@/lib/distance';
import { t } from '@/lib/i18n';
import { formatRaceDuration } from '@/lib/raceTotals';
import { index as standings } from '@/routes/standings';
import type { EventTotals, RoundOutcome, Standing } from '@/types/race';

type Props = {
    event: {
        name: string | null;
        finished_at: string | null;
    };
    winners: Standing[];
    totals: EventTotals;
    rounds: RoundOutcome[];
};

const props = defineProps<Props>();

const EMPTY = '—';

const title = computed(() => props.event.name ?? t('race.results.title'));

const description = computed(() =>
    props.event.finished_at === null
        ? t('race.results.no_instant')
        : t('race.results.description', { instant: props.event.finished_at }),
);

const winnerTitle = computed(() =>
    props.winners.length > 1
        ? t('race.results.winners')
        : t('race.results.winner'),
);

const counts = computed(() => [
    {
        label: t('race.results.participants'),
        value: props.totals.participants,
    },
    {
        label: t('race.results.laps'),
        value: props.totals.validated_laps,
    },
    {
        label: t('race.results.distance'),
        value: formatKilometers(props.totals.covered_meters),
        unit: t('event.unit.kilometers'),
    },
    {
        label: t('race.results.duration'),
        value: formatRaceDuration(props.totals.duration_seconds),
    },
]);

function distance(winner: Standing): string | undefined {
    const kilometers = formatKilometers(winner.covered_meters);

    return kilometers === null
        ? undefined
        : `${kilometers} ${t('event.unit.kilometers')}`;
}
</script>

<template>
    <Head :title="title" />

    <BoardPage>
        <div class="grid gap-6">
            <Heading
                :title="t('race.results.title')"
                :description="description"
            />

            <BoardSection :title="winnerTitle">
                <RunnerSlat
                    v-for="winner in winners"
                    :key="`${winner.bib_label}-${winner.last_name}`"
                    :bib="winner.bib_label ?? EMPTY"
                    :first-name="winner.first_name"
                    :last-name="winner.last_name"
                    :status="winner.status"
                    :laps="winner.validated_laps"
                    :meta="distance(winner)"
                />

                <EmptyState
                    v-if="!winners.length"
                    :icon="Trophy"
                    :title="t('race.results.no_winner_title')"
                    :description="t('race.results.no_winner_description')"
                />
            </BoardSection>

            <BoardSection :title="t('race.results.totals')">
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    <StatCounter
                        v-for="count in counts"
                        :key="count.label"
                        :value="count.value"
                        :label="count.label"
                        :unit="count.unit"
                    />
                </div>
            </BoardSection>

            <RoundOutcomeList :rounds="rounds" />

            <ActionBar>
                <ActionButton as-child>
                    <Link :href="standings()">
                        {{ t('race.results.standings') }}
                    </Link>
                </ActionButton>
            </ActionBar>
        </div>
    </BoardPage>
</template>
