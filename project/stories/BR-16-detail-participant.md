# BR-16 — Consulter l'historique d'un coureur

| | |
|---|---|
| **Epic** | 3 — Interface de course |
| **Statut** | À faire |
| **Estimation** | 5 pts |
| **Dépend de** | BR-14 |

## User story

En tant que **participant comme gérant**,
Je veux **voir le détail des boucles d'un coureur**,
Afin de **suivre sa progression tour par tour et comparer ses temps**.

## Contexte

Le tableau des coureurs donne l'état, la fiche donne l'histoire : la liste des boucles avec
leur temps et leur vitesse. C'est aussi de là que le gérant déclenche un abandon ou une
correction.

## Périmètre fonctionnel

**Inclus**
- Entête : prénom, nom, dossard, statut, nombre de boucles, distance totale.
- Liste des boucles validées avec distance, durée et vitesse moyenne.
- Motif et heure de sortie pour un coureur sorti.
- Accès aux actions du gérant depuis la fiche.

**Exclu**
- Les données personnelles du coureur, sauf pour le gérant et pour l'intéressé.
- La modification des boucles depuis cet écran : elle passe par BR-12.

**Dépendances** — BR-14.

## Règles métier

- La fiche est accessible à tout utilisateur connecté, mais les informations personnelles
  n'apparaissent que pour le gérant et pour le coureur concerné.
- Les boucles sont listées du tour 1 au dernier tour couru.
- Une boucle non validée n'affiche ni durée ni vitesse.
- Une boucle corrigée est signalée comme telle.
- Les actions gérant n'apparaissent que si l'utilisateur a la permission correspondante.

## Critères d'acceptation

```gherkin
Étant donné un coureur en course avec 8 boucles validées de 6 km
Lorsqu'un participant ouvre sa fiche
Alors l'entête affiche 8 boucles et 48 km
Et les 8 boucles sont listées du tour 1 au tour 8
Et chaque boucle affiche sa distance, sa durée et sa vitesse moyenne

Étant donné un coureur éliminé hors délai au tour 6
Lorsqu'un participant ouvre sa fiche
Alors le motif et l'heure de sortie sont affichés

Étant donné un coureur dont la boucle du tour 5 a été corrigée
Lorsque sa fiche est affichée
Alors cette boucle est signalée comme corrigée

Étant donné un participant qui ouvre la fiche d'un autre coureur
Lorsque la fiche est affichée
Alors aucune donnée personnelle de ce coureur n'apparaît
Et aucune action de gestion n'est proposée

Étant donné le gérant qui ouvre la même fiche
Lorsque la fiche est affichée
Alors les actions d'abandon et de correction sont proposées
```

## Cas limites et erreurs

- Coureur sans aucune boucle validée : la liste est vide et le dit.
- Boucle en cours au moment de la consultation : elle apparaît sans temps.
- Distance de boucle corrigée par le gérant : les distances et vitesses affichées suivent la nouvelle valeur.

## Impacts techniques

Aucun — l'écran lit des données déjà produites par le moteur de course.

## Tâches

- [ ] **T1** — Requête de la fiche : entête agrégé et boucles ordonnées `2 pts`
- [ ] **T2** — Écran de fiche coureur, responsive `2 pts`
- [ ] **T3** — Affichage conditionnel des données personnelles et des actions gérant `1 pt`
- [ ] **T4** — Tests : contenu, cloisonnement des données personnelles, actions selon permission `2 pts`
