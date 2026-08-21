<script setup lang="ts">
type Props = {
    label: string;
    mono?: boolean;
};

withDefaults(defineProps<Props>(), { mono: false });
</script>

<template>
    <div
        class="flex items-baseline justify-between gap-4 border-b border-border-soft py-2.5 first:border-t first:border-border lg:py-2"
    >
        <dt
            class="shrink-0 font-mono text-label text-muted-foreground uppercase"
        >
            {{ label }}
        </dt>
        <dd
            class="text-right text-sm"
            :class="mono && 'font-mono tracking-tight tabular-nums'"
        >
            <slot />
        </dd>
    </div>
</template>
