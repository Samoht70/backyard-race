<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { Label } from 'reka-ui';
import ActionButton from '@/components/ActionButton.vue';
import FieldError from '@/components/form/FieldError.vue';
import TextField from '@/components/form/TextField.vue';
import Notice from '@/components/Notice.vue';
import SeatCounter from '@/components/registration/SeatCounter.vue';
import TextLink from '@/components/TextLink.vue';
import { t } from '@/lib/i18n';
import { login } from '@/routes';
import { store } from '@/routes/account';

defineProps<{
    status?: string;
    open: boolean;
    seats: { confirmed: number; capacity: number | null } | null;
}>();

setLayoutProps({
    title: t('auth.register.start.title'),
    description: t('auth.register.start.description'),
});
</script>

<template>
    <Head :title="t('auth.register.start.title')" />

    <div class="flex flex-col gap-6">
        <SeatCounter
            v-if="seats"
            :confirmed="seats.confirmed"
            :capacity="seats.capacity"
        />

        <p
            v-if="status"
            class="border-l-2 border-status-running bg-status-running-surface px-3 py-2 text-sm text-status-running"
            data-test="register-status"
        >
            {{ status }}
        </p>

        <Notice
            v-if="!open"
            :title="t('auth.register.start.closed_title')"
            data-test="register-closed"
        >
            {{ t('auth.register.start.closed') }}
        </Notice>

        <Form
            v-else
            v-bind="store.form()"
            reset-on-success
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <FieldError :message="errors.event" />

                <div class="grid gap-2">
                    <Label for="email">{{
                        t('auth.register.start.email')
                    }}</Label>
                    <TextField
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="email@example.com"
                    />
                    <FieldError :message="errors.email" />
                </div>

                <ActionButton
                    type="submit"
                    block
                    class="mt-2"
                    :tabindex="2"
                    :loading="processing"
                    :disabled="processing"
                    data-test="register-request-button"
                >
                    {{ t('auth.register.start.submit') }}
                </ActionButton>
            </div>
        </Form>

        <div class="text-center text-sm text-muted-foreground">
            {{ t('auth.register.start.has_account') }}
            <TextLink :href="login()" :tabindex="3">{{
                t('auth.register.start.login')
            }}</TextLink>
        </div>
    </div>
</template>
