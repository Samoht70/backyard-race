<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarOff, SlidersHorizontal, Ticket } from '@lucide/vue';
import { computed, onMounted, onUnmounted } from 'vue';
import ActionButton from '@/components/ActionButton.vue';
import BoardPage from '@/components/board/BoardPage.vue';
import Heading from '@/components/Heading.vue';
import NextRoundDuration from '@/components/race/NextRoundDuration.vue';
import RunnerDetailPanel from '@/components/race/RunnerDetailPanel.vue';
import RunnerSearchBoard from '@/components/race/RunnerSearchBoard.vue';
import RunnerSlat from '@/components/race/RunnerSlat.vue';
import EmptyState from '@/components/state/EmptyState.vue';
import { usePolling } from '@/composables/usePolling';
import { canReach } from '@/lib/access';
import { t } from '@/lib/i18n';
import { runnerStatusLabelKey } from '@/lib/runnerStatus';
import { dashboard, home } from '@/routes';
import { index as showManage } from '@/routes/manage';
import { show as showRegistration } from '@/routes/registration';
import type { NextRound, RunnerSearchResult, RunnerTally } from '@/types/race';

type Mode =
    | 'no_event'
    | 'manager_idle'
    | 'manager_search'
    | 'no_registration'
    | 'runner_waiting'
    | 'runner_active';

type Props = {
    mode: Mode;
    event: { name: string | null; status: string } | null;
    query?: string | null;
    tally?: RunnerTally;
    runners?: RunnerSearchResult[];
    runner?: RunnerSearchResult;
    nextRound?: NextRound | null;
};

const props = defineProps<Props>();

const title = computed(() => props.event?.name ?? t('ui.dashboard.title'));

const runnerMeta = computed(() => {
    if (!props.runner || props.runner.exited_at === null) {
        return undefined;
    }

    return `${t(runnerStatusLabelKey(props.runner.status))} · ${props.runner.exited_at}`;
});

function search(term: string): void {
    router.get(
        dashboard().url,
        { q: term },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['runners', 'tally', 'query'],
        },
    );
}

const { start, stop } = usePolling([
    'mode',
    'event',
    'query',
    'tally',
    'runners',
    'runner',
    'nextRound',
]);

onMounted(start);
onUnmounted(stop);
</script>

<template>
    <Head :title="title" />

    <BoardPage>
        <EmptyState
            v-if="mode === 'no_event'"
            :icon="CalendarOff"
            :title="t('ui.dashboard.no_event_title')"
            :description="t('ui.dashboard.no_event_description')"
        />

        <EmptyState
            v-else-if="mode === 'manager_idle'"
            :icon="SlidersHorizontal"
            :title="t('ui.dashboard.manager_title')"
            :description="t('ui.dashboard.manager_description')"
        >
            <template #action>
                <ActionButton as-child>
                    <Link :href="showManage()">
                        {{ t('ui.dashboard.manager_action') }}
                    </Link>
                </ActionButton>
            </template>
        </EmptyState>

        <EmptyState
            v-else-if="mode === 'no_registration'"
            :icon="Ticket"
            :title="t('ui.dashboard.no_registration_title')"
            :description="t('ui.dashboard.no_registration_description')"
        >
            <template #action>
                <ActionButton v-if="canReach('event')" as-child>
                    <Link :href="home()">
                        {{ t('ui.dashboard.no_registration_action') }}
                    </Link>
                </ActionButton>
            </template>
        </EmptyState>

        <EmptyState
            v-else-if="mode === 'runner_waiting'"
            :icon="Ticket"
            :title="t('ui.dashboard.runner_waiting_title')"
            :description="t('ui.dashboard.runner_waiting_description')"
        >
            <template #action>
                <ActionButton as-child>
                    <Link :href="showRegistration()">
                        {{ t('ui.dashboard.runner_waiting_action') }}
                    </Link>
                </ActionButton>
            </template>
        </EmptyState>

        <div v-else-if="mode === 'runner_active' && runner" class="grid gap-6">
            <Heading :title="t('ui.dashboard.runner_active_title')" />

            <RunnerSlat
                :bib="runner.bib_label ?? '—'"
                :first-name="runner.first_name"
                :last-name="runner.last_name"
                :status="runner.status"
                :laps="runner.validated_laps"
                :meta="runnerMeta"
            />

            <RunnerDetailPanel :runner="runner" />
        </div>

        <div v-else-if="mode === 'manager_search' && tally" class="grid gap-6">
            <RunnerSearchBoard
                :query="query ?? null"
                :tally="tally"
                :runners="runners ?? []"
                @search="search"
            />

            <NextRoundDuration v-if="nextRound" :round="nextRound" />
        </div>
    </BoardPage>
</template>
