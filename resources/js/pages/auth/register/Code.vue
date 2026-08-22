<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { Check, Copy } from '@lucide/vue';
import { useClipboard } from '@vueuse/core';
import ActionButton from '@/components/ActionButton.vue';
import Notice from '@/components/Notice.vue';
import TextLink from '@/components/TextLink.vue';
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
            class="border border-l-4 border-primary bg-primary/5 py-6 text-center font-mono text-2xl font-bold tracking-[0.2em] text-primary select-all"
            data-test="access-code"
        >
            {{ code }}
        </p>

        <ActionButton
            v-if="isSupported"
            type="button"
            tone="quiet"
            block
            :icon="copied ? Check : Copy"
            @click="copy()"
        >
            {{
                copied
                    ? t('auth.register.code.copied')
                    : t('auth.register.code.copy')
            }}
        </ActionButton>

        <Notice tone="danger" :title="t('auth.register.code.warning_title')">
            {{ t('auth.register.code.warning') }}
        </Notice>

        <TextLink :href="login()" class="text-center">{{
            t('auth.register.code.login')
        }}</TextLink>
    </div>
</template>
