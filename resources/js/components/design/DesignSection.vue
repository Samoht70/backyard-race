<script setup lang="ts">
type Props = {
    title: string;
    note?: string;
};

defineProps<Props>();
</script>

<template>
    <section class="flex flex-col gap-3">
        <div class="flex flex-col gap-0.5">
            <h2 class="font-mono text-label text-muted-foreground uppercase">
                {{ title }}
            </h2>
            <p v-if="note" class="text-sm text-muted-foreground">{{ note }}</p>
        </div>
        <slot />
    </section>
</template>
