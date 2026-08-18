# BR-12 — Corriger exceptionnellement une boucle

| | |
|---|---|
| **Epic** | 2 — Moteur de course |
| **Statut** | À faire |
| **Estimation** | 5 pts |
| **Dépend de** | BR-11 |

## User story

En tant que **gérant**,
Je veux **pouvoir rattraper une erreur de saisie ou une élimination injuste**,
Afin qu'**un coureur ne soit pas sorti de la course à cause d'un téléphone qui n'a pas envoyé la requête**.

## Contexte

Sur le terrain, un appui se perd, une validation arrive trois secondes trop tard, le gérant
valide le mauvais coureur. Sans porte de sortie, la seule option serait d'intervenir en base
de données pendant la course. Cette story est ce garde-fou, volontairement étroit.

## Périmètre fonctionnel

**Inclus**
- Réintégration d'un coureur éliminé, avec validation rétroactive de la boucle concernée.
- Annulation d'une validation posée sur le mauvais coureur.
- Traçabilité minimale : chaque correction indique qu'elle est une correction.

**Exclu**
- La saisie libre d'une heure de fin dans le parcours normal de validation (BR-09).
- Un historique complet des modifications : hors périmètre (voir D-15).

**Dépendances** — BR-11.

## Règles métier

- La correction est réservée au porteur de la permission `manage-laps`.
- Elle passe par un écran distinct du geste de validation courant, pour qu'on ne s'y trompe pas.
- Réintégrer un coureur remet son statut à « en course » et passe la boucle concernée en
  `validated`. La durée est alors recalculée à partir de l'heure fournie par le gérant.
- Annuler une validation replace la boucle en `pending` si le tour est encore ouvert, ou en
  `eliminated` si l'heure limite est passée.
- La correction n'est possible que tant que l'événement est en `running`.
- Une boucle corrigée est marquée comme telle, de façon visible sur la fiche du coureur.

## Critères d'acceptation

```gherkin
Étant donné un coureur éliminé hors délai sur le tour 5
Lorsque le gérant le réintègre en indiquant une heure de fin de 17:58
Alors le coureur repasse "en course"
Et sa boucle du tour 5 est "validated" avec une durée calculée sur 17:58
Et la boucle est marquée comme corrigée

Étant donné une boucle validée par erreur sur le mauvais coureur, tour encore ouvert
Lorsque le gérant annule cette validation
Alors la boucle repasse au statut "pending"

Étant donné une boucle validée par erreur, heure limite du tour dépassée
Lorsque le gérant annule cette validation
Alors la boucle passe au statut "eliminated"
Et le coureur est éliminé

Étant donné un événement au statut "finished"
Lorsque le gérant tente une correction
Alors l'action est refusée

Étant donné un participant connecté
Lorsqu'il tente d'accéder à l'écran de correction
Alors l'accès est refusé
```

## Cas limites et erreurs

- Heure de fin fournie antérieure au départ théorique du tour : refus à la validation.
- Heure de fin postérieure à l'heure limite : acceptée, c'est précisément l'objet de la correction.
- Réintégration d'un coureur ayant abandonné volontairement : autorisée, mais le motif d'abandon est effacé.
- Correction sur un tour antérieur au tour courant : autorisée, les tours suivants ne sont pas rejoués pour autant.

## Impacts techniques

C'est la seule porte par laquelle une heure saisie à la main entre dans le système. Elle doit
rester visiblement exceptionnelle, sinon elle devient le geste courant et le calcul
automatique perd son sens.

## Tâches

- [ ] **T1** — Marqueur de correction sur la boucle `1 pt`
- [ ] **T2** — Action de réintégration avec recalcul de durée et de vitesse `2 pts`
- [ ] **T3** — Action d'annulation d'une validation, selon que le tour est ouvert ou échu `2 pts`
- [ ] **T4** — Écran de correction, distinct du geste de validation courant `2 pts`
- [ ] **T5** — Tests : réintégration, annulation dans les deux cas, bornes d'heure, refus hors course `2 pts`
