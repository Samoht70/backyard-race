<script setup lang="ts">
import { Check } from '@lucide/vue';
import { CheckboxIndicator, CheckboxRoot, Label } from 'reka-ui';
import type { CheckboxRootProps } from 'reka-ui';

const props = defineProps<
    CheckboxRootProps & {
        label: string;
    }
>();
</script>

<template>
    <Label
        :for="props.id"
        class="flex min-h-11 cursor-pointer items-center gap-3 select-none lg:min-h-10"
    >
        <CheckboxRoot
            v-bind="props"
            class="grid size-5 shrink-0 place-content-center rounded-sm border border-input bg-card text-primary-foreground transition-colors outline-none focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-ring/35 data-[state=checked]:border-primary data-[state=checked]:bg-primary"
        >
            <CheckboxIndicator>
                <Check class="size-3.5" aria-hidden="true" />
            </CheckboxIndicator>
        </CheckboxRoot>
        <span class="text-sm">{{ label }}</span>
    </Label>
</template>
