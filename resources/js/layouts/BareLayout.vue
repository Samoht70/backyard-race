<script setup lang="ts">
import AppearanceToggle from '@/components/AppearanceToggle.vue';
</script>

<template>
    <AppearanceToggle
        class="fixed top-[max(0.5rem,env(safe-area-inset-top))] right-2 z-50"
    />
    <slot />
</template>
