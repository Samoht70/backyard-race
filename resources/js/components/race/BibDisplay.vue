<script setup lang="ts">
import { computed } from 'vue';

type Props = {
    value: string | null;
    label: string;
};

const props = defineProps<Props>();

const BIB_FLAPS = 3;

const characters = computed(() =>
    props.value === null
        ? Array.from({ length: BIB_FLAPS }, () => '')
        : props.value.split(''),
);

const description = computed(() =>
    props.value === null ? props.label : `${props.label} ${props.value}`,
);
</script>

<template>
    <div class="grid justify-items-start gap-3">
        <p class="font-mono text-label text-muted-foreground uppercase">
            {{ label }}
        </p>
        <p class="flex gap-1.5" :aria-label="description">
            <span
                v-for="(character, index) in characters"
                :key="`${index}-${character}`"
                aria-hidden="true"
                class="relative grid size-14 animate-flip place-items-center rounded-sm bg-card font-mono text-readout tabular-nums after:absolute after:inset-x-0 after:top-1/2 after:h-px after:bg-background motion-reduce:animate-none lg:size-22 lg:text-marquee"
                :class="value === null && 'text-muted-foreground/40'"
                :style="{ animationDelay: `${index * 60}ms` }"
            >
                {{ character }}
            </span>
        </p>
    </div>
</template>
