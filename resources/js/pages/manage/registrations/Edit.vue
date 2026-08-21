<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import RegistrationController from '@/actions/App/Http/Controllers/Manage/RegistrationController';
import ActionBar from '@/components/board/ActionBar.vue';
import BoardColumns from '@/components/board/BoardColumns.vue';
import BoardPage from '@/components/board/BoardPage.vue';
import BoardSection from '@/components/board/BoardSection.vue';
import EventField from '@/components/event/EventField.vue';
import EventFieldset from '@/components/event/EventFieldset.vue';
import Heading from '@/components/Heading.vue';
import ActionButton from '@/components/race/ActionButton.vue';
import BibDisplay from '@/components/race/BibDisplay.vue';
import RegistrationActionForm from '@/components/registration/RegistrationActionForm.vue';
import RegistrationFields from '@/components/registration/RegistrationFields.vue';
import RegistrationStatusBadge from '@/components/registration/RegistrationStatusBadge.vue';
import { Input } from '@/components/ui/input';
import { t } from '@/lib/i18n';
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
                <EventFieldset :title="t('registration.section.identity')">
                    <EventField
                        name="first_name"
                        :span="3"
                        :label="t('registration.field.first_name')"
                        :error="errors.first_name"
                    >
                        <Input
                            id="first_name"
                            name="first_name"
                            required
                            autocomplete="off"
                            maxlength="120"
                            :default-value="registration.first_name"
                        />
                    </EventField>

                    <EventField
                        name="last_name"
                        :span="3"
                        :label="t('registration.field.last_name')"
                        :error="errors.last_name"
                    >
                        <Input
                            id="last_name"
                            name="last_name"
                            required
                            autocomplete="off"
                            maxlength="120"
                            :default-value="registration.last_name"
                        />
                    </EventField>

                    <EventField
                        name="email"
                        :span="4"
                        :label="t('registration.field.email')"
                        :error="errors.email"
                    >
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autocomplete="off"
                            maxlength="255"
                            :default-value="registration.email"
                        />
                    </EventField>
                </EventFieldset>

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
