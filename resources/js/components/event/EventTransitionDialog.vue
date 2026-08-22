<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import {
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogOverlay,
    AlertDialogPortal,
    AlertDialogRoot,
    AlertDialogTitle,
    AlertDialogTrigger,
} from 'reka-ui';
import { computed } from 'vue';
import ActionButton from '@/components/ActionButton.vue';
import AlertError from '@/components/AlertError.vue';
import FieldError from '@/components/form/FieldError.vue';
import { t } from '@/lib/i18n';
import {
    overlayBackdrop,
    overlayDescription,
    overlayFooter,
    overlayPanel,
    overlayTitle,
} from '@/lib/overlayClasses';
import type { EventStatus } from '@/types/event';
import type { RouteFormDefinition } from '@/wayfinder';

type Props = {
    action: RouteFormDefinition<'post'>;
    to: EventStatus;
    label: string;
    description: string;
    refusals: string[];
    refusalsTitle: string;
    tone?: 'primary' | 'quiet';
};

const props = withDefaults(defineProps<Props>(), { tone: 'primary' });

const isBlocked = computed(() => props.refusals.length > 0);
const refusalsId = computed(() => `transition-refusals-${props.to}`);
</script>

<template>
    <div class="grid gap-4">
        <AlertError
            v-if="isBlocked"
            :id="refusalsId"
            :title="refusalsTitle"
            :errors="refusals"
        />

        <AlertDialogRoot>
            <AlertDialogTrigger as-child>
                <ActionButton
                    :tone="tone"
                    :disabled="isBlocked"
                    :aria-describedby="isBlocked ? refusalsId : undefined"
                >
                    {{ label }}
                </ActionButton>
            </AlertDialogTrigger>

            <AlertDialogPortal>
                <AlertDialogOverlay :class="overlayBackdrop" />
                <AlertDialogContent :class="overlayPanel">
                    <Form
                        v-bind="action"
                        :options="{ preserveScroll: true }"
                        class="flex flex-col gap-4"
                        v-slot="{ errors, processing }"
                    >
                        <input type="hidden" name="to" :value="to" />

                        <AlertDialogTitle :class="overlayTitle">
                            {{ label }}
                        </AlertDialogTitle>
                        <AlertDialogDescription :class="overlayDescription">
                            {{ description }}
                        </AlertDialogDescription>

                        <FieldError :message="errors.to" />

                        <div :class="overlayFooter">
                            <AlertDialogCancel as-child>
                                <ActionButton tone="quiet">
                                    {{ t('event.transition.cancel') }}
                                </ActionButton>
                            </AlertDialogCancel>
                            <AlertDialogAction as-child>
                                <ActionButton
                                    type="submit"
                                    :loading="processing"
                                >
                                    {{ t('event.transition.confirm') }}
                                </ActionButton>
                            </AlertDialogAction>
                        </div>
                    </Form>
                </AlertDialogContent>
            </AlertDialogPortal>
        </AlertDialogRoot>
    </div>
</template>
