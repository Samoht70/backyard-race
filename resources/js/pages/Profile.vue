<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Label } from 'reka-ui';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/ProfileController';
import ActionButton from '@/components/ActionButton.vue';
import ActionBar from '@/components/board/ActionBar.vue';
import BoardPage from '@/components/board/BoardPage.vue';
import DeleteUser from '@/components/DeleteUser.vue';
import FieldError from '@/components/form/FieldError.vue';
import TextField from '@/components/form/TextField.vue';
import { t } from '@/lib/i18n';

const user = computed(() => usePage().props.auth.user);
</script>

<template>
    <Head :title="t('ui.profile.title')" />

    <BoardPage>
        <div v-if="user" class="grid max-w-4xl gap-6">
            <header class="flex flex-col gap-2">
                <h1 class="text-title">{{ t('ui.profile.title') }}</h1>
                <p class="text-sm text-muted-foreground">
                    {{ t('ui.profile.description') }}
                </p>
            </header>

            <Form
                v-bind="ProfileController.update.form()"
                class="grid gap-6"
                v-slot="{ errors, processing }"
            >
                <div class="@container grid gap-4 @min-[52rem]:grid-cols-6">
                    <div class="grid gap-2 @min-[52rem]:col-span-3">
                        <Label for="first_name">{{
                            t('ui.profile.first_name')
                        }}</Label>
                        <TextField
                            id="first_name"
                            name="first_name"
                            :default-value="user.first_name"
                            required
                            autocomplete="given-name"
                            :placeholder="t('ui.profile.first_name')"
                        />
                        <FieldError :message="errors.first_name" />
                    </div>

                    <div class="grid gap-2 @min-[52rem]:col-span-3">
                        <Label for="last_name">{{
                            t('ui.profile.last_name')
                        }}</Label>
                        <TextField
                            id="last_name"
                            name="last_name"
                            :default-value="user.last_name"
                            required
                            autocomplete="family-name"
                            :placeholder="t('ui.profile.last_name')"
                        />
                        <FieldError :message="errors.last_name" />
                    </div>

                    <div class="grid gap-2 @min-[52rem]:col-span-4">
                        <Label for="email">{{ t('ui.profile.email') }}</Label>
                        <TextField
                            id="email"
                            type="email"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="username"
                            :placeholder="t('ui.profile.email')"
                        />
                        <FieldError :message="errors.email" />
                    </div>
                </div>

                <ActionBar>
                    <ActionButton
                        type="submit"
                        :loading="processing"
                        data-test="update-profile-button"
                    >
                        {{ t('ui.profile.save') }}
                    </ActionButton>
                </ActionBar>
            </Form>

            <DeleteUser />
        </div>
    </BoardPage>
</template>
