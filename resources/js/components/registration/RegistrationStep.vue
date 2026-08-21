<script setup lang="ts">
type Props = {
    index: number;
    current: number;
};

defineProps<Props>();
</script>

<template>
    <div
        v-show="index === current"
        :data-step="index"
        tabindex="-1"
        class="flex flex-col gap-6 outline-none"
    >
        <slot />
    </div>
</template>
