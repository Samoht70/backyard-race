<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ActionButton from '@/components/race/ActionButton.vue';
import FestoonDivider from '@/components/race/FestoonDivider.vue';
import { dashboard, login, register } from '@/routes';
</script>

<template>
    <Head title="Backyard Race" />

    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background px-6 py-12 text-foreground"
    >
        <div class="flex w-full max-w-sm flex-col items-center gap-1">
            <p class="font-display text-label text-muted-foreground uppercase">
                Backyard Ultra
            </p>
            <h1 class="text-center font-display text-lap">Backyard Race</h1>
        </div>

        <FestoonDivider />

        <div class="flex w-full max-w-xs flex-col gap-2">
            <Link v-if="$page.props.auth.user" :href="dashboard()">
                <ActionButton>Ouvrir la course</ActionButton>
            </Link>
            <template v-else>
                <Link :href="login()">
                    <ActionButton>Se connecter</ActionButton>
                </Link>
                <Link :href="register()">
                    <ActionButton tone="quiet">S’inscrire</ActionButton>
                </Link>
            </template>
        </div>
    </div>
</template>
