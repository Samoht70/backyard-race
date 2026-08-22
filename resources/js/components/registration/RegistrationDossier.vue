<script setup lang="ts">
import { X } from '@lucide/vue';
import { computed } from 'vue';
import ActionButton from '@/components/ActionButton.vue';
import BoardRow from '@/components/board/BoardRow.vue';
import BoardRows from '@/components/board/BoardRows.vue';
import BoardSection from '@/components/board/BoardSection.vue';
import Notice from '@/components/Notice.vue';
import BibDisplay from '@/components/race/BibDisplay.vue';
import RegistrationActionForm from '@/components/registration/RegistrationActionForm.vue';
import RegistrationDeleteForm from '@/components/registration/RegistrationDeleteForm.vue';
import RegistrationStatusBadge from '@/components/registration/RegistrationStatusBadge.vue';
import { t } from '@/lib/i18n';
import type { ManagedRegistration } from '@/types/registration';

type Props = {
    registration: ManagedRegistration;
    variant?: 'board' | 'drawer';
    blocked?: boolean;
    describedBy?: string;
    deletionRefusal?: string | null;
};

const props = withDefaults(defineProps<Props>(), {
    variant: 'board',
    blocked: false,
    deletionRefusal: null,
});

const emit = defineEmits<{ close: [] }>();

const fullName = computed(
    () => `${props.registration.first_name} ${props.registration.last_name}`,
);

const isDrawer = computed(() => props.variant === 'drawer');
const isDeletionRefused = computed(() => props.deletionRefusal !== null);
const deletionRefusalId = computed(
    () =>
        `registration-${props.registration.id}-${props.variant}-deletion-refusal`,
);
</script>

<template>
    <div class="grid content-start gap-6">
        <div class="flex items-start justify-between gap-3">
            <div class="grid justify-items-start gap-3">
                <BibDisplay
                    :value="registration.bib_label"
                    :label="t('registration.manage.bib')"
                />
                <h2 class="text-title">{{ fullName }}</h2>
                <RegistrationStatusBadge :status="registration.status" />
                <p
                    v-if="registration.bib_label === null"
                    class="text-sm text-muted-foreground"
                >
                    {{ t('registration.manage.no_bib') }}
                </p>
            </div>

            <ActionButton
                tone="ghost"
                size="icon"
                :icon="X"
                :aria-label="t('registration.manage.close')"
                data-test="close-dossier"
                @click="emit('close')"
            />
        </div>

        <BoardSection
            :class="isDrawer ? 'order-last' : undefined"
            :title="t('registration.manage.actions_title')"
            level="h3"
        >
            <div class="grid gap-3">
                <div class="grid gap-2 sm:flex sm:flex-wrap sm:items-start">
                    <RegistrationActionForm
                        v-for="transition in registration.allowed_transitions"
                        :key="transition"
                        :registration-id="registration.id"
                        :runner-name="fullName"
                        :transition="transition"
                        :disabled="blocked && transition === 'confirm'"
                        :described-by="blocked ? describedBy : undefined"
                    />

                    <RegistrationDeleteForm
                        :registration-id="registration.id"
                        :runner-name="fullName"
                        :disabled="isDeletionRefused"
                        :described-by="
                            isDeletionRefused ? deletionRefusalId : undefined
                        "
                    />
                </div>

                <Notice
                    v-if="isDeletionRefused"
                    :id="deletionRefusalId"
                    tone="danger"
                    :title="t('registration.delete.blocked_title')"
                >
                    {{ deletionRefusal }}
                </Notice>
            </div>
        </BoardSection>

        <BoardSection :title="t('registration.section.runner')" level="h3">
            <BoardRows>
                <BoardRow :label="t('registration.field.email')">
                    {{ registration.email }}
                </BoardRow>
                <BoardRow :label="t('registration.field.phone')" mono>
                    {{ registration.phone }}
                </BoardRow>
                <BoardRow :label="t('registration.field.birth_date')" mono>
                    {{ registration.birth_date }}
                </BoardRow>
                <BoardRow :label="t('registration.field.pps_number')" mono>
                    {{
                        registration.pps_number ?? t('registration.show.no_pps')
                    }}
                </BoardRow>
            </BoardRows>
        </BoardSection>

        <BoardSection :title="t('registration.section.emergency')" level="h3">
            <BoardRows>
                <BoardRow
                    :label="t('registration.field.emergency_contact_name')"
                >
                    {{ registration.emergency_contact_name }}
                </BoardRow>
                <BoardRow
                    :label="t('registration.field.emergency_contact_phone')"
                    mono
                >
                    {{ registration.emergency_contact_phone }}
                </BoardRow>
            </BoardRows>
        </BoardSection>

        <BoardSection
            v-if="registration.notes"
            :title="t('registration.section.notes')"
            level="h3"
        >
            <p class="text-sm whitespace-pre-line">{{ registration.notes }}</p>
        </BoardSection>
    </div>
</template>
