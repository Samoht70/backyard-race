<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { CircleSlash } from '@lucide/vue';
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
import LapReversionController from '@/actions/App/Http/Controllers/Manage/LapReversionController';
import ActionButton from '@/components/ActionButton.vue';
import FieldError from '@/components/form/FieldError.vue';
import { t } from '@/lib/i18n';
import {
    overlayBackdrop,
    overlayDescription,
    overlayFooter,
    overlayPanel,
    overlayTitle,
} from '@/lib/overlayClasses';
import type { CorrectableLap } from '@/types/race';

type Props = {
    lap: CorrectableLap;
};

const props = defineProps<Props>();

const runnerName = computed(
    () => `${props.lap.first_name} ${props.lap.last_name}`,
);

const errorBag = computed(() => `lap-${props.lap.lap_id}-reversion`);
</script>

<template>
    <DialogRoot>
        <DialogTrigger as-child>
            <ActionButton
                tone="quiet"
                class="w-auto max-sm:gap-0"
                :icon="CircleSlash"
                :aria-label="
                    t('race.correction.revert_aria', {
                        name: runnerName,
                        number: lap.round_number,
                    })
                "
            >
                <span class="hidden sm:inline">
                    {{ t('race.correction.revert_open') }}
                </span>
            </ActionButton>
        </DialogTrigger>

        <DialogPortal>
            <DialogOverlay :class="overlayBackdrop" />
            <DialogContent :class="overlayPanel">
                <Form
                    v-bind="LapReversionController.form(lap.lap_id)"
                    :error-bag="errorBag"
                    :options="{ preserveScroll: true }"
                    class="flex flex-col gap-4"
                    v-slot="{ errors, processing }"
                >
                    <DialogTitle :class="overlayTitle">
                        {{
                            t('race.correction.revert_title', {
                                name: runnerName,
                                number: lap.round_number,
                            })
                        }}
                    </DialogTitle>

                    <p class="font-mono text-data tabular-nums">
                        {{
                            t('race.correction.reinstate_window', {
                                start: lap.round_starts_at,
                                deadline: lap.round_deadline_at,
                            })
                        }}
                    </p>

                    <DialogDescription :class="overlayDescription">
                        {{ t('race.correction.revert_consequence') }}
                    </DialogDescription>

                    <FieldError :message="errors.lap" />

                    <div :class="overlayFooter">
                        <DialogClose as-child>
                            <ActionButton tone="quiet">
                                {{ t('race.correction.keep') }}
                            </ActionButton>
                        </DialogClose>
                        <ActionButton
                            type="submit"
                            tone="danger"
                            :loading="processing"
                        >
                            {{ t('race.correction.revert_submit') }}
                        </ActionButton>
                    </div>
                </Form>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
