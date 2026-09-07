<script setup lang="ts">
import { Search } from '@lucide/vue';
import { useVModel } from '@vueuse/core';
import TextField from '@/components/form/TextField.vue';

const props = defineProps<{
    id: string;
    label: string;
    placeholder: string;
    modelValue?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const term = useVModel(props, 'modelValue', emit, {
    passive: true,
    defaultValue: '',
});
</script>

<template>
    <div class="grid gap-1.5">
        <label :for="props.id" class="sr-only">{{ props.label }}</label>
        <div class="relative">
            <Search
                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                aria-hidden="true"
            />
            <TextField
                :id="props.id"
                v-model="term"
                type="search"
                autocomplete="off"
                :placeholder="props.placeholder"
                class="pl-9"
            />
        </div>
    </div>
</template>
