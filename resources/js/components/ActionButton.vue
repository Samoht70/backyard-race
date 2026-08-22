<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';
import { cva } from 'class-variance-authority';
import { Primitive } from 'reka-ui';
import type { Component } from 'vue';
import { computed, useSlots } from 'vue';
import Spinner from '@/components/Spinner.vue';
import { cn } from '@/lib/utils';

type Props = {
    size?: 'touch' | 'validate' | 'icon';
    type?: 'button' | 'submit';
    tone?: 'primary' | 'danger' | 'quiet' | 'ghost';
    loading?: boolean;
    disabled?: boolean;
    icon?: LucideIcon;
    as?: string | Component;
    asChild?: boolean;
    block?: boolean;
    class?: string;
};

const props = withDefaults(defineProps<Props>(), {
    size: 'touch',
    type: 'button',
    tone: 'primary',
    loading: false,
    disabled: false,
    as: 'button',
    asChild: false,
    block: false,
});

const slots = useSlots();

const actionButtonVariants = cva(
    'inline-flex shrink-0 touch-manipulation items-center justify-center gap-2 rounded-sm border font-bold tracking-wider uppercase transition-colors outline-none select-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none aria-disabled:opacity-50',
    {
        variants: {
            block: {
                true: 'w-full',
                false: 'w-full sm:w-auto',
            },
            size: {
                touch: 'min-h-11 px-4 text-xs lg:min-h-10',
                validate: 'min-h-[3.125rem] min-w-24 px-5 text-sm',
                icon: 'size-11 shrink-0 lg:size-10',
            },
            tone: {
                primary:
                    'border-primary bg-primary text-primary-foreground hover:bg-primary/90',
                danger: 'border-destructive bg-destructive text-destructive-foreground hover:bg-destructive/90',
                quiet: 'border-foreground bg-transparent text-foreground hover:bg-accent hover:text-accent-foreground',
                ghost: 'border-transparent bg-transparent text-muted-foreground hover:bg-accent hover:text-accent-foreground',
            },
        },
    },
);

const isButton = computed(() => !props.asChild && props.as === 'button');

const classes = computed(() =>
    cn(
        actionButtonVariants({
            size: props.size,
            tone: props.tone,
            block: props.size === 'icon' ? undefined : props.block,
        }),
        props.class,
    ),
);
</script>

<template>
    <Primitive
        :as="as"
        :as-child="asChild"
        :type="isButton ? type : undefined"
        :class="classes"
        :disabled="isButton && (disabled || loading) ? true : undefined"
        :aria-disabled="disabled || undefined"
        :aria-busy="loading"
    >
        <Spinner v-if="loading" class="size-4 shrink-0" />
        <component
            v-else-if="icon"
            :is="icon"
            :class="size === 'icon' ? 'size-5 shrink-0' : 'size-4 shrink-0'"
            aria-hidden="true"
        />
        <span v-if="slots.default"><slot /></span>
    </Primitive>
</template>
