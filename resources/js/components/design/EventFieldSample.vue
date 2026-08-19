<script setup lang="ts">
import EventField from '@/components/event/EventField.vue';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
</script>

<template>
    <div class="flex flex-col gap-4">
        <EventField name="demo-name" label="Nom de l’événement">
            <Input id="demo-name" default-value="Backyard des 40 ans" />
        </EventField>

        <EventField
            name="demo-distance"
            label="Distance d’une boucle"
            unit="m"
            hint="Toutes les vitesses affichées en dépendent."
        >
            <Input id="demo-distance" type="number" default-value="6706" />
        </EventField>

        <EventField name="demo-description" label="Description">
            <Textarea id="demo-description" rows="3" />
        </EventField>

        <EventField
            name="demo-locked"
            label="Heure du premier départ"
            locked
            value="13:00"
            locked-reason="Figé depuis le lancement de la course : les horaires déjà calculés en dépendent."
        />

        <EventField
            name="demo-error"
            label="Nombre maximum de participants"
            error="Le champ nombre maximum de participants doit être au moins de 1."
        >
            <Input id="demo-error" type="number" default-value="0" />
        </EventField>
    </div>
</template>
