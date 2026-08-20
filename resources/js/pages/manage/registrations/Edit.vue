<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { computed } from 'vue';
import RegistrationController from '@/actions/App/Http/Controllers/Manage/RegistrationController';
import EventField from '@/components/event/EventField.vue';
import EventFieldset from '@/components/event/EventFieldset.vue';
import Heading from '@/components/Heading.vue';
import ActionButton from '@/components/race/ActionButton.vue';
import StatCounter from '@/components/race/StatCounter.vue';
import RegistrationActionForm from '@/components/registration/RegistrationActionForm.vue';
import RegistrationFields from '@/components/registration/RegistrationFields.vue';
import RegistrationStatusBadge from '@/components/registration/RegistrationStatusBadge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { t } from '@/lib/i18n';
import { index as manage } from '@/routes/manage';
import { edit, index } from '@/routes/manage/registrations';
import type { ManagedRegistration } from '@/types/registration';

type Props = {
    registration: ManagedRegistration;
};

const props = defineProps<Props>();

const fullName = computed(
    () => `${props.registration.first_name} ${props.registration.last_name}`,
);

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Gestion',
            href: manage(),
        },
        {
            title: 'Inscriptions',
            href: index(),
        },
        {
            title: 'Fiche',
            href: edit(props.registration.id),
        },
    ],
});
</script>

<template>
    <Head :title="t('registration.manage.edit_title')" />

    <div class="flex flex-col gap-6 p-4">
        <header class="flex flex-col items-start gap-2">
            <h1 class="text-title">{{ fullName }}</h1>
            <RegistrationStatusBadge :status="registration.status" />
        </header>

        <div class="flex flex-col gap-1">
            <StatCounter
                :value="registration.bib_label"
                :label="t('registration.manage.bib')"
                size="lg"
            />
            <p
                v-if="registration.bib_label === null"
                class="text-sm text-muted-foreground"
            >
                {{ t('registration.manage.no_bib') }}
            </p>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="font-mono text-label uppercase">
                    {{ t('registration.manage.actions_title') }}
                </CardTitle>
            </CardHeader>

            <CardContent class="flex flex-col gap-2">
                <RegistrationActionForm
                    v-for="transition in registration.allowed_transitions"
                    :key="transition"
                    :registration-id="registration.id"
                    :runner-name="fullName"
                    :transition="transition"
                />
            </CardContent>
        </Card>

        <Heading
            :title="t('registration.manage.edit_title')"
            :description="t('registration.manage.edit_description')"
        />

        <Form
            v-bind="RegistrationController.update.form(registration.id)"
            :options="{ preserveScroll: true }"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <EventFieldset :title="t('registration.section.identity')">
                <EventField
                    name="first_name"
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

            <ActionButton type="submit" :loading="processing">
                {{ t('registration.manage.save') }}
            </ActionButton>
        </Form>
    </div>
</template>
