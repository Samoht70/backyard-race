# BR-16 — Consulter l'historique d'un coureur

| | |
|---|---|
| **Epic** | 3 — Interface de course |
| **Statut** | ✅ Terminé |
| **Estimation** | 3 pts |
| **Révisée** | 2026-08-20 — réduite de 5 à 2 pts (voir D-47) ; 2026-09-07 — remontée à 3 pts, la validation de boucle rejoint le panneau (voir [D-82](../DECISIONS.md)) |
| **Dépend de** | BR-14 |

## User story

En tant que **participant comme gérant**,
Je veux **voir le détail des boucles d'un coureur**,
Afin de **suivre sa progression tour par tour et comparer ses temps**.

## Contexte

La recherche donne l'état d'un coureur, le détail donne son histoire : la liste des boucles avec
leur temps et leur vitesse. C'est aussi de là que le gérant déclenche un abandon ou une
correction.

Le détail **se déplie dans le résultat de recherche de BR-14**, il n'a pas d'écran à lui. Une main
occupée à 4 h du matin n'a pas à quitter le coureur qu'elle vient de trouver, ni à le rechercher
une seconde fois — c'est ce qui fait tomber la story de cinq points à deux.

BR-14 livre la coquille du panneau, portant déjà la distance totale et la dernière boucle validée.
BR-16 la remplit : les boucles une par une, leurs durées, leurs vitesses, et les actions gérant.

Q-05, tranchée par [D-82](../DECISIONS.md), ajoute la validation de boucle à ces actions : le
gérant qui vient de retrouver un coureur précis n'a plus à retourner sur le tableau de BR-13 pour
valider sa boucle en cours. Le bouton de validation en liste de BR-13 ne disparaît pas — les deux
chemins coexistent.

## Périmètre fonctionnel

**Inclus**
- Entête : prénom, nom, dossard, statut, nombre de boucles, distance totale.
- Liste des boucles validées avec distance, durée et vitesse moyenne.
- Motif et heure de sortie pour un coureur sorti.
- Accès aux actions du gérant depuis le panneau : abandon et, depuis D-82, validation de la
  boucle en attente du tour courant.

**Exclu**
- Les données personnelles du coureur, sauf pour le gérant et pour l'intéressé.
- La modification d'une boucle déjà validée depuis cet écran : correction, réintégration et
  annulation restent sur le poste de BR-12, qui n'a pas de filtre par coureur.
- Un écran dédié et sa route : le détail est un panneau dépliable des résultats de BR-14.
- L'entête, la distance totale et la dernière boucle validée : BR-14 les livre déjà.

**Dépendances** — BR-14.

## Règles métier

- Le détail est accessible à tout utilisateur connecté, mais les informations personnelles
  n'apparaissent que pour le gérant et pour le coureur concerné. Depuis [D-80](../DECISIONS.md),
  la recherche qui ouvre ce panneau n'est de toute façon accessible qu'au gérant : un participant
  ne peut plus le déplier que sur sa propre course, jamais sur celle d'un autre.
- Les boucles sont listées du tour 1 au dernier tour couru.
- Une boucle non validée n'affiche ni durée ni vitesse.
- Une boucle corrigée est signalée comme telle.
- Le bouton de validation n'apparaît que si le coureur a une boucle en attente sur le tour courant
  et que l'utilisateur porte la permission `validate-laps`.
- Le bouton d'abandon n'apparaît que si le coureur est en course et que l'utilisateur porte la
  permission `manage-laps`.

## Critères d'acceptation

```gherkin
Étant donné un coureur en course avec 8 boucles validées de 6 km
Lorsqu'un participant déplie son détail
Alors l'entête affiche 8 boucles et 48 km
Et les 8 boucles sont listées du tour 1 au tour 8
Et chaque boucle affiche sa distance, sa durée et sa vitesse moyenne

Étant donné un coureur éliminé hors délai au tour 6
Lorsqu'un participant déplie son détail
Alors le motif et l'heure de sortie sont affichés

Étant donné un coureur dont la boucle du tour 5 a été corrigée
Lorsque son détail est déplié
Alors cette boucle est signalée comme corrigée

Étant donné le gérant qui retrouve un coureur en course, tour courant en attente de validation
Lorsqu'il déplie son détail
Alors les actions d'abandon et de validation sont proposées

Étant donné un coureur qui consulte son propre détail depuis l'accueil
Lorsque son détail est affiché
Alors aucune action de gestion n'est proposée
```

## Cas limites et erreurs

- Coureur sans aucune boucle validée : la liste est vide et le dit.
- Boucle en cours au moment de la consultation : elle apparaît sans temps.
- Distance de boucle corrigée par le gérant : les distances et vitesses affichées suivent la nouvelle valeur.

## Impacts techniques

L'écran lit des données déjà produites par le moteur de course, mais la recherche renvoie une
liste de coureurs : les boucles de chacun doivent être chargées en une passe (`laps.round` en
eager loading), jamais requêtées coureur par coureur.

`Manage\LapValidationController` et `Manage\RunnerWithdrawalController` redirigeaient tous deux
vers `manage.index` en dur ; avec un second point d'entrée depuis `/dashboard` (D-82), ils
reviennent désormais sur la page d'où la requête est partie.

## Tâches

- [x] **T1** — Boucles du coureur chargées en une passe avec son résultat, panneau de BR-14
      rempli `1 pt`
- [x] **T2** — Bouton de validation dans le panneau, redirection des deux contrôleurs vers la
      page d'origine, avec ses tests `1 pt`
- [x] **T3** — Cloisonnement des données personnelles et des actions gérant, avec ses tests `1 pt`
