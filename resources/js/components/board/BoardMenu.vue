<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Menu } from '@lucide/vue';
import { useMediaQuery } from '@vueuse/core';
import { ref, watch } from 'vue';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { t } from '@/lib/i18n';
import { mainNavItems } from '@/lib/mainNav';

const items = mainNavItems();
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
    <Sheet v-model:open="isOpen">
        <SheetTrigger
            class="inline-flex h-11 shrink-0 items-center gap-2 px-4 font-mono text-label uppercase outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-inset md:hidden"
            data-test="board-menu"
        >
            <Menu class="size-4" aria-hidden="true" />
            {{ t('ui.nav.menu') }}
        </SheetTrigger>

        <SheetContent side="left" class="w-full max-w-80 gap-0 p-0">
            <SheetHeader class="border-b border-border px-4 py-3">
                <SheetTitle class="font-mono text-label uppercase">
                    {{ t('ui.nav.main') }}
                </SheetTitle>
            </SheetHeader>

            <nav class="grid content-start">
                <SheetClose v-for="item in items" :key="item.title" as-child>
                    <Link
                        :href="item.href"
                        :aria-current="
                            isCurrentUrl(item.href) ? 'page' : undefined
                        "
                        class="flex min-h-13 items-center border-b border-l-2 border-border-soft border-l-transparent px-4 font-mono text-label uppercase outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-inset aria-[current=page]:border-l-foreground aria-[current=page]:bg-accent"
                    >
                        {{ item.title }}
                    </Link>
                </SheetClose>
            </nav>
        </SheetContent>
    </Sheet>
</template>
