<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { LogOut, UserRound } from '@lucide/vue';
import {
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuPortal,
    DropdownMenuRoot,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from 'reka-ui';
import { computed } from 'vue';
import { useInitials } from '@/composables/useInitials';
import { t } from '@/lib/i18n';
import { overlayMenu, overlayMenuItem } from '@/lib/overlayClasses';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';

const user = computed(() => usePage().props.auth.user);
const { getInitials } = useInitials();

function flushBeforeLogout(): void {
    router.flushAll();
}
</script>

<template>
    <DropdownMenuRoot v-if="user">
        <DropdownMenuTrigger
            class="flex size-8 shrink-0 items-center justify-center border border-border font-mono text-label uppercase transition-colors outline-none hover:border-primary hover:text-primary focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background data-[state=open]:border-primary data-[state=open]:bg-primary data-[state=open]:text-primary-foreground"
            :aria-label="t('ui.board.account')"
            data-test="board-account"
        >
            {{ getInitials(user.name) }}
        </DropdownMenuTrigger>

        <DropdownMenuPortal>
            <DropdownMenuContent
                :class="overlayMenu"
                align="end"
                :side-offset="6"
            >
                <DropdownMenuLabel class="grid gap-0.5 px-3 py-2">
                    <span class="truncate text-sm font-medium">
                        {{ user.name }}
                    </span>
                    <span class="truncate text-xs text-muted-foreground">
                        {{ user.email }}
                    </span>
                </DropdownMenuLabel>

                <DropdownMenuSeparator class="my-1 h-px bg-border" />

                <DropdownMenuItem as-child>
                    <Link :class="overlayMenuItem" :href="edit()" prefetch>
                        <UserRound class="size-4" aria-hidden="true" />
                        {{ t('ui.nav.profile') }}
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuItem as-child>
                    <Link
                        :class="overlayMenuItem"
                        :href="logout()"
                        as="button"
                        data-test="logout-button"
                        @click="flushBeforeLogout"
                    >
                        <LogOut class="size-4" aria-hidden="true" />
                        {{ t('ui.nav.logout') }}
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenuPortal>
    </DropdownMenuRoot>
</template>
