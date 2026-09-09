<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { FolderOpen } from '@lucide/vue';
import BoardPage from '@/components/board/BoardPage.vue';
import BoardSection from '@/components/board/BoardSection.vue';
import BriefingContent from '@/components/briefing/BriefingContent.vue';
import DocumentRow from '@/components/document/DocumentRow.vue';
import EmptyState from '@/components/state/EmptyState.vue';
import { t } from '@/lib/i18n';
import type { EventDocument } from '@/types/document';

type Props = {
    html: string;
    documents: EventDocument[];
};

defineProps<Props>();
</script>

<template>
    <Head :title="t('event.briefing.title')" />

    <BoardPage>
        <div class="grid items-start gap-8 lg:grid-cols-12 lg:gap-12">
            <BriefingContent
                :html="html"
                class="max-w-[68ch] min-w-0 lg:col-span-7"
            />

            <BoardSection
                :title="t('document.title')"
                class="min-w-0 lg:col-span-5"
            >
                <div
                    v-if="documents.length"
                    class="grid gap-1.5 sm:grid-cols-2 lg:grid-cols-1"
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
            </BoardSection>
        </div>
    </BoardPage>
</template>
