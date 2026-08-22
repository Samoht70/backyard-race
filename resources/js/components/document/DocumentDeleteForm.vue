<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import {
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
    DialogTrigger,
} from 'reka-ui';
import { computed } from 'vue';
import DocumentController from '@/actions/App/Http/Controllers/Manage/DocumentController';
import ActionButton from '@/components/ActionButton.vue';
import { t } from '@/lib/i18n';
import {
    overlayBackdrop,
    overlayDescription,
    overlayFooter,
    overlayPanel,
    overlayTitle,
} from '@/lib/overlayClasses';

type Props = {
    documentId: number;
    title: string;
};

const props = defineProps<Props>();

const errorBag = computed(() => `document-${props.documentId}`);
</script>

<template>
    <DialogRoot>
        <DialogTrigger as-child>
            <ActionButton
                tone="danger"
                :icon="Trash2"
                :aria-label="t('document.manage.delete_aria', { title })"
                class="w-auto px-3"
            >
                {{ t('document.manage.delete') }}
            </ActionButton>
        </DialogTrigger>

        <DialogPortal>
            <DialogOverlay :class="overlayBackdrop" />
            <DialogContent :class="overlayPanel">
                <Form
                    v-bind="DocumentController.destroy.form(documentId)"
                    :error-bag="errorBag"
                    :options="{ preserveScroll: true }"
                    class="flex flex-col gap-4"
                    v-slot="{ processing }"
                >
                    <DialogTitle :class="overlayTitle">
                        {{ t('document.manage.delete_title') }}
                    </DialogTitle>
                    <DialogDescription :class="overlayDescription">
                        {{ t('document.manage.delete_description') }}
                    </DialogDescription>

                    <div :class="overlayFooter">
                        <DialogClose as-child>
                            <ActionButton tone="quiet">
                                {{ t('document.manage.cancel') }}
                            </ActionButton>
                        </DialogClose>
                        <ActionButton
                            type="submit"
                            tone="danger"
                            :loading="processing"
                        >
                            {{ t('document.manage.delete_confirm') }}
                        </ActionButton>
                    </div>
                </Form>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
