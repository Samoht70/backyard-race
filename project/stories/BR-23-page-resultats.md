# BR-23 — Afficher les résultats et les chiffres de l'événement

| | |
|---|---|
| **Epic** | 5 — Après-course |
| **Statut** | À faire |
| **Estimation** | 8 pts |
| **Révisée** | 2026-08-20 — passe de 5 à 8 pts, absorbe BR-21 (voir D-47) |
| **Dépend de** | BR-20 |

## User story

En tant que **participant**,
Je veux **arriver sur les résultats de la course en ouvrant l'application**,
Afin de **voir le vainqueur et les chiffres de la soirée sans rien chercher**.

## Contexte

Une fois l'événement terminé, l'écran d'accueil n'a plus à parler du tour en cours. Il devient
la page d'après-course : le vainqueur, les grands chiffres, le tableau tour par tour, et l'accès
au classement.

C'est **la seule** page d'après-course. BR-21 annonçait les mêmes indicateurs sur une seconde
page : les deux ont fusionné le 2026-08-20, ce qui vaut à cette story les trois points de la
partie statistiques. Uniquement des chiffres, pas de graphique (voir D-16).

## Périmètre fonctionnel

**Inclus**
- Bascule automatique de l'accueil vers les résultats quand l'événement est terminé.
- Le vainqueur, son nombre de boucles et sa distance.
- Les indicateurs de l'événement : nombre de participants, nombre total de boucles, distance
  totale parcourue, durée totale de l'événement.
- Un tableau par tour : boucles terminées, coureurs restants, éliminations, en distinguant
  abandons et hors délai.
- L'accès au classement figé, et un lien vers l'album photos partagé.

**Exclu**
- Le détail du classement : il vit sur sa propre page (BR-20).
- Tout graphique, courbe ou histogramme (voir D-16).
- Les statistiques individuelles comparées entre coureurs.
- L'export des données (voir D-15).
- Une galerie de photos hébergée par l'application : abandonnée avec BR-22 ; le lien pointe sur
  un album partagé.
- Le partage sur les réseaux sociaux.

**Dépendances** — BR-20 pour le classement figé et la notion de vainqueur.

## Règles métier

- La page ne s'affiche que si l'événement est en `finished`.
- Le vainqueur est le premier du classement figé, jamais recalculé à l'affichage.
- En cas d'ex æquo en tête, tous les premiers sont annoncés.
- Le nombre total de boucles est le nombre de boucles `validated`, tous coureurs confondus.
- La distance totale est le nombre de boucles validées multiplié par la distance de boucle de
  l'événement (voir D-17).
- La durée totale de l'événement court du premier départ à la clôture.
- Les éliminations sont comptées au tour où elles ont eu lieu, en distinguant abandons et
  hors délai.
- Les indicateurs sont calculés par agrégation en base, jamais par parcours en PHP.

## Critères d'acceptation

```gherkin
Étant donné un événement terminé dont le vainqueur a 18 boucles et 108 km
Lorsqu'un participant ouvre l'accueil
Alors la page annonce la fin de la Backyard
Et affiche le vainqueur avec 18 boucles et 108 km
Et affiche le nombre de participants, de boucles et de kilomètres
Et propose l'accès au classement

Étant donné un événement terminé avec 37 participants, 247 boucles validées et 1482 km
Lorsqu'un participant ouvre les résultats
Alors ces trois indicateurs sont affichés

Étant donné un événement dont 40 coureurs sont partis, 32 ont terminé le tour 3 et 20 le tour 5
Lorsque le tableau par tour est affiché
Alors la ligne du tour 3 indique 32 boucles terminées
Et la ligne du tour 5 indique 20 boucles terminées

Étant donné un tour ayant vu 3 abandons et 5 éliminations hors délai
Lorsque le tableau par tour est affiché
Alors la ligne de ce tour distingue les 3 abandons des 5 hors délai

Étant donné un événement encore en course
Lorsqu'un participant ouvre l'accueil
Alors la page de résultats n'est pas affichée

Étant donné un événement terminé avec deux vainqueurs à égalité
Lorsque la page de résultats est affichée
Alors les deux sont annoncés

Étant donné la page de résultats affichée sur un écran de 375 px
Lorsque le tableau par tour est affiché
Alors il reste lisible sans défilement horizontal de la page
```

## Cas limites et erreurs

- Aucune boucle validée sur l'événement : la page le dit sans annoncer de vainqueur, et les
  indicateurs sont à zéro plutôt qu'un tableau vide.
- Un seul tour couru : le tableau ne comporte qu'une ligne, ce qui reste correct.
- Distance de boucle corrigée par le gérant : les totaux suivent la nouvelle valeur.
- Lien d'album absent : l'accès correspondant n'est pas proposé, plutôt que de mener nulle part.

## Impacts techniques

La page agrège toutes les boucles de l'événement. Faite naïvement, elle produit une requête par
tour et par coureur : ses agrégats sont calculés en base, en une passe par indicateur. Comme
l'événement est terminé, les valeurs ne bougent plus — la mise en cache n'est pas nécessaire.

## Tâches

- [ ] **T1** — Bascule de l'accueil selon le statut de l'événement `1 pt`
- [ ] **T2** — Service d'agrégation des indicateurs, tout en base `2 pts`
- [ ] **T3** — Comptages par tour : boucles terminées, coureurs restants, abandons, hors délai `2 pts`
- [ ] **T4** — Page de résultats : vainqueur, indicateurs, tableau par tour, accès et états vides `2 pts`
- [ ] **T5** — Tests : affichage selon statut, ex æquo, aucune boucle, comptages par tour `1 pt`
