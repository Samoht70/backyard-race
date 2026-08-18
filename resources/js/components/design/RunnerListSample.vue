<script setup lang="ts">
import RunnerCard from '@/components/race/RunnerCard.vue';
import { RUNNER_STATUSES } from '@/types/race';
import type { RunnerStatus } from '@/types/race';

type Sample = {
    bib: number;
    firstName: string;
    lastName: string;
    status: RunnerStatus;
    laps: number;
};

const names: [string, string][] = [
    ['Marie', 'Lambert'],
    ['Thomas', 'Pierre'],
    ['Julie', 'Martin'],
    ['Jean-Baptiste', 'de la Vallée-Poussin-Longuet'],
];

const runners: Sample[] = names.map(([firstName, lastName], position) => ({
    bib: 101 + position * 13,
    firstName,
    lastName,
    status: RUNNER_STATUSES[position % RUNNER_STATUSES.length],
    laps: 17 - position,
}));
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-border bg-card">
        <RunnerCard
            v-for="runner in runners"
            :key="runner.bib"
            :bib="runner.bib"
            :first-name="runner.firstName"
            :last-name="runner.lastName"
            :status="runner.status"
            :laps="runner.laps"
        />
    </div>
</template>
