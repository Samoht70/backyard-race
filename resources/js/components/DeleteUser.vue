<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import {
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
    DialogTrigger,
    Label,
} from 'reka-ui';
import { useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/ProfileController';
import ActionButton from '@/components/ActionButton.vue';
import FieldError from '@/components/form/FieldError.vue';
import PasswordField from '@/components/form/PasswordField.vue';
import Heading from '@/components/Heading.vue';
import Notice from '@/components/Notice.vue';
import { t } from '@/lib/i18n';
import {
    overlayBackdrop,
    overlayDescription,
    overlayFooter,
    overlayPanel,
    overlayTitle,
} from '@/lib/overlayClasses';

const passwordInput = useTemplateRef('passwordInput');
</script>

<template>
    <div class="flex flex-col gap-4">
        <Heading
            variant="small"
            :title="t('ui.profile.delete.title')"
            :description="t('ui.profile.delete.description')"
        />

        <Notice tone="danger" :title="t('ui.profile.delete.warning_title')">
            {{ t('ui.profile.delete.warning') }}
        </Notice>

        <DialogRoot>
            <DialogTrigger as-child>
                <ActionButton tone="danger" data-test="delete-user-button">
                    {{ t('ui.profile.delete.open') }}
                </ActionButton>
            </DialogTrigger>

            <DialogPortal>
                <DialogOverlay :class="overlayBackdrop" />
                <DialogContent :class="overlayPanel">
                    <Form
                        v-bind="ProfileController.destroy.form()"
                        reset-on-success
                        :options="{ preserveScroll: true }"
                        class="flex flex-col gap-4"
                        v-slot="{ errors, processing, reset, clearErrors }"
                        @error="() => passwordInput?.focus()"
                    >
                        <DialogTitle :class="overlayTitle">
                            {{ t('ui.profile.delete.confirm_title') }}
                        </DialogTitle>
                        <DialogDescription :class="overlayDescription">
                            {{ t('ui.profile.delete.confirm_description') }}
                        </DialogDescription>

                        <div class="grid gap-2">
                            <Label for="password" class="sr-only">
                                {{ t('ui.profile.delete.password') }}
                            </Label>
                            <PasswordField
                                id="password"
                                ref="passwordInput"
                                name="password"
                                :placeholder="t('ui.profile.delete.password')"
                            />
                            <FieldError :message="errors.password" />
                        </div>

                        <div :class="overlayFooter">
                            <DialogClose as-child>
                                <ActionButton
                                    tone="quiet"
                                    @click="
                                        () => {
                                            clearErrors();
                                            reset();
                                        }
                                    "
                                >
                                    {{ t('ui.profile.delete.cancel') }}
                                </ActionButton>
                            </DialogClose>
                            <ActionButton
                                type="submit"
                                tone="danger"
                                :loading="processing"
                                data-test="confirm-delete-user-button"
                            >
                                {{ t('ui.profile.delete.submit') }}
                            </ActionButton>
                        </div>
                    </Form>
                </DialogContent>
            </DialogPortal>
        </DialogRoot>
    </div>
</template>
