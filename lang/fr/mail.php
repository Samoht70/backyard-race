<?php

return [
    'registration_link' => [
        'subject' => 'Finalise ton inscription à la Backyard Race',
        'heading' => 'Encore une étape',
        'body' => 'Tu as demandé à t’inscrire à la Backyard Race. Ce lien ouvre le formulaire d’inscription ; à la fin, un code d’inscription te sera affiché — c’est lui qui te servira à te connecter.',
        'action' => 'Finaliser mon inscription',
        'expires' => 'Ce lien est valable :hours heures.',
        'ignore' => 'Si tu n’es pas à l’origine de cette demande, ignore ce message : aucun compte n’a été créé.',
        'salutation' => 'À bientôt sur la piste,',
    ],

    'registration_received' => [
        'subject' => 'Ton inscription à la Backyard Race est enregistrée',
        'heading' => 'Bienvenue dans la boucle, :name !',
        'body' => 'Ton inscription est enregistrée et passe maintenant devant le gérant : tu recevras un mail dès qu’elle est traitée. Ici, la vitesse ne sert à rien — tout ce qu’on te demande, c’est de repartir pour un tour. Puis un autre. Puis encore un.',
        'code' => 'Ton code d’inscription :',
        'keep' => 'Garde-le en sécurité : c’est lui qui te sert à te connecter, et il ne te sera plus réaffiché.',
        'action' => 'Me connecter',
        'encouragement' => 'D’ici là, soigne tes appuis, tes chaussettes et ton sommeil — le reste, tu l’apprendras en tournant.',
        'salutation' => 'À bientôt sur la piste,',
    ],

    'registration_approved' => [
        'subject' => 'C’est officiel : tu cours la Backyard Race',
        'heading' => 'Tu es dans la boucle, :name !',
        'body' => 'Le gérant a validé ton inscription : tu fais officiellement partie des partants. Ton numéro de dossard t’attend sur ta page d’inscription.',
        'action' => 'Voir mon inscription',
        'closing' => 'Il ne te reste qu’à choisir tes chaussettes. La vitesse, elle, ne te servira à rien.',
        'salutation' => 'À bientôt sur la piste,',
    ],

    'registration_refused' => [
        'subject' => 'Ton inscription à la Backyard Race n’a pas été retenue',
        'heading' => 'Pas cette fois, :name',
        'body' => 'Le gérant n’a pas retenu ton inscription. Rien de ce que tu as saisi n’est perdu : si c’est une erreur, contacte-le, il peut la remettre en attente.',
        'action' => 'Voir mon inscription',
        'closing' => 'Ce n’est pas un jugement sur tes jambes — juste une liste de départ qui doit tenir.',
        'salutation' => 'À une prochaine boucle,',
    ],

    'registration_cancelled' => [
        'subject' => 'Ton inscription à la Backyard Race est annulée',
        'heading' => 'Inscription annulée, :name',
        'body' => 'Le gérant a annulé ton inscription : ta place est libérée et tu ne figures plus parmi les partants. Ton dossard te reste attribué si l’inscription est remise en attente.',
        'action' => 'Voir mon inscription',
        'closing' => 'Si ce n’est pas ce que tu avais demandé, contacte le gérant : rien n’est joué tant que la course n’est pas partie.',
        'salutation' => 'À une prochaine boucle,',
    ],

    'registration_reopened' => [
        'subject' => 'Ton inscription à la Backyard Race est de nouveau modifiable',
        'heading' => 'À toi de jouer, :name',
        'body' => 'Le gérant a remis ton inscription en attente. Tu peux corriger tes informations ; elle repassera ensuite devant lui pour validation.',
        'action' => 'Corriger mon inscription',
        'closing' => 'Vérifie ton téléphone et ton contact d’urgence : c’est ce que le gérant regarde en premier.',
        'salutation' => 'À bientôt sur la piste,',
    ],
];
