<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import EventField from '@/components/event/EventField.vue';
import EventFieldset from '@/components/event/EventFieldset.vue';
import InputError from '@/components/InputError.vue';
import RegistrationFields from '@/components/registration/RegistrationFields.vue';
import SeatCounter from '@/components/registration/SeatCounter.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { t } from '@/lib/i18n';
import { update } from '@/routes/account';

defineProps<{
    email: string;
    seats: { confirmed: number; capacity: number | null } | null;
}>();

setLayoutProps({
    title: t('auth.register.complete.title'),
    description: t('auth.register.complete.description'),
});
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
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <InputError :message="errors.event" />

            <EventFieldset :title="t('auth.register.complete.identity')">
                <EventField
                    name="email"
                    :label="t('auth.register.complete.email')"
                >
                    <Input
                        id="email"
                        type="email"
                        :model-value="email"
                        disabled
                    />
                </EventField>

                <EventField
                    name="first_name"
                    :label="t('auth.register.complete.first_name')"
                    :error="errors.first_name"
                >
                    <Input
                        id="first_name"
                        name="first_name"
                        required
                        autofocus
                        autocomplete="given-name"
                        maxlength="120"
                    />
                </EventField>

                <EventField
                    name="last_name"
                    :label="t('auth.register.complete.last_name')"
                    :error="errors.last_name"
                >
                    <Input
                        id="last_name"
                        name="last_name"
                        required
                        autocomplete="family-name"
                        maxlength="120"
                    />
                </EventField>
            </EventFieldset>

            <RegistrationFields :errors="errors" />

            <Button
                type="submit"
                class="w-full"
                :disabled="processing"
                data-test="register-complete-button"
            >
                <Spinner v-if="processing" />
                {{ t('auth.register.complete.submit') }}
            </Button>
        </Form>
    </div>
</template>
