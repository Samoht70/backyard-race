<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ActionButton from '@/components/race/ActionButton.vue';
import { dashboard, login } from '@/routes';
import { create as register } from '@/routes/account';
</script>

<template>
    <Head title="Backyard Race" />

    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background px-6 py-12 text-foreground"
    >
        <div class="flex w-full max-w-sm flex-col items-center gap-1">
            <p class="font-mono text-label text-muted-foreground uppercase">
                Backyard Ultra
            </p>
            <h1 class="text-center text-3xl font-bold tracking-tight">
                Backyard Race
            </h1>
        </div>

        <div class="flex w-full max-w-xs flex-col gap-2">
            <ActionButton v-if="$page.props.auth.user" block as-child>
                <Link :href="dashboard()">Ouvrir la course</Link>
            </ActionButton>
            <template v-else>
                <ActionButton block as-child>
                    <Link :href="login()">Se connecter</Link>
                </ActionButton>
                <ActionButton tone="quiet" block as-child>
                    <Link :href="register()">S’inscrire</Link>
                </ActionButton>
            </template>
        </div>
    </div>
</template>
