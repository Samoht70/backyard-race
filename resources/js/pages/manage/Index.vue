<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { SlidersHorizontal } from '@lucide/vue';
import { t } from '@/lib/i18n';
import { index as manage } from '@/routes/manage';
import { edit as editEvent } from '@/routes/manage/event';

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

        <nav class="flex flex-col gap-2">
            <Link
                :href="editEvent()"
                class="flex min-h-11 items-center gap-3 rounded-lg border border-border bg-card px-3 py-2"
            >
                <SlidersHorizontal
                    class="size-5 shrink-0 text-muted-foreground"
                    aria-hidden="true"
                />
                <span class="text-sm font-medium">{{
                    t('ui.manage.event')
                }}</span>
            </Link>
        </nav>
    </div>
</template>
