<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardList } from '@lucide/vue';
import { computed } from 'vue';
import AlertError from '@/components/AlertError.vue';
import Heading from '@/components/Heading.vue';
import BoardFilter from '@/components/race/BoardFilter.vue';
import RegistrationActionForm from '@/components/registration/RegistrationActionForm.vue';
import RegistrationSlat from '@/components/registration/RegistrationSlat.vue';
import SeatCounter from '@/components/registration/SeatCounter.vue';
import EmptyState from '@/components/state/EmptyState.vue';
import { t } from '@/lib/i18n';
import { registrationStatusLabelKey } from '@/lib/registrationStatus';
import { index as manage } from '@/routes/manage';
import { edit, index } from '@/routes/manage/registrations';
import { REGISTRATION_STATUSES } from '@/types/registration';
import type {
    ManagedRegistration,
    RegistrationCounts,
    RegistrationSeats,
} from '@/types/registration';
import type { BoardFilterOption } from '@/types/ui';

type Props = {
    registrations: ManagedRegistration[];
    counts: RegistrationCounts;
    seats: RegistrationSeats;
    status: string | null;
    refusals: string[];
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Gestion',
                href: manage(),
            },
            {
                title: 'Inscriptions',
                href: index(),
            },
        ],
    },
});

const filters = computed<BoardFilterOption[]>(() => [
    {
        value: null,
        label: t('registration.manage.filter_all'),
        href: index(),
        count: props.counts.all,
    },
    ...REGISTRATION_STATUSES.map((status) => ({
        value: status,
        label: t(registrationStatusLabelKey(status)),
        href: index({ query: { status } }),
        count: props.counts[status],
    })),
]);

const isBlocked = computed(() => props.refusals.length > 0);
</script>

<template>
    <Head :title="t('registration.manage.title')" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            :title="t('registration.manage.title')"
            :description="t('registration.manage.description')"
        />

        <AlertError
            v-if="isBlocked"
            id="registration-refusals"
            :title="t('registration.transition.blocked_title')"
            :errors="refusals"
        />

        <SeatCounter :confirmed="seats.confirmed" :capacity="seats.capacity" />

        <BoardFilter
            :label="t('registration.manage.filter_label')"
            :options="filters"
            :active-value="status"
        />

        <div v-if="registrations.length" class="slats">
            <RegistrationSlat
                v-for="registration in registrations"
                :key="registration.id"
                :bib="registration.bib_label"
                :first-name="registration.first_name"
                :last-name="registration.last_name"
                :status="registration.status"
                :href="edit(registration.id)"
            >
                <template #cell>
                    <RegistrationActionForm
                        v-if="registration.allowed_transitions.length"
                        :registration-id="registration.id"
                        :runner-name="`${registration.first_name} ${registration.last_name}`"
                        :transition="registration.allowed_transitions[0]"
                        :disabled="
                            isBlocked &&
                            registration.allowed_transitions[0] === 'confirm'
                        "
                        :described-by="
                            isBlocked ? 'registration-refusals' : undefined
                        "
                        class="w-auto px-3"
                    />
                </template>
            </RegistrationSlat>
        </div>

        <EmptyState
            v-else-if="status"
            :icon="ClipboardList"
            :title="t('registration.manage.empty_filtered_title')"
            :description="t('registration.manage.empty_filtered_description')"
        >
            <template #action>
                <Link :href="index()" class="text-sm underline">
                    {{ t('registration.manage.show_all') }}
                </Link>
            </template>
        </EmptyState>

        <EmptyState
            v-else
            :icon="ClipboardList"
            :title="t('registration.manage.empty_title')"
            :description="t('registration.manage.empty_description')"
        />
    </div>
</template>
