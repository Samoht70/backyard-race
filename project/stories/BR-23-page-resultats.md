# BR-23 — Afficher les résultats de l'événement

| | |
|---|---|
| **Epic** | 5 — Après-course |
| **Statut** | À faire |
| **Estimation** | 5 pts |
| **Dépend de** | BR-20, BR-21, BR-22 |

## User story

En tant que **participant**,
Je veux **arriver sur les résultats de la course en ouvrant l'application**,
Afin de **voir le vainqueur et les chiffres de la soirée sans rien chercher**.

## Contexte

Une fois l'événement terminé, l'écran d'accueil n'a plus à parler du tour en cours. Il devient
la page de résultats : le vainqueur, les grands chiffres, et l'accès au classement, aux
statistiques et aux photos.

## Périmètre fonctionnel

**Inclus**
- Bascule automatique de l'accueil vers les résultats quand l'événement est terminé.
- Le vainqueur, son nombre de boucles et sa distance.
- Les chiffres collectifs : participants, boucles réalisées, kilomètres parcourus.
- Les accès au classement, aux statistiques et à la galerie.

**Exclu**
- Le détail du classement : il vit sur sa propre page (BR-20).
- Le partage sur les réseaux sociaux.

**Dépendances** — BR-20, BR-21, BR-22.

## Règles métier

- La page ne s'affiche que si l'événement est en `finished`.
- Le vainqueur est le premier du classement figé, jamais recalculé à l'affichage.
- Les chiffres affichés sont ceux du service de statistiques, sans second calcul.
- En cas d'ex æquo en tête, tous les premiers sont annoncés.

## Critères d'acceptation

```gherkin
Étant donné un événement terminé dont le vainqueur a 18 boucles et 108 km
Lorsqu'un participant ouvre l'accueil
Alors la page annonce la fin de la Backyard
Et affiche le vainqueur avec 18 boucles et 108 km
Et affiche le nombre de participants, de boucles et de kilomètres
Et propose l'accès au classement, aux statistiques et aux photos

Étant donné un événement encore en course
Lorsqu'un participant ouvre l'accueil
Alors la page de résultats n'est pas affichée

Étant donné un événement terminé avec deux vainqueurs à égalité
Lorsque la page de résultats est affichée
Alors les deux sont annoncés
```

## Cas limites et erreurs

- Aucune boucle validée sur l'événement : la page le dit sans annoncer de vainqueur.
- Galerie vide ou statistiques indisponibles : les accès correspondants ne mènent pas à une page cassée.

## Impacts techniques

Aucun — la page réunit des données déjà produites par les stories précédentes.

## Tâches

- [ ] **T1** — Bascule de l'accueil selon le statut de l'événement `1 pt`
- [ ] **T2** — Page de résultats : vainqueur, chiffres, accès `3 pts`
- [ ] **T3** — Tests : affichage selon statut, ex æquo, aucune boucle `2 pts`
