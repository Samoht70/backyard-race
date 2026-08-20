<?php

return [
    'required' => 'Le champ :attribute est obligatoire.',
    'required_with' => 'Le champ :attribute est obligatoire quand :values est renseigné.',
    'string' => 'Le champ :attribute doit être du texte.',
    'integer' => 'Le champ :attribute doit être un nombre entier.',
    'numeric' => 'Le champ :attribute doit être un nombre.',
    'date' => 'Le champ :attribute n’est pas une date valide.',
    'email' => 'Le champ :attribute doit être une adresse email valide.',
    'confirmed' => 'La confirmation du champ :attribute ne correspond pas.',
    'unique' => 'Cette valeur du champ :attribute est déjà utilisée.',
    'prohibited' => 'Le champ :attribute n’est plus modifiable.',
    'enum' => 'La valeur du champ :attribute est invalide.',
    'current_password' => 'Le mot de passe est incorrect.',

    'min' => [
        'numeric' => 'Le champ :attribute doit être au moins de :min.',
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'max' => [
        'numeric' => 'Le champ :attribute ne peut pas dépasser :max.',
        'string' => 'Le champ :attribute ne peut pas contenir plus de :max caractères.',
    ],
    'between' => [
        'numeric' => 'Le champ :attribute doit être compris entre :min et :max.',
        'string' => 'Le champ :attribute doit contenir entre :min et :max caractères.',
    ],

    'attributes' => [
        'name' => 'nom',
        'first_name' => 'prénom',
        'last_name' => 'nom',
        'description' => 'description',
        'briefing' => 'briefing',
        'first_start_at' => 'heure du premier départ',
        'start_date' => 'date',
        'start_time' => 'heure du premier départ',
        'lap_distance_meters' => 'distance d’une boucle',
        'lap_duration_minutes' => 'durée d’une boucle',
        'address' => 'adresse',
        'latitude' => 'latitude',
        'longitude' => 'longitude',
        'max_participants' => 'nombre maximum de participants',
        'to' => 'étape suivante',
        'email' => 'adresse email',
        'password' => 'mot de passe',
    ],
];
