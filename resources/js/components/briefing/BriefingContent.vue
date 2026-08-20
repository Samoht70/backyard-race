<script setup lang="ts">
type Props = {
    html: string;
};

defineProps<Props>();
</script>

<template>
    <article class="briefing" v-html="html" />
</template>
