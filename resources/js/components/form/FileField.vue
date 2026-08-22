<script setup lang="ts">
import { fieldControl } from '@/lib/fieldClasses';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: string;
}>();
</script>

<template>
    <input
        type="file"
        :class="
            cn(
                fieldControl,
                'py-2 file:mr-3 file:h-7 file:border file:border-primary file:bg-primary file:px-2 file:font-mono file:text-label file:tracking-widest file:text-primary-foreground file:uppercase',
                props.class,
            )
        "
    />
</template>
