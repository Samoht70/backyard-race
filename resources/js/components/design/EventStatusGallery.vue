<script setup lang="ts">
import EventStatusBadge from '@/components/event/EventStatusBadge.vue';
import { EVENT_STATUSES } from '@/types/event';
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <EventStatusBadge
            v-for="status in EVENT_STATUSES"
            :key="status"
            :status="status"
        />
    </div>
</template>
