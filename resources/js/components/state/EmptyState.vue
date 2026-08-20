<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';

type Props = {
    title: string;
    description?: string;
    icon?: LucideIcon;
};

defineProps<Props>();
</script>

<template>
    <div class="flex flex-col items-center gap-2 bg-card px-6 py-8 text-center">
        <component
            :is="icon"
            v-if="icon"
            class="size-7 text-muted-foreground"
            aria-hidden="true"
        />
        <p class="text-base font-bold tracking-tight">{{ title }}</p>
        <p
            v-if="description"
            class="max-w-[32ch] text-sm text-muted-foreground"
        >
            {{ description }}
        </p>
        <div class="mt-2 w-full max-w-xs">
            <slot name="action" />
        </div>
    </div>
</template>
