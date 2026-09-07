<script setup lang="ts">
import { Search, Users } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import TextField from '@/components/form/TextField.vue';
import RoundTally from '@/components/race/RoundTally.vue';
import RunnerDetailPanel from '@/components/race/RunnerDetailPanel.vue';
import RunnerSlat from '@/components/race/RunnerSlat.vue';
import EmptyState from '@/components/state/EmptyState.vue';
import { t } from '@/lib/i18n';
import { meetsSearchThreshold } from '@/lib/runnerSearch';
import { runnerStatusLabelKey } from '@/lib/runnerStatus';
import type { RunnerSearchResult, RunnerTally } from '@/types/race';

type Props = {
    query: string | null;
    tally: RunnerTally;
    runners: RunnerSearchResult[];
};

const props = defineProps<Props>();
const emit = defineEmits<{ search: [term: string] }>();

const term = ref(props.query ?? '');
const expandedId = ref<number | null>(null);

let debounceHandle: ReturnType<typeof setTimeout> | undefined;

const counts = computed(() => [
    { label: t('race.round.runners_left'), value: props.tally.running },
    { label: t('race.round.runners_out'), value: props.tally.out },
]);

const isSearchable = computed(() => meetsSearchThreshold(term.value));

const state = computed(() => {
    if (!isSearchable.value) {
        return 'invitation';
    }

    return props.runners.length ? 'results' : 'empty';
});

watch(term, (value) => {
    clearTimeout(debounceHandle);

    if (!meetsSearchThreshold(value)) {
        return;
    }

    debounceHandle = setTimeout(() => emit('search', value), 300);
});

function toggle(runnerId: number): void {
    expandedId.value = expandedId.value === runnerId ? null : runnerId;
}

function fullName(runner: RunnerSearchResult): string {
    return `${runner.first_name} ${runner.last_name}`;
}

function meta(runner: RunnerSearchResult): string | undefined {
    if (runner.exited_at === null) {
        return undefined;
    }

    return `${t(runnerStatusLabelKey(runner.status))} · ${runner.exited_at}`;
}
</script>

<template>
    <div class="grid gap-6">
        <RoundTally :counts="counts" />

        <div class="grid gap-1.5">
            <label for="runner-search" class="sr-only">
                {{ t('race.search.label') }}
            </label>
            <div class="relative">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    aria-hidden="true"
                />
                <TextField
                    id="runner-search"
                    v-model="term"
                    type="search"
                    autocomplete="off"
                    :placeholder="t('race.search.placeholder')"
                    class="pl-9"
                />
            </div>
        </div>

        <EmptyState
            v-if="state === 'invitation'"
            :icon="Search"
            :title="t('race.search.invitation')"
        />

        <EmptyState
            v-else-if="state === 'empty'"
            :icon="Users"
            :title="t('race.search.empty')"
        />

        <div v-else class="grid gap-1.5">
            <template v-for="runner in props.runners" :key="runner.runner_id">
                <button
                    type="button"
                    class="w-full text-left"
                    :aria-expanded="expandedId === runner.runner_id"
                    :aria-label="
                        t(
                            expandedId === runner.runner_id
                                ? 'race.search.collapse'
                                : 'race.search.expand',
                            { name: fullName(runner) },
                        )
                    "
                    @click="toggle(runner.runner_id)"
                >
                    <RunnerSlat
                        :bib="runner.bib_label ?? '—'"
                        :first-name="runner.first_name"
                        :last-name="runner.last_name"
                        :status="runner.status"
                        :laps="runner.validated_laps"
                        :meta="meta(runner)"
                    />
                </button>

                <RunnerDetailPanel
                    v-if="expandedId === runner.runner_id"
                    :covered-meters="runner.covered_meters"
                    :last-validated-round="runner.last_validated_round"
                />
            </template>
        </div>
    </div>
</template>
