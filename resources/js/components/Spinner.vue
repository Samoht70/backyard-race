<script setup lang="ts">
import { Loader2 } from '@lucide/vue';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: string;
}>();
</script>

<template>
    <Loader2
        role="status"
        :aria-label="t('ui.state.loading')"
        :class="cn('size-4 animate-spin', props.class)"
    />
</template>
