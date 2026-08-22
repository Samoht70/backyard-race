<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarOff, SlidersHorizontal, Ticket } from '@lucide/vue';
import { computed } from 'vue';
import ActionButton from '@/components/ActionButton.vue';
import ActionBar from '@/components/board/ActionBar.vue';
import BoardColumns from '@/components/board/BoardColumns.vue';
import BoardPage from '@/components/board/BoardPage.vue';
import BoardRow from '@/components/board/BoardRow.vue';
import BoardRows from '@/components/board/BoardRows.vue';
import BoardSection from '@/components/board/BoardSection.vue';
import Notice from '@/components/Notice.vue';
import BibDisplay from '@/components/race/BibDisplay.vue';
import RegistrationStatusBadge from '@/components/registration/RegistrationStatusBadge.vue';
import EmptyState from '@/components/state/EmptyState.vue';
import { canReach } from '@/lib/access';
import { t } from '@/lib/i18n';
import { can } from '@/lib/permissions';
import { home } from '@/routes';
import { show as showBriefing } from '@/routes/briefing';
import { index as showDocuments } from '@/routes/documents';
import { index as showManage } from '@/routes/manage';
import { show as showRegistration } from '@/routes/registration';
import type { RegistrationStatus } from '@/types/registration';

type Props = {
    event: { name: string | null; status: string } | null;
    registration: {
        status: RegistrationStatus;
        status_label: string;
        bib_label: string | null;
        submitted_on: string | null;
        editable: boolean;
    } | null;
};

const props = defineProps<Props>();

const title = computed(() => props.event?.name ?? t('ui.dashboard.title'));
const status = computed(() => props.registration?.status ?? null);
const isConfirmed = computed(() => status.value === 'confirmed');
const isManager = computed(() => can('manage-event'));
</script>

<template>
    <Head :title="title" />

    <BoardPage>
        <EmptyState
            v-if="event === null"
            :icon="CalendarOff"
            :title="t('ui.dashboard.no_event_title')"
            :description="t('ui.dashboard.no_event_description')"
        />

        <EmptyState
            v-else-if="registration === null && isManager"
            :icon="SlidersHorizontal"
            :title="t('ui.dashboard.manager_title')"
            :description="t('ui.dashboard.manager_description')"
        >
            <template #action>
                <ActionButton as-child>
                    <Link :href="showManage()">
                        {{ t('ui.dashboard.manager_action') }}
                    </Link>
                </ActionButton>
            </template>
        </EmptyState>

        <EmptyState
            v-else-if="registration === null"
            :icon="Ticket"
            :title="t('ui.dashboard.no_registration_title')"
            :description="t('ui.dashboard.no_registration_description')"
        >
            <template #action>
                <ActionButton v-if="canReach('event')" as-child>
                    <Link :href="home()">
                        {{ t('ui.dashboard.no_registration_action') }}
                    </Link>
                </ActionButton>
            </template>
        </EmptyState>

        <BoardColumns v-else>
            <template #lead>
                <BibDisplay
                    v-if="isConfirmed"
                    :value="registration.bib_label"
                    :label="t('registration.field.bib')"
                />
                <RegistrationStatusBadge :status="registration.status" />
            </template>

            <Notice
                v-if="status === 'cancelled'"
                tone="danger"
                :title="t('registration.show.cancelled_title')"
            >
                {{ t('registration.show.cancelled_description') }}
            </Notice>

            <BoardSection :title="t('ui.dashboard.my_registration')">
                <BoardRows>
                    <BoardRow
                        v-if="registration.submitted_on"
                        :label="t('ui.dashboard.submitted_on')"
                        mono
                    >
                        {{ registration.submitted_on }}
                    </BoardRow>
                    <BoardRow :label="t('ui.dashboard.editable')">
                        {{
                            registration.editable
                                ? t('ui.dashboard.editable_yes')
                                : t('ui.dashboard.editable_no')
                        }}
                    </BoardRow>
                </BoardRows>
            </BoardSection>

            <ActionBar>
                <template #note>
                    {{
                        isConfirmed
                            ? t('ui.dashboard.confirmed')
                            : t('ui.dashboard.pending')
                    }}
                </template>

                <ActionButton v-if="canReach('event')" tone="quiet" as-child>
                    <Link :href="showBriefing()">
                        {{ t('ui.nav.briefing') }}
                    </Link>
                </ActionButton>

                <ActionButton
                    v-if="canReach('documents')"
                    tone="quiet"
                    as-child
                >
                    <Link :href="showDocuments()">
                        {{ t('ui.nav.documents') }}
                    </Link>
                </ActionButton>

                <ActionButton as-child>
                    <Link :href="showRegistration()">
                        {{ t('registration.show.call_to_action') }}
                    </Link>
                </ActionButton>
            </ActionBar>
        </BoardColumns>
    </BoardPage>
</template>
