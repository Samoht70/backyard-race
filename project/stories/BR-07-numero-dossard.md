# BR-07 — Attribuer automatiquement un numéro de dossard unique

| | |
|---|---|
| **Epic** | 1 — Fondations |
| **Statut** | ✅ Terminé |
| **Estimation** | 5 pts |
| **Dépend de** | BR-06 |

## User story

En tant que **gérant**,
Je veux **qu'un numéro de dossard soit attribué tout seul à chaque coureur confirmé**,
Afin de **ne pas tenir une liste de numéros à la main et de n'avoir aucun doublon au départ**.

## Contexte

Le numéro de dossard identifie le coureur sur le terrain, dans le tableau des coureurs et sur
son tableau de bord. Il doit être unique, stable, et attribué sans intervention.

## Périmètre fonctionnel

**Inclus**
- Attribution du premier numéro libre à la confirmation d'une inscription.
- Format d'affichage sur trois chiffres : `001`, `002`, … `040`.
- Affichage du numéro sur la fiche du participant et dans les listes.

**Exclu**
- L'attribution manuelle ou le choix de son numéro par le coureur.
- Le dossard imprimable : abandonné le 2026-08-20 (voir D-47). Le numéro s'affiche à
  l'écran, sur le tableau de bord du coureur (BR-24).

**Dépendances** — BR-06.

## Règles métier

- Le numéro est attribué au moment de la confirmation, jamais à l'inscription.
- Il vaut le plus petit entier positif non encore utilisé sur l'événement.
- Il est unique sur l'événement, garanti en base et pas seulement dans le code.
- Il reste attaché au participant à vie, y compris après annulation, abandon ou élimination.
- Un numéro libéré par une annulation n'est **pas** réattribué.
- Un participant déjà numéroté conserve son numéro si son inscription est reconfirmée.

## Critères d'acceptation

```gherkin
Étant donné un événement sans aucun participant confirmé
Lorsque le gérant confirme une première inscription
Alors le numéro de dossard attribué est 1
Et il s'affiche "001"

Étant donné des participants portant les numéros 1, 2 et 3
Lorsque le gérant confirme une nouvelle inscription
Alors le numéro attribué est 4

Étant donné un participant portant le numéro 2 dont l'inscription est annulée
Lorsque le gérant confirme une nouvelle inscription
Alors le numéro attribué est le premier libre au-delà du plus grand attribué
Et le numéro 2 reste associé au participant annulé

Étant donné un participant portant déjà le numéro 7
Lorsque son inscription est confirmée une seconde fois
Alors il porte toujours le numéro 7
```

## Cas limites et erreurs

- Deux confirmations simultanées : les deux coureurs obtiennent deux numéros distincts, jamais le même.
- Confirmation au-delà de la centaine : le format d'affichage reste correct.
- Tentative d'écriture d'un numéro déjà pris : rejetée par la contrainte d'unicité en base.

## Impacts techniques

Le risque réel est la collision entre deux confirmations concurrentes. La garantie doit être
portée par la base de données, la vérification applicative seule ne suffit pas.

## Tâches

- [x] **T1** — Colonne de numéro de dossard, index unique par événement `1 pt`
- [x] **T2** — Action d'attribution du premier numéro libre, à l'abri des accès concurrents `2 pts`
- [x] **T3** — Formatage sur trois chiffres, partagé par tous les écrans `1 pt`
- [x] **T4** — Tests : premier numéro, suite, annulation, reconfirmation, concurrence `2 pts`

## Ce que la concurrence est réellement, ici

L'attribution n'a pas de mécanisme propre : elle hérite du verrou de ligne que la confirmation
prend déjà (D-48), et le numéro s'écrit dans le même `UPDATE` conditionnel que le statut. Les deux
autres stratégies — réessai sur violation d'unicité, insertion conditionnelle — sont fermées, la
première par `NoTryCatchRule`, la seconde par MySQL. Voir [D-49](../DECISIONS.md).

Le cas limite « deux confirmations simultanées » n'a pas de test qui simule deux connexions : sous
`RefreshDatabase` il serait vert sans rien exercer. Ce sont l'écriture conditionnelle et l'index
unique qui sont testés, chacun de son côté.
