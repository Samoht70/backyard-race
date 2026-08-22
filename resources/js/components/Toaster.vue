<script setup lang="ts">
import { CircleAlert, CircleCheck, Info, TriangleAlert, X } from '@lucide/vue';
import { Toaster as Sonner } from 'vue-sonner';
import type { ToasterProps } from 'vue-sonner';
import Spinner from '@/components/Spinner.vue';
import { cn } from '@/lib/utils';

import 'vue-sonner/style.css';

const props = defineProps<ToasterProps>();
</script>

<template>
    <Sonner
        v-bind="props"
        :class="cn('toaster group font-sans', props.class)"
        :style="{
            '--normal-bg': 'var(--popover)',
            '--normal-text': 'var(--popover-foreground)',
            '--normal-border': 'var(--primary)',
            '--success-border': 'var(--status-running)',
            '--error-border': 'var(--destructive)',
            '--border-radius': 'var(--radius)',
        }"
    >
        <template #success-icon>
            <CircleCheck class="size-4 text-status-running" />
        </template>
        <template #info-icon>
            <Info class="size-4 text-primary" />
        </template>
        <template #warning-icon>
            <TriangleAlert class="size-4 text-status-finished" />
        </template>
        <template #error-icon>
            <CircleAlert class="size-4 text-destructive" />
        </template>
        <template #loading-icon>
            <Spinner />
        </template>
        <template #close-icon>
            <X class="size-4" />
        </template>
    </Sonner>
</template>
