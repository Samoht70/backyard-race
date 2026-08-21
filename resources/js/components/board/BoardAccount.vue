<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useInitials } from '@/composables/useInitials';
import { t } from '@/lib/i18n';

const user = computed(() => usePage().props.auth.user);
const { getInitials } = useInitials();
</script>

<template>
    <DropdownMenu v-if="user">
        <DropdownMenuTrigger
            class="flex size-8 shrink-0 items-center justify-center border border-border font-mono text-label uppercase outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
            :aria-label="t('ui.board.account')"
            data-test="board-account"
        >
            {{ getInitials(user.name) }}
        </DropdownMenuTrigger>
        <DropdownMenuContent class="min-w-56" align="end" :side-offset="6">
            <UserMenuContent :user="user" />
        </DropdownMenuContent>
    </DropdownMenu>
</template>
