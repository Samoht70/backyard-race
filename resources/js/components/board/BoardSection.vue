<script setup lang="ts">
type Props = {
    title?: string;
    level?: 'h2' | 'h3';
};

withDefaults(defineProps<Props>(), { level: 'h2' });
</script>

<template>
    <section class="grid content-start gap-2">
        <component
            :is="level"
            v-if="title"
            class="font-mono text-label text-muted-foreground uppercase"
        >
            {{ title }}
        </component>
        <slot />
    </section>
</template>
