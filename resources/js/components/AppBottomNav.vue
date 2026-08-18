<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Ellipsis } from '@lucide/vue';
import { useSidebar } from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { t } from '@/lib/i18n';
import { mainNavItems } from '@/lib/mainNav';

const items = mainNavItems();
const { isCurrentUrl } = useCurrentUrl();
const { setOpenMobile } = useSidebar();

function openSections(): void {
    setOpenMobile(true);
}
</script>

<template>
    <nav
        :aria-label="t('ui.nav.main')"
        class="flex shrink-0 border-t border-border bg-background pb-safe"
    >
        <Link
            v-for="item in items"
            :key="item.title"
            :href="item.href"
            :aria-current="isCurrentUrl(item.href) ? 'page' : undefined"
            class="flex min-h-11 flex-1 flex-col items-center justify-center gap-1 px-2 py-2 text-muted-foreground transition-colors aria-[current=page]:text-primary"
        >
            <component :is="item.icon" class="size-5" aria-hidden="true" />
            <span class="font-display text-label uppercase">{{
                item.title
            }}</span>
        </Link>
        <button
            type="button"
            class="flex min-h-11 flex-1 flex-col items-center justify-center gap-1 px-2 py-2 text-muted-foreground transition-colors"
            @click="openSections"
        >
            <Ellipsis class="size-5" aria-hidden="true" />
            <span class="font-display text-label uppercase">{{
                t('ui.nav.more')
            }}</span>
        </button>
    </nav>
</template>
