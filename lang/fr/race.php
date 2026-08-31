<?php

return [
    'status' => [
        'running' => 'En course',
        'eliminated' => 'Éliminé',
        'withdrawn' => 'Abandon',
        'finished' => 'Terminé',
    ],
    'round' => [
        'number' => 'Tour',
        'short' => 'T:number',
        'runners_left' => 'en course',
        'runners_out' => 'sortis',
        'start' => 'Départ',
        'deadline' => 'Limite',
    ],
    'duration' => [
        'title' => 'Durée du prochain tour',
        'next_round' => 'Tour :number, départ :start',
        'field' => 'Durée du tour',
        'hint' => 'Le tour en cours et ceux déjà courus ne bougent pas.',
        'onwards' => 'À partir de ce tour',
        'single_round' => 'Ce tour seulement',
        'onwards_saved' => 'À partir du tour :number, une boucle dure :minutes minutes.',
        'single_round_saved' => 'Le tour :number dure :minutes minutes, puis la grille reprend son cours.',
    ],
    'board' => [
        'title' => 'Tour en cours',
        'empty' => 'Aucune boucle ouverte sur ce tour.',
        'refused' => 'Validation refusée',
    ],
    'lap' => [
        'speed_unit' => 'km/h',
    ],
    'refusal' => [
        'round_started' => 'Ce tour est déjà parti : sa durée n’est plus modifiable.',
        'no_schedule' => 'La grille horaire n’est pas configurée : il n’y a pas de durée à changer.',
        'deadline_passed' => 'L’heure limite du tour est passée : cette boucle relève de la correction exceptionnelle.',
        'runner_out' => 'Ce coureur est sorti de la course : sa boucle ne se valide plus.',
        'runner_already_out' => 'Ce coureur est déjà sorti de la course : son abandon ne s’enregistre pas une seconde fois.',
    ],
    'withdrawal' => [
        'open' => 'Abandon',
        'aria_open' => 'Déclarer l’abandon de :name',
        'confirm_title' => 'Déclarer l’abandon de :name ?',
        'last_lap' => 'Dernière boucle validée : n° :number',
        'no_lap' => 'Aucune boucle validée.',
        'consequence' => 'Sa boucle en cours ne compte pas, ses boucles validées restent acquises, et plus aucune boucle ne s’ouvrira pour lui.',
        'submit' => 'Enregistrer l’abandon',
        'keep' => 'Le laisser en course',
        'recorded' => 'Abandon de :name enregistré.',
    ],
    'runner' => [
        'bib' => 'Dossard',
        'laps_completed' => 'boucles',
        'arrived' => 'rentré',
        'out' => 'sorti',
        'validate' => 'Valider',
    ],
];
