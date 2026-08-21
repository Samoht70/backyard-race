# BR-21 — Raconter la course en chiffres

| | |
|---|---|
| **Epic** | 5 — Après-course |
| **Statut** | ⛔ Abandonné |
| **Estimation** | 8 pts, non engagés |
| **Dépend de** | BR-20 |

## Pourquoi cette story n'est pas faite

Abandonnée le 2026-08-20, à l'élagage du backlog (voir D-47).

Le périmètre recoupait celui de BR-23, qui annonce déjà « participants, boucles réalisées,
kilomètres parcourus ». Deux pages d'après-course pour les mêmes chiffres, sur un événement qui
sert une fois, c'est une page de trop.

Le contenu n'est pas perdu : les indicateurs et le tableau par tour — boucles terminées, coureurs
restants, abandons et hors délai — sont repris par BR-23, qui passe de 5 à 8 points. Les règles
d'agrégation en base et le calcul de la distance totale y valent inchangées.

## User story

En tant que **participant comme gérant**,
Je veux **voir les chiffres de l'événement**,
Afin de **mesurer ce qu'on vient de faire et d'avoir de quoi en parler longtemps**.

## Contexte

C'est la page souvenir : combien on était, combien de boucles, combien de kilomètres, à quel
tour le peloton a fondu. Uniquement des chiffres — pas de graphique, arbitré en D-16.

## Périmètre fonctionnel

**Inclus**
- Les indicateurs de l'événement : nombre de participants, nombre total de boucles, distance
  totale parcourue, coureurs encore en course, distance et nombre de boucles du vainqueur,
  durée totale de l'événement.
- Un tableau par tour : boucles terminées, coureurs restants, éliminations, en distinguant
  abandons et hors délai.

**Exclu**
- Tout graphique, courbe ou histogramme (voir D-16).
- Les statistiques individuelles comparées entre coureurs.
- L'export des données (voir D-15).

**Dépendances** — BR-20 pour la notion de vainqueur.

## Règles métier

- La page est accessible au porteur de la permission `view-statistics` et à tous les
  participants une fois l'événement terminé.
- Pendant la course, les indicateurs sont ceux de l'instant ; le vainqueur n'existe pas encore.
- Le nombre total de boucles est le nombre de boucles `validated`, tous coureurs confondus.
- La distance totale est le nombre de boucles validées multiplié par la distance de boucle de
  l'événement (voir D-17).
- La durée totale de l'événement court du premier départ à la clôture.
- Les éliminations sont comptées au tour où elles ont eu lieu, en distinguant abandons et
  hors délai.
- Les indicateurs sont calculés par agrégation en base, jamais par parcours en PHP.

## Critères d'acceptation

```gherkin
Étant donné un événement terminé avec 37 participants, 247 boucles validées et 1482 km
Lorsqu'un participant ouvre la page statistiques
Alors ces trois indicateurs sont affichés

Étant donné un événement terminé dont le vainqueur a 18 boucles et une distance de boucle de 6 km
Lorsque la page statistiques est affichée
Alors le vainqueur affiché a 18 boucles et 108 km

Étant donné un événement dont 40 coureurs sont partis, 32 ont terminé le tour 3 et 20 le tour 5
Lorsque le tableau par tour est affiché
Alors la ligne du tour 3 indique 32 boucles terminées
Et la ligne du tour 5 indique 20 boucles terminées

Étant donné un tour ayant vu 3 abandons et 5 éliminations hors délai
Lorsque le tableau par tour est affiché
Alors la ligne de ce tour distingue les 3 abandons des 5 hors délai

Étant donné un événement encore en course
Lorsque le gérant ouvre la page statistiques
Alors les indicateurs de l'instant sont affichés
Et aucun vainqueur n'est annoncé

Étant donné un événement encore en course
Lorsqu'un participant tente d'ouvrir la page statistiques
Alors l'accès est refusé

Étant donné la page statistiques affichée sur un écran de 375 px
Lorsque le tableau par tour est affiché
Alors il reste lisible sans défilement horizontal de la page
```

## Cas limites et erreurs

- Événement sans aucune boucle validée : les indicateurs sont à zéro et la page le dit, plutôt qu'un tableau vide.
- Un seul tour couru : le tableau ne comporte qu'une ligne, ce qui reste correct.
- Plusieurs vainqueurs ex æquo : l'affichage les mentionne tous.
- Distance de boucle corrigée par le gérant : les totaux suivent la nouvelle valeur.

## Impacts techniques

La page agrège toutes les boucles de l'événement. Faite naïvement, elle produit une requête par
tour et par coureur. Comme elle est consultable pendant la course, ses agrégats doivent être
calculés en base et, si nécessaire, mis en cache.

## Tâches

- [ ] **T1** — Service d'agrégation des indicateurs, tout en base `3 pts`
- [ ] **T2** — Comptages par tour : boucles terminées, coureurs restants, abandons, hors délai `2 pts`
- [ ] **T3** — Page statistiques responsive : indicateurs et tableau par tour, avec états vides `2 pts`
- [ ] **T4** — Tests : indicateurs, comptages par tour, accès selon statut de l'événement `2 pts`
