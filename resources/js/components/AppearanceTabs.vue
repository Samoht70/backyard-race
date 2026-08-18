<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import { useAppearance } from '@/composables/useAppearance';
import { t } from '@/lib/i18n';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, labelKey: 'ui.appearance.light' },
    { value: 'dark', Icon: Moon, labelKey: 'ui.appearance.dark' },
    { value: 'system', Icon: Monitor, labelKey: 'ui.appearance.system' },
] as const;
</script>

<template>
    <div class="inline-flex gap-1 rounded-lg bg-muted p-1">
        <button
            v-for="{ value, Icon, labelKey } in tabs"
            :key="value"
            type="button"
            :aria-pressed="appearance === value"
            :class="[
                'flex min-h-11 items-center rounded-md px-3.5 transition-colors',
                appearance === value
                    ? 'bg-background text-foreground shadow-xs'
                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
            ]"
            @click="updateAppearance(value)"
        >
            <component :is="Icon" class="-ml-1 size-4" aria-hidden="true" />
            <span class="ml-1.5 text-sm">{{ t(labelKey) }}</span>
        </button>
    </div>
</template>
