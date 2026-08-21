<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CircleSlash } from '@lucide/vue';
import { computed } from 'vue';
import ActionBar from '@/components/board/ActionBar.vue';
import BoardColumns from '@/components/board/BoardColumns.vue';
import BoardPage from '@/components/board/BoardPage.vue';
import BoardRow from '@/components/board/BoardRow.vue';
import BoardRows from '@/components/board/BoardRows.vue';
import BoardSection from '@/components/board/BoardSection.vue';
import ActionButton from '@/components/race/ActionButton.vue';
import BibDisplay from '@/components/race/BibDisplay.vue';
import RegistrationStatusBadge from '@/components/registration/RegistrationStatusBadge.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { t } from '@/lib/i18n';
import { edit } from '@/routes/registration';
import type { RegistrationDetails } from '@/types/registration';

type Props = {
    registration: RegistrationDetails;
    canEdit: boolean;
};

const props = defineProps<Props>();

const isCancelled = computed(() => props.registration.status === 'cancelled');
const fullName = computed(
    () => `${props.registration.first_name} ${props.registration.last_name}`,
);
</script>

<template>
    <Head :title="t('registration.show.title')" />

    <BoardPage>
        <BoardColumns>
            <template #lead>
                <BibDisplay
                    v-if="registration.bib_label"
                    :value="registration.bib_label"
                    :label="t('registration.field.bib')"
                />
                <div class="grid justify-items-start gap-2">
                    <h1 class="text-title">{{ fullName }}</h1>
                    <RegistrationStatusBadge :status="registration.status" />
                </div>
            </template>

            <Alert v-if="isCancelled" variant="destructive">
                <CircleSlash class="size-4" />
                <AlertTitle>
                    {{ t('registration.show.cancelled_title') }}
                </AlertTitle>
                <AlertDescription>
                    {{ t('registration.show.cancelled_description') }}
                </AlertDescription>
            </Alert>

            <BoardSection :title="t('registration.section.runner')">
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
                            registration.pps_number ??
                            t('registration.show.no_pps')
                        }}
                    </BoardRow>
                </BoardRows>
            </BoardSection>

            <BoardSection :title="t('registration.section.emergency')">
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
            >
                <p class="text-sm whitespace-pre-line">
                    {{ registration.notes }}
                </p>
            </BoardSection>

            <ActionBar>
                <template v-if="!canEdit && !isCancelled" #note>
                    {{ t('registration.show.locked') }}
                </template>

                <ActionButton v-if="canEdit" tone="quiet" as-child>
                    <Link :href="edit()">
                        {{ t('registration.show.edit') }}
                    </Link>
                </ActionButton>
            </ActionBar>
        </BoardColumns>
    </BoardPage>
</template>
