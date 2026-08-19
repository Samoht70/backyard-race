<script setup lang="ts">
import { computed } from 'vue';
import { t } from '@/lib/i18n';
import {
    registrationStatusIcons,
    registrationStatusLabelKey,
    registrationStatusVariants,
} from '@/lib/registrationStatus';
import type { RegistrationStatus } from '@/types/registration';

type Props = {
    status: RegistrationStatus;
    size?: 'sm' | 'md';
};

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
});

const icon = computed(() => registrationStatusIcons[props.status]);
const label = computed(() => t(registrationStatusLabelKey(props.status)));
</script>

<template>
    <span :class="registrationStatusVariants({ status, size })">
        <component :is="icon" class="size-3.5 shrink-0" aria-hidden="true" />
        <span>{{ label }}</span>
    </span>
</template>
