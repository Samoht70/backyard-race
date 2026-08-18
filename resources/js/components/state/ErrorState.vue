<script setup lang="ts">
import { TriangleAlert } from '@lucide/vue';
import ActionButton from '@/components/race/ActionButton.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
    <Alert variant="destructive" role="alert">
        <TriangleAlert class="size-4" />
        <AlertTitle>{{ title ?? t('ui.state.error_title') }}</AlertTitle>
        <AlertDescription>
            <p>{{ description ?? t('ui.state.error_description') }}</p>
            <ActionButton
                v-if="retryable"
                tone="quiet"
                class="mt-3 w-auto"
                @click="$emit('retry')"
            >
                {{ t('ui.state.retry') }}
            </ActionButton>
        </AlertDescription>
    </Alert>
</template>
