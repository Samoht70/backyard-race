<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed } from 'vue';
import RegistrationController from '@/actions/App/Http/Controllers/Manage/RegistrationController';
import ActionButton from '@/components/ActionButton.vue';
import ActionBar from '@/components/board/ActionBar.vue';
import BoardColumns from '@/components/board/BoardColumns.vue';
import BoardPage from '@/components/board/BoardPage.vue';
import BoardSection from '@/components/board/BoardSection.vue';
import FormField from '@/components/form/FormField.vue';
import FormFieldset from '@/components/form/FormFieldset.vue';
import TextField from '@/components/form/TextField.vue';
import Heading from '@/components/Heading.vue';
import BibDisplay from '@/components/race/BibDisplay.vue';
import RegistrationActionForm from '@/components/registration/RegistrationActionForm.vue';
import RegistrationFields from '@/components/registration/RegistrationFields.vue';
import RegistrationStatusBadge from '@/components/registration/RegistrationStatusBadge.vue';
import { t } from '@/lib/i18n';
import { index } from '@/routes/manage/registrations';
import type { ManagedRegistration } from '@/types/registration';

type Props = {
    registration: ManagedRegistration;
};

const props = defineProps<Props>();

const fullName = computed(
    () => `${props.registration.first_name} ${props.registration.last_name}`,
);
</script>

<template>
    <Head :title="t('registration.manage.edit_title')" />

    <BoardPage>
        <div class="mb-6 flex">
            <ActionButton tone="quiet" :icon="ArrowLeft" as-child>
                <Link :href="index()">
                    {{ t('registration.manage.back') }}
                </Link>
            </ActionButton>
        </div>

        <BoardColumns>
            <template #lead>
                <BibDisplay
                    :value="registration.bib_label"
                    :label="t('registration.manage.bib')"
                />

                <div class="grid justify-items-start gap-2">
                    <h1 class="text-title">{{ fullName }}</h1>
                    <RegistrationStatusBadge :status="registration.status" />
                    <p
                        v-if="registration.bib_label === null"
                        class="text-sm text-muted-foreground"
                    >
                        {{ t('registration.manage.no_bib') }}
                    </p>
                </div>

                <BoardSection
                    v-if="registration.allowed_transitions.length"
                    :title="t('registration.manage.actions_title')"
                >
                    <div class="flex flex-wrap gap-2">
                        <RegistrationActionForm
                            v-for="transition in registration.allowed_transitions"
                            :key="transition"
                            :registration-id="registration.id"
                            :runner-name="fullName"
                            :transition="transition"
                        />
                    </div>
                </BoardSection>
            </template>

            <Heading
                :title="t('registration.manage.edit_title')"
                :description="t('registration.manage.edit_description')"
            />

            <Form
                v-bind="RegistrationController.update.form(registration.id)"
                :options="{ preserveScroll: true }"
                v-slot="{ errors, processing }"
                class="grid gap-6"
            >
                <FormFieldset :title="t('registration.section.identity')">
                    <FormField
                        name="first_name"
                        :span="3"
                        :label="t('registration.field.first_name')"
                        :error="errors.first_name"
                    >
                        <TextField
                            id="first_name"
                            name="first_name"
                            required
                            autocomplete="off"
                            maxlength="120"
                            :default-value="registration.first_name"
                        />
                    </FormField>

                    <FormField
                        name="last_name"
                        :span="3"
                        :label="t('registration.field.last_name')"
                        :error="errors.last_name"
                    >
                        <TextField
                            id="last_name"
                            name="last_name"
                            required
                            autocomplete="off"
                            maxlength="120"
                            :default-value="registration.last_name"
                        />
                    </FormField>

                    <FormField
                        name="email"
                        :span="4"
                        :label="t('registration.field.email')"
                        :error="errors.email"
                    >
                        <TextField
                            id="email"
                            type="email"
                            name="email"
                            required
                            autocomplete="off"
                            maxlength="255"
                            :default-value="registration.email"
                        />
                    </FormField>
                </FormFieldset>

                <RegistrationFields
                    :registration="registration"
                    :errors="errors"
                    lock-pps
                />

                <ActionBar>
                    <ActionButton type="submit" :loading="processing">
                        {{ t('registration.manage.save') }}
                    </ActionButton>
                </ActionBar>
            </Form>
        </BoardColumns>
    </BoardPage>
</template>
