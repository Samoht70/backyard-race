# BR-34 — Demander le numéro PPS à l'inscription

| | |
|---|---|
| **Epic** | 1 — Fondations |
| **Statut** | À faire |
| **Estimation** | 2 pts |
| **Créée** | 2026-08-20 — lot « inscription en ligne d'abord » |
| **Révisée** | 2026-08-20 — le justificatif en pièce jointe tombe, il ne reste que le champ |
| **Dépend de** | BR-05 |

## User story

En tant que **gérant**,
Je veux **que chaque coureur déclare son numéro PPS en s'inscrivant**,
Afin de **l'avoir sous les yeux sans le réclamer par mail à quarante personnes**.

## Contexte

Le PPS — Parcours Prévention Santé — est ce que les organisateurs demandent d'ordinaire à la place
d'un certificat médical. Ici il est **demandé, jamais vérifié** : personne n'appelle un service
tiers, personne ne lit un document, et un numéro inventé passe sans encombre du moment qu'il a la
forme d'un numéro. Ce qui est contrôlé est la forme, pas la vérité.

Le dépôt d'un fichier justificatif a été retiré du périmètre le 2026-08-20 : pour un événement entre
amis, la pièce jointe apportait une collection Media Library, une route de téléchargement sous
Policy, une donnée de santé nominative à protéger et à purger — tout un appareil pour un document
que personne n'allait ouvrir.

C'est aussi le seul champ du produit qui a le droit de faire sourire.

## Périmètre fonctionnel

**Inclus**
- Un champ « Numéro PPS » sur le formulaire d'inscription et sur sa correction.
- Sa glose sous le champ : **Prévois Pansements et Sommeil**.
- Une validation de forme : trois lettres suivies de huit chiffres.
- Le numéro affiché sur la fiche d'inscription, côté coureur comme côté gérant.

**Exclu**
- Toute pièce jointe, tout fichier, tout justificatif — retiré du périmètre le 2026-08-20.
- Toute vérification du numéro : rien n'est appelé, rien n'est comparé, aucun doublon n'est cherché.
- Tout blocage : un coureur sans numéro s'inscrit quand même, et reste confirmable.
- Toute date de validité.

**Dépendances** — BR-05.

## La copie du champ

Le libellé est **Numéro PPS**. La glose sous le champ est **Prévois Pansements et Sommeil** —
ampoules et nuit blanche, c'est la course en trois mots.

Le vrai nom, *Parcours Prévention Santé*, reste écrit sur l'écran malgré tout : un coureur qui
cherche son numéro doit reconnaître de quoi on parle, et la blague ne marche que si l'original est
lisible à côté.

## Règles métier

- Le numéro est **facultatif** : une inscription sans numéro est valide et confirmable.
- S'il est renseigné, il vaut trois lettres suivies de huit chiffres — `PPS12345678`.
- Il est normalisé avant enregistrement : majuscules, espaces et tirets retirés. Le coureur ne se
  fait pas refuser pour avoir tapé `pps 1234 5678`.
- Aucune unicité : deux coureurs peuvent déclarer le même numéro sans que rien ne s'y oppose.
- Il se corrige tant que l'inscription est modifiable par son coureur, selon `isEditableByRunner()`
  (D-48) — pas un test de statut réécrit ailleurs.
- Le gérant le lit sur la fiche d'inscription ; il ne le saisit pas à la place du coureur.

## Critères d'acceptation

```gherkin
Étant donné un coureur qui remplit son inscription
Lorsqu'il saisit "PPS12345678" comme numéro PPS
Alors le numéro est enregistré sur son inscription

Étant donné un coureur qui remplit son inscription
Lorsqu'il saisit "pps 1234 5678"
Alors le numéro enregistré est "PPS12345678"

Étant donné un coureur qui remplit son inscription
Lorsqu'il saisit "12345678"
Alors le champ est refusé avec un message qui rappelle la forme attendue

Étant donné un coureur qui remplit son inscription
Lorsqu'il laisse le numéro PPS vide
Alors son inscription est enregistrée
Et elle reste confirmable par le gérant

Étant donné un coureur dont l'inscription est confirmée
Lorsqu'il tente de corriger son numéro PPS
Alors l'action est refusée

Étant donné le gérant sur la fiche d'une inscription
Lorsqu'il l'ouvre
Alors il lit le numéro PPS déclaré, ou la mention qu'il manque
```

## Cas limites et erreurs

- Numéro saisi en minuscules, avec espaces ou tirets : normalisé, jamais refusé pour ça.
- Numéro absent : c'est un cas nominal, l'écran ne le présente pas comme un manquement grave.
- Coureur qui invente un numéro : c'est prévu, et ça ne casse rien.
- Copier-coller depuis un mail, avec une espace insécable en fin : la normalisation la retire.

## Impacts techniques

Une colonne, une règle de validation, deux libellés. Le seul piège est la normalisation : si elle
vit dans l'écran, la correction et la saisie initiale divergeront le jour où l'une des deux change.
Elle appartient donc à la règle de validation, partagée par les deux requêtes comme
`RegistrationValidationRules` le fait déjà.

Le format est une constante nommée, pas une expression régulière recopiée dans deux Form Requests.

## Tâches

- [ ] **T1** — Colonne `pps_number`, règle de format et normalisation partagées `1 pt`
- [ ] **T2** — Champ et glose dans les deux formulaires, affichage sur les fiches, tests : format
  valide, format refusé, normalisation, champ vide `1 pt`
