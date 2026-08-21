<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { Check, Copy } from '@lucide/vue';
import { useClipboard } from '@vueuse/core';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { t } from '@/lib/i18n';
import { login } from '@/routes';

const { code } = defineProps<{
    code: string;
}>();

const { copy, copied, isSupported } = useClipboard({ source: code });

setLayoutProps({
    title: t('auth.register.code.title'),
    description: t('auth.register.code.description'),
});
</script>

<template>
    <Head :title="t('auth.register.code.title')" />

    <div class="flex flex-col gap-6">
        <p
            class="rounded-lg border border-dashed border-primary/40 bg-primary/5 py-6 text-center font-mono text-2xl font-semibold tracking-[0.2em] text-primary select-all"
            data-test="access-code"
        >
            {{ code }}
        </p>

        <Button
            v-if="isSupported"
            type="button"
            variant="outline"
            class="w-full"
            @click="copy()"
        >
            <component :is="copied ? Check : Copy" class="size-4" />
            {{
                copied
                    ? t('auth.register.code.copied')
                    : t('auth.register.code.copy')
            }}
        </Button>

        <div
            class="space-y-1 rounded-lg border border-red-100 bg-red-50 p-4 text-red-600 dark:border-red-200/10 dark:bg-red-700/10 dark:text-red-100"
        >
            <p class="font-medium">
                {{ t('auth.register.code.warning_title') }}
            </p>
            <p class="text-sm">{{ t('auth.register.code.warning') }}</p>
        </div>

        <TextLink :href="login()" class="text-center">{{
            t('auth.register.code.login')
        }}</TextLink>
    </div>
</template>
