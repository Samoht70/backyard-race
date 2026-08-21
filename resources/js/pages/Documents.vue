<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { FolderOpen } from '@lucide/vue';
import DocumentRow from '@/components/document/DocumentRow.vue';
import Heading from '@/components/Heading.vue';
import EmptyState from '@/components/state/EmptyState.vue';
import { t } from '@/lib/i18n';
import { index } from '@/routes/documents';
import type { EventDocument } from '@/types/document';

type Props = {
    documents: EventDocument[];
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Documents',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="t('document.title')" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            :title="t('document.title')"
            :description="t('document.description')"
        />

        <div v-if="documents.length" class="slats">
            <DocumentRow
                v-for="document in documents"
                :key="document.id"
                :document="document"
            />
        </div>

        <EmptyState
            v-else
            :icon="FolderOpen"
            :title="t('document.empty_title')"
            :description="t('document.empty_description')"
        />
    </div>
</template>
