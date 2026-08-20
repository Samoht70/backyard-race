<?php

return [
    'title' => 'Documents',
    'description' => 'Le règlement, les consignes et la trace de la boucle.',
    'empty_title' => 'Aucun document',
    'empty_description' => 'Le gérant n’a encore rien déposé. Reviens un peu plus tard.',
    'download' => 'Télécharger',
    'download_aria' => 'Télécharger « :title »',
    'manage' => [
        'title' => 'Documents de la course',
        'description' => 'Ces fichiers sont consultables par tous les coureurs inscrits.',
        'empty_title' => 'Aucun document déposé',
        'empty_description' => 'Dépose le règlement, les consignes ou la trace GPX de la boucle.',
        'add_title' => 'Déposer un document',
        'field' => [
            'title' => 'Titre',
            'description' => 'Description',
            'file' => 'Fichier',
        ],
        'hint' => [
            'title' => 'Ce que les coureurs liront dans la liste.',
            'description' => 'Une phrase pour dire ce que le fichier contient.',
            'file' => 'PDF, image ou trace GPX, :max Mo au maximum.',
        ],
        'submit' => 'Déposer le document',
        'saved' => 'Document déposé.',
        'deleted' => 'Document supprimé.',
        'delete' => 'Supprimer',
        'delete_aria' => 'Supprimer « :title »',
        'delete_title' => 'Supprimer ce document ?',
        'delete_description' => 'Le fichier est supprimé définitivement et les coureurs ne pourront plus le télécharger.',
        'delete_confirm' => 'Supprimer définitivement',
        'cancel' => 'Annuler',
        'readonly_title' => 'Événement terminé',
        'readonly_description' => 'La course est close : les documents ne sont plus modifiables.',
    ],
    'file' => [
        'unreadable' => 'Le fichier n’a pas pu être lu. Réessaie.',
        'extension' => 'Le fichier doit être de type : :extensions.',
        'mismatch' => 'Le contenu du fichier ne correspond pas à un ":extension".',
        'too_large' => 'Le fichier ne peut pas dépasser :max Mo.',
    ],
];
