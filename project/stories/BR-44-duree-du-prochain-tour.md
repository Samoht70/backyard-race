# BR-44 — Changer la durée du prochain tour avant qu'il parte

| | |
|---|---|
| **Epic** | 2 — Moteur de course |
| **Statut** | À faire |
| **Estimation** | 5 pts |
| **Créée** | 2026-08-31 — demandée par le propriétaire avant d'ouvrir l'epic 2 |
| **Dépend de** | BR-04 |

## User story

En tant que **gérant**,
Je veux **changer la durée d'un tour qui n'a pas encore commencé**,
Afin de **resserrer ou desserrer la course sur le moment, sans réécrire les tours déjà courus**.

## Contexte

La grille horaire de BR-04 est une fonction arithmétique : `premier départ + (N - 1) × durée de
boucle`. Elle a une propriété qu'on ne voit pas en la lisant — **elle n'a pas de passé**. Changer
`lap_duration_minutes` ne déplace pas les tours à venir, ça recalcule aussi les tours déjà courus, et
avec eux les heures limites contre lesquelles des coureurs auront été éliminés par BR-11.

C'est pour cette raison que `RunningEventState::frozenAttributes()` gèle `first_start_at` et
`lap_duration_minutes` dès que l'événement passe en course : le seul geste disponible aujourd'hui est
si dangereux que l'application le refuse. Le gérant qui veut serrer la course d'un cran n'a donc
aucun moyen de le faire, et le champ qui semblait fait pour ça est celui qui réécrirait la nuit.

Cette story n'ouvre pas ce champ. Elle ajoute un objet qui, par construction, ne peut atteindre que
le futur : **une durée qui prend effet à partir d'un tour donné**. La grille cesse d'être une
multiplication et devient une fonction par morceaux, dont chaque morceau commence à un tour non
encore parti.

L'idée est née d'un autre sujet — mettre des gages sur les tours — et c'est le seul gage qui ait
survécu à l'examen : celui qui change le temps du tour n'est pas une annonce à faire au corral, c'est
une heure limite contre laquelle des coureurs vont être éliminés. Les autres gages restent à l'oral
et ne laissent rien dans le code ([D-72](../DECISIONS.md)).

## Périmètre fonctionnel

**Inclus**
- Une durée de boucle qui prend effet **à partir d'un tour** et vaut pour tous les tours suivants.
- Deux gestes sur ce même enregistrement : « à partir de ce tour » et « ce tour seulement ».
- Le geste porte sur le **prochain tour**, depuis l'écran de pilotage du gérant.
- Le refus d'un tour déjà parti, énoncé au gérant.
- Les horaires affichés — tour courant, heure de départ, heure limite — qui suivent la nouvelle
  grille sans rien savoir d'elle.

**Exclu**
- Le dégel de `first_start_at` et `lap_duration_minutes` en course. Les deux restent gelés : la
  configuration décrit la grille avant le départ, ce geste-ci la corrige après.
- Une durée par tour **passé**, et toute forme de correction rétroactive d'un horaire. La correction
  d'une boucle déjà courue est BR-12, et elle porte sur la boucle, pas sur la grille.
- Une distance par tour. [D-17](../DECISIONS.md) tient : la distance est unique pour l'événement, et
  cette story ne l'entrouvre pas.
- Un tableau de planification des tours à venir. Le geste réel se fait cinq minutes avant le départ,
  sur le tour d'après ; une grille prévisionnelle éditable n'a pas d'appelant.
- Une consigne, un gage ou un texte libre porté par le tour. Voir D-72.
- La notification des coureurs. [D-15](../DECISIONS.md) tient : le changement est annoncé au corral,
  l'application ne l'envoie à personne.

**Dépendances** — BR-04 pour la grille. Aucune sur BR-08 : la story ne touche pas aux boucles.

## Règles métier

- Une durée de boucle vaut **à partir d'un tour et jusqu'au changement suivant**. En l'absence de
  tout changement, la grille est celle de BR-04, à l'identique.
- **Seul un tour qui n'est pas encore parti se change.** C'est la règle qui remplace le gel de
  `RunningEventState`, et elle est plus fine que lui : le passé n'est pas protégé par une
  interdiction générale, il l'est parce qu'un tour parti est hors d'atteinte.
- Le tour parti se constate sur la **ligne de tour existante**, pas sur une comparaison d'horloge.
  Un tour dont la ligne est écrite est un tour dont les horaires sont figés ; c'est cette ligne, et
  elle seule, que BR-11 lira pour éliminer.
- Le changement rattrape donc la matérialisation avant de se prononcer : les tours dus à l'heure
  serveur sont ouverts, puis le numéro demandé doit être strictement supérieur au dernier tour
  ouvert. Sans ce rattrapage, la minute qui sépare deux passages du planificateur est une fenêtre
  où un tour déjà commencé passe pour un tour à venir.
- « Ce tour seulement » n'est pas un second mécanisme : c'est le même changement, doublé du retour à
  la durée précédente au tour d'après.
- Un changement écrit sur un tour qui en portait déjà un le remplace. Deux durées ne prennent pas
  effet au même tour.
- La durée obéit aux mêmes bornes que celle de la configuration : au moins une minute, au plus
  1440.
- Les changements sont exprimés en **numéros de tour**, jamais en horaires. Ils survivent donc
  intacts à un déplacement du premier départ tant que la course n'a pas commencé : toute la grille
  glisse, les morceaux restent aux mêmes tours.
- Le refus est une exception de domaine, comme les transitions d'inscription et d'événement. Il ne
  se déduit pas d'un booléen rendu par l'écran.
- Le geste est réservé à la permission `manage-event` : la grille horaire est une propriété de
  l'événement, et c'est déjà derrière cette permission que vit `lap_duration_minutes`.

