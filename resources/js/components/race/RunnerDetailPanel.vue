<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Check } from '@lucide/vue';
import { computed } from 'vue';
import LapValidationController from '@/actions/App/Http/Controllers/Manage/LapValidationController';
import ActionButton from '@/components/ActionButton.vue';
import RunnerWithdrawalDialog from '@/components/race/RunnerWithdrawalDialog.vue';
import { formatKilometers } from '@/lib/distance';
import { t } from '@/lib/i18n';
import { formatLapDuration, formatSpeed } from '@/lib/lapReadout';
import { can } from '@/lib/permissions';
import type { RunnerLap, RunnerSearchResult } from '@/types/race';

type Props = {
    runner: RunnerSearchResult;
};

const props = defineProps<Props>();

const distanceLabel = computed(() => {
    const kilometers = formatKilometers(props.runner.covered_meters);

    return kilometers === null
        ? '—'
        : `${kilometers} ${t('event.unit.kilometers')}`;
});

const fullName = computed(
    () => `${props.runner.first_name} ${props.runner.last_name}`,
);

const showsValidate = computed(
    () => props.runner.pending_lap_id !== null && can('validate-laps'),
);

const showsWithdrawal = computed(
    () => props.runner.status === 'running' && can('manage-laps'),
);

function readout(lap: RunnerLap): string {
    const parts = [t('race.round.short', { number: lap.round_number })];

    if (lap.duration_seconds === null) {
        parts.push(t('race.detail.pending'));
    } else {
        parts.push(formatLapDuration(lap.duration_seconds));

        const kilometers = formatKilometers(lap.distance_meters);

        if (kilometers !== null) {
            parts.push(`${kilometers} ${t('event.unit.kilometers')}`);
        }

        if (lap.speed_kmh !== null) {
            parts.push(
                `${formatSpeed(lap.speed_kmh)} ${t('race.lap.speed_unit')}`,
            );
        }
    }

    if (lap.corrected) {
        parts.push(t('race.correction.marker'));
    }

    return parts.join(' · ');
}
</script>

<template>
    <div class="grid gap-3">
        <dl
            class="grid grid-cols-2 gap-x-4 gap-y-1 rounded-sm border border-border bg-card px-4 py-3 font-mono"
        >
            <div class="flex flex-col gap-0.5">
                <dt class="text-label text-muted-foreground uppercase">
                    {{ t('race.search.total_distance') }}
                </dt>
                <dd class="text-sm font-bold tabular-nums">
                    {{ distanceLabel }}
                </dd>
            </div>
            <div class="flex flex-col gap-0.5">
                <dt class="text-label text-muted-foreground uppercase">
                    {{ t('race.search.last_round') }}
                </dt>
                <dd class="text-sm font-bold tabular-nums">
                    {{ runner.last_validated_round ?? '—' }}
                </dd>
            </div>
        </dl>

        <p v-if="!runner.laps.length" class="text-sm text-muted-foreground">
            {{ t('race.detail.empty') }}
        </p>

        <ul v-else class="grid gap-1 font-mono text-data">
            <li v-for="lap in runner.laps" :key="lap.round_number">
                {{ readout(lap) }}
            </li>
        </ul>

        <div
            v-if="showsValidate || showsWithdrawal"
            class="flex items-center gap-1.5"
        >
            <Form
                v-if="showsValidate"
                v-bind="LapValidationController.form(runner.pending_lap_id!)"
                :options="{ preserveScroll: true }"
                v-slot="{ processing }"
            >
                <ActionButton
                    type="submit"
                    class="w-auto max-sm:gap-0"
                    :icon="Check"
                    :loading="processing"
                    :aria-label="t('race.runner.validate')"
                >
                    {{ t('race.runner.validate') }}
                </ActionButton>
            </Form>

            <RunnerWithdrawalDialog
                v-if="showsWithdrawal"
                :runner-id="runner.runner_id"
                :runner-name="fullName"
                :validated-laps="runner.validated_laps"
                :covered-meters="runner.covered_meters"
            />
        </div>
    </div>
</template>
