<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { FolderOpen } from '@lucide/vue';
import DocumentController from '@/actions/App/Http/Controllers/Manage/DocumentController';
import BoardPage from '@/components/board/BoardPage.vue';
import DocumentDeleteForm from '@/components/document/DocumentDeleteForm.vue';
import DocumentRow from '@/components/document/DocumentRow.vue';
import EventField from '@/components/event/EventField.vue';
import EventFieldset from '@/components/event/EventFieldset.vue';
import Heading from '@/components/Heading.vue';
import ActionButton from '@/components/race/ActionButton.vue';
import EmptyState from '@/components/state/EmptyState.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { t } from '@/lib/i18n';
import type { EventDocument } from '@/types/document';

type Props = {
    documents: EventDocument[];
    isEditable: boolean;
    maxFileMegabytes: number;
};

defineProps<Props>();
</script>

<template>
    <Head :title="t('document.manage.title')" />

    <BoardPage>
        <div class="grid max-w-4xl gap-6">
            <Heading
                :title="t('document.manage.title')"
                :description="t('document.manage.description')"
            />

            <Alert v-if="!isEditable">
                <AlertTitle>{{
                    t('document.manage.readonly_title')
                }}</AlertTitle>
                <AlertDescription>
                    {{ t('document.manage.readonly_description') }}
                </AlertDescription>
            </Alert>

            <div v-if="documents.length" class="slats">
                <DocumentRow
                    v-for="document in documents"
                    :key="document.id"
                    :document="document"
                >
                    <template #cell>
                        <DocumentDeleteForm
                            v-if="isEditable"
                            :document-id="document.id"
                            :title="document.title"
                        />
                    </template>
                </DocumentRow>
            </div>

            <EmptyState
                v-else
                :icon="FolderOpen"
                :title="t('document.manage.empty_title')"
                :description="t('document.manage.empty_description')"
            />

            <Form
                v-if="isEditable"
                v-bind="DocumentController.store.form()"
                :options="{ preserveScroll: true }"
                reset-on-success
                class="flex flex-col gap-8"
                v-slot="{ errors, processing }"
            >
                <EventFieldset :title="t('document.manage.add_title')">
                    <EventField
                        name="title"
                        :label="t('document.manage.field.title')"
                        :hint="t('document.manage.hint.title')"
                        :error="errors.title"
                    >
                        <Input id="title" name="title" required />
                    </EventField>

                    <EventField
                        name="description"
                        :label="t('document.manage.field.description')"
                        :hint="t('document.manage.hint.description')"
                        :error="errors.description"
                    >
                        <Textarea
                            id="description"
                            name="description"
                            rows="3"
                        />
                    </EventField>

                    <EventField
                        name="file"
                        :label="t('document.manage.field.file')"
                        :hint="
                            t('document.manage.hint.file', {
                                max: maxFileMegabytes,
                            })
                        "
                        :error="errors.file"
                    >
                        <input
                            id="file"
                            name="file"
                            type="file"
                            required
                            class="h-11 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base file:mr-3 file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none md:text-sm"
                        />
                    </EventField>
                </EventFieldset>

                <div
                    class="sticky bottom-0 -mx-4 border-t border-border bg-background/95 px-4 py-3 backdrop-blur"
                >
                    <ActionButton type="submit" :loading="processing">
                        {{ t('document.manage.submit') }}
                    </ActionButton>
                </div>
            </Form>
        </div>
    </BoardPage>
</template>
