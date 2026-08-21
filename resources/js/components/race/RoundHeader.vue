<script setup lang="ts">
import { computed } from 'vue';
import { t } from '@/lib/i18n';

type Props = {
    round: number;
    startAt: string;
    deadlineAt: string;
    eventName?: string;
};

const props = defineProps<Props>();

const roundLabel = computed(() =>
    t('race.round.short', { number: String(props.round).padStart(2, '0') }),
);
</script>

<template>
    <header
        class="sticky top-0 z-10 flex flex-col gap-2 border-b-[3px] border-foreground bg-background px-4 pt-4 pb-3"
    >
        <p
            v-if="eventName"
            class="truncate font-mono text-label text-muted-foreground uppercase"
        >
            {{ eventName }}
        </p>
        <p class="flex items-baseline gap-2.5 font-mono">
            <span class="text-label text-muted-foreground">{{
                roundLabel
            }}</span>
            <span class="text-readout">{{ startAt }}</span>
            <span
                class="text-sm font-normal tracking-tight text-muted-foreground"
            >
                → {{ deadlineAt }}
            </span>
        </p>
        <slot name="trailing" />
    </header>
</template>
