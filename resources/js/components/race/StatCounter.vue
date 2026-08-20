<script setup lang="ts">
import { computed } from 'vue';

type Props = {
    value: number | string | null;
    label: string;
    unit?: string;
    size?: 'md' | 'lg';
};

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
});

const displayed = computed(() => props.value ?? '—');
const valueClass = computed(() =>
    props.size === 'lg' ? 'text-readout' : 'text-figure',
);
</script>

<template>
    <div class="flex min-w-0 flex-col gap-1 bg-card px-3 py-2.5">
        <p class="font-mono text-label text-muted-foreground uppercase">
            {{ label }}
        </p>
        <p class="flex items-baseline gap-1 font-mono">
            <span class="tabular-nums" :class="valueClass">{{
                displayed
            }}</span>
            <span
                v-if="unit"
                class="text-label text-muted-foreground uppercase"
            >
                {{ unit }}
            </span>
        </p>
    </div>
</template>
