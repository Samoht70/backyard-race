<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { t } from '@/lib/i18n';
import type { PageLink } from '@/types/ui';

type Props = {
    label: string;
    pages: PageLink[];
    previous: PageLink | null;
    next: PageLink | null;
};

defineProps<Props>();

const linkClasses =
    'flex min-h-11 min-w-11 touch-manipulation items-center justify-center rounded-sm border px-3 font-mono text-label tabular-nums';

const idleClasses =
    'border-border bg-card text-muted-foreground hover:border-primary hover:text-primary';

const currentClasses = 'border-primary bg-primary text-primary-foreground';
</script>

<template>
    <nav
        :aria-label="label"
        class="flex flex-wrap items-center justify-center gap-1.5"
    >
        <Link
            v-if="previous"
            :href="previous.href"
            :aria-label="t('ui.pagination.previous')"
            :class="[linkClasses, idleClasses]"
        >
            <ChevronLeft class="size-4" aria-hidden="true" />
        </Link>

        <Link
            v-for="link in pages"
            :key="link.page"
            :href="link.href"
            :aria-label="t('ui.pagination.page', { page: link.page })"
            :aria-current="link.current ? 'page' : undefined"
            :class="[linkClasses, link.current ? currentClasses : idleClasses]"
        >
            {{ link.page }}
        </Link>

        <Link
            v-if="next"
            :href="next.href"
            :aria-label="t('ui.pagination.next')"
            :class="[linkClasses, idleClasses]"
        >
            <ChevronRight class="size-4" aria-hidden="true" />
        </Link>
    </nav>
</template>