## Critères d'acceptation

```gherkin
Étant donné un événement dont le premier départ est à 13:00 et la durée de boucle d'une heure
Et qu'aucune durée n'a été changée
Lorsque les horaires du tour 4 sont demandés
Alors il part à 16:00 et se termine à 17:00

Étant donné le même événement en course, le tour 2 ouvert, et il est 14:30
Lorsque le gérant fixe la durée à 55 minutes à partir du tour 3
Alors le tour 3 part à 15:00 et se termine à 15:55
Et le tour 4 part à 15:55 et se termine à 16:50
Et le tour 1 part toujours à 13:00 et se termine à 14:00

Étant donné le même événement, le tour 2 ouvert, et il est 14:30
Lorsque le gérant fixe la durée du tour 3 à 55 minutes, pour ce tour seulement
Alors le tour 3 part à 15:00 et se termine à 15:55
Et le tour 4 part à 15:55 et se termine à 16:55

Étant donné un événement en course dont le tour 3 est ouvert
Lorsque le gérant tente de changer la durée à partir du tour 3
Alors l'opération est refusée
Et aucune durée n'est écrite

Étant donné un événement en course dont le tour 2 est dû depuis 40 secondes
Et que le planificateur n'a pas encore ouvert ce tour
Lorsque le gérant tente de changer la durée à partir du tour 2
Alors le tour 2 est ouvert par le rattrapage
Et l'opération est refusée

Étant donné un événement dont la durée a été fixée à 55 minutes à partir du tour 3
Lorsque le gérant la fixe à 50 minutes à partir de ce même tour 3
Alors le tour 3 se termine 50 minutes après son départ
Et un seul changement porte sur le tour 3

Étant donné un événement en course dont la durée est de 55 minutes à partir du tour 3
Et qu'il est 15:30
Lorsque le tour courant est demandé
Alors c'est le tour 3, départ 15:00, fin 15:55

Étant donné un événement en course
Lorsqu'un coureur tente de changer la durée d'un tour
Alors l'accès est refusé
```

## Cas limites et erreurs

- **Le changement pendant la minute du départ.** Le rattrapage de matérialisation le transforme en
  refus plutôt qu'en heure limite déplacée sous les pieds des coureurs déjà partis. C'est le cas
  qui justifie que la garde ne soit pas une simple comparaison à l'heure serveur.
- **Le planificateur mort.** Les tours en retard sont ouverts par le rattrapage au moment du geste,
  avec les durées en vigueur pour chacun d'eux. Un gérant qui change la durée après une panne du
  planificateur ne peut donc pas la faire porter sur le trou.
- **Le double clic.** Le second envoi écrit la même durée sur le même tour et ne produit aucun
  changement supplémentaire.
- **Le retour en brouillon (BR-41).** Les changements survivent : ils sont exprimés en numéros de
  tour, aucun n'est rendu faux par un demi-tour de l'événement. Le gérant qui n'en veut plus les
  supprime.
- **Aucune grille.** Premier départ ou durée de boucle absents de la configuration : il n'y a pas de
  grille à corriger, le geste n'est pas proposé.
- **Événement pas encore en course.** Le geste reste disponible : aucun tour n'est parti, donc tous
  sont atteignables. Il n'a pas d'intérêt — la configuration fait la même chose plus simplement —
  et il n'a pas besoin d'une règle de plus pour l'interdire.
- **Une durée qui repousse un tour au-delà de la fin de la nuit.** Rien ne l'interdit : il n'existe
  pas de nombre de tours prédéfini, et c'est le gérant qui déclare la course terminée.

## Impacts techniques

`RoundSchedule` garde ses trois méthodes — `startOf`, `deadlineOf`, `numberAt` — et c'est ce qui
tient le coût de la story. Seule leur implémentation change : la multiplication devient un parcours
des morceaux. `ResolveCurrentRound`, `OpenDueRounds`, `CurrentRoundResource` et l'écran ne changent
pas d'une ligne, et BR-11 s'écrira contre la même surface.

`numberAt` cesse d'être une division entière et devient un parcours cumulatif. Sur une nuit de
course avec une poignée de changements, le coût est nul ; il est appelé à chaque rendu d'écran, donc
le parcours doit se faire en mémoire sur les morceaux chargés, pas en requêtes.

La table des changements ne peut pas être une colonne de `rounds` : les tours sont matérialisés
paresseusement par `OpenDueRounds`, donc la ligne du tour 7 n'existe pas quand le gérant décide de
sa durée. Cette même paresse est ce qui protège le passé — un tour couru a sa ligne, avec ses
horaires figés dedans.

**Le piège à ne pas laisser passer dans l'epic 2 :** aucun calcul de la forme
`premier départ + N × durée` ailleurs que dans `RoundSchedule`. C'est vrai aujourd'hui, et c'est ce
qui rend cette story petite. Une boucle de BR-08 qui recalculerait sa propre heure limite au lieu de
la lire de son tour ferait doubler le prix.

## Tâches

- [ ] **T1** — Migration et modèle du changement de durée, unicité événement + tour d'effet `0,5 pt`
- [ ] **T2** — `RoundSchedule` par morceaux : départ, heure limite et résolution du tour courant `1,5 pt`
- [ ] **T3** — Action de changement : rattrapage de matérialisation, garde sur le tour parti, les deux gestes `1 pt`
- [ ] **T4** — Route, requête et geste sur le prochain tour depuis l'écran de pilotage `1 pt`
- [ ] **T5** — Tests : grille sans changement, deux gestes, refus sur tour ouvert, fenêtre du planificateur, remplacement, tour courant `1 pt`
