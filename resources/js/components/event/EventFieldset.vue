<script setup lang="ts">
type Props = {
    title: string;
};

defineProps<Props>();
</script>

<template>
    <section class="flex flex-col gap-4">
        <h2 class="font-mono text-label text-muted-foreground uppercase">
            {{ title }}
        </h2>
        <div class="grid gap-4 sm:grid-cols-6">
            <slot />
        </div>
    </section>
</template>
