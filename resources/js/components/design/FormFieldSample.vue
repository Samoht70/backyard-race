<script setup lang="ts">
import CheckField from '@/components/form/CheckField.vue';
import DateField from '@/components/form/DateField.vue';
import FormField from '@/components/form/FormField.vue';
import NumberField from '@/components/form/NumberField.vue';
import PasswordField from '@/components/form/PasswordField.vue';
import TextAreaField from '@/components/form/TextAreaField.vue';
import TextField from '@/components/form/TextField.vue';
import TimeField from '@/components/form/TimeField.vue';
</script>

<template>
    <div class="flex flex-col gap-4">
        <FormField name="demo-name" label="Nom de l’événement">
            <TextField id="demo-name" default-value="Backyard des 40 ans" />
        </FormField>

        <FormField name="demo-date" label="Date du premier départ">
            <DateField id="demo-date" default-value="2026-09-12" />
        </FormField>

        <FormField name="demo-time" label="Heure du premier départ">
            <TimeField id="demo-time" default-value="13:00" />
        </FormField>

        <FormField
            name="demo-distance"
            label="Distance d’une boucle"
            unit="m"
            hint="Toutes les vitesses affichées en dépendent."
        >
            <NumberField
                id="demo-distance"
                :min="1"
                :max="100000"
                :default-value="6706"
            />
        </FormField>

        <FormField name="demo-code" label="Code d’inscription">
            <PasswordField id="demo-code" value="ABCD-EFGH-JKLM" />
        </FormField>

        <FormField name="demo-description" label="Description">
            <TextAreaField id="demo-description" rows="3" />
        </FormField>

        <CheckField id="demo-remember" label="Se souvenir de moi" />

        <FormField
            name="demo-locked"
            label="Heure du premier départ"
            locked
            value="13:00"
            locked-reason="Figé depuis le lancement de la course : les horaires déjà calculés en dépendent."
        />

        <FormField
            name="demo-error"
            label="Nombre maximum de participants"
            error="Le champ nombre maximum de participants doit être au moins de 1."
        >
            <NumberField id="demo-error" :min="1" :default-value="0" />
        </FormField>
    </div>
</template>
