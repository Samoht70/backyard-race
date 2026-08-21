<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import DocumentController from '@/actions/App/Http/Controllers/Manage/DocumentController';
import ActionButton from '@/components/race/ActionButton.vue';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { t } from '@/lib/i18n';

type Props = {
    documentId: number;
    title: string;
};

const props = defineProps<Props>();

const errorBag = computed(() => `document-${props.documentId}`);
</script>

<template>
    <Dialog>
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

        <DialogContent>
            <Form
                v-bind="DocumentController.destroy.form(documentId)"
                :error-bag="errorBag"
                :options="{ preserveScroll: true }"
                class="flex flex-col gap-4"
                v-slot="{ processing }"
            >
                <DialogHeader class="space-y-3">
                    <DialogTitle>
                        {{ t('document.manage.delete_title') }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ t('document.manage.delete_description') }}
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="gap-2">
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
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
