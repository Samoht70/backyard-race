<script setup lang="ts">
import { Check } from '@lucide/vue';
import { computed } from 'vue';
import AdvanceEventController from '@/actions/App/Http/Controllers/Manage/AdvanceEventController';
import RevertEventController from '@/actions/App/Http/Controllers/Manage/RevertEventController';
import EventStatusBadge from '@/components/event/EventStatusBadge.vue';
import EventTransitionDialog from '@/components/event/EventTransitionDialog.vue';
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

            <EventTransitionDialog
                v-else
                :action="AdvanceEventController.form()"
                :to="transition.next"
                :label="t(eventTransitionLabelKey(transition.next))"
                :description="
                    transition.nextIsReversible
                        ? t('event.transition.confirm_reversible')
                        : t('event.transition.confirm_irreversible')
                "
                :refusals="transition.refusals"
                :refusals-title="t('event.transition.blocked_title')"
            />

            <EventTransitionDialog
                v-if="transition.previous"
                :action="RevertEventController.form()"
                :to="transition.previous"
                tone="quiet"
                :label="t('event.transition.back_to_draft')"
                :description="t('event.transition.back_to_draft_confirm')"
                :refusals="transition.revertRefusals"
                :refusals-title="
                    t('event.transition.back_to_draft_blocked_title')
                "
            />
        </div>
    </section>
</template>
