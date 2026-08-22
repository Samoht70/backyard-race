<script setup lang="ts">
import { Moon, Sun } from '@lucide/vue';
import { computed } from 'vue';
import ActionButton from '@/components/ActionButton.vue';
import { useAppearance } from '@/composables/useAppearance';
import { t } from '@/lib/i18n';

const { resolvedAppearance, toggleAppearance } = useAppearance();

const isDark = computed(() => resolvedAppearance.value === 'dark');

const label = computed(() =>
    t(isDark.value ? 'ui.appearance.to_light' : 'ui.appearance.to_dark'),
);
</script>

<template>
    <ActionButton
        type="button"
        tone="ghost"
        size="icon"
        :icon="isDark ? Sun : Moon"
        :aria-label="label"
        :title="label"
        data-test="appearance-toggle"
        @click="toggleAppearance"
    />
</template>
