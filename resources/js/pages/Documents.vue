<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { FolderOpen } from '@lucide/vue';
import BoardPage from '@/components/board/BoardPage.vue';
import DocumentRow from '@/components/document/DocumentRow.vue';
import Heading from '@/components/Heading.vue';
import EmptyState from '@/components/state/EmptyState.vue';
import { t } from '@/lib/i18n';
import type { EventDocument } from '@/types/document';

type Props = {
    documents: EventDocument[];
};

defineProps<Props>();
</script>

<template>
    <Head :title="t('document.title')" />

    <BoardPage>
        <div class="grid gap-6">
            <Heading
                :title="t('document.title')"
                :description="t('document.description')"
            />

            <div
                v-if="documents.length"
                class="grid gap-1.5 sm:grid-cols-2 xl:grid-cols-3"
            >
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
    </BoardPage>
</template>
