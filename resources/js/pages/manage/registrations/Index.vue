<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardList, MousePointerClick } from '@lucide/vue';
import { useMediaQuery } from '@vueuse/core';
import { VisuallyHidden } from 'reka-ui';
import {
    DialogContent,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
} from 'reka-ui';
import { computed, ref } from 'vue';
import AlertError from '@/components/AlertError.vue';
import BoardPage from '@/components/board/BoardPage.vue';
import Heading from '@/components/Heading.vue';
import BoardFilter from '@/components/race/BoardFilter.vue';
import RegistrationDossier from '@/components/registration/RegistrationDossier.vue';
import RegistrationSlat from '@/components/registration/RegistrationSlat.vue';
import SeatCounter from '@/components/registration/SeatCounter.vue';
import EmptyState from '@/components/state/EmptyState.vue';
import { t } from '@/lib/i18n';
import { overlayBackdrop, overlayDrawer } from '@/lib/overlayClasses';
import { registrationStatusLabelKey } from '@/lib/registrationStatus';
import { index } from '@/routes/manage/registrations';
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
    deletionRefusal: string | null;
};

const props = defineProps<Props>();

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

const selectedId = ref<number | null>(null);

const selected = computed(
    () =>
        props.registrations.find(
            (registration) => registration.id === selectedId.value,
        ) ?? null,
);

const isWide = useMediaQuery('(min-width: 64rem)');

const isDossierOpen = computed({
    get: () => !isWide.value && selected.value !== null,
    set: (open: boolean) => {
        if (!open) {
            selectedId.value = null;
        }
    },
});

const dossierTitle = computed(() =>
    selected.value === null
        ? ''
        : `${selected.value.first_name} ${selected.value.last_name}`,
);
</script>

<template>
    <Head :title="t('registration.manage.title')" />

    <BoardPage>
        <div class="grid gap-6">
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

            <SeatCounter
                :confirmed="seats.confirmed"
                :capacity="seats.capacity"
            />

            <BoardFilter
                :label="t('registration.manage.filter_label')"
                :options="filters"
                :active-value="status"
            />

            <div
                v-if="registrations.length"
                class="grid items-start gap-6 lg:grid-cols-12 lg:gap-8"
            >
                <div class="slats min-w-0 lg:col-span-5">
                    <RegistrationSlat
                        v-for="registration in registrations"
                        :key="registration.id"
                        as="button"
                        type="button"
                        :bib="registration.bib_label"
                        :first-name="registration.first_name"
                        :last-name="registration.last_name"
                        :status="registration.status"
                        :active="registration.id === selectedId"
                        @click="selectedId = registration.id"
                    />
                </div>

                <div class="hidden lg:col-span-7 lg:block">
                    <div class="lg:sticky lg:top-0">
                        <RegistrationDossier
                            v-if="selected"
                            :registration="selected"
                            :blocked="isBlocked"
                            described-by="registration-refusals"
                            :deletion-refusal="deletionRefusal"
                        />

                        <EmptyState
                            v-else
                            :icon="MousePointerClick"
                            :title="t('registration.manage.select_title')"
                            :description="
                                t('registration.manage.select_description')
                            "
                        />
                    </div>
                </div>
            </div>

            <EmptyState
                v-else-if="status"
                :icon="ClipboardList"
                :title="t('registration.manage.empty_filtered_title')"
                :description="
                    t('registration.manage.empty_filtered_description')
                "
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

        <DialogRoot v-model:open="isDossierOpen">
            <DialogPortal>
                <DialogOverlay :class="overlayBackdrop" />
                <DialogContent :class="[overlayDrawer, 'overflow-y-auto p-4']">
                    <VisuallyHidden>
                        <DialogTitle>{{ dossierTitle }}</DialogTitle>
                    </VisuallyHidden>

                    <RegistrationDossier
                        v-if="selected"
                        :registration="selected"
                        :blocked="isBlocked"
                        described-by="registration-refusals"
                        :deletion-refusal="deletionRefusal"
                    />
                </DialogContent>
            </DialogPortal>
        </DialogRoot>
    </BoardPage>
</template>
