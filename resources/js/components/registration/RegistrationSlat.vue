<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Primitive } from 'reka-ui';
import type { Component } from 'vue';
import { computed } from 'vue';
import { t } from '@/lib/i18n';
import {
    registrationStatusBar,
    registrationStatusIcons,
    registrationStatusLabelKey,
    registrationStatusTone,
} from '@/lib/registrationStatus';
import type { RegistrationStatus } from '@/types/registration';

type Props = {
    bib: string | null;
    firstName: string;
    lastName: string;
    status: RegistrationStatus;
    href?: NonNullable<InertiaLinkProps['href']>;
    as?: string | Component;
    active?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    as: 'div',
    active: false,
});

const fullName = computed(() => `${props.firstName} ${props.lastName}`);
const icon = computed(() => registrationStatusIcons[props.status]);
const tone = computed(() => registrationStatusTone[props.status]);
const bar = computed(() => registrationStatusBar[props.status]);
const statusLabel = computed(() => t(registrationStatusLabelKey(props.status)));
const element = computed(() => (props.href ? Link : props.as));
const isInteractive = computed(
    () => props.href !== undefined || props.as !== 'div',
);
</script>

<template>
    <Primitive
        :as="element"
        :href="href"
        :aria-current="active ? 'true' : undefined"
        :aria-label="
            isInteractive
                ? t('registration.manage.open_runner', { name: fullName })
                : undefined
        "
        class="flex min-h-[4.25rem] w-full min-w-0 touch-manipulation items-center gap-3 overflow-hidden rounded-sm border border-border bg-card py-2.5 pr-3 text-left outline-none"
        :class="[
            active && 'border-foreground bg-accent',
            isInteractive &&
                'transition-colors hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-inset',
        ]"
    >
        <span
            class="w-1 shrink-0 self-stretch"
            :class="bar"
            aria-hidden="true"
        />
        <span class="w-9 shrink-0 font-mono text-data font-bold tabular-nums">
            {{ bib ?? '—' }}
        </span>

        <span class="flex min-w-0 flex-1 flex-col gap-px">
            <span class="truncate font-semibold">{{ fullName }}</span>
            <span
                class="flex min-w-0 items-center gap-1.5 font-mono text-data text-muted-foreground"
            >
                <component
                    :is="icon"
                    class="size-3 shrink-0"
                    :class="tone"
                    aria-hidden="true"
                />
                <span class="truncate">{{ statusLabel }}</span>
            </span>
        </span>

        <slot name="cell" />
    </Primitive>
</template>
