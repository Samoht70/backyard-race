# BR-24 — Donner au coureur sa vue de course

| | |
|---|---|
| **Epic** | 6 — Expérience participant |
| **Statut** | ✅ Terminé |
| **Estimation** | 8 pts |
| **Révisée** | 2026-09-07 — le détail boucle par boucle reste chez le coureur et les raccourcis ne sont pas livrés (voir [D-85](../DECISIONS.md)) |
| **Dépend de** | BR-02, BR-08, BR-33 |

## User story

En tant que **participant**,
Je veux **voir ma situation en un écran**,
Afin de **savoir où j'en suis et à quelle heure je repars, sans demander au gérant**.

## Contexte

Le coureur n'a pas besoin du tableau de bord du gérant : il veut son nombre de boucles, sa
distance, son dernier temps et l'heure du prochain départ. Un écran, quatre chiffres, quelques
raccourcis.

## Frontière avec BR-33

BR-33 a livré l'accueil d'avant-course — statut d'inscription, dossard, raccourcis — et la
navigation qui y mène. Cette story **remplace le contenu de cet écran** par les chiffres de course
dès que le moteur existe ; elle ne repose ni une route, ni une entrée de menu, ni un second tableau
de bord.

## Périmètre fonctionnel

**Inclus**
- Son prénom, son numéro de dossard, son statut de course.
- Ses boucles réalisées, sa distance totale, la durée et la vitesse de sa dernière boucle.
- Le prochain départ : numéro du tour et heure.
- Le détail boucle par boucle : temps, distance et vitesse de chacune ([D-85](../DECISIONS.md)).

**Exclu**
- Toute action sur la course : le coureur ne valide rien et n'abandonne pas lui-même.
- Les raccourcis vers le briefing et les documents : la navigation les porte depuis BR-33 ([D-85](../DECISIONS.md)).
- Tout compte à rebours (voir D-15).

**Dépendances** — BR-02, BR-08.

## Règles métier

- Le coureur ne voit que ses propres données de course.
- L'écran affiche le statut réel : en course, éliminé, abandon.
- Le prochain départ n'est affiché que si le coureur est encore en course, et seulement en l'absence du bandeau du tour, qui porte déjà l'échéance ([D-85](../DECISIONS.md)).
- Un coureur sorti voit son résultat figé et le motif de sa sortie.
- Un utilisateur connecté sans inscription confirmée est orienté vers son inscription plutôt
  que vers un écran vide.

## Critères d'acceptation

```gherkin
Étant donné un coureur en course, dossard 12, 7 boucles validées de 6 km, dernière boucle en 48:32
Et un événement dont le tour 8 part à 20:00
Lorsqu'il ouvre son tableau de bord
Alors il voit son dossard 12 et son statut "en course"
Et il voit 7 boucles et 42 km
Et il voit 48:32 comme durée de sa dernière boucle
Et il voit le tour 8 à 20:00 comme prochain départ

Étant donné un coureur éliminé au tour 6
Lorsqu'il ouvre son tableau de bord
Alors il voit son statut "éliminé" et le motif de sa sortie
Et aucun prochain départ ne lui est annoncé

Étant donné un utilisateur connecté dont l'inscription est encore "pending"
Lorsqu'il ouvre son tableau de bord
Alors il est orienté vers l'état de son inscription

Étant donné un coureur connecté
Lorsqu'il consulte son tableau de bord
Alors aucune action de validation ou d'abandon ne lui est proposée
```

## Cas limites et erreurs

- Coureur confirmé avant le départ de la course : zéro boucle, et le premier départ annoncé comme prochain départ.
- Aucune boucle validée alors que la course a commencé : la durée de dernière boucle est vide, pas à zéro.
- Événement terminé : plus aucun prochain départ n'est annoncé, et le renvoi vers les résultats attend BR-23 ([D-85](../DECISIONS.md)).

## Impacts techniques

Aucun — l'écran lit les agrégats déjà produits pour le tableau des coureurs.

## Tâches

- [x] **T1** — Requête de la situation du coureur connecté `2 pts`
- [x] **T2** — Écran mobile : statut, chiffres, prochain départ, tableau des boucles `3 pts`
- [x] **T3** — Redirections selon l'état de l'inscription et de l'événement `1 pt`
- [x] **T4** — Tests : contenu, coureur sorti, inscription non confirmée, aucune action de gestion `2 pts`
