<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Separator } from 'reka-ui';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const name = usePage().props.name;
</script>

<template>
    <div class="min-w-0 items-center gap-3">
        <AppLogoIcon class="size-8 shrink-0" />
        <Separator
            orientation="vertical"
            decorative
            class="h-8 w-px shrink-0 bg-border"
        />
        <span class="truncate text-title">{{ name }}</span>
    </div>
</template>
