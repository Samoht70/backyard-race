<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { computed } from 'vue';
import SlatCell from '@/components/race/SlatCell.vue';
import { t } from '@/lib/i18n';
import {
    runnerStatusBar,
    runnerStatusIcons,
    runnerStatusLabelKey,
    runnerStatusTone,
} from '@/lib/runnerStatus';
import type { RunnerStatus } from '@/types/race';

type Props = {
    bib: number | string;
    firstName: string;
    lastName: string;
    status: RunnerStatus;
    laps: number;
    meta?: string;
    href?: NonNullable<InertiaLinkProps['href']>;
};

const props = defineProps<Props>();

const fullName = computed(() => `${props.firstName} ${props.lastName}`);
const icon = computed(() => runnerStatusIcons[props.status]);
const tone = computed(() => runnerStatusTone[props.status]);
const bar = computed(() => runnerStatusBar[props.status]);
const statusLabel = computed(() => t(runnerStatusLabelKey(props.status)));
const isRunning = computed(() => props.status === 'running');
</script>

<template>
    <div
        class="flex min-h-[4.25rem] min-w-0 items-center gap-2 overflow-hidden rounded-sm border border-border bg-card py-2.5 pr-3"
    >
        <span
            class="w-1 shrink-0 self-stretch"
            :class="bar"
            aria-hidden="true"
        />
        <span class="w-9 shrink-0 font-mono text-data font-bold tabular-nums">{{
            bib
        }}</span>
        <span class="flex min-w-0 flex-1 flex-col gap-px">
            <component
                :is="href ? Link : 'span'"
                :href="href"
                class="-my-1.5 truncate py-1.5 font-semibold"
                :class="
                    href && 'underline decoration-border underline-offset-4'
                "
            >
                {{ fullName }}
            </component>
            <span
                class="flex min-w-0 items-center gap-1.5 font-mono text-data text-muted-foreground"
            >
                <component
                    :is="icon"
                    class="size-3 shrink-0"
                    :class="tone"
                    aria-hidden="true"
                />
                <span class="truncate">{{ meta ?? statusLabel }}</span>
            </span>
        </span>
        <slot name="cell">
            <SlatCell
                :value="laps"
                :label="t('race.runner.laps_completed')"
                :tone="isRunning ? 'strong' : 'quiet'"
            />
        </slot>
    </div>
</template>
