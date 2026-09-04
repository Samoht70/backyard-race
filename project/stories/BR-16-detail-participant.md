# BR-16 — Consulter l'historique d'un coureur

| | |
|---|---|
| **Epic** | 3 — Interface de course |
| **Statut** | À faire |
| **Estimation** | 2 pts |
| **Révisée** | 2026-08-20 — réduite de 5 à 2 pts (voir D-47) |
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

## Périmètre fonctionnel

**Inclus**
- Entête : prénom, nom, dossard, statut, nombre de boucles, distance totale.
- Liste des boucles validées avec distance, durée et vitesse moyenne.
- Motif et heure de sortie pour un coureur sorti.
- Accès aux actions du gérant depuis le panneau.

**Exclu**
- Les données personnelles du coureur, sauf pour le gérant et pour l'intéressé.
- La modification des boucles depuis cet écran : elle passe par BR-12.
- Un écran dédié et sa route : le détail est un panneau dépliable des résultats de BR-14.
- L'entête, la distance totale et la dernière boucle validée : BR-14 les livre déjà.

**Dépendances** — BR-14.

## Règles métier

- Le détail est accessible à tout utilisateur connecté, mais les informations personnelles
  n'apparaissent que pour le gérant et pour le coureur concerné.
- Les boucles sont listées du tour 1 au dernier tour couru.
- Une boucle non validée n'affiche ni durée ni vitesse.
- Une boucle corrigée est signalée comme telle.
- Les actions gérant n'apparaissent que si l'utilisateur a la permission correspondante.

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

Étant donné un participant qui déplie le détail d'un autre coureur
Lorsque le détail est affiché
Alors aucune donnée personnelle de ce coureur n'apparaît
Et aucune action de gestion n'est proposée

Étant donné le gérant qui déplie le même détail
Lorsque le détail est affiché
Alors les actions d'abandon et de correction sont proposées
```

## Cas limites et erreurs

- Coureur sans aucune boucle validée : la liste est vide et le dit.
- Boucle en cours au moment de la consultation : elle apparaît sans temps.
- Distance de boucle corrigée par le gérant : les distances et vitesses affichées suivent la nouvelle valeur.

## Impacts techniques

Aucun — l'écran lit des données déjà produites par le moteur de course.

## Tâches

- [ ] **T1** — Boucles du coureur chargées avec son résultat, panneau de BR-14 rempli `1 pt`
- [ ] **T2** — Cloisonnement des données personnelles et des actions gérant, avec ses tests `1 pt`
