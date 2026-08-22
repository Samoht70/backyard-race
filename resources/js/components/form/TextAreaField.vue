<script setup lang="ts">
import { useVModel } from '@vueuse/core';
import { fieldArea } from '@/lib/fieldClasses';
import { cn } from '@/lib/utils';

const props = defineProps<{
    defaultValue?: string | number;
    modelValue?: string | number;
    class?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string | number];
}>();

const value = useVModel(props, 'modelValue', emit, {
    passive: true,
    defaultValue: props.defaultValue,
});
</script>

<template>
    <textarea v-model="value" :class="cn(fieldArea, props.class)" />
</template>
