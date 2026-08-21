<script setup lang="ts">
type Props = {
    label: string;
    mono?: boolean;
};

withDefaults(defineProps<Props>(), { mono: false });
</script>

<template>
    <div
        class="grid gap-0.5 border-b border-border-soft py-2.5 first:border-t first:border-border lg:flex lg:items-baseline lg:justify-between lg:gap-4 lg:py-2"
    >
        <dt
            class="font-mono text-label text-muted-foreground uppercase lg:shrink-0"
        >
            {{ label }}
        </dt>
        <dd
            class="min-w-0 text-sm break-words lg:text-right"
            :class="mono && 'font-mono tracking-tight tabular-nums'"
        >
            <slot />
        </dd>
    </div>
</template>
