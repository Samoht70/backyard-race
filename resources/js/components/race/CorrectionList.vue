<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';
import RunnerSlat from '@/components/race/RunnerSlat.vue';
import { t } from '@/lib/i18n';
import type { CorrectableLap } from '@/types/race';

type Props = {
    title: string;
    icon: LucideIcon;
    empty: string;
    laps: CorrectableLap[];
};

const props = defineProps<Props>();

defineSlots<{
    action(props: { lap: CorrectableLap }): unknown;
}>();

function readout(lap: CorrectableLap): string {
    const parts = [t('race.round.short', { number: lap.round_number })];

    parts.push(
        lap.validated_at === null
            ? t('race.correction.round_window', {
                  start: lap.round_starts_at,
                  deadline: lap.round_deadline_at,
              })
            : `${t('race.runner.arrived')} ${lap.validated_at}`,
    );

    if (lap.corrected) {
        parts.push(t('race.correction.marker'));
    }

    return parts.join(' · ');
}
</script>

<template>
    <section class="grid gap-4 rounded-sm border border-border bg-card p-4">
        <h2
            class="flex items-center gap-2 font-mono text-label text-muted-foreground uppercase"
        >
            <component
                :is="props.icon"
                class="size-4 shrink-0"
                aria-hidden="true"
            />
            {{ props.title }}
        </h2>

        <p v-if="!props.laps.length" class="text-sm text-muted-foreground">
            {{ props.empty }}
        </p>

        <div v-else class="grid gap-1.5">
            <RunnerSlat
                v-for="lap in props.laps"
                :key="lap.lap_id"
                :bib="lap.bib_label ?? '—'"
                :first-name="lap.first_name"
                :last-name="lap.last_name"
                :status="lap.status"
                :laps="lap.validated_laps"
                :meta="readout(lap)"
            >
                <template #cell>
                    <slot name="action" :lap="lap" />
                </template>
            </RunnerSlat>
        </div>
    </section>
</template>
