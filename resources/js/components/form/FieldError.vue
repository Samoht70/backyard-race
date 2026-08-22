<script setup lang="ts">
defineProps<{
    message?: string;
}>();
</script>

<template>
    <p
        v-if="message"
        class="flex items-start gap-1.5 border-l-2 border-destructive pl-2 text-sm text-destructive"
    >
        {{ message }}
    </p>
</template>
