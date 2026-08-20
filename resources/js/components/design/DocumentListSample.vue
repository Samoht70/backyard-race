<script setup lang="ts">
import { Trash2 } from '@lucide/vue';
import DocumentRow from '@/components/document/DocumentRow.vue';
import ActionButton from '@/components/race/ActionButton.vue';
import type { EventDocument } from '@/types/document';

const documents: EventDocument[] = [
    {
        id: 1,
        title: 'Règlement de la course',
        description:
            'Les règles de la nuit, de la première boucle au dernier coureur debout.',
        file_name: 'reglement-backyard-2026.pdf',
        extension: 'pdf',
        size: 428_112,
        url: '#',
    },
    {
        id: 2,
        title: 'Trace GPX de la boucle',
        description: null,
        file_name: 'boucle.gpx',
        extension: 'gpx',
        size: 18_430,
        url: '#',
    },
    {
        id: 3,
        title: 'Plan du campement',
        description: 'Où planter la tente et où se garer.',
        file_name: 'plan-campement.png',
        extension: 'png',
        size: 2_310_000,
        url: null,
    },
];
</script>

<template>
    <div class="slats">
        <DocumentRow
            v-for="document in documents"
            :key="document.id"
            :document="document"
        >
            <template #cell>
                <ActionButton tone="danger" :icon="Trash2" class="w-auto px-3">
                    Supprimer
                </ActionButton>
            </template>
        </DocumentRow>
    </div>
</template>
