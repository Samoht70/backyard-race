<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import BoardPage from '@/components/board/BoardPage.vue';
import BriefingContent from '@/components/briefing/BriefingContent.vue';
import { t } from '@/lib/i18n';

type Props = {
    html: string;
};

defineProps<Props>();
</script>

<template>
    <Head :title="t('event.briefing.title')" />

    <BoardPage>
        <BriefingContent :html="html" class="max-w-[68ch]" />
    </BoardPage>
</template>
