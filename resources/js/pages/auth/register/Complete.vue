<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { ref } from 'vue';
import ActionButton from '@/components/ActionButton.vue';
import FieldError from '@/components/form/FieldError.vue';
import FormField from '@/components/form/FormField.vue';
import FormFieldset from '@/components/form/FormFieldset.vue';
import TextField from '@/components/form/TextField.vue';
import RegistrationFields from '@/components/registration/RegistrationFields.vue';
import RegistrationStep from '@/components/registration/RegistrationStep.vue';
import RegistrationStepper from '@/components/registration/RegistrationStepper.vue';
import SeatCounter from '@/components/registration/SeatCounter.vue';
import { useFormSteps } from '@/composables/useFormSteps';
import { t } from '@/lib/i18n';
import {
    firstFieldInError,
    firstStepInError,
    REGISTRATION_STEP_COUNT,
} from '@/lib/registrationSteps';
import { update } from '@/routes/account';

defineProps<{
    email: string;
    seats: { confirmed: number; capacity: number | null } | null;
}>();

setLayoutProps({
    title: t('auth.register.complete.title'),
    description: t('auth.register.complete.description'),
});

const steps = ref<HTMLElement | null>(null);

const { current, isFirst, isLast, goTo, next, back } = useFormSteps(
    REGISTRATION_STEP_COUNT,
    steps,
);

function returnToError(errors: Record<string, string>): void {
    const step = firstStepInError(errors);

    if (step === null) {
        return;
    }

    void goTo(step, firstFieldInError(errors, step));
}
</script>

<template>
    <Head :title="t('auth.register.complete.title')" />

    <div class="flex flex-col gap-6">
        <SeatCounter
            v-if="seats"
            :confirmed="seats.confirmed"
            :capacity="seats.capacity"
        />

        <Form
            v-bind="update.form()"
            :options="{ preserveScroll: true }"
            novalidate
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
            @error="returnToError"
        >
            <FieldError :message="errors.event" />

            <RegistrationStepper :current="current" @go="goTo" />

            <div ref="steps" class="flex flex-col gap-6">
                <RegistrationStep :index="0" :current="current">
                    <FormFieldset :title="t('auth.register.complete.identity')">
                        <FormField
                            name="email"
                            :label="t('auth.register.complete.email')"
                        >
                            <TextField
                                id="email"
                                type="email"
                                :model-value="email"
                                disabled
                            />
                        </FormField>

                        <FormField
                            name="first_name"
                            :label="t('auth.register.complete.first_name')"
                            :error="errors.first_name"
                        >
                            <TextField
                                id="first_name"
                                name="first_name"
                                required
                                autofocus
                                autocomplete="given-name"
                                maxlength="120"
                            />
                        </FormField>

                        <FormField
                            name="last_name"
                            :label="t('auth.register.complete.last_name')"
                            :error="errors.last_name"
                        >
                            <TextField
                                id="last_name"
                                name="last_name"
                                required
                                autocomplete="family-name"
                                maxlength="120"
                            />
                        </FormField>
                    </FormFieldset>
                </RegistrationStep>

                <RegistrationStep :index="1" :current="current">
                    <RegistrationFields section="runner" :errors="errors" />
                </RegistrationStep>

                <RegistrationStep :index="2" :current="current">
                    <RegistrationFields section="emergency" :errors="errors" />
                </RegistrationStep>

                <RegistrationStep :index="3" :current="current">
                    <RegistrationFields section="notes" :errors="errors" />
                </RegistrationStep>
            </div>

            <div
                class="grid gap-2"
                :class="isFirst ? 'grid-cols-1' : 'grid-cols-2'"
            >
                <ActionButton v-if="!isFirst" tone="quiet" @click="back">
                    {{ t('auth.register.complete.back') }}
                </ActionButton>

                <ActionButton v-if="!isLast" @click="next">
                    {{ t('auth.register.complete.next') }}
                </ActionButton>

                <ActionButton
                    v-else
                    type="submit"
                    :loading="processing"
                    data-test="register-complete-button"
                >
                    {{ t('auth.register.complete.submit') }}
                </ActionButton>
            </div>
        </Form>
    </div>
</template>
