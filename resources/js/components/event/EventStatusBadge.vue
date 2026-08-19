<script setup lang="ts">
import { computed } from 'vue';
import {
    eventStatusIcons,
    eventStatusLabelKey,
    eventStatusVariants,
} from '@/lib/eventStatus';
import { t } from '@/lib/i18n';
import type { EventStatus } from '@/types/event';

type Props = {
    status: EventStatus;
    size?: 'sm' | 'md';
};

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
});

const icon = computed(() => eventStatusIcons[props.status]);
const label = computed(() => t(eventStatusLabelKey(props.status)));
</script>

<template>
    <span :class="eventStatusVariants({ status, size })">
        <component :is="icon" class="size-3.5 shrink-0" aria-hidden="true" />
        <span>{{ label }}</span>
    </span>
</template>
