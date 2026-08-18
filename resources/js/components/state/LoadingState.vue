<script setup lang="ts">
import { Spinner } from '@/components/ui/spinner';
import { t } from '@/lib/i18n';

type Props = {
    label?: string;
    variant?: 'block' | 'inline';
};

withDefaults(defineProps<Props>(), {
    variant: 'block',
});
</script>

<template>
    <div
        role="status"
        aria-live="polite"
        class="flex items-center justify-center gap-2 text-muted-foreground"
        :class="variant === 'block' ? 'py-10' : 'py-2'"
    >
        <Spinner class="size-5 shrink-0" />
        <span class="text-sm">{{ label ?? t('ui.state.loading') }}</span>
    </div>
</template>
