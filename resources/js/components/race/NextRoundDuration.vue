<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Timer } from '@lucide/vue';
import RoundDurationController from '@/actions/App/Http/Controllers/Manage/RoundDurationController';
import ActionButton from '@/components/ActionButton.vue';
import FieldError from '@/components/form/FieldError.vue';
import NumberField from '@/components/form/NumberField.vue';
import { t } from '@/lib/i18n';
import type { NextRound } from '@/types/race';

type Props = {
    round: NextRound;
};

const props = defineProps<Props>();
</script>

<template>
    <section class="grid gap-2 rounded-sm border border-border bg-card p-4">
        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
            <h2
                class="flex items-center gap-2 font-mono text-label text-muted-foreground uppercase"
            >
                <Timer class="size-4 shrink-0" aria-hidden="true" />
                {{ t('race.duration.title') }}
            </h2>

            <p class="font-mono text-sm text-muted-foreground">
                {{
                    t('race.duration.next_round', {
                        number: props.round.number,
                        start: props.round.starts_at,
                    })
                }}
                · {{ t('race.duration.hint') }}
            </p>
        </div>

        <Form
            v-bind="RoundDurationController.form()"
            :options="{ preserveScroll: true }"
            class="grid gap-2"
            v-slot="{ errors, processing }"
        >
            <input type="hidden" name="from" :value="props.round.number" />

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <label for="lap_duration_minutes" class="sr-only">
                    {{ t('race.duration.field') }}
                </label>
                <div class="flex items-center gap-2">
                    <NumberField
                        id="lap_duration_minutes"
                        name="lap_duration_minutes"
                        class="sm:w-32"
                        :default-value="props.round.lap_duration_minutes"
                        :min="1"
                        :max="1440"
                        required
                    />
                    <span class="font-mono text-sm text-muted-foreground">
                        {{ t('event.unit.minutes') }}
                    </span>
                </div>

                <ActionButton
                    type="submit"
                    name="change"
                    value="onwards"
                    :loading="processing"
                >
                    {{ t('race.duration.onwards') }}
                </ActionButton>
                <ActionButton
                    type="submit"
                    tone="quiet"
                    name="change"
                    value="single_round"
                    :loading="processing"
                >
                    {{ t('race.duration.single_round') }}
                </ActionButton>
            </div>

            <FieldError :message="errors.from" />
            <FieldError :message="errors.lap_duration_minutes" />
        </Form>
    </section>
</template>
