<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ClipboardList,
    Files,
    Flag,
    ScrollText,
    SlidersHorizontal,
    Undo2,
} from '@lucide/vue';
import { computed } from 'vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { t } from '@/lib/i18n';
import { can } from '@/lib/permissions';
import { corrections, index as race } from '@/routes/manage';
import { edit as editBriefing } from '@/routes/manage/briefing';
import { index as documents } from '@/routes/manage/documents';
import { edit as editEvent } from '@/routes/manage/event';
import { index as registrations } from '@/routes/manage/registrations';

const page = usePage();
const { isCurrentUrl } = useCurrentUrl();

const isRacing = computed(() => page.props.board?.status === 'running');

const desks = computed(() =>
    [
        {
            key: 'race',
            icon: Flag,
            label: t('ui.manage.race'),
            href: race(),
            shown: can('manage-laps'),
        },
        {
            key: 'corrections',
            icon: Undo2,
            label: t('ui.manage.corrections'),
            href: corrections(),
            shown: can('manage-laps') && isRacing.value,
        },
        {
            key: 'event',
            icon: SlidersHorizontal,
            label: t('ui.manage.event'),
            href: editEvent(),
            shown: can('manage-event'),
        },
        {
            key: 'registrations',
            icon: ClipboardList,
            label: t('ui.manage.registrations'),
            href: registrations(),
            shown: can('manage-participants'),
        },
        {
            key: 'briefing',
            icon: ScrollText,
            label: t('ui.manage.briefing'),
            href: editBriefing(),
            shown: can('manage-documents'),
        },
        {
            key: 'documents',
            icon: Files,
            label: t('ui.manage.documents'),
            href: documents(),
            shown: can('manage-documents'),
        },
    ].filter((desk) => desk.shown),
);
</script>

<template>
    <nav
        class="flex flex-col gap-1.5 sm:grid sm:grid-cols-3 lg:flex lg:flex-row"
        :aria-label="t('ui.manage.title')"
    >
        <Link
            v-for="desk in desks"
            :key="desk.key"
            :href="desk.href"
            :aria-current="isCurrentUrl(desk.href) ? 'page' : undefined"
            class="group flex min-h-11 items-center gap-3 rounded-sm border border-border bg-card px-3 py-2.5 transition-colors outline-none hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring aria-[current=page]:border-primary aria-[current=page]:bg-accent aria-[current=page]:text-primary lg:flex-1"
        >
            <component
                :is="desk.icon"
                class="size-5 shrink-0 text-muted-foreground group-aria-[current=page]:text-primary"
                aria-hidden="true"
            />
            <span class="text-sm font-medium">{{ desk.label }}</span>
        </Link>
    </nav>
</template>
