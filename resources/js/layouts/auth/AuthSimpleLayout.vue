<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import AppLogo from '@/components/AppLogo.vue';
import { t } from '@/lib/i18n';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
    >
        <Link
            :href="home()"
            class="fixed top-[max(0.5rem,env(safe-area-inset-top))] left-2 z-50 flex min-h-11 touch-manipulation items-center gap-2 rounded-sm px-3 font-mono text-label text-muted-foreground uppercase transition-colors hover:bg-accent hover:text-accent-foreground"
        >
            <ArrowLeft class="size-4 shrink-0" aria-hidden="true" />
            {{ t('ui.nav.back_to_event') }}
        </Link>

        <AppearanceToggle
            class="fixed top-[max(0.5rem,env(safe-area-inset-top))] right-2 z-50"
        />
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link :href="home()" class="mb-1 flex items-center">
                        <AppLogo class="flex" />
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="grid gap-2 text-center">
                        <h1 class="text-xl font-medium">{{ title }}</h1>
                        <p class="text-center text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
