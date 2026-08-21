<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    NavigationMenuRoot,
} from 'reka-ui';
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import BoardAccount from '@/components/board/BoardAccount.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { t } from '@/lib/i18n';
import { mainNavItems } from '@/lib/mainNav';

const items = mainNavItems();
const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <div
        class="flex shrink-0 items-stretch border-b border-border sm:px-6 lg:px-8"
    >
        <NavigationMenuRoot
            orientation="horizontal"
            class="min-w-0 flex-1"
            :aria-label="t('ui.nav.main')"
        >
            <NavigationMenuList class="scroll-rail flex items-stretch">
                <NavigationMenuItem
                    v-for="item in items"
                    :key="item.title"
                    class="shrink-0"
                >
                    <NavigationMenuLink
                        as-child
                        :active="isCurrentUrl(item.href)"
                        class="inline-flex h-11 items-center border-b-2 border-transparent px-4 font-mono text-label whitespace-nowrap text-muted-foreground uppercase outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-inset data-[active]:border-foreground data-[active]:text-foreground"
                    >
                        <Link :href="item.href">{{ item.title }}</Link>
                    </NavigationMenuLink>
                </NavigationMenuItem>
            </NavigationMenuList>
        </NavigationMenuRoot>

        <div class="flex shrink-0 items-center gap-1 pr-2 pl-3 sm:pr-0">
            <AppearanceToggle />
            <BoardAccount />
        </div>
    </div>
</template>
