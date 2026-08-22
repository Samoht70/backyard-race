<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { BoardFilterOption } from '@/types/ui';

type Props = {
    label: string;
    options: BoardFilterOption[];
    activeValue: string | null;
};

defineProps<Props>();
</script>

<template>
    <nav :aria-label="label" class="grid grid-cols-2 gap-1.5 sm:grid-cols-4">
        <Link
            v-for="option in options"
            :key="option.value ?? 'all'"
            :href="option.href"
            preserve-scroll
            :aria-current="option.value === activeValue ? 'page' : undefined"
            class="flex min-h-11 touch-manipulation items-center justify-between gap-2 rounded-sm border px-3 font-mono text-label uppercase"
            :class="
                option.value === activeValue
                    ? 'border-primary bg-primary text-primary-foreground'
                    : 'border-border bg-card text-muted-foreground hover:border-primary hover:text-primary'
            "
        >
            <span class="truncate">{{ option.label }}</span>
            <span class="tabular-nums">{{ option.count }}</span>
        </Link>
    </nav>
</template>
