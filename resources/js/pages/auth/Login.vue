<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { Label } from 'reka-ui';
import ActionButton from '@/components/ActionButton.vue';
import CheckField from '@/components/form/CheckField.vue';
import FieldError from '@/components/form/FieldError.vue';
import PasswordField from '@/components/form/PasswordField.vue';
import TextField from '@/components/form/TextField.vue';
import TextLink from '@/components/TextLink.vue';
import { t } from '@/lib/i18n';
import { create } from '@/routes/account';
import { store } from '@/routes/login';

defineProps<{
    status?: string;
}>();

setLayoutProps({
    title: t('auth.login.title'),
    description: t('auth.login.description'),
});
</script>

<template>
    <Head :title="t('auth.login.title')" />

    <p
        v-if="status"
        class="mb-4 border-l-2 border-status-running bg-status-running-surface px-3 py-2 text-sm text-status-running"
    >
        {{ status }}
    </p>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">{{ t('auth.login.email') }}</Label>
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

            <div class="grid gap-2">
                <Label for="password">{{ t('auth.login.password') }}</Label>
                <PasswordField
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    :placeholder="t('auth.login.password')"
                />
                <FieldError :message="errors.password" />
            </div>

            <CheckField
                id="remember"
                name="remember"
                :tabindex="3"
                :label="t('auth.login.remember')"
            />

            <ActionButton
                type="submit"
                block
                class="mt-4"
                :tabindex="4"
                :loading="processing"
                :disabled="processing"
                data-test="login-button"
            >
                {{ t('auth.login.submit') }}
            </ActionButton>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            {{ t('auth.login.no_account') }}
            <TextLink :href="create()" :tabindex="5">{{
                t('auth.login.register')
            }}</TextLink>
        </div>
    </Form>
</template>
