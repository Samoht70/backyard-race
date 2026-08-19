<script setup lang="ts">
type Props = {
    title: string;
};

defineProps<Props>();
</script>

<template>
    <section class="flex flex-col gap-4">
        <h2 class="font-display text-label text-muted-foreground uppercase">
            {{ title }}
        </h2>
        <slot />
    </section>
</template>
