<script setup lang="ts">
import { TriangleAlert } from '@lucide/vue';
import ActionButton from '@/components/ActionButton.vue';
import { t } from '@/lib/i18n';

type Props = {
    title?: string;
    description?: string;
    retryable?: boolean;
};

withDefaults(defineProps<Props>(), {
    retryable: false,
});

defineEmits<{
    retry: [];
}>();
</script>

<template>
    <div
        role="alert"
        class="flex flex-col items-center gap-2 rounded-sm border border-l-[3px] border-border border-l-destructive bg-card px-6 py-8 text-center"
    >
        <TriangleAlert class="size-7 text-destructive" aria-hidden="true" />
        <p class="text-base font-bold tracking-tight">
            {{ title ?? t('ui.state.error_title') }}
        </p>
        <p class="max-w-[32ch] text-sm text-muted-foreground">
            {{ description ?? t('ui.state.error_description') }}
        </p>
        <div v-if="retryable" class="mt-2 w-full max-w-xs">
            <ActionButton @click="$emit('retry')">
                {{ t('ui.state.retry') }}
            </ActionButton>
        </div>
    </div>
</template>
