<script setup lang="ts">
import { Minus, Plus } from '@lucide/vue';
import {
    NumberFieldDecrement,
    NumberFieldIncrement,
    NumberFieldInput,
    NumberFieldRoot,
} from 'reka-ui';
import { fieldShell, fieldStepper } from '@/lib/fieldClasses';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        id?: string;
        name?: string;
        defaultValue?: number;
        min?: number;
        max?: number;
        step?: number;
        fractionDigits?: number;
        required?: boolean;
        class?: string;
    }>(),
    {
        step: 1,
        fractionDigits: 0,
    },
);
</script>

<template>
    <NumberFieldRoot
        :id="id"
        :name="name"
        :default-value="defaultValue"
        :min="min"
        :max="max"
        :step="step"
        :required="required"
        :format-options="{
            useGrouping: false,
            maximumFractionDigits: fractionDigits,
        }"
        locale="fr-FR"
        :class="cn(fieldShell, props.class)"
    >
        <NumberFieldInput
            class="w-full min-w-0 bg-transparent px-3 tabular-nums outline-none"
        />
        <NumberFieldDecrement
            :class="fieldStepper"
            :aria-label="t('ui.field.decrease')"
        >
            <Minus class="size-4" aria-hidden="true" />
        </NumberFieldDecrement>
        <NumberFieldIncrement
            :class="fieldStepper"
            :aria-label="t('ui.field.increase')"
        >
            <Plus class="size-4" aria-hidden="true" />
        </NumberFieldIncrement>
    </NumberFieldRoot>
</template>
