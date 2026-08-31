<script setup lang="ts">
import { Check } from '@lucide/vue';
import ActionButton from '@/components/ActionButton.vue';
import RunnerSlat from '@/components/race/RunnerSlat.vue';
import SlatCell from '@/components/race/SlatCell.vue';
import { t } from '@/lib/i18n';
import { RUNNER_STATUSES } from '@/types/race';
import type { RunnerStatus } from '@/types/race';

type Sample = {
    bib: string;
    firstName: string;
    lastName: string;
    status: RunnerStatus;
    laps: number;
    arrivedAt: string | null;
};

const names: [string, string][] = [
    ['Marie', 'Lambert'],
    ['Thomas', 'Pierre'],
    ['Julie', 'Martin'],
    ['Jean-Baptiste', 'de la Vallée-Poussin-Longuet'],
];

const runners: Sample[] = names.map(([firstName, lastName], position) => ({
    bib: ['007', '020', '033', '046'][position],
    firstName,
    lastName,
    status: RUNNER_STATUSES[position % RUNNER_STATUSES.length],
    laps: 17 - position,
    arrivedAt: position === 0 ? '18:48:32' : null,
}));
</script>

<template>
    <div class="slats">
        <RunnerSlat
            v-for="runner in runners"
            :key="runner.bib"
            :bib="runner.bib"
            :first-name="runner.firstName"
            :last-name="runner.lastName"
            :status="runner.status"
            :laps="runner.laps"
        >
            <template #cell>
                <SlatCell
                    v-if="runner.arrivedAt"
                    flip
                    :value="runner.arrivedAt"
                    :label="t('race.runner.arrived')"
                />
                <ActionButton
                    v-else-if="runner.status === 'running'"
                    class="w-auto max-sm:gap-0"
                    :icon="Check"
                    :aria-label="t('race.runner.validate')"
                >
                    <span class="hidden sm:inline">
                        {{ t('race.runner.validate') }}
                    </span>
                </ActionButton>
                <SlatCell
                    v-else
                    tone="quiet"
                    value="—"
                    :label="t('race.runner.out')"
                />
            </template>
        </RunnerSlat>
    </div>
</template>
