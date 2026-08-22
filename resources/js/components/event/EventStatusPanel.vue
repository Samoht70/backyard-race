<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Check } from '@lucide/vue';
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
import AdvanceEventController from '@/actions/App/Http/Controllers/Manage/AdvanceEventController';
import ActionButton from '@/components/ActionButton.vue';
import AlertError from '@/components/AlertError.vue';
import EventStatusBadge from '@/components/event/EventStatusBadge.vue';
import FieldError from '@/components/form/FieldError.vue';
import {
    eventStatusIcons,
    eventStatusLabelKey,
    eventTransitionLabelKey,
} from '@/lib/eventStatus';
import { t } from '@/lib/i18n';
import {
    overlayBackdrop,
    overlayDescription,
    overlayFooter,
    overlayPanel,
    overlayTitle,
} from '@/lib/overlayClasses';
import { EVENT_STATUSES } from '@/types/event';
import type { EventTransition } from '@/types/event';

type Props = {
    transition: EventTransition;
};

const props = defineProps<Props>();

const steps = computed(() => {
    const reached = EVENT_STATUSES.indexOf(props.transition.current);

    return EVENT_STATUSES.map((status, position) => ({
        status,
        done: position < reached,
        current: position === reached,
    }));
});

const isBlocked = computed(() => props.transition.refusals.length > 0);
</script>

<template>
    <section class="grid gap-4 rounded-sm border border-border bg-card p-4">
        <h2 class="font-mono text-label text-muted-foreground uppercase">
            {{ t('event.status.section_title') }}
        </h2>

        <div class="flex flex-col gap-4">
            <ol class="flex flex-col gap-2">
                <li
                    v-for="step in steps"
                    :key="step.status"
                    class="flex items-center gap-3"
                    :aria-current="step.current ? 'step' : undefined"
                >
                    <Check
                        v-if="step.done"
                        class="size-4 shrink-0 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <component
                        :is="eventStatusIcons[step.status]"
                        v-else
                        class="size-4 shrink-0"
                        :class="
                            step.current
                                ? 'text-foreground'
                                : 'text-muted-foreground/60'
                        "
                        aria-hidden="true"
                    />
                    <span
                        class="text-sm"
                        :class="
                            step.current
                                ? 'font-medium text-foreground'
                                : 'text-muted-foreground'
                        "
                    >
                        {{ t(eventStatusLabelKey(step.status)) }}
                    </span>
                    <EventStatusBadge
                        v-if="step.current"
                        :status="step.status"
                        size="sm"
                        class="ml-auto"
                    />
                    <span
                        v-else
                        class="ml-auto font-mono text-label text-muted-foreground uppercase"
                    >
                        {{
                            step.done
                                ? t('event.step.done')
                                : t('event.step.todo')
                        }}
                    </span>
                </li>
            </ol>

            <p v-if="!transition.next" class="text-sm text-muted-foreground">
                {{ t('event.transition.none') }}
            </p>

            <template v-else>
                <AlertError
                    v-if="isBlocked"
                    id="transition-refusals"
                    :title="t('event.transition.blocked_title')"
                    :errors="transition.refusals"
                />

                <AlertDialogRoot>
                    <AlertDialogTrigger as-child>
                        <ActionButton
                            :disabled="isBlocked"
                            :aria-describedby="
                                isBlocked ? 'transition-refusals' : undefined
                            "
                        >
                            {{ t(eventTransitionLabelKey(transition.next)) }}
                        </ActionButton>
                    </AlertDialogTrigger>

                    <AlertDialogPortal>
                        <AlertDialogOverlay :class="overlayBackdrop" />
                        <AlertDialogContent :class="overlayPanel">
                            <Form
                                v-bind="AdvanceEventController.form()"
                                :options="{ preserveScroll: true }"
                                class="flex flex-col gap-4"
                                v-slot="{ errors, processing }"
                            >
                                <input
                                    type="hidden"
                                    name="to"
                                    :value="transition.next"
                                />

                                <AlertDialogTitle :class="overlayTitle">
                                    {{
                                        t(
                                            eventTransitionLabelKey(
                                                transition.next,
                                            ),
                                        )
                                    }}
                                </AlertDialogTitle>
                                <AlertDialogDescription
                                    :class="overlayDescription"
                                >
                                    {{
                                        t(
                                            'event.transition.confirm_irreversible',
                                        )
                                    }}
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
            </template>
        </div>
    </section>
</template>
