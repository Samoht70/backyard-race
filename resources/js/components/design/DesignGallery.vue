<script setup lang="ts">
import { CircleOff } from '@lucide/vue';
import DesignSection from '@/components/design/DesignSection.vue';
import EventFieldSample from '@/components/design/EventFieldSample.vue';
import EventStatusGallery from '@/components/design/EventStatusGallery.vue';
import RunnerListSample from '@/components/design/RunnerListSample.vue';
import StatusGallery from '@/components/design/StatusGallery.vue';
import TypeSpecimen from '@/components/design/TypeSpecimen.vue';
import ActionButton from '@/components/race/ActionButton.vue';
import FestoonDivider from '@/components/race/FestoonDivider.vue';
import RoundHeader from '@/components/race/RoundHeader.vue';
import StatCounter from '@/components/race/StatCounter.vue';
import EmptyState from '@/components/state/EmptyState.vue';
import ErrorState from '@/components/state/ErrorState.vue';
import LoadingState from '@/components/state/LoadingState.vue';
import RunnerListSkeleton from '@/components/state/RunnerListSkeleton.vue';

type Props = {
    theme: string;
};

defineProps<Props>();
</script>

<template>
    <div class="flex flex-col gap-8 bg-background px-4 py-6 text-foreground">
        <p class="font-display text-label text-muted-foreground uppercase">
            {{ theme }}
        </p>

        <DesignSection title="Entête de tour" note="L’élément signature.">
            <RoundHeader
                :round="17"
                :runners-left="24"
                start-at="18:00"
                deadline-at="19:00"
                class="-mx-4"
            />
        </DesignSection>

        <FestoonDivider />

        <DesignSection title="Typographie">
            <TypeSpecimen />
        </DesignSection>

        <DesignSection
            title="Statuts"
            note="Couleur, pictogramme et libellé — jamais la couleur seule."
        >
            <StatusGallery />
        </DesignSection>

        <DesignSection
            title="Statuts d’événement"
            note="Quatre étapes, jamais de retour. Aucun token neuf : le brouillon en sourdine, les inscriptions sur l’accent, la course et la fin sur les encres du coureur."
        >
            <EventStatusGallery />
        </DesignSection>

        <DesignSection
            title="Champs de formulaire"
            note="Le plancher de 44 px vaut pour les saisies comme pour les boutons. Un champ figé est un fait, pas un champ désactivé."
        >
            <EventFieldSample />
        </DesignSection>

        <DesignSection title="Compteurs">
            <div class="flex items-end gap-6">
                <StatCounter :value="17" label="Tours" size="lg" />
                <StatCounter :value="24" label="En course" />
                <StatCounter :value="null" label="Vitesse" unit="km/h" />
            </div>
        </DesignSection>

        <DesignSection
            title="Boutons d’action"
            note="44 px minimum, 72 px pour la validation d’une boucle."
        >
            <div class="flex flex-col gap-2">
                <ActionButton size="validate">Valider une boucle</ActionButton>
                <ActionButton>Action courante</ActionButton>
                <ActionButton tone="danger">Déclarer un abandon</ActionButton>
                <ActionButton tone="quiet">Action secondaire</ActionButton>
                <ActionButton loading>Enregistrement</ActionButton>
                <ActionButton disabled>Indisponible</ActionButton>
            </div>
        </DesignSection>

        <DesignSection
            title="Liste de coureurs"
            note="Le dernier nom teste la troncature."
        >
            <RunnerListSample />
        </DesignSection>

        <DesignSection title="États d’écran">
            <div class="flex flex-col gap-4">
                <LoadingState />
                <div class="overflow-hidden rounded-lg border border-border">
                    <RunnerListSkeleton :rows="3" />
                </div>
                <EmptyState
                    title="Aucun coureur"
                    description="Les inscriptions apparaîtront ici dès la première validation."
                    :icon="CircleOff"
                >
                    <template #action>
                        <ActionButton tone="quiet">Inscrire</ActionButton>
                    </template>
                </EmptyState>
                <ErrorState retryable />
            </div>
        </DesignSection>
    </div>
</template>
