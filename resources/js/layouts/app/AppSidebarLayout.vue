<script setup lang="ts">
import AppBottomNav from '@/components/AppBottomNav.vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell>
        <AppSidebar />
        <AppContent>
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <div
                scroll-region
                class="min-h-0 flex-1 overflow-y-auto overscroll-contain"
            >
                <slot />
            </div>
            <AppBottomNav class="md:hidden" />
        </AppContent>
        <Toaster position="top-center" />
    </AppShell>
</template>
