<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { t } from '@/lib/i18n';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Course',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Course" />

    <div class="flex flex-col gap-4 p-4">
        <p class="text-sm text-muted-foreground">{{ t('ui.nav.race') }}</p>
    </div>
</template>
