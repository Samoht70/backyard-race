<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import RegistrationController from '@/actions/App/Http/Controllers/RegistrationController';
import ActionBar from '@/components/board/ActionBar.vue';
import BoardPage from '@/components/board/BoardPage.vue';
import ActionButton from '@/components/race/ActionButton.vue';
import RegistrationFields from '@/components/registration/RegistrationFields.vue';
import { t } from '@/lib/i18n';
import type { RegistrationDetails } from '@/types/registration';

type Props = {
    registration: RegistrationDetails;
};

defineProps<Props>();
</script>

<template>
    <Head :title="t('registration.edit.title')" />

    <BoardPage>
        <div class="grid max-w-4xl gap-6">
            <header class="grid gap-2">
                <h1 class="text-title">{{ t('registration.edit.title') }}</h1>
                <p class="text-sm text-muted-foreground">
                    {{ t('registration.edit.description') }}
                </p>
            </header>

            <Form
                v-bind="RegistrationController.update.form()"
                :options="{ preserveScroll: true }"
                v-slot="{ errors, processing }"
                class="grid gap-6"
            >
                <RegistrationFields
                    :registration="registration"
                    :errors="errors"
                />

                <ActionBar>
                    <ActionButton type="submit" :loading="processing">
                        {{ t('registration.edit.submit') }}
                    </ActionButton>
                </ActionBar>
            </Form>
        </div>
    </BoardPage>
</template>
