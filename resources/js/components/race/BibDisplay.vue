<script setup lang="ts">
import { computed } from 'vue';

type Props = {
    value: string | null;
    label: string;
};

const props = defineProps<Props>();

const characters = computed(() => (props.value ?? '—').split(''));
</script>

<template>
    <div class="grid justify-items-start gap-3">
        <p class="font-mono text-label text-muted-foreground uppercase">
            {{ label }}
        </p>
        <p class="flex gap-1.5" :aria-label="`${label} ${value ?? ''}`">
            <span
                v-for="(character, index) in characters"
                :key="`${index}-${character}`"
                aria-hidden="true"
                class="relative grid size-14 animate-flip place-items-center bg-card font-mono text-readout tabular-nums after:absolute after:inset-x-0 after:top-1/2 after:h-px after:bg-background motion-reduce:animate-none lg:size-22 lg:text-marquee"
                :style="{ animationDelay: `${index * 60}ms` }"
            >
                {{ character }}
            </span>
        </p>
    </div>
</template>
