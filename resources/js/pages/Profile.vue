<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import FestoonDivider from '@/components/race/FestoonDivider.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { t } from '@/lib/i18n';
import { edit } from '@/routes/profile';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profil',
                href: edit(),
            },
        ],
    },
});

const user = computed(() => usePage().props.auth.user);
</script>

<template>
    <Head :title="t('ui.profile.title')" />

    <div class="mx-auto flex max-w-xl flex-col gap-6 p-4">
        <header class="flex flex-col gap-2">
            <h1 class="font-display text-lap">{{ t('ui.profile.title') }}</h1>
            <p class="text-sm text-muted-foreground">
                {{ t('ui.profile.description') }}
            </p>
        </header>

        <Form
            v-bind="ProfileController.update.form()"
            class="flex flex-col gap-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="first_name">{{ t('ui.profile.first_name') }}</Label>
                <Input
                    id="first_name"
                    name="first_name"
                    :default-value="user.first_name"
                    required
                    autocomplete="given-name"
                    :placeholder="t('ui.profile.first_name')"
                />
                <InputError :message="errors.first_name" />
            </div>

            <div class="grid gap-2">
                <Label for="last_name">{{ t('ui.profile.last_name') }}</Label>
                <Input
                    id="last_name"
                    name="last_name"
                    :default-value="user.last_name"
                    required
                    autocomplete="family-name"
                    :placeholder="t('ui.profile.last_name')"
                />
                <InputError :message="errors.last_name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">{{ t('ui.profile.email') }}</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="username"
                    :placeholder="t('ui.profile.email')"
                />
                <InputError :message="errors.email" />
            </div>

            <Button
                type="submit"
                :disabled="processing"
                data-test="update-profile-button"
            >
                {{ t('ui.profile.save') }}
            </Button>
        </Form>

        <FestoonDivider />

        <DeleteUser />
    </div>
</template>
