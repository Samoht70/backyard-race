<script setup lang="ts">
import { CircleOff } from '@lucide/vue';
import ActionButton from '@/components/ActionButton.vue';
import BoardFilterSample from '@/components/design/BoardFilterSample.vue';
import DesignSection from '@/components/design/DesignSection.vue';
import DocumentListSample from '@/components/design/DocumentListSample.vue';
import EventStatusGallery from '@/components/design/EventStatusGallery.vue';
import FormFieldSample from '@/components/design/FormFieldSample.vue';
import RegistrationListSample from '@/components/design/RegistrationListSample.vue';
import RegistrationStatusGallery from '@/components/design/RegistrationStatusGallery.vue';
import RegistrationStepperSample from '@/components/design/RegistrationStepperSample.vue';
import RunnerListSample from '@/components/design/RunnerListSample.vue';
import StatusGallery from '@/components/design/StatusGallery.vue';
import TypeSpecimen from '@/components/design/TypeSpecimen.vue';
import Notice from '@/components/Notice.vue';
import RoundHeader from '@/components/race/RoundHeader.vue';
import RoundTally from '@/components/race/RoundTally.vue';
import StatCounter from '@/components/race/StatCounter.vue';
import EmptyState from '@/components/state/EmptyState.vue';
import ErrorState from '@/components/state/ErrorState.vue';
import LoadingState from '@/components/state/LoadingState.vue';
import RunnerListSkeleton from '@/components/state/RunnerListSkeleton.vue';
import { t } from '@/lib/i18n';

type Props = {
    theme: string;
};

defineProps<Props>();

const counts = [
    { label: t('race.round.runners_left'), value: 24 },
    { label: t('race.round.runners_out'), value: 13 },
];
</script>

<template>
    <div class="flex flex-col gap-8 bg-background pb-8 text-foreground">
        <div>
            <RoundHeader
                :round="6"
                event-name="La Backyard de Thomas"
                start-at="18:00"
                deadline-at="19:00"
            />
            <RoundTally :counts="counts" />
        </div>

        <div class="flex flex-col gap-8 px-4">
            <p class="font-mono text-label text-muted-foreground uppercase">
                {{ theme }}
            </p>

            <DesignSection
                title="La planche et les lattes"
                note="La dalle gris-bleu est le fond de l’appareil. Chaque latte est une fenêtre encadrée, plus claire que la dalle, avec une barre de statut au bord gauche."
            >
                <RunnerListSample />
            </DesignSection>

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
                note="Quatre étapes, jamais de retour."
            >
                <EventStatusGallery />
            </DesignSection>

            <DesignSection
                title="Statuts d’inscription"
                note="Trois états : en attente, confirmée, annulée."
            >
                <RegistrationStatusGallery />
            </DesignSection>

            <DesignSection
                title="Lattes d’inscription"
                note="Une seule action par latte, celle que l’état permet. Le nom ouvre la fiche, le bouton agit."
            >
                <RegistrationListSample />
            </DesignSection>

            <DesignSection
                title="Lattes de document"
                note="Le titre est éditorial et mène au fichier ; le nom réel et le poids restent lisibles en dessous."
            >
                <DocumentListSample />
            </DesignSection>

            <DesignSection
                title="Filtres de vue"
                note="Quatre vues au maximum, toutes visibles sans ouvrir de menu."
            >
                <BoardFilterSample />
            </DesignSection>

            <DesignSection
                title="Repères d’étape"
                note="Quatre repères, jamais une barre de progression : la position est un fait, pas une animation. Les étapes franchies ramènent en arrière, les suivantes restent inertes."
            >
                <RegistrationStepperSample />
            </DesignSection>

            <DesignSection
                title="Champs de formulaire"
                note="Le plancher de 44 px vaut pour les saisies comme pour les boutons. Un champ figé est un fait, pas un champ désactivé."
            >
                <FormFieldSample />
            </DesignSection>

            <DesignSection
                title="Champs de lecture"
                note="Un cadran : étiquette minuscule au-dessus, valeur en largeur fixe, le tout encadré d’un filet."
            >
                <div class="grid grid-cols-2 gap-1.5">
                    <StatCounter :value="7" label="Boucles" />
                    <StatCounter value="42 km" label="Distance" />
                    <StatCounter value="48:32" label="Dernière boucle" />
                    <StatCounter :value="null" label="Moyenne" unit="km/h" />
                </div>
            </DesignSection>

            <DesignSection
                title="Boutons d’action"
                note="44 px de haut au doigt, 40 px au grand format — une seule taille, gestes de course compris."
            >
                <div class="flex flex-col gap-1.5">
                    <ActionButton>
                        {{ t('race.runner.validate') }}
                    </ActionButton>
                    <ActionButton>Action courante</ActionButton>
                    <ActionButton tone="danger">
                        Déclarer un abandon
                    </ActionButton>
                    <ActionButton tone="quiet">Action secondaire</ActionButton>
                    <ActionButton loading>Enregistrement</ActionButton>
                    <ActionButton disabled>Indisponible</ActionButton>
                </div>
            </DesignSection>

            <DesignSection
                title="Encarts"
                note="L’encre annonce un fait à connaître, le rouge un refus. Le filet de gauche porte le ton, jamais le fond."
            >
                <div class="flex flex-col gap-4">
                    <Notice title="Événement en brouillon">
                        Seuls les gérants voient cette page tant que l’événement
                        n’est pas publié.
                    </Notice>
                    <Notice tone="danger" title="Inscription annulée">
                        Le gérant a annulé cette inscription. Reprends contact
                        avec lui pour comprendre.
                    </Notice>
                </div>
            </DesignSection>

            <DesignSection
                title="États d’écran"
                note="Au chargement d’une liste, des lattes vides — pas un tourniquet."
            >
                <div class="flex flex-col gap-4">
                    <RunnerListSkeleton :rows="3" />
                    <LoadingState />
                    <EmptyState
                        title="Personne n’est rentré"
                        description="Le tour 6 vient de partir. Les heures d’arrivée s’afficheront ici."
                        :icon="CircleOff"
                    />
                    <ErrorState retryable />
                </div>
            </DesignSection>
        </div>
    </div>
</template>
