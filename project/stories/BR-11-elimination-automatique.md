# BR-11 — Éliminer automatiquement les coureurs hors délai

| | |
|---|---|
| **Epic** | 2 — Moteur de course |
| **Statut** | À faire |
| **Estimation** | 8 pts |
| **Dépend de** | BR-09 |

## User story

En tant que **gérant**,
Je veux **que les coureurs n'ayant pas terminé leur boucle dans l'heure soient éliminés tout seuls**,
Afin de **ne pas avoir à faire le tri à chaque changement de tour, à 4 h du matin**.

## Contexte

C'est la règle qui fait une Backyard : boucle non terminée dans le temps imparti, coureur
éliminé. Elle doit s'appliquer même si personne n'a l'application ouverte — donc côté serveur,
sur une tâche planifiée, jamais depuis un écran.

## Périmètre fonctionnel

**Inclus**
- Une tâche récurrente qui repère les boucles arrivées à échéance sans validation.
- Élimination du coureur concerné avec le motif « hors délai » et l'heure d'élimination.
- Ouverture du tour suivant pour les coureurs restants.
- Supervision des files dans Horizon.

**Exclu**
- Toute élimination décidée par le front.
- La notification du coureur éliminé (voir D-15).

**Dépendances** — BR-09.

## Règles métier

- À l'heure limite d'un tour, toute boucle encore `pending` passe en `eliminated` et son
  coureur passe en `eliminated`, motif « hors délai ».
- Un coureur dont la boucle a été validée avant l'heure limite reste en course, y compris
  s'il a validé à la dernière seconde.
- L'heure d'élimination enregistrée est l'heure limite du tour, pas l'heure d'exécution de la
  tâche : un retard de traitement ne doit pas déformer le résultat.
- La tâche est **rejouable** : la relancer deux fois sur le même tour ne produit aucun
  changement supplémentaire.
- La tâche ne s'exécute que si l'événement est en `running`.
- Quand tous les coureurs sont sortis, plus aucun tour n'est ouvert.

## Critères d'acceptation

```gherkin
Étant donné un tour dont le départ est 18:00 et l'heure limite 19:00
Et un coureur dont la boucle est encore "pending" à 19:00
Lorsque la tâche d'élimination s'exécute
Alors la boucle passe au statut "eliminated"
Et le coureur passe au statut "eliminated" avec le motif "hors délai"
Et l'heure d'élimination enregistrée est 19:00

Étant donné un coureur ayant validé sa boucle à 18:59:59
Lorsque la tâche d'élimination s'exécute à 19:00
Alors ce coureur est toujours en course

Étant donné une tâche d'élimination déjà passée sur le tour 6
Lorsqu'elle est relancée sur ce même tour
Alors aucun coureur supplémentaire n'est éliminé

Étant donné un tour arrivé à échéance à 19:00
Et une tâche qui ne s'exécute qu'à 19:04 pour cause de retard
Lorsqu'elle s'exécute
Alors l'heure d'élimination enregistrée est bien 19:00

Étant donné un événement au statut "finished"
Lorsque la tâche s'exécute
Alors elle ne modifie aucun coureur
```

## Cas limites et erreurs

- Queue arrêtée pendant une heure : au redémarrage, tous les tours en retard sont traités avec les bonnes heures limites.
- Le dernier coureur en course est éliminé : la course n'a plus de partant, le gérant peut clore l'événement.
- Deux exécutions concurrentes de la tâche : une seule produit l'élimination, l'autre ne fait rien.
- Événement encore en `registration` : la tâche ne fait rien.

## Impacts techniques

C'est le seul mécanisme du produit qui décide seul, sans qu'un humain appuie sur un bouton.
Une erreur de fuseau ou une queue arrêtée sans qu'on le voie fausserait le résultat de la
course, sans message d'erreur visible. La supervision Horizon n'est donc pas décorative.

## Tâches

- [ ] **T1** — Job d'élimination des boucles échues, rejouable et sans effet de bord `3 pts`
- [ ] **T2** — Planification récurrente du job et garde sur le statut de l'événement `2 pts`
- [ ] **T3** — Enchaînement sur l'ouverture du tour suivant `1 pt`
- [ ] **T4** — Configuration Horizon : file dédiée, accès réservé au gérant `2 pts`
- [ ] **T5** — Tests : élimination, validation en limite, rejouabilité, retard d'exécution, événement clos `3 pts`
