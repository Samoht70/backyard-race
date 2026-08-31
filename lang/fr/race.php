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
    'refusal' => [
        'round_started' => 'Ce tour est déjà parti : sa durée n’est plus modifiable.',
        'no_schedule' => 'La grille horaire n’est pas configurée : il n’y a pas de durée à changer.',
    ],
    'runner' => [
        'bib' => 'Dossard',
        'laps_completed' => 'boucles',
        'arrived' => 'rentré',
        'out' => 'sorti',
        'validate' => 'Valider',
    ],
];
