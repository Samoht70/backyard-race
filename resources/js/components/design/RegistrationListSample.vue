<script setup lang="ts">
import ActionButton from '@/components/race/ActionButton.vue';
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
    firstName: string;
    lastName: string;
    status: RegistrationStatus;
    transition: RegistrationTransition;
};

const registrations: Sample[] = [
    {
        firstName: 'Marie',
        lastName: 'Lambert',
        status: 'pending',
        transition: 'confirm',
    },
    {
        firstName: 'Thomas',
        lastName: 'Pierre',
        status: 'confirmed',
        transition: 'cancel',
    },
    {
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
