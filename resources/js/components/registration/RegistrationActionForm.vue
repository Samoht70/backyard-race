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
} from 'reka-ui';
import { computed } from 'vue';
import RegistrationTransitionController from '@/actions/App/Http/Controllers/Manage/RegistrationTransitionController';
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
import {
    registrationTransitionAriaKey,
    registrationTransitionLabelKey,
    registrationTransitions,
} from '@/lib/registrationStatus';
import type { RegistrationTransition } from '@/types/registration';

type Props = {
    registrationId: number;
    runnerName: string;
    transition: RegistrationTransition;
    disabled?: boolean;
    describedBy?: string;
    class?: string;
};

const props = defineProps<Props>();

const presentation = computed(() => registrationTransitions[props.transition]);
const label = computed(() =>
    t(registrationTransitionLabelKey(props.transition)),
);
const ariaLabel = computed(() =>
    t(registrationTransitionAriaKey(props.transition), {
        name: props.runnerName,
    }),
);
const errorBag = computed(() => `registration-${props.registrationId}`);
</script>

<template>
    <DialogRoot v-if="presentation.needsConfirmation">
        <DialogTrigger as-child>
            <ActionButton
                :tone="presentation.tone"
                :icon="presentation.icon"
                :disabled="disabled"
                :aria-label="ariaLabel"
                :aria-describedby="describedBy"
                :class="props.class"
            >
                {{ label }}
            </ActionButton>
        </DialogTrigger>

        <DialogPortal>
            <DialogOverlay :class="overlayBackdrop" />
            <DialogContent :class="overlayPanel">
                <Form
                    v-bind="
                        RegistrationTransitionController.form(registrationId)
                    "
                    :error-bag="errorBag"
                    :options="{ preserveScroll: true }"
                    class="flex flex-col gap-4"
                    v-slot="{ errors, processing }"
                >
                    <input
                        type="hidden"
                        name="transition"
                        :value="transition"
                    />

                    <DialogTitle :class="overlayTitle">
                        {{
                            t('registration.transition.confirm_cancel_title', {
                                name: runnerName,
                            })
                        }}
                    </DialogTitle>
                    <DialogDescription :class="overlayDescription">
                        {{
                            t(
                                'registration.transition.confirm_cancel_description',
                            )
                        }}
                    </DialogDescription>

                    <FieldError :message="errors.transition" />

                    <div :class="overlayFooter">
                        <DialogClose as-child>
                            <ActionButton tone="quiet">
                                {{ t('registration.transition.keep') }}
                            </ActionButton>
                        </DialogClose>
                        <ActionButton
                            type="submit"
                            tone="danger"
                            :loading="processing"
                        >
                            {{
                                t(
                                    'registration.transition.confirm_cancel_submit',
                                )
                            }}
                        </ActionButton>
                    </div>
                </Form>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>

    <Form
        v-else
        v-bind="RegistrationTransitionController.form(registrationId)"
        :error-bag="errorBag"
        :options="{ preserveScroll: true }"
        class="flex w-full flex-col items-stretch gap-1 sm:w-auto"
        v-slot="{ errors, processing }"
    >
        <input type="hidden" name="transition" :value="transition" />

        <ActionButton
            type="submit"
            :tone="presentation.tone"
            :icon="presentation.icon"
            :loading="processing"
            :disabled="disabled"
            :aria-label="ariaLabel"
            :aria-describedby="describedBy"
            :class="props.class"
        >
            {{ label }}
        </ActionButton>

        <FieldError :message="errors.transition" />
    </Form>
</template>
