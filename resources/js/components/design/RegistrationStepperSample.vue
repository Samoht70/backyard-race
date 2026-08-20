<script setup lang="ts">
import { ref } from 'vue';
import RegistrationStepper from '@/components/registration/RegistrationStepper.vue';

const current = ref(1);
</script>

<template>
    <RegistrationStepper
        :current="current"
        @go="(step: number) => (current = step)"
    />
</template>
