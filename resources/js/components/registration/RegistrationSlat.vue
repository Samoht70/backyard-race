<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { computed } from 'vue';
import { t } from '@/lib/i18n';
import {
    registrationStatusIcons,
    registrationStatusLabelKey,
    registrationStatusTone,
} from '@/lib/registrationStatus';
import type { RegistrationStatus } from '@/types/registration';

type Props = {
    firstName: string;
    lastName: string;
    status: RegistrationStatus;
    href?: NonNullable<InertiaLinkProps['href']>;
};

const props = defineProps<Props>();

const fullName = computed(() => `${props.firstName} ${props.lastName}`);
const icon = computed(() => registrationStatusIcons[props.status]);
const tone = computed(() => registrationStatusTone[props.status]);
const statusLabel = computed(() => t(registrationStatusLabelKey(props.status)));
</script>

<template>
    <div
        class="flex min-h-[4.25rem] min-w-0 items-center gap-2 bg-card px-3 py-2.5"
    >
        <component
            :is="href ? Link : 'div'"
            :href="href"
            :aria-label="
                href
                    ? t('registration.manage.open_runner', { name: fullName })
                    : undefined
            "
            class="flex min-h-11 min-w-0 flex-1 touch-manipulation flex-col justify-center gap-px"
        >
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
        </component>

        <slot name="cell" />
    </div>
</template>
