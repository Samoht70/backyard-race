<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';
import { cva } from 'class-variance-authority';
import { computed } from 'vue';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

type Props = {
    size?: 'touch' | 'validate';
    tone?: 'primary' | 'danger' | 'quiet';
    loading?: boolean;
    disabled?: boolean;
    icon?: LucideIcon;
    class?: string;
};

const props = withDefaults(defineProps<Props>(), {
    size: 'touch',
    tone: 'primary',
    loading: false,
    disabled: false,
});

const actionButtonVariants = cva(
    'inline-flex w-full touch-manipulation items-center justify-center gap-2 rounded-lg font-display font-black tracking-wide uppercase transition-[background-color,transform] outline-none select-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background active:scale-[0.98] disabled:pointer-events-none aria-disabled:opacity-50',
    {
        variants: {
            size: {
                touch: 'min-h-11 px-4 text-sm',
                validate: 'min-h-18 px-6 text-xl',
            },
            tone: {
                primary: 'bg-primary text-primary-foreground',
                danger: 'bg-destructive text-destructive-foreground',
                quiet: 'border border-border bg-card text-foreground',
            },
        },
    },
);

const classes = computed(() =>
    cn(
        actionButtonVariants({ size: props.size, tone: props.tone }),
        props.class,
    ),
);
</script>

<template>
    <button
        type="button"
        :class="classes"
        :disabled="disabled || loading"
        :aria-disabled="disabled || undefined"
        :aria-busy="loading"
    >
        <Spinner v-if="loading" class="size-5 shrink-0" />
        <component
            v-else-if="icon"
            :is="icon"
            class="size-5 shrink-0"
            aria-hidden="true"
        />
        <span><slot /></span>
    </button>
</template>
