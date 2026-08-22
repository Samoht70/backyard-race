<script setup lang="ts">
import { Lock } from '@lucide/vue';
import { Label } from 'reka-ui';
import { computed } from 'vue';
import FieldError from '@/components/form/FieldError.vue';

/**
 * A frozen field is rendered as a fact, not as a disabled input: `disabled`
 * drops the value from the submission, `readonly` is poorly honoured by the
 * segmented date and time controls, and neither says why the field is locked.
 *
 * It still renders its error: a stale tab submitting a frozen field is the one
 * case where that error can fire, and it would otherwise have nowhere to go.
 */
type Props = {
    name: string;
    label: string;
    hint?: string;
    unit?: string;
    error?: string;
    locked?: boolean;
    lockedReason?: string;
    value?: string;
    span?: 2 | 3 | 4 | 6;
};

const props = withDefaults(defineProps<Props>(), {
    locked: false,
    span: 6,
});

const spans = {
    2: '@min-[52rem]:col-span-2',
    3: '@min-[52rem]:col-span-3',
    4: '@min-[52rem]:col-span-4',
    6: '@min-[52rem]:col-span-6',
};

const spanClass = computed(() => spans[props.span]);
</script>

<template>
    <div
        v-if="locked"
        class="grid gap-1 border border-l-2 border-border border-l-muted-foreground bg-muted/40 px-3 py-2"
        :class="spanClass"
    >
        <p class="font-mono text-label text-muted-foreground uppercase">
            {{ label }}
        </p>
        <p class="flex items-center gap-2">
            <Lock
                class="size-4 shrink-0 text-muted-foreground"
                aria-hidden="true"
            />
            <span class="tabular-nums">{{ value ?? '—' }}</span>
            <span v-if="unit" class="text-sm text-muted-foreground">
                {{ unit }}
            </span>
        </p>
        <p class="text-sm text-muted-foreground">{{ lockedReason }}</p>
        <FieldError :message="error" />
    </div>

    <div v-else class="grid content-start gap-2" :class="spanClass">
        <Label
            :for="name"
            class="font-mono text-label text-muted-foreground uppercase"
        >
            {{ label }}
            <span v-if="unit" class="normal-case">({{ unit }})</span>
        </Label>
        <slot />
        <p v-if="hint" class="text-sm text-muted-foreground">{{ hint }}</p>
        <FieldError :message="error" />
    </div>
</template>
