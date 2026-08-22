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
import RegistrationController from '@/actions/App/Http/Controllers/Manage/RegistrationController';
import ActionButton from '@/components/ActionButton.vue';
import FieldError from '@/components/form/FieldError.vue';
import { t } from '@/lib/i18n';
import {
    overlayBackdrop,
    overlayDescription,
    overlayFooter,
    overlayPanel,
    overlayTitle,
} from '@/lib/overlayClasses';

type Props = {
    registrationId: number;
    runnerName: string;
    disabled?: boolean;
    describedBy?: string;
};

const props = withDefaults(defineProps<Props>(), { disabled: false });

const ariaLabel = computed(() =>
    t('registration.delete.aria_open', { name: props.runnerName }),
);
const errorBag = computed(() => `registration-${props.registrationId}-delete`);
</script>

<template>
    <DialogRoot>
        <DialogTrigger as-child>
            <ActionButton
                tone="danger"
                :icon="Trash2"
                :disabled="disabled"
                :aria-label="ariaLabel"
                :aria-describedby="describedBy"
            >
                {{ t('registration.delete.open') }}
            </ActionButton>
        </DialogTrigger>

        <DialogPortal>
            <DialogOverlay :class="overlayBackdrop" />
            <DialogContent :class="overlayPanel">
                <Form
                    v-bind="RegistrationController.destroy.form(registrationId)"
                    :error-bag="errorBag"
                    :options="{ preserveScroll: true }"
                    class="flex flex-col gap-4"
                    v-slot="{ errors, processing }"
                >
                    <DialogTitle :class="overlayTitle">
                        {{
                            t('registration.delete.confirm_title', {
                                name: runnerName,
                            })
                        }}
                    </DialogTitle>
                    <DialogDescription :class="overlayDescription">
                        {{
                            t('registration.delete.confirm_description', {
                                name: runnerName,
                            })
                        }}
                    </DialogDescription>

                    <FieldError :message="errors.registration" />

                    <div :class="overlayFooter">
                        <DialogClose as-child>
                            <ActionButton tone="quiet">
                                {{ t('registration.delete.keep') }}
                            </ActionButton>
                        </DialogClose>
                        <ActionButton
                            type="submit"
                            tone="danger"
                            :loading="processing"
                        >
                            {{ t('registration.delete.submit') }}
                        </ActionButton>
                    </div>
                </Form>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
