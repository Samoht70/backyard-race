<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Undo2 } from '@lucide/vue';
import {
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
    DialogTrigger,
    Label,
} from 'reka-ui';
import { computed } from 'vue';
import LapReinstatementController from '@/actions/App/Http/Controllers/Manage/LapReinstatementController';
import ActionButton from '@/components/ActionButton.vue';
import FieldError from '@/components/form/FieldError.vue';
import TimeField from '@/components/form/TimeField.vue';
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

const errorBag = computed(() => `lap-${props.lap.lap_id}-reinstatement`);

const fieldId = computed(() => `finished-at-${props.lap.lap_id}`);
</script>

<template>
    <DialogRoot>
        <DialogTrigger as-child>
            <ActionButton
                class="w-auto max-sm:gap-0"
                :icon="Undo2"
                :aria-label="
                    t('race.correction.reinstate_aria', {
                        name: runnerName,
                        number: lap.round_number,
                    })
                "
            >
                <span class="hidden sm:inline">
                    {{ t('race.correction.reinstate_open') }}
                </span>
            </ActionButton>
        </DialogTrigger>

        <DialogPortal>
            <DialogOverlay :class="overlayBackdrop" />
            <DialogContent :class="overlayPanel">
                <Form
                    v-bind="LapReinstatementController.form(lap.lap_id)"
                    :error-bag="errorBag"
                    :options="{ preserveScroll: true }"
                    class="flex flex-col gap-4"
                    v-slot="{ errors, processing }"
                >
                    <DialogTitle :class="overlayTitle">
                        {{
                            t('race.correction.reinstate_title', {
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

                    <div class="grid gap-2 sm:max-w-40">
                        <Label
                            :for="fieldId"
                            class="font-mono text-label text-muted-foreground uppercase"
                        >
                            {{ t('race.correction.reinstate_field') }}
                        </Label>
                        <TimeField
                            :id="fieldId"
                            name="finished_at"
                            :default-value="lap.round_deadline_at"
                            required
                        />
                    </div>

                    <DialogDescription :class="overlayDescription">
                        {{ t('race.correction.reinstate_consequence') }}
                    </DialogDescription>

                    <FieldError :message="errors.finished_at" />
                    <FieldError :message="errors.lap" />

                    <div :class="overlayFooter">
                        <DialogClose as-child>
                            <ActionButton tone="quiet">
                                {{ t('race.correction.keep') }}
                            </ActionButton>
                        </DialogClose>
                        <ActionButton type="submit" :loading="processing">
                            {{ t('race.correction.reinstate_submit') }}
                        </ActionButton>
                    </div>
                </Form>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
