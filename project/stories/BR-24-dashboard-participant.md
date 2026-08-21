# BR-24 — Donner au coureur sa vue de course

| | |
|---|---|
| **Epic** | 6 — Expérience participant |
| **Statut** | À faire |
| **Estimation** | 8 pts |
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
- Ses boucles réalisées, sa distance totale, la durée de sa dernière boucle.
- Le prochain départ : numéro du tour et heure.
- Les raccourcis vers le briefing et les documents.

**Exclu**
- Toute action sur la course : le coureur ne valide rien et n'abandonne pas lui-même.
- Le détail boucle par boucle : il se déplie dans le tableau des coureurs (BR-14, BR-16).
- Tout compte à rebours (voir D-15).

**Dépendances** — BR-02, BR-08.

## Règles métier

- Le coureur ne voit que ses propres données de course.
- L'écran affiche le statut réel : en course, éliminé, abandon.
- Le prochain départ n'est affiché que si le coureur est encore en course.
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
- Événement terminé : l'écran renvoie vers les résultats (BR-23).

## Impacts techniques

Aucun — l'écran lit les agrégats déjà produits pour le tableau des coureurs.

## Tâches

- [ ] **T1** — Requête de la situation du coureur connecté `2 pts`
- [ ] **T2** — Écran mobile : statut, chiffres, prochain départ, raccourcis `3 pts`
- [ ] **T3** — Redirections selon l'état de l'inscription et de l'événement `1 pt`
- [ ] **T4** — Tests : contenu, coureur sorti, inscription non confirmée, aucune action de gestion `2 pts`
