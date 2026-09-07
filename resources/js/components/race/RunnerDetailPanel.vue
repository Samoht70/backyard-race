<script setup lang="ts">
import { computed } from 'vue';
import { formatKilometers } from '@/lib/distance';
import { t } from '@/lib/i18n';

type Props = {
    coveredMeters: number | null;
    lastValidatedRound: number | null;
};

const props = defineProps<Props>();

const distanceLabel = computed(() => {
    const kilometers = formatKilometers(props.coveredMeters);

    return kilometers === null
        ? '—'
        : `${kilometers} ${t('event.unit.kilometers')}`;
});
</script>

<template>
    <dl
        class="grid grid-cols-2 gap-x-4 gap-y-1 rounded-sm border border-border bg-card px-4 py-3 font-mono"
    >
        <div class="flex flex-col gap-0.5">
            <dt class="text-label text-muted-foreground uppercase">
                {{ t('race.search.total_distance') }}
            </dt>
            <dd class="text-sm font-bold tabular-nums">{{ distanceLabel }}</dd>
        </div>
        <div class="flex flex-col gap-0.5">
            <dt class="text-label text-muted-foreground uppercase">
                {{ t('race.search.last_round') }}
            </dt>
            <dd class="text-sm font-bold tabular-nums">
                {{ lastValidatedRound ?? '—' }}
            </dd>
        </div>
    </dl>
</template>
