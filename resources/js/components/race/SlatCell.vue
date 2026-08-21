<script setup lang="ts">
type Props = {
    value: number | string;
    label: string;
    tone?: 'strong' | 'quiet';
    flip?: boolean;
};

withDefaults(defineProps<Props>(), {
    tone: 'strong',
    flip: false,
});
</script>

<template>
    <p
        class="min-w-[5.625rem] shrink-0 text-right font-mono"
        :class="flip && 'animate-flip motion-reduce:animate-none'"
    >
        <span
            class="block text-sm font-bold tracking-tighter tabular-nums"
            :class="tone === 'quiet' && 'text-muted-foreground'"
        >
            {{ value }}
        </span>
        <span class="block text-label text-muted-foreground uppercase">
            {{ label }}
        </span>
    </p>
</template>
