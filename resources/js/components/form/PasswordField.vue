<script setup lang="ts">
import { Eye, EyeOff } from '@lucide/vue';
import { ref, useTemplateRef } from 'vue';
import { fieldShell, fieldStepper } from '@/lib/fieldClasses';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    class?: string;
}>();

const isRevealed = ref(false);
const inputRef = useTemplateRef<HTMLInputElement>('inputRef');

defineExpose({
    $el: inputRef,
    focus: () => inputRef.value?.focus(),
});
</script>

<template>
    <div :class="cn(fieldShell, props.class)">
        <input
            ref="inputRef"
            :type="isRevealed ? 'text' : 'password'"
            class="w-full min-w-0 bg-transparent px-3 caret-primary outline-none placeholder:text-muted-foreground"
            v-bind="$attrs"
        />
        <button
            type="button"
            :class="fieldStepper"
            :aria-label="isRevealed ? t('ui.field.hide') : t('ui.field.reveal')"
            :tabindex="-1"
            @click="isRevealed = !isRevealed"
        >
            <EyeOff v-if="isRevealed" class="size-4" aria-hidden="true" />
            <Eye v-else class="size-4" aria-hidden="true" />
        </button>
    </div>
</template>
