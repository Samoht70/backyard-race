<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CircleSlash } from '@lucide/vue';
import { computed } from 'vue';
import RegistrationStatusBadge from '@/components/registration/RegistrationStatusBadge.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { t } from '@/lib/i18n';
import { edit, show } from '@/routes/registration';
import type { RegistrationDetails } from '@/types/registration';

type Props = {
    registration: RegistrationDetails;
    canEdit: boolean;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Inscription',
                href: show(),
            },
        ],
    },
});

const isCancelled = computed(() => props.registration.status === 'cancelled');

const facts = computed(() => [
    ...(props.registration.bib_label === null
        ? []
        : [
              {
                  term: t('registration.field.bib'),
                  detail: props.registration.bib_label,
              },
          ]),
    {
        term: t('registration.field.email'),
        detail: props.registration.email,
    },
    {
        term: t('registration.field.phone'),
        detail: props.registration.phone,
    },
    {
        term: t('registration.field.birth_date'),
        detail: props.registration.birth_date,
    },
    {
        term: t('registration.field.pps_number'),
        detail: props.registration.pps_number ?? t('registration.show.no_pps'),
    },
    {
        term: t('registration.field.emergency_contact_name'),
        detail: props.registration.emergency_contact_name,
    },
    {
        term: t('registration.field.emergency_contact_phone'),
        detail: props.registration.emergency_contact_phone,
    },
]);
</script>

<template>
    <Head :title="t('registration.show.title')" />

    <div class="flex flex-col gap-6 p-4">
        <header class="flex flex-col items-center gap-2 text-center">
            <h1 class="text-title">
                {{ registration.first_name }} {{ registration.last_name }}
            </h1>
            <RegistrationStatusBadge :status="registration.status" />
        </header>

        <Alert v-if="isCancelled" variant="destructive">
            <CircleSlash class="size-4" />
            <AlertTitle>{{
                t('registration.show.cancelled_title')
            }}</AlertTitle>
            <AlertDescription>
                {{ t('registration.show.cancelled_description') }}
            </AlertDescription>
        </Alert>

        <dl class="flex flex-col gap-2">
            <div
                v-for="fact in facts"
                :key="fact.term"
                class="flex flex-wrap justify-between gap-2 border-b border-border pb-2"
            >
                <dt
                    class="font-mono text-label text-muted-foreground uppercase"
                >
                    {{ fact.term }}
                </dt>
                <dd class="text-sm tabular-nums">{{ fact.detail }}</dd>
            </div>
        </dl>

        <p v-if="registration.notes" class="text-sm whitespace-pre-line">
            {{ registration.notes }}
        </p>

        <Link
            v-if="canEdit"
            :href="edit()"
            class="inline-flex min-h-11 w-full touch-manipulation items-center justify-center border border-foreground text-xs font-bold tracking-widest uppercase"
        >
            {{ t('registration.show.edit') }}
        </Link>

        <p
            v-else-if="!isCancelled"
            class="text-center text-sm text-muted-foreground"
        >
            {{ t('registration.show.locked') }}
        </p>
    </div>
</template>
