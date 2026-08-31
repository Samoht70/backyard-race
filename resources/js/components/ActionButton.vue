<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';
import { Primitive } from 'reka-ui';
import type { Component } from 'vue';
import { computed, useSlots } from 'vue';
import Spinner from '@/components/Spinner.vue';
import { actionButtonVariants } from '@/lib/actionButton';
import { cn } from '@/lib/utils';

type Props = {
    size?: 'touch' | 'icon';
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
