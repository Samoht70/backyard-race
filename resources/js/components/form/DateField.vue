<script setup lang="ts">
import { CalendarDays } from '@lucide/vue';
import { DateFieldInput, DateFieldRoot } from 'reka-ui';
import { computed } from 'vue';
import { fieldSegment, fieldShell } from '@/lib/fieldClasses';
import { toCalendarDate } from '@/lib/temporal';
import { cn } from '@/lib/utils';

const props = defineProps<{
    id?: string;
    name?: string;
    defaultValue?: string;
    required?: boolean;
    class?: string;
}>();

const initial = computed(() => toCalendarDate(props.defaultValue));
</script>

<template>
    <DateFieldRoot
        v-slot="{ segments }"
        :id="id"
        :name="name"
        :default-value="initial"
        :required="required"
        granularity="day"
        locale="fr-FR"
        :class="cn(fieldShell, 'items-center gap-px px-3', props.class)"
    >
        <DateFieldInput
            v-for="(segment, index) in segments"
            :key="index"
            :part="segment.part"
            :class="
                segment.part === 'literal'
                    ? 'text-muted-foreground'
                    : fieldSegment
            "
        >
            {{ segment.value }}
        </DateFieldInput>

        <CalendarDays
            class="ml-auto size-4 shrink-0 text-muted-foreground"
            aria-hidden="true"
        />
    </DateFieldRoot>
</template>
