<script setup lang="ts">
import { t } from '@/lib/i18n';

type Props = {
    rows?: number;
};

withDefaults(defineProps<Props>(), {
    rows: 8,
});
</script>

<template>
    <div role="status" aria-live="polite" class="slats">
        <span class="sr-only">{{ t('ui.state.loading') }}</span>
        <div
            v-for="row in rows"
            :key="row"
            class="min-h-[4.25rem] rounded-sm border border-border bg-card"
            aria-hidden="true"
        />
    </div>
</template>
