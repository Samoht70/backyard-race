<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import BoardRow from '@/components/board/BoardRow.vue';
import BoardRows from '@/components/board/BoardRows.vue';
import BoardSection from '@/components/board/BoardSection.vue';
import ActionButton from '@/components/race/ActionButton.vue';
import BibDisplay from '@/components/race/BibDisplay.vue';
import RegistrationActionForm from '@/components/registration/RegistrationActionForm.vue';
import RegistrationStatusBadge from '@/components/registration/RegistrationStatusBadge.vue';
import { t } from '@/lib/i18n';
import { edit } from '@/routes/manage/registrations';
import type { ManagedRegistration } from '@/types/registration';

type Props = {
    registration: ManagedRegistration;
    blocked?: boolean;
    describedBy?: string;
};

const props = withDefaults(defineProps<Props>(), { blocked: false });

const fullName = computed(
    () => `${props.registration.first_name} ${props.registration.last_name}`,
);
</script>

<template>
    <div class="grid content-start gap-6">
        <div class="grid justify-items-start gap-3">
            <BibDisplay
                :value="registration.bib_label"
                :label="t('registration.manage.bib')"
            />
            <h2 class="text-title">{{ fullName }}</h2>
            <RegistrationStatusBadge :status="registration.status" />
            <p
                v-if="registration.bib_label === null"
                class="text-sm text-muted-foreground"
            >
                {{ t('registration.manage.no_bib') }}
            </p>
        </div>

        <BoardSection
            v-if="registration.allowed_transitions.length"
            :title="t('registration.manage.actions_title')"
            level="h3"
        >
            <div class="flex flex-wrap items-start gap-2">
                <RegistrationActionForm
                    v-for="transition in registration.allowed_transitions"
                    :key="transition"
                    :registration-id="registration.id"
                    :runner-name="fullName"
                    :transition="transition"
                    :disabled="blocked && transition === 'confirm'"
                    :described-by="blocked ? describedBy : undefined"
                />
            </div>
        </BoardSection>

        <BoardSection :title="t('registration.section.runner')" level="h3">
            <BoardRows>
                <BoardRow :label="t('registration.field.email')">
                    {{ registration.email }}
                </BoardRow>
                <BoardRow :label="t('registration.field.phone')" mono>
                    {{ registration.phone }}
                </BoardRow>
                <BoardRow :label="t('registration.field.birth_date')" mono>
                    {{ registration.birth_date }}
                </BoardRow>
                <BoardRow :label="t('registration.field.pps_number')" mono>
                    {{
                        registration.pps_number ?? t('registration.show.no_pps')
                    }}
                </BoardRow>
            </BoardRows>
        </BoardSection>

        <BoardSection :title="t('registration.section.emergency')" level="h3">
            <BoardRows>
                <BoardRow
                    :label="t('registration.field.emergency_contact_name')"
                >
                    {{ registration.emergency_contact_name }}
                </BoardRow>
                <BoardRow
                    :label="t('registration.field.emergency_contact_phone')"
                    mono
                >
                    {{ registration.emergency_contact_phone }}
                </BoardRow>
            </BoardRows>
        </BoardSection>

        <BoardSection
            v-if="registration.notes"
            :title="t('registration.section.notes')"
            level="h3"
        >
            <p class="text-sm whitespace-pre-line">{{ registration.notes }}</p>
        </BoardSection>

        <div class="flex justify-start">
            <ActionButton tone="quiet" as-child>
                <Link :href="edit(registration.id)">
                    {{ t('registration.manage.open_form') }}
                </Link>
            </ActionButton>
        </div>
    </div>
</template>
