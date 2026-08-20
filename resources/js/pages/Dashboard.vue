<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarOff, Ticket } from '@lucide/vue';
import { computed } from 'vue';
import StatCounter from '@/components/race/StatCounter.vue';
import RegistrationStatusBadge from '@/components/registration/RegistrationStatusBadge.vue';
import EmptyState from '@/components/state/EmptyState.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { canReach } from '@/lib/access';
import { t } from '@/lib/i18n';
import { dashboard } from '@/routes';
import { show as showBriefing } from '@/routes/briefing';
import { index as showDocuments } from '@/routes/documents';
import { show as showEvent } from '@/routes/event';
import { show as showRegistration } from '@/routes/registration';
import type { RegistrationStatus } from '@/types/registration';

type Props = {
    event: { name: string | null; status: string } | null;
    registration: {
        status: RegistrationStatus;
        status_label: string;
        bib_label: string | null;
    } | null;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Accueil',
                href: dashboard(),
            },
        ],
    },
});

const primaryLinkClasses =
    'inline-flex min-h-11 w-full touch-manipulation items-center justify-center border border-primary bg-primary text-xs font-bold tracking-widest text-primary-foreground uppercase';
const outlineLinkClasses =
    'inline-flex min-h-11 w-full touch-manipulation items-center justify-center border border-foreground text-xs font-bold tracking-widest uppercase';

const title = computed(() => props.event?.name ?? t('ui.dashboard.title'));
const status = computed(() => props.registration?.status ?? null);
</script>

<template>
    <Head :title="title" />

    <div class="flex flex-col gap-6 p-4">
        <header class="flex flex-col items-center gap-2 text-center">
            <h1 class="text-title">{{ title }}</h1>
            <RegistrationStatusBadge
                v-if="registration"
                :status="registration.status"
            />
        </header>

        <EmptyState
            v-if="event === null"
            :icon="CalendarOff"
            :title="t('ui.dashboard.no_event_title')"
            :description="t('ui.dashboard.no_event_description')"
        />

        <EmptyState
            v-else-if="registration === null"
            :icon="Ticket"
            :title="t('ui.dashboard.no_registration_title')"
            :description="t('ui.dashboard.no_registration_description')"
        >
            <template #action>
                <Link
                    v-if="canReach('event')"
                    :href="showEvent()"
                    :class="primaryLinkClasses"
                >
                    {{ t('ui.dashboard.no_registration_action') }}
                </Link>
            </template>
        </EmptyState>

        <template v-else>
            <Alert v-if="status === 'cancelled'" variant="destructive">
                <AlertTitle>
                    {{ t('registration.show.cancelled_title') }}
                </AlertTitle>
                <AlertDescription>
                    {{ t('registration.show.cancelled_description') }}
                </AlertDescription>
            </Alert>

            <StatCounter
                v-else-if="status === 'confirmed'"
                :value="registration.bib_label"
                :label="t('registration.field.bib')"
                size="lg"
            />

            <p v-else class="text-center text-sm text-muted-foreground">
                {{ t('ui.dashboard.pending') }}
            </p>

            <p
                v-if="status === 'confirmed'"
                class="text-center text-sm text-muted-foreground"
            >
                {{ t('ui.dashboard.confirmed') }}
            </p>

            <Link :href="showRegistration()" :class="primaryLinkClasses">
                {{ t('registration.show.call_to_action') }}
            </Link>
        </template>

        <nav
            v-if="canReach('event') || canReach('documents')"
            :aria-label="t('ui.dashboard.practical')"
            class="flex flex-col gap-2"
        >
            <p class="font-mono text-label text-muted-foreground uppercase">
                {{ t('ui.dashboard.practical') }}
            </p>

            <Link
                v-if="canReach('event')"
                :href="showBriefing()"
                :class="outlineLinkClasses"
            >
                {{ t('ui.nav.briefing') }}
            </Link>

            <Link
                v-if="canReach('documents')"
                :href="showDocuments()"
                :class="outlineLinkClasses"
            >
                {{ t('ui.nav.documents') }}
            </Link>
        </nav>
    </div>
</template>
