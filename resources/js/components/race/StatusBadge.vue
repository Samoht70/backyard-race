<script setup lang="ts">
import { computed } from 'vue';
import { t } from '@/lib/i18n';
import {
    runnerStatusIcons,
    runnerStatusLabelKey,
    runnerStatusVariants,
} from '@/lib/runnerStatus';
import type { RunnerStatus } from '@/types/race';

type Props = {
    status: RunnerStatus;
    size?: 'sm' | 'md';
};

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
});

const icon = computed(() => runnerStatusIcons[props.status]);
const label = computed(() => t(runnerStatusLabelKey(props.status)));
</script>

<template>
    <span :class="runnerStatusVariants({ status, size })">
        <component :is="icon" class="size-3.5 shrink-0" aria-hidden="true" />
        <span>{{ label }}</span>
    </span>
</template>
