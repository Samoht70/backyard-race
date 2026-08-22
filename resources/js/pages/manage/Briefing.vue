<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import BriefingController from '@/actions/App/Http/Controllers/Manage/BriefingController';
import ActionButton from '@/components/ActionButton.vue';
import BoardPage from '@/components/board/BoardPage.vue';
import BriefingContent from '@/components/briefing/BriefingContent.vue';
import FormField from '@/components/form/FormField.vue';
import FormFieldset from '@/components/form/FormFieldset.vue';
import TextAreaField from '@/components/form/TextAreaField.vue';
import Heading from '@/components/Heading.vue';
import Notice from '@/components/Notice.vue';
import { t } from '@/lib/i18n';

type Props = {
    markdown: string;
    html: string;
    isEditable: boolean;
};

defineProps<Props>();
</script>

<template>
    <Head :title="t('event.briefing.manage_title')" />

    <BoardPage>
        <div class="grid max-w-4xl gap-6">
            <Heading
                :title="t('event.briefing.manage_title')"
                :description="t('event.briefing.manage_description')"
            />

            <template v-if="!isEditable">
                <Notice :title="t('event.briefing.readonly_title')">
                    {{ t('event.briefing.readonly_description') }}
                </Notice>

                <BriefingContent :html="html" />
            </template>

            <Form
                v-else
                v-bind="BriefingController.update.form()"
                :options="{ preserveScroll: true }"
                class="flex flex-col gap-8"
                v-slot="{ errors, processing }"
            >
                <FormFieldset :title="t('event.briefing.title')">
                    <FormField
                        name="briefing"
                        :label="t('event.briefing.field')"
                        :hint="t('event.briefing.hint')"
                        :error="errors.briefing"
                    >
                        <TextAreaField
                            id="briefing"
                            name="briefing"
                            rows="16"
                            required
                            :default-value="markdown"
                        />
                    </FormField>
                </FormFieldset>

                <div
                    class="sticky bottom-0 -mx-4 border-t border-border bg-background/95 px-4 py-3 backdrop-blur"
                >
                    <ActionButton type="submit" :loading="processing">
                        {{ t('event.briefing.save') }}
                    </ActionButton>
                </div>
            </Form>
        </div>
    </BoardPage>
</template>
