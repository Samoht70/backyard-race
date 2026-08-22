<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Menu } from '@lucide/vue';
import { useMediaQuery } from '@vueuse/core';
import {
    DialogClose,
    DialogContent,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
    DialogTrigger,
} from 'reka-ui';
import { computed, ref, watch } from 'vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { t } from '@/lib/i18n';
import { mainNavItems } from '@/lib/mainNav';
import { overlayBackdrop, overlayRail } from '@/lib/overlayClasses';

const items = computed(() => mainNavItems());
const { isCurrentUrl } = useCurrentUrl();
const isOpen = ref(false);
const isRailVisible = useMediaQuery('(min-width: 48rem)');

watch(isRailVisible, (visible) => {
    if (visible) {
        isOpen.value = false;
    }
});
</script>

<template>
    <DialogRoot v-model:open="isOpen">
        <DialogTrigger
            class="inline-flex h-11 shrink-0 items-center gap-2 px-4 font-mono text-label uppercase outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-inset md:hidden"
            data-test="board-menu"
        >
            <Menu class="size-4" aria-hidden="true" />
            {{ t('ui.nav.menu') }}
        </DialogTrigger>

        <DialogPortal>
            <DialogOverlay :class="overlayBackdrop" />
            <DialogContent :class="overlayRail">
                <DialogTitle
                    class="border-b border-border px-4 py-3 font-mono text-label uppercase"
                >
                    {{ t('ui.nav.main') }}
                </DialogTitle>

                <nav class="grid content-start">
                    <DialogClose
                        v-for="item in items"
                        :key="item.title"
                        as-child
                    >
                        <Link
                            :href="item.href"
                            :aria-current="
                                isCurrentUrl(item.href) ? 'page' : undefined
                            "
                            class="flex min-h-13 items-center border-b border-l-2 border-border-soft border-l-transparent px-4 font-mono text-label uppercase outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-inset aria-[current=page]:border-l-primary aria-[current=page]:bg-accent aria-[current=page]:text-primary"
                        >
                            {{ item.title }}
                        </Link>
                    </DialogClose>
                </nav>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
