<script setup lang="ts">
import { Lock } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';

/**
 * A frozen field is rendered as a fact, not as a disabled input: `disabled`
 * drops the value from the submission, `readonly` is poorly honoured by the
 * native date and time pickers, and neither says why the field is locked.
 *
 * It still renders its error: a stale tab submitting a frozen field is the one
 * case where that error can fire, and it would otherwise have nowhere to go.
 *
 * The 44px touch floor lives here too — shadcn inputs are born at h-9.
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
    2: 'sm:col-span-2',
    3: 'sm:col-span-3',
    4: 'sm:col-span-4',
    6: 'sm:col-span-6',
};

const spanClass = computed(() => spans[props.span]);
</script>

<template>
    <div
        v-if="locked"
        class="grid gap-1 border border-border bg-muted/40 px-3 py-2"
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
        <InputError :message="error" />
    </div>

    <div
        v-else
        class="grid content-start gap-2 [&_input]:h-11 sm:[&_input]:h-10 [&_textarea]:min-h-24"
        :class="spanClass"
    >
        <Label :for="name">
            {{ label }}
            <span v-if="unit" class="text-muted-foreground">({{ unit }})</span>
        </Label>
        <slot />
        <p v-if="hint" class="text-sm text-muted-foreground">{{ hint }}</p>
        <InputError :message="error" />
    </div>
</template>
