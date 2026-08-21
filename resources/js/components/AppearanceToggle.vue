<script setup lang="ts">
import { Moon, Sun } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/composables/useAppearance';
import { t } from '@/lib/i18n';

const { resolvedAppearance, toggleAppearance } = useAppearance();

const isDark = computed(() => resolvedAppearance.value === 'dark');

const label = computed(() =>
    t(isDark.value ? 'ui.appearance.to_light' : 'ui.appearance.to_dark'),
);
</script>

<template>
    <Button
        type="button"
        variant="ghost"
        size="icon"
        class="size-11 text-muted-foreground"
        :aria-label="label"
        :title="label"
        data-test="appearance-toggle"
        @click="toggleAppearance"
    >
        <component
            :is="isDark ? Sun : Moon"
            class="size-5"
            aria-hidden="true"
        />
    </Button>
</template>
