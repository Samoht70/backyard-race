<script setup lang="ts">
import { computed } from 'vue';
import Notice from '@/components/Notice.vue';
import { t } from '@/lib/i18n';

type Props = {
    errors: string[];
    title?: string;
};

const props = defineProps<Props>();

const uniqueErrors = computed(() => Array.from(new Set(props.errors)));
</script>

<template>
    <Notice tone="danger" :title="title ?? t('ui.state.validation_title')">
        <ul class="grid gap-0.5">
            <li
                v-for="(error, index) in uniqueErrors"
                :key="index"
                class="border-l border-destructive/40 pl-2"
            >
                {{ error }}
            </li>
        </ul>
    </Notice>
</template>
