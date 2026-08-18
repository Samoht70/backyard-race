<script setup lang="ts">
import StatCounter from '@/components/race/StatCounter.vue';
import { t } from '@/lib/i18n';

type Props = {
    lap: number;
    runnersLeft: number;
    startAt: string;
    deadlineAt: string;
};

defineProps<Props>();
</script>

<template>
    <header
        class="flex items-end justify-between gap-4 border-b border-border bg-background/95 px-4 py-3 backdrop-blur supports-[backdrop-filter]:bg-background/80"
    >
        <div class="flex min-w-0 items-end gap-5">
            <StatCounter :value="lap" :label="t('race.lap.number')" size="lg" />
            <StatCounter
                :value="runnersLeft"
                :label="t('race.lap.runners_left')"
            />
        </div>
        <dl
            class="flex shrink-0 flex-col items-end gap-0.5 font-display text-label text-muted-foreground uppercase"
        >
            <div class="flex gap-1.5">
                <dt>{{ t('race.lap.start') }}</dt>
                <dd class="text-foreground tabular-nums">{{ startAt }}</dd>
            </div>
            <div class="flex gap-1.5">
                <dt>{{ t('race.lap.deadline') }}</dt>
                <dd class="text-foreground tabular-nums">{{ deadlineAt }}</dd>
            </div>
        </dl>
        <slot name="trailing" />
    </header>
</template>
