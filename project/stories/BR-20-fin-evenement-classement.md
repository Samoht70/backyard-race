# BR-20 — Clore l'événement et figer le classement final

| | |
|---|---|
| **Epic** | 5 — Après-course |
| **Statut** | À faire |
| **Estimation** | 8 pts |
| **Dépend de** | BR-11 |

## User story

En tant que **gérant**,
Je veux **déclarer la course terminée et obtenir le classement**,
Afin d'**annoncer le vainqueur sur une base incontestable**.

## Contexte

Une Backyard n'a pas de ligne d'arrivée : elle s'arrête quand le gérant le décide. Le
classement n'existe donc pas pendant la course — il est produit une fois, au moment de la
clôture, et ne bouge plus.

## Périmètre fonctionnel

**Inclus**
- Action « Terminer l'événement », avec confirmation.
- Génération du classement final au moment de la clôture.
- Consultation du classement par tous.

**Exclu**
- Tout classement pendant la course.
- Les critères de départage autres que le nombre de boucles.

**Dépendances** — BR-11.

## Règles métier

- Seul le porteur de la permission `finish-event` peut clore l'événement.
- Une confirmation explicite est obligatoire : l'action est irréversible.
- Le classement est établi **uniquement sur le nombre de boucles validées**, par ordre
  décroissant. Ni la vitesse, ni le meilleur temps, ni le temps cumulé, ni la moyenne
  n'entrent en ligne de compte.
- À égalité de boucles, les coureurs sont ex æquo : aucun critère ne les départage.
- Le classement est figé au moment de la clôture : une correction ultérieure ne le recalcule pas.
- Après clôture, l'événement est en lecture seule : plus de validation, plus d'abandon, plus
  de correction.

## Critères d'acceptation

```gherkin
Étant donné un événement en course avec Thomas à 18 boucles, Paul à 17 et Julie à 16
Lorsque le gérant confirme la fin de l'événement
Alors le statut de l'événement est "finished"
Et le classement est Thomas 1er, Paul 2e, Julie 3e

Étant donné deux coureurs à 12 boucles validées chacun
Lorsque le classement est généré
Alors ils sont ex æquo au même rang

Étant donné un coureur ayant 10 boucles validées et une vitesse moyenne supérieure à tous
Et un coureur ayant 11 boucles validées
Lorsque le classement est généré
Alors le coureur à 11 boucles est classé devant

Étant donné l'écran de confirmation de fin d'événement
Lorsque le gérant annule
Alors l'événement reste au statut "running"

Étant donné un événement au statut "finished"
Lorsque le gérant tente de valider une boucle
Alors l'action est refusée

Étant donné un participant connecté
Lorsqu'il tente de terminer l'événement
Alors l'action est refusée
```

## Cas limites et erreurs

- Clôture alors que des coureurs sont encore en course : autorisée, leurs boucles validées comptent, la boucle en cours ne compte pas.
- Clôture d'un événement déjà terminé : sans effet.
- Aucun coureur n'a validé de boucle : le classement est vide et l'annonce le dit.
- Tous les coureurs ex æquo : il n'y a pas de vainqueur unique, l'affichage doit le supporter.

## Impacts techniques

La clôture est irréversible et fige le résultat de la soirée. C'est le seul geste du produit
qu'on ne peut pas défaire depuis l'application, d'où la confirmation obligatoire.

## Tâches

- [ ] **T1** — Action de clôture : transition de statut et verrouillage en lecture seule `2 pts`
- [ ] **T2** — Génération et persistance du classement au moment de la clôture `3 pts`
- [ ] **T3** — Écran de confirmation et page de classement `2 pts`
- [ ] **T4** — Tests : ordre par boucles, ex æquo, vitesse ignorée, lecture seule après clôture `3 pts`
