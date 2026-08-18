<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { computed } from 'vue';
import StatusBadge from '@/components/race/StatusBadge.vue';
import { Card } from '@/components/ui/card';
import { t } from '@/lib/i18n';
import type { RunnerStatus } from '@/types/race';

type Props = {
    bib: number | string;
    firstName: string;
    lastName: string;
    status: RunnerStatus;
    laps: number;
    meta?: string;
    href?: NonNullable<InertiaLinkProps['href']>;
    variant?: 'row' | 'card';
};

const props = withDefaults(defineProps<Props>(), {
    variant: 'row',
});

const fullName = computed(() => `${props.firstName} ${props.lastName}`);
const rootComponent = computed(() => {
    if (props.variant === 'card') {
        return Card;
    }

    return props.href ? Link : 'div';
});
</script>

<template>
    <component
        :is="rootComponent"
        :href="href"
        class="flex min-w-0 items-center gap-3"
        :class="
            variant === 'card'
                ? 'p-4'
                : 'min-h-11 border-b border-border px-3 py-2.5'
        "
    >
        <span
            class="font-display text-lg font-black text-muted-foreground tabular-nums"
            >{{ bib }}</span
        >
        <span class="flex min-w-0 flex-1 flex-col">
            <span class="truncate font-medium">{{ fullName }}</span>
            <span v-if="meta" class="truncate text-xs text-muted-foreground">{{
                meta
            }}</span>
        </span>
        <span class="flex shrink-0 items-center gap-2">
            <span class="font-display text-sm tabular-nums">
                {{ laps }}
                <span class="text-label text-muted-foreground uppercase">{{
                    t('race.runner.laps_completed')
                }}</span>
            </span>
            <StatusBadge :status="status" size="sm" />
        </span>
        <slot name="actions" />
    </component>
</template>
