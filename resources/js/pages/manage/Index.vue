<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { t } from '@/lib/i18n';
import { index as manage } from '@/routes/manage';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Gestion',
                href: manage(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Gestion" />

    <div class="flex flex-col gap-4 p-4">
        <h1 class="font-display text-lg uppercase">
            {{ t('ui.manage.title') }}
        </h1>
        <p class="text-sm text-muted-foreground">{{ t('ui.manage.empty') }}</p>
    </div>
</template>
