<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import RegistrationController from '@/actions/App/Http/Controllers/RegistrationController';
import InputError from '@/components/InputError.vue';
import ActionButton from '@/components/race/ActionButton.vue';
import FestoonDivider from '@/components/race/FestoonDivider.vue';
import RegistrationFields from '@/components/registration/RegistrationFields.vue';
import SeatCounter from '@/components/registration/SeatCounter.vue';
import { t } from '@/lib/i18n';
import { create } from '@/routes/registration';
import type { EventDetails } from '@/types/event';

type Props = {
    event: EventDetails;
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Inscription',
                href: create(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="t('registration.create.title')" />

    <div class="flex flex-col gap-6 p-4">
        <header class="flex flex-col items-center gap-2 text-center">
            <h1 class="font-display text-lap">
                {{ t('registration.create.title') }}
            </h1>
            <p class="text-sm text-muted-foreground">
                {{ t('registration.create.description') }}
            </p>
        </header>

        <SeatCounter
            :confirmed="event.confirmed_participants"
            :capacity="event.max_participants"
        />

        <FestoonDivider />

        <Form
            v-bind="RegistrationController.store.form()"
            :options="{ preserveScroll: true }"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <InputError :message="errors.event" />

            <RegistrationFields :errors="errors" />

            <ActionButton type="submit" :loading="processing">
                {{ t('registration.create.submit') }}
            </ActionButton>
        </Form>
    </div>
</template>
