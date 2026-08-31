<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import { Check, Flag } from '@lucide/vue';
import { computed } from 'vue';
import LapValidationController from '@/actions/App/Http/Controllers/Manage/LapValidationController';
import ActionButton from '@/components/ActionButton.vue';
import Notice from '@/components/Notice.vue';
import RunnerSlat from '@/components/race/RunnerSlat.vue';
import RunnerWithdrawalDialog from '@/components/race/RunnerWithdrawalDialog.vue';
import SlatCell from '@/components/race/SlatCell.vue';
import { formatKilometers } from '@/lib/distance';
import { t } from '@/lib/i18n';
import { formatLapDuration, formatSpeed } from '@/lib/lapReadout';
import type { RoundRunner } from '@/types/race';

type Props = {
    runners: RoundRunner[];
};

const props = defineProps<Props>();

const page = usePage();

const refusal = computed(() => page.props.errors.lap);

function readout(runner: RoundRunner): string | undefined {
    const parts: string[] = [];

    if (runner.duration_seconds !== null) {
        parts.push(formatLapDuration(runner.duration_seconds));

        const kilometers = formatKilometers(runner.distance_meters);

        if (kilometers !== null) {
            parts.push(`${kilometers} ${t('event.unit.kilometers')}`);
        }

        if (runner.speed_kmh !== null) {
            parts.push(
                `${formatSpeed(runner.speed_kmh)} ${t('race.lap.speed_unit')}`,
            );
        }
    }

    if (runner.corrected) {
        parts.push(t('race.correction.marker'));
    }

    return parts.length === 0 ? undefined : parts.join(' · ');
}
</script>

<template>
    <section class="grid gap-4 rounded-sm border border-border bg-card p-4">
        <h2
            class="flex items-center gap-2 font-mono text-label text-muted-foreground uppercase"
        >
            <Flag class="size-4 shrink-0" aria-hidden="true" />
            {{ t('race.board.title') }}
        </h2>

        <Notice v-if="refusal" :title="t('race.board.refused')" tone="danger">
            {{ refusal }}
        </Notice>

        <p v-if="!props.runners.length" class="text-sm text-muted-foreground">
            {{ t('race.board.empty') }}
        </p>

        <div v-else class="grid gap-1.5">
            <RunnerSlat
                v-for="runner in props.runners"
                :key="runner.lap_id"
                :bib="runner.bib_label ?? '—'"
                :first-name="runner.first_name"
                :last-name="runner.last_name"
                :status="runner.status"
                :laps="runner.validated_laps"
                :meta="readout(runner)"
            >
                <template #cell>
                    <div class="flex items-center gap-1.5">
                        <RunnerWithdrawalDialog
                            v-if="
                                runner.status === 'running' &&
                                runner.lap_status === 'pending'
                            "
                            :runner-id="runner.runner_id"
                            :runner-name="`${runner.first_name} ${runner.last_name}`"
                            :validated-laps="runner.validated_laps"
                            :covered-meters="runner.covered_meters"
                        />
                        <SlatCell
                            v-if="runner.validated_at"
                            flip
                            :value="runner.validated_at"
                            :label="t('race.runner.arrived')"
                        />
                        <Form
                            v-else-if="runner.lap_status === 'pending'"
                            v-bind="LapValidationController.form(runner.lap_id)"
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
                                <span class="hidden sm:inline">
                                    {{ t('race.runner.validate') }}
                                </span>
                            </ActionButton>
                        </Form>
                        <SlatCell
                            v-else
                            tone="quiet"
                            value="—"
                            :label="t('race.runner.out')"
                        />
                    </div>
                </template>
            </RunnerSlat>
        </div>
    </section>
</template>
