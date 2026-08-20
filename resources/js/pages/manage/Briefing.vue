<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import BriefingController from '@/actions/App/Http/Controllers/Manage/BriefingController';
import BriefingContent from '@/components/briefing/BriefingContent.vue';
import EventField from '@/components/event/EventField.vue';
import EventFieldset from '@/components/event/EventFieldset.vue';
import Heading from '@/components/Heading.vue';
import ActionButton from '@/components/race/ActionButton.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Textarea } from '@/components/ui/textarea';
import { t } from '@/lib/i18n';
import { index as manage } from '@/routes/manage';
import { edit } from '@/routes/manage/briefing';

type Props = {
    markdown: string;
    html: string;
    isEditable: boolean;
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Gestion', href: manage() },
            { title: 'Briefing', href: edit() },
        ],
    },
});
</script>

<template>
    <Head :title="t('event.briefing.manage_title')" />

    <div class="flex flex-col gap-6 p-4 pb-24">
        <Heading
            :title="t('event.briefing.manage_title')"
            :description="t('event.briefing.manage_description')"
        />

        <template v-if="!isEditable">
            <Alert>
                <AlertTitle>{{
                    t('event.briefing.readonly_title')
                }}</AlertTitle>
                <AlertDescription>
                    {{ t('event.briefing.readonly_description') }}
                </AlertDescription>
            </Alert>

            <BriefingContent :html="html" />
        </template>

        <Form
            v-else
            v-bind="BriefingController.update.form()"
            :options="{ preserveScroll: true }"
            class="flex flex-col gap-8"
            v-slot="{ errors, processing }"
        >
            <EventFieldset :title="t('event.briefing.title')">
                <EventField
                    name="briefing"
                    :label="t('event.briefing.field')"
                    :hint="t('event.briefing.hint')"
                    :error="errors.briefing"
                >
                    <Textarea
                        id="briefing"
                        name="briefing"
                        rows="16"
                        required
                        :default-value="markdown"
                    />
                </EventField>
            </EventFieldset>

            <div
                class="sticky bottom-0 -mx-4 border-t border-border bg-background/95 px-4 py-3 backdrop-blur"
            >
                <ActionButton type="submit" :loading="processing">
                    {{ t('event.briefing.save') }}
                </ActionButton>
            </div>
        </Form>
    </div>
</template>
