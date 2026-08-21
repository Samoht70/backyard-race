<script setup lang="ts">
import BoardFilter from '@/components/race/BoardFilter.vue';
import type { BoardFilterOption } from '@/types/ui';

const options: BoardFilterOption[] = [
    { value: null, label: 'Toutes', href: '#', count: 31 },
    { value: 'pending', label: 'En attente', href: '#', count: 7 },
    { value: 'confirmed', label: 'Confirmées', href: '#', count: 22 },
    { value: 'cancelled', label: 'Annulées', href: '#', count: 2 },
];
</script>

<template>
    <BoardFilter
        label="Filtrer par statut"
        :options="options"
        active-value="pending"
    />
</template>
