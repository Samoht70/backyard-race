<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import SeatCounter from '@/components/registration/SeatCounter.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
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

        <div
            v-if="status"
            class="text-center text-sm font-medium text-green-600"
            data-test="register-status"
        >
            {{ status }}
        </div>

        <p
            v-if="!open"
            class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-center text-sm text-amber-700 dark:border-amber-200/10 dark:bg-amber-700/10 dark:text-amber-100"
            data-test="register-closed"
        >
            {{ t('auth.register.start.closed') }}
        </p>

        <Form
            v-else
            v-bind="store.form()"
            reset-on-success
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <InputError :message="errors.event" />

                <div class="grid gap-2">
                    <Label for="email">{{
                        t('auth.register.start.email')
                    }}</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full"
                    :tabindex="2"
                    :disabled="processing"
                    data-test="register-request-button"
                >
                    <Spinner v-if="processing" />
                    {{ t('auth.register.start.submit') }}
                </Button>
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
