<script setup lang="ts">
import ActionButton from '@/components/ActionButton.vue';
import RegistrationSlat from '@/components/registration/RegistrationSlat.vue';
import { t } from '@/lib/i18n';
import {
    registrationTransitionLabelKey,
    registrationTransitions,
} from '@/lib/registrationStatus';
import type {
    RegistrationStatus,
    RegistrationTransition,
} from '@/types/registration';

type Sample = {
    bib: string | null;
    firstName: string;
    lastName: string;
    status: RegistrationStatus;
    transition: RegistrationTransition;
};

const registrations: Sample[] = [
    {
        bib: null,
        firstName: 'Marie',
        lastName: 'Lambert',
        status: 'pending',
        transition: 'confirm',
    },
    {
        bib: '007',
        firstName: 'Thomas',
        lastName: 'Pierre',
        status: 'confirmed',
        transition: 'cancel',
    },
    {
        bib: '003',
        firstName: 'Jean-Baptiste',
        lastName: 'de la Vallée-Poussin-Longuet',
        status: 'cancelled',
        transition: 'reopen',
    },
];
</script>

<template>
    <div class="slats">
        <RegistrationSlat
            v-for="registration in registrations"
            :key="registration.lastName"
            :bib="registration.bib"
            :first-name="registration.firstName"
            :last-name="registration.lastName"
            :status="registration.status"
        >
            <template #cell>
                <ActionButton
                    :tone="
                        registrationTransitions[registration.transition].tone
                    "
                    :icon="
                        registrationTransitions[registration.transition].icon
                    "
                    class="w-auto px-3"
                >
                    {{
                        t(
                            registrationTransitionLabelKey(
                                registration.transition,
                            ),
                        )
                    }}
                </ActionButton>
            </template>
        </RegistrationSlat>
    </div>
</template>
