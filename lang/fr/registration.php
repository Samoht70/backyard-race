<?php

return [
    'status' => [
        'pending' => 'En attente',
        'confirmed' => 'Confirmée',
        'cancelled' => 'Annulée',
    ],
    'refusal' => [
        'full' => 'L’événement est complet : toutes les places confirmées sont prises.',
    ],
    'section' => [
        'runner' => 'Le coureur',
        'emergency' => 'En cas de pépin',
        'notes' => 'À signaler',
    ],
    'field' => [
        'email' => 'Adresse email',
        'phone' => 'Téléphone',
        'birth_date' => 'Date de naissance',
        'emergency_contact_name' => 'Personne à prévenir',
        'emergency_contact_phone' => 'Téléphone de cette personne',
        'notes' => 'Remarques',
    ],
    'hint' => [
        'phone' => 'Le gérant t’appelle dessus la nuit de la course.',
        'birth_date' => 'Il faut être majeur le jour du départ.',
        'notes' => 'Allergie, traitement, régime : tout ce que le gérant doit savoir.',
    ],
    'seats' => [
        'counted' => ':count coureurs confirmés sur :max places',
        'unlimited' => ':count coureurs confirmés, sans limite de places',
    ],
    'create' => [
        'title' => 'S’inscrire à la course',
        'description' => 'Ton inscription part en attente : le gérant la confirme ensuite.',
        'submit' => 'Envoyer mon inscription',
        'call_to_action' => 'S’inscrire',
    ],
    'show' => [
        'title' => 'Mon inscription',
        'edit' => 'Corriger mon inscription',
        'locked' => 'Ton inscription est confirmée : contacte le gérant pour la faire changer.',
        'call_to_action' => 'Voir mon inscription',
    ],
    'edit' => [
        'title' => 'Corriger mon inscription',
        'description' => 'Tant qu’elle est en attente, tu peux tout reprendre.',
        'submit' => 'Enregistrer',
    ],
    'stored' => 'Inscription enregistrée.',
    'updated' => 'Inscription mise à jour.',
];
