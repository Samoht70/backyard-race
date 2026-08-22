<script setup lang="ts">
import { Clock } from '@lucide/vue';
import { TimeFieldInput, TimeFieldRoot } from 'reka-ui';
import { computed } from 'vue';
import { fieldSegment, fieldShell } from '@/lib/fieldClasses';
import { toTime } from '@/lib/temporal';
import { cn } from '@/lib/utils';

const props = defineProps<{
    id?: string;
    name?: string;
    defaultValue?: string;
    required?: boolean;
    class?: string;
}>();

const initial = computed(() => toTime(props.defaultValue));
</script>

<template>
    <TimeFieldRoot
        v-slot="{ segments }"
        :id="id"
        :name="name"
        :default-value="initial"
        :required="required"
        granularity="minute"
        :hour-cycle="24"
        locale="fr-FR"
        :class="cn(fieldShell, 'items-center gap-px px-3', props.class)"
    >
        <TimeFieldInput
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
        </TimeFieldInput>

        <Clock
            class="ml-auto size-4 shrink-0 text-muted-foreground"
            aria-hidden="true"
        />
    </TimeFieldRoot>
</template>
