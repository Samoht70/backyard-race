<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import BriefingContent from '@/components/briefing/BriefingContent.vue';
import { t } from '@/lib/i18n';
import { show } from '@/routes/briefing';

type Props = {
    html: string;
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Briefing',
                href: show(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="t('event.briefing.title')" />

    <div class="flex flex-col gap-6 p-4">
        <BriefingContent :html="html" />
    </div>
</template>
