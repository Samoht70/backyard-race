<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Skull } from '@lucide/vue';
import {
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
    DialogTrigger,
} from 'reka-ui';
import { computed } from 'vue';
import RunnerWithdrawalController from '@/actions/App/Http/Controllers/Manage/RunnerWithdrawalController';
import ActionButton from '@/components/ActionButton.vue';
import FieldError from '@/components/form/FieldError.vue';
import { formatKilometers } from '@/lib/distance';
import { t } from '@/lib/i18n';
import {
    overlayBackdrop,
    overlayDescription,
    overlayFooter,
    overlayPanel,
    overlayTitle,
} from '@/lib/overlayClasses';

type Props = {
    runnerId: number;
    runnerName: string;
    validatedLaps: number;
    coveredMeters: number | null;
};

const props = defineProps<Props>();

const errorBag = computed(() => `runner-${props.runnerId}-withdrawal`);

const recap = computed(() => {
    if (props.validatedLaps === 0) {
        return t('race.withdrawal.no_lap');
    }

    const parts = [
        t('race.withdrawal.last_lap', { number: props.validatedLaps }),
    ];
    const kilometers = formatKilometers(props.coveredMeters);

    if (kilometers !== null) {
        parts.push(`${kilometers} ${t('event.unit.kilometers')}`);
    }

    return parts.join(' · ');
});
</script>

<template>
    <DialogRoot>
        <DialogTrigger as-child>
            <ActionButton
                tone="quiet"
                class="w-auto max-sm:gap-0"
                :icon="Skull"
                :aria-label="
                    t('race.withdrawal.aria_open', { name: runnerName })
                "
            >
                <span class="hidden sm:inline">
                    {{ t('race.withdrawal.open') }}
                </span>
            </ActionButton>
        </DialogTrigger>

        <DialogPortal>
            <DialogOverlay :class="overlayBackdrop" />
            <DialogContent :class="overlayPanel">
                <Form
                    v-bind="RunnerWithdrawalController.form(runnerId)"
                    :error-bag="errorBag"
                    :options="{ preserveScroll: true }"
                    class="flex flex-col gap-4"
                    v-slot="{ errors, processing }"
                >
                    <DialogTitle :class="overlayTitle">
                        {{
                            t('race.withdrawal.confirm_title', {
                                name: runnerName,
                            })
                        }}
                    </DialogTitle>

                    <p class="font-mono text-data tabular-nums">
                        {{ recap }}
                    </p>

                    <DialogDescription :class="overlayDescription">
                        {{ t('race.withdrawal.consequence') }}
                    </DialogDescription>

                    <FieldError :message="errors.runner" />

                    <div :class="overlayFooter">
                        <DialogClose as-child>
                            <ActionButton tone="quiet">
                                {{ t('race.withdrawal.keep') }}
                            </ActionButton>
                        </DialogClose>
                        <ActionButton
                            type="submit"
                            tone="danger"
                            :loading="processing"
                        >
                            {{ t('race.withdrawal.submit') }}
                        </ActionButton>
                    </div>
                </Form>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
