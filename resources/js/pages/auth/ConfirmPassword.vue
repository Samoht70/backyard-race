<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { Label } from 'reka-ui';
import ActionButton from '@/components/ActionButton.vue';
import FieldError from '@/components/form/FieldError.vue';
import PasswordField from '@/components/form/PasswordField.vue';
import { t } from '@/lib/i18n';
import { store } from '@/routes/password/confirm';

setLayoutProps({
    title: t('auth.confirm.title'),
    description: t('auth.confirm.description'),
});
</script>

<template>
    <Head :title="t('auth.confirm.title')" />

    <Form
        v-bind="store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
    >
        <div class="space-y-6">
            <div class="grid gap-2">
                <Label htmlFor="password">{{
                    t('auth.confirm.password')
                }}</Label>
                <PasswordField
                    id="password"
                    name="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
                    autofocus
                />

                <FieldError :message="errors.password" />
            </div>

            <ActionButton
                type="submit"
                block
                :loading="processing"
                :disabled="processing"
                data-test="confirm-password-button"
            >
                {{ t('auth.confirm.submit') }}
            </ActionButton>
        </div>
    </Form>
</template>
