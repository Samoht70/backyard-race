# BR-04 — Calculer automatiquement les horaires des boucles

| | |
|---|---|
| **Epic** | 1 — Fondations |
| **Statut** | À faire |
| **Estimation** | 5 pts |
| **Dépend de** | BR-03 |

## User story

En tant que **gérant**,
Je veux **que les horaires de départ et de fin de chaque boucle se déduisent de la configuration**,
Afin de **ne jamais saisir un horaire à la main et de ne pas me tromper à 3 h du matin**.

## Contexte

Un départ par heure, tous les coureurs partent ensemble. La grille horaire est donc
entièrement déterminée par l'heure du premier départ et la durée d'une boucle. Elle est le
référentiel de temps de toute la course : la validation d'un tour et l'élimination
automatique s'y adossent.

## Périmètre fonctionnel

**Inclus**
- Un tour de course porte son numéro, son heure théorique de départ et son heure limite.
- Le tour courant est déterminé côté serveur à partir de l'heure serveur.
- Le tour suivant est ouvert quand le précédent arrive à échéance.
- Affichage du tour courant : numéro, heure de départ, heure de fin.

**Exclu**
- Tout compte à rebours : le gérant a son propre chronomètre.
- Les boucles individuelles des participants : BR-08.
- Une durée de boucle variable d'un tour à l'autre.

**Dépendances** — BR-03.

## Règles métier

- Le tour 1 démarre à l'heure du premier départ. Le tour N démarre à
  `premier départ + (N - 1) × durée de boucle` et se termine au départ du tour N + 1.
- Aucun horaire n'est écrit en dur, nulle part.
- La distance d'une boucle n'est pas portée par le tour : elle est unique pour l'événement et
  saisie par le gérant (voir D-17).
- Il n'existe pas de nombre de tours prédéfini : la course dure jusqu'à ce que le gérant la
  déclare terminée.
- Deux tours ne peuvent pas partager le même numéro sur un même événement.

## Critères d'acceptation

```gherkin
Étant donné un événement dont le premier départ est à 13:00 et la durée de boucle d'une heure
Lorsque les tours sont générés
Alors le tour 1 part à 13:00 et se termine à 14:00
Et le tour 4 part à 16:00 et se termine à 17:00

Étant donné un événement en course dont le premier départ était à 13:00
Et qu'il est 17:30 côté serveur
Lorsque le tour courant est demandé
Alors c'est le tour 5, départ 17:00, fin 18:00

Étant donné une durée de boucle de 45 minutes et un premier départ à 09:00
Lorsque les tours sont générés
Alors le tour 3 part à 10:30
```

## Cas limites et erreurs

- Heure serveur antérieure au premier départ : il n'y a pas encore de tour courant, la course n'a pas commencé.
- Passage à l'heure d'été ou d'hiver pendant la nuit de course : les horaires restent
  cohérents en heure locale de Paris.
- Événement pas encore en course : aucun tour courant n'est renvoyé.

## Impacts techniques

Cette grille horaire devient la référence de temps unique de l'application. Une erreur de
fuseau ici décalerait toute la course de deux heures, et donc toutes les éliminations.

## Tâches

- [ ] **T1** — Migration et modèle du tour de course, unicité du numéro par événement `1 pt`
- [ ] **T2** — Service de calcul des horaires et de résolution du tour courant `2 pts`
- [ ] **T3** — Ouverture du tour suivant à échéance du précédent `1 pt`
- [ ] **T4** — Tests du calcul : premier départ, tour courant, durée non horaire, avant course `2 pts`
