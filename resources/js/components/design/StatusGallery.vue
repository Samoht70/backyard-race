<script setup lang="ts">
import StatusBadge from '@/components/race/StatusBadge.vue';
import { RUNNER_STATUSES } from '@/types/race';
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <StatusBadge
            v-for="status in RUNNER_STATUSES"
            :key="status"
            :status="status"
        />
    </div>
</template>
