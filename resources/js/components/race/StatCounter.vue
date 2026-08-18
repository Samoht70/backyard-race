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
    props.size === 'lg' ? 'text-lap' : 'text-metric',
);
</script>

<template>
    <div class="flex min-w-0 flex-col gap-0.5">
        <p class="flex items-baseline gap-1">
            <span class="font-display tabular-nums" :class="valueClass">{{
                displayed
            }}</span>
            <span
                v-if="unit"
                class="font-display text-label text-muted-foreground uppercase"
                >{{ unit }}</span
            >
        </p>
        <p class="font-display text-label text-muted-foreground uppercase">
            {{ label }}
        </p>
    </div>
</template>
