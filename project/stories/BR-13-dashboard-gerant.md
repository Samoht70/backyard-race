# BR-13 — Piloter la course depuis un téléphone

| | |
|---|---|
| **Epic** | 3 — Interface de course |
| **Statut** | À faire |
| **Estimation** | 13 pts |
| **Dépend de** | BR-02, BR-09, BR-10 |

## User story

En tant que **gérant en pleine course**,
Je veux **un seul écran qui me montre le tour en cours et me laisse valider les arrivées**,
Afin de **tenir l'événement de bout en bout sans ouvrir un ordinateur**.

## Contexte

C'est l'écran que le gérant regardera pendant quinze heures, debout, une main occupée. Tout
ce qui n'est pas « quel tour, qui est encore là, qui vient de rentrer » l'encombre.

## Périmètre fonctionnel

**Inclus**
- L'entête de tour : numéro, heure de départ, heure de fin.
- Les compteurs : coureurs en course, coureurs sortis.
- La liste des coureurs actifs du tour courant, chacun avec son bouton de validation.
- L'accès en un geste à l'abandon, à la fiche d'un coureur et aux autres vues.

**Exclu**
- Tout compte à rebours (voir D-15).
- Les résultats et le classement : ils ont leur propre page (BR-20, BR-23).
- La configuration de l'événement.

**Dépendances** — BR-02, BR-09, BR-10.

## Règles métier

- L'écran n'est accessible qu'au porteur de la permission `manage-laps`.
- Seuls les coureurs **actifs** apparaissent dans la liste de validation.
- Un coureur dont la boucle vient d'être validée reste visible mais son bouton est remplacé
  par son temps : le gérant doit voir ce qu'il vient de faire.
- Les compteurs sont calculés côté serveur, jamais dérivés de la liste affichée.
- Quand l'événement n'est pas en `running`, l'écran indique l'état réel plutôt qu'une liste vide.

## Critères d'acceptation

```gherkin
Étant donné un événement en course, tour 6, départ 18:00, fin 19:00
Et 24 coureurs en course et 13 coureurs sortis
Lorsque le gérant ouvre son dashboard
Alors l'entête affiche le tour 6, 18:00 et 19:00
Et les compteurs affichent 24 en course et 13 sortis
Et 24 coureurs sont listés avec un bouton de validation

Étant donné cette liste
Lorsque le gérant valide la boucle d'un coureur
Alors la ligne de ce coureur affiche son temps au lieu du bouton
Et le compteur des coureurs en course reste à 24

Étant donné un écran de 375 px de large
Lorsque le dashboard est affiché
Alors chaque bouton de validation est atteignable au pouce
Et aucun défilement horizontal n'est nécessaire

Étant donné un événement au statut "registration"
Lorsque le gérant ouvre son dashboard
Alors l'écran indique que la course n'a pas commencé

Étant donné un participant connecté
Lorsqu'il tente d'ouvrir le dashboard gérant
Alors l'accès est refusé
```

## Cas limites et erreurs

- 40 coureurs à valider : la liste reste utilisable, l'entête de tour reste visible pendant le défilement.
- Validation qui échoue faute de réseau : la ligne revient à son état initial et l'échec est visible.
- Changement de tour pendant que l'écran est ouvert : le contenu suit le nouveau tour (BR-15).
- Aucun coureur actif restant : l'écran le dit clairement au lieu d'afficher une liste vide.

## Impacts techniques

L'écran est rechargé très souvent pendant la course : ses données doivent être agrégées en
base et non recalculées coureur par coureur, sinon le téléphone rame au pire moment.

## Tâches

- [ ] **T1** — Requête d'état de course : tour courant, compteurs, coureurs actifs, en une passe `3 pts`
- [ ] **T2** — Contrôleur Inertia et types TypeScript partagés `2 pts`
- [ ] **T3** — Écran mobile : entête de tour, compteurs, liste de validation `3 pts`
- [ ] **T4** — Retour visuel après validation, y compris en cas d'échec `2 pts`
- [ ] **T5** — Accès rapides : abandon, fiche coureur, autres vues `2 pts`
- [ ] **T6** — Tests : accès, contenu, refus participant, événement hors course `2 pts`
