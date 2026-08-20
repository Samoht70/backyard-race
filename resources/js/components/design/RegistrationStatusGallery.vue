<script setup lang="ts">
import RegistrationStatusBadge from '@/components/registration/RegistrationStatusBadge.vue';
import { REGISTRATION_STATUSES } from '@/types/registration';
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <RegistrationStatusBadge
            v-for="status in REGISTRATION_STATUSES"
            :key="status"
            :status="status"
        />
    </div>
</template>
