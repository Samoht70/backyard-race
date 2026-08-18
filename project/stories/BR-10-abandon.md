# BR-10 — Déclarer l'abandon volontaire d'un coureur

| | |
|---|---|
| **Epic** | 2 — Moteur de course |
| **Statut** | À faire |
| **Estimation** | 5 pts |
| **Dépend de** | BR-08 |

## User story

En tant que **gérant**,
Je veux **enregistrer qu'un coureur arrête la course de son plein gré**,
Afin qu'**il disparaisse des coureurs à surveiller et que son résultat soit figé**.

## Contexte

Un coureur qui s'arrête vient le dire au gérant. Sans cet enregistrement, il serait éliminé
automatiquement à l'heure limite, ce qui donnerait une raison de sortie fausse et laisserait
le gérant l'attendre inutilement.

## Périmètre fonctionnel

**Inclus**
- Action d'abandon depuis la fiche du coureur et depuis le tableau de course.
- Écran de confirmation rappelant la dernière boucle validée et la distance parcourue.
- Sortie immédiate du coureur des effectifs actifs.

**Exclu**
- L'abandon déclaré par le coureur lui-même.
- L'annulation d'un abandon : traitée comme une correction exceptionnelle (BR-12).

**Dépendances** — BR-08.

## Règles métier

- Seul un utilisateur porteur de la permission `manage-laps` peut déclarer un abandon.
- Une confirmation explicite est obligatoire avant l'enregistrement.
- L'abandon fait passer le participant en `eliminated` et enregistre l'heure de sortie ainsi
  que le motif « abandon ».
- La boucle en cours du coureur passe en `eliminated` : elle ne compte pas.
- Les boucles déjà validées sont conservées telles quelles.
- Un coureur ayant abandonné ne reçoit plus de boucle aux tours suivants et n'apparaît plus
  parmi les coureurs actifs.
- Un coureur déjà sorti ne peut pas abandonner une seconde fois.

## Critères d'acceptation

```gherkin
Étant donné un coureur en course avec 7 boucles validées sur des tours de 6 km
Lorsque le gérant ouvre l'écran d'abandon
Alors la dernière boucle validée affichée est la 7e
Et la distance affichée est 42 km

Étant donné cet écran de confirmation
Lorsque le gérant annule
Alors le coureur est toujours en course

Étant donné cet écran de confirmation
Lorsque le gérant confirme l'abandon
Alors le coureur passe au statut "eliminated"
Et le motif enregistré est l'abandon
Et sa boucle en cours passe au statut "eliminated"
Et ses 7 boucles validées sont inchangées

Étant donné un coureur ayant abandonné
Lorsque le tour suivant est ouvert
Alors aucune boucle ne lui est créée
Et il n'apparaît pas parmi les coureurs actifs

Étant donné un participant connecté
Lorsqu'il tente de déclarer son propre abandon
Alors l'action est refusée
```

## Cas limites et erreurs

- Abandon déclaré alors que la boucle du coureur venait d'être validée : la boucle validée est conservée, l'abandon prend effet pour la suite.
- Abandon d'un coureur déjà éliminé automatiquement : l'action est refusée, le motif initial est conservé.
- Confirmation envoyée deux fois : le second envoi ne change rien.

## Impacts techniques

L'abandon et l'élimination automatique aboutissent au même statut mais pas au même motif.
Distinguer les deux est ce qui permet, après la course, de dire qui s'est arrêté et qui a été
rattrapé par le chrono.

## Tâches

- [ ] **T1** — Motif de sortie et heure de sortie sur le participant `1 pt`
- [ ] **T2** — Action d'abandon : statut, motif, boucle en cours `2 pts`
- [ ] **T3** — Écran de confirmation avec rappel des boucles et de la distance `2 pts`
- [ ] **T4** — Tests : abandon nominal, double abandon, boucles validées préservées, refus participant `2 pts`
