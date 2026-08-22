<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { FolderOpen } from '@lucide/vue';
import DocumentController from '@/actions/App/Http/Controllers/Manage/DocumentController';
import ActionButton from '@/components/ActionButton.vue';
import BoardPage from '@/components/board/BoardPage.vue';
import DocumentDeleteForm from '@/components/document/DocumentDeleteForm.vue';
import DocumentRow from '@/components/document/DocumentRow.vue';
import FileField from '@/components/form/FileField.vue';
import FormField from '@/components/form/FormField.vue';
import FormFieldset from '@/components/form/FormFieldset.vue';
import TextAreaField from '@/components/form/TextAreaField.vue';
import TextField from '@/components/form/TextField.vue';
import Heading from '@/components/Heading.vue';
import Notice from '@/components/Notice.vue';
import EmptyState from '@/components/state/EmptyState.vue';
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

            <Notice
                v-if="!isEditable"
                :title="t('document.manage.readonly_title')"
            >
                {{ t('document.manage.readonly_description') }}
            </Notice>

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
                <FormFieldset :title="t('document.manage.add_title')">
                    <FormField
                        name="title"
                        :label="t('document.manage.field.title')"
                        :hint="t('document.manage.hint.title')"
                        :error="errors.title"
                    >
                        <TextField id="title" name="title" required />
                    </FormField>

                    <FormField
                        name="description"
                        :label="t('document.manage.field.description')"
                        :hint="t('document.manage.hint.description')"
                        :error="errors.description"
                    >
                        <TextAreaField
                            id="description"
                            name="description"
                            rows="3"
                        />
                    </FormField>

                    <FormField
                        name="file"
                        :label="t('document.manage.field.file')"
                        :hint="
                            t('document.manage.hint.file', {
                                max: maxFileMegabytes,
                            })
                        "
                        :error="errors.file"
                    >
                        <FileField id="file" name="file" required />
                    </FormField>
                </FormFieldset>

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
