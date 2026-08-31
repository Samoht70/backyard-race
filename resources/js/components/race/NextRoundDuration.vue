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
    <section class="grid gap-4 rounded-sm border border-border bg-card p-4">
        <h2
            class="flex items-center gap-2 font-mono text-label text-muted-foreground uppercase"
        >
            <Timer class="size-4 shrink-0" aria-hidden="true" />
            {{ t('race.duration.title') }}
        </h2>

        <Form
            v-bind="RoundDurationController.form()"
            :options="{ preserveScroll: true }"
            class="grid gap-4"
            v-slot="{ errors, processing }"
        >
            <input type="hidden" name="from" :value="props.round.number" />

            <div class="grid gap-2 sm:grid-cols-[auto_10rem] sm:items-end">
                <p class="font-mono text-sm text-muted-foreground">
                    {{
                        t('race.duration.next_round', {
                            number: props.round.number,
                            start: props.round.starts_at,
                        })
                    }}
                </p>

                <div class="grid gap-2">
                    <label
                        for="lap_duration_minutes"
                        class="font-mono text-label text-muted-foreground uppercase"
                    >
                        {{ t('race.duration.field') }}
                        <span class="normal-case">
                            ({{ t('event.unit.minutes') }})
                        </span>
                    </label>
                    <NumberField
                        id="lap_duration_minutes"
                        name="lap_duration_minutes"
                        :default-value="props.round.lap_duration_minutes"
                        :min="1"
                        :max="1440"
                        required
                    />
                </div>
            </div>

            <p class="text-sm text-muted-foreground">
                {{ t('race.duration.hint') }}
            </p>

            <FieldError :message="errors.from" />
            <FieldError :message="errors.lap_duration_minutes" />

            <div class="flex flex-col gap-2 sm:flex-row">
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
        </Form>
    </section>
</template>
