<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';
import { t } from '@/lib/i18n';

type Props = {
    rows?: number;
};

withDefaults(defineProps<Props>(), {
    rows: 8,
});
</script>

<template>
    <div role="status" aria-live="polite">
        <span class="sr-only">{{ t('ui.state.loading') }}</span>
        <div
            v-for="row in rows"
            :key="row"
            class="flex min-h-11 items-center gap-3 border-b border-border px-3 py-2.5"
        >
            <Skeleton class="h-5 w-8 shrink-0" />
            <Skeleton class="h-4 min-w-0 flex-1" />
            <Skeleton class="h-6 w-20 shrink-0 rounded-md" />
        </div>
    </div>
</template>
