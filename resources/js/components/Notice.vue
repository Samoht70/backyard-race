<script setup lang="ts">
import { CircleAlert, Info } from '@lucide/vue';
import { computed } from 'vue';

type Props = {
    title: string;
    tone?: 'info' | 'danger';
};

const props = withDefaults(defineProps<Props>(), {
    tone: 'info',
});

const isDanger = computed(() => props.tone === 'danger');
</script>

<template>
    <div
        role="alert"
        class="grid w-full gap-1.5 rounded-sm border border-l-2 border-border bg-card px-4 py-3"
        :class="isDanger ? 'border-l-destructive' : 'border-l-primary'"
    >
        <p
            class="flex items-center gap-2 font-mono text-label uppercase"
            :class="isDanger ? 'text-destructive' : 'text-primary'"
        >
            <component
                :is="isDanger ? CircleAlert : Info"
                class="size-3.5 shrink-0"
                aria-hidden="true"
            />
            {{ title }}
        </p>
        <div class="text-sm text-muted-foreground">
            <slot />
        </div>
    </div>
</template>
