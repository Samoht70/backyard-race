<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import RegistrationController from '@/actions/App/Http/Controllers/RegistrationController';
import ActionButton from '@/components/race/ActionButton.vue';
import RegistrationFields from '@/components/registration/RegistrationFields.vue';
import { t } from '@/lib/i18n';
import { show } from '@/routes/registration';
import type { RegistrationDetails } from '@/types/registration';

type Props = {
    registration: RegistrationDetails;
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Inscription',
                href: show(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="t('registration.edit.title')" />

    <div class="flex flex-col gap-6 p-4">
        <header class="flex flex-col items-center gap-2 text-center">
            <h1 class="text-title">
                {{ t('registration.edit.title') }}
            </h1>
            <p class="text-sm text-muted-foreground">
                {{ t('registration.edit.description') }}
            </p>
        </header>

        <Form
            v-bind="RegistrationController.update.form()"
            :options="{ preserveScroll: true }"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <RegistrationFields :registration="registration" :errors="errors" />

            <ActionButton type="submit" :loading="processing">
                {{ t('registration.edit.submit') }}
            </ActionButton>
        </Form>
    </div>
</template>
