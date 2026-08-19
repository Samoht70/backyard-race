<script setup lang="ts">
import { computed } from 'vue';
import { t } from '@/lib/i18n';

type Props = {
    confirmed: number;
    capacity: number | null;
};

const props = defineProps<Props>();

const wording = computed(() =>
    props.capacity === null
        ? t('registration.seats.unlimited', { count: props.confirmed })
        : t('registration.seats.counted', {
              count: props.confirmed,
              max: props.capacity,
          }),
);
</script>

<template>
    <p class="text-center text-sm text-muted-foreground tabular-nums">
        {{ wording }}
    </p>
</template>
