<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Check } from '@lucide/vue';
import { computed } from 'vue';
import AdvanceEventController from '@/actions/App/Http/Controllers/Manage/AdvanceEventController';
import AlertError from '@/components/AlertError.vue';
import EventStatusBadge from '@/components/event/EventStatusBadge.vue';
import InputError from '@/components/InputError.vue';
import ActionButton from '@/components/race/ActionButton.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    eventStatusIcons,
    eventStatusLabelKey,
    eventTransitionLabelKey,
} from '@/lib/eventStatus';
import { t } from '@/lib/i18n';
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
    <Card>
        <CardHeader>
            <CardTitle class="font-mono text-label uppercase">
                {{ t('event.status.section_title') }}
            </CardTitle>
        </CardHeader>

        <CardContent class="flex flex-col gap-4">
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

                <Dialog>
                    <DialogTrigger as-child>
                        <ActionButton
                            :disabled="isBlocked"
                            :aria-describedby="
                                isBlocked ? 'transition-refusals' : undefined
                            "
                        >
                            {{ t(eventTransitionLabelKey(transition.next)) }}
                        </ActionButton>
                    </DialogTrigger>

                    <DialogContent>
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

                            <DialogHeader class="space-y-3">
                                <DialogTitle>
                                    {{
                                        t(
                                            eventTransitionLabelKey(
                                                transition.next,
                                            ),
                                        )
                                    }}
                                </DialogTitle>
                                <DialogDescription>
                                    {{
                                        t(
                                            'event.transition.confirm_irreversible',
                                        )
                                    }}
                                </DialogDescription>
                            </DialogHeader>

                            <InputError :message="errors.to" />

                            <DialogFooter class="gap-2">
                                <DialogClose as-child>
                                    <ActionButton tone="quiet">
                                        {{ t('event.transition.cancel') }}
                                    </ActionButton>
                                </DialogClose>
                                <ActionButton
                                    type="submit"
                                    :loading="processing"
                                >
                                    {{ t('event.transition.confirm') }}
                                </ActionButton>
                            </DialogFooter>
                        </Form>
                    </DialogContent>
                </Dialog>
            </template>
        </CardContent>
    </Card>
</template>
