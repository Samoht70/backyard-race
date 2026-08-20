<script setup lang="ts">
type Count = {
    label: string;
    value: number;
};

type Props = {
    counts: Count[];
};

defineProps<Props>();
</script>

<template>
    <dl
        class="flex flex-wrap gap-x-4 gap-y-1 border-b border-border px-4 py-2.5 font-mono"
    >
        <div v-for="count in counts" :key="count.label" class="flex gap-1.5">
            <dt class="text-label text-muted-foreground uppercase">
                {{ count.label }}
            </dt>
            <dd class="text-label tabular-nums">{{ count.value }}</dd>
        </div>
    </dl>
</template>
