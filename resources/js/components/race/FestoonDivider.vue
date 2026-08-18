<script setup lang="ts">
const dots = [40, 65, 90, 65, 40, 65, 90, 65, 40, 65, 90, 65];
</script>

<template>
    <div aria-hidden="true" class="flex items-center justify-center gap-2 py-3">
        <span
            v-for="(opacity, position) in dots"
            :key="position"
            class="size-1.5 rounded-full bg-primary"
            :style="{ opacity: opacity / 100 }"
        />
    </div>
</template>
