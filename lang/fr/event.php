<?php

return [
    'status' => [
        'draft' => 'Brouillon',
        'registration' => 'Inscriptions ouvertes',
        'running' => 'Course en cours',
        'finished' => 'Terminé',
    ],
    'refusal' => [
        'incomplete' => 'La configuration de la course est incomplète.',
        'finished' => 'L’événement est terminé : il n’évolue plus.',
        'missing_first_start' => 'L’heure du premier départ n’est pas renseignée.',
        'missing_lap_distance' => 'La distance d’une boucle n’est pas renseignée.',
        'missing_lap_duration' => 'La durée d’une boucle n’est pas renseignée.',
        'illegal_transition' => 'Cette étape n’est pas la suivante : recharge la page.',
    ],
    'manage' => [
        'saved' => 'Configuration enregistrée.',
        'advanced' => 'L’événement est passé en « :status ».',
    ],
];
