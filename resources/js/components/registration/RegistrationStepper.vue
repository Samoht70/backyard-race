<script setup lang="ts">
import {
    StepperItem,
    StepperRoot,
    StepperTitle,
    StepperTrigger,
} from 'reka-ui';
import { computed } from 'vue';
import { t } from '@/lib/i18n';
import { REGISTRATION_STEPS } from '@/lib/registrationSteps';

type Props = {
    current: number;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    go: [step: number];
}>();

const markers = computed(() =>
    REGISTRATION_STEPS.map((step, index) => ({
        index,
        position: index + 1,
        label: t(step.label),
        isReached: index < props.current,
    })),
);

const position = computed(() =>
    t('auth.register.complete.position', {
        current: props.current + 1,
        total: REGISTRATION_STEPS.length,
    }),
);

function goToStep(step: number | undefined): void {
    if (step === undefined) {
        return;
    }

    emit('go', step - 1);
}
</script>

<template>
    <div class="flex flex-col gap-2">
        <StepperRoot
            :model-value="current + 1"
            :aria-label="t('auth.register.complete.nav')"
            class="grid grid-cols-4 gap-1.5"
            @update:model-value="goToStep"
        >
            <StepperItem
                v-for="marker in markers"
                :key="marker.index"
                :step="marker.position"
                :completed="marker.isReached"
                :disabled="!marker.isReached"
            >
                <StepperTrigger
                    :aria-label="
                        marker.isReached
                            ? t('auth.register.complete.go_to', {
                                  position: marker.position,
                              })
                            : undefined
                    "
                    class="flex min-h-11 w-full touch-manipulation flex-col items-center justify-center gap-0.5 rounded-sm border border-border bg-card px-1 font-mono text-label text-muted-foreground uppercase transition-colors outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-inset data-[state=active]:border-primary data-[state=active]:bg-primary data-[state=active]:text-primary-foreground data-[state=completed]:border-l-2 data-[state=completed]:border-l-primary data-[state=completed]:text-foreground"
                >
                    <span class="tabular-nums">{{ marker.position }}</span>
                    <StepperTitle class="w-full truncate text-center">
                        {{ marker.label }}
                    </StepperTitle>
                </StepperTrigger>
            </StepperItem>
        </StepperRoot>

        <p class="font-mono text-label text-muted-foreground uppercase">
            {{ position }}
        </p>
    </div>
</template>
