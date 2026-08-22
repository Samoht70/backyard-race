<script setup lang="ts">
type Props = {
    title: string;
};

defineProps<Props>();
</script>

<template>
    <section class="@container flex flex-col gap-4">
        <h2
            class="flex items-center gap-2 font-mono text-label text-muted-foreground uppercase"
        >
            <span class="h-3 w-0.5 shrink-0 bg-primary" aria-hidden="true" />
            {{ title }}
        </h2>
        <div class="grid gap-4 @min-[52rem]:grid-cols-6">
            <slot />
        </div>
    </section>
</template>
