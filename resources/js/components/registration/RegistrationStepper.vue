<script setup lang="ts">
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
        isCurrent: index === props.current,
        isReached: index < props.current,
    })),
);

const position = computed(() =>
    t('auth.register.complete.position', {
        current: props.current + 1,
        total: REGISTRATION_STEPS.length,
    }),
);
</script>

<template>
    <div class="flex flex-col gap-2">
        <ol
            :aria-label="t('auth.register.complete.nav')"
            class="grid grid-cols-4 gap-1.5"
        >
            <li v-for="marker in markers" :key="marker.index">
                <button
                    type="button"
                    :disabled="!marker.isReached"
                    :aria-current="marker.isCurrent ? 'step' : undefined"
                    :aria-label="
                        marker.isReached
                            ? t('auth.register.complete.go_to', {
                                  position: marker.position,
                              })
                            : undefined
                    "
                    class="flex min-h-11 w-full touch-manipulation flex-col items-center justify-center gap-0.5 border px-1 font-mono text-label uppercase"
                    :class="
                        marker.isCurrent
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border bg-card text-muted-foreground'
                    "
                    @click="emit('go', marker.index)"
                >
                    <span class="tabular-nums">{{ marker.position }}</span>
                    <span class="w-full truncate text-center">
                        {{ marker.label }}
                    </span>
                </button>
            </li>
        </ol>

        <p class="font-mono text-label text-muted-foreground uppercase">
            {{ position }}
        </p>
    </div>
</template>
