# BR-03 — Configurer l'événement et son cycle de vie

| | |
|---|---|
| **Epic** | 1 — Fondations |
| **Statut** | ✅ Terminé |
| **Estimation** | 8 pts |
| **Dépend de** | BR-01 |

## User story

En tant que **gérant**,
Je veux **décrire mon événement et faire évoluer son statut**,
Afin d'**ouvrir les inscriptions, lancer la course puis la clore au bon moment**.

## Contexte

L'événement est l'objet racine : participants, tours, documents et médias s'y rattachent.
Son statut commande ce que chaque profil peut faire à un instant donné.

## Périmètre fonctionnel

**Inclus**
- Création et modification de l'événement par le gérant : nom, description, date, heure du
  premier départ, distance d'une boucle, durée d'une boucle, adresse, coordonnées, nombre
  maximum de participants.
- Progression du statut : brouillon, inscriptions ouvertes, course en cours, terminé.
- Consultation des informations de l'événement par les participants.

**Exclu**
- La génération des horaires de boucles : BR-04.
- La clôture définitive et le classement : BR-20.
- Le multi-événement : l'application gère un événement à la fois.

**Dépendances** — BR-01 pour la permission `manage-event`.

## Règles métier

- Statuts et transitions : `draft → registration → running → finished`. On n'en saute
  aucune et on ne revient jamais en arrière.
- En `draft`, l'événement n'est pas visible des participants.
- Les inscriptions ne sont acceptées qu'en `registration`.
- Le passage en `running` exige une heure de premier départ, une distance et une durée de boucle.
- Une fois en `running`, la durée d'une boucle et l'heure du premier départ ne sont plus
  modifiables : les horaires déjà calculés en dépendent.
- En `finished`, l'événement est en lecture seule.

## Critères d'acceptation

```gherkin
Étant donné un événement au statut "draft"
Lorsqu'un participant consulte la page de l'événement
Alors il n'y accède pas

Étant donné un événement au statut "registration"
Lorsque le gérant le passe en "running"
Alors le statut est "running"
Et l'heure du premier départ n'est plus modifiable

Étant donné un événement au statut "draft" sans heure de premier départ
Lorsque le gérant tente de le passer en "running"
Alors la transition est refusée
Et le motif est présenté au gérant

Étant donné un événement au statut "running"
Lorsque le gérant tente de le repasser en "registration"
Alors la transition est refusée
```

## Cas limites et erreurs

- Durée de boucle nulle ou négative, distance de boucle nulle : refus à la validation. Cette distance est la seule du produit, tout calcul de vitesse en dépend (voir D-17).
- Nombre maximum de participants inférieur au nombre de participants déjà confirmés : refus.
  **Reporté à BR-05 :** aucun modèle `Participant` n'existe au moment de BR-03, il n'y a donc rien
  à compter. Rien n'a été posé en attendant — ni règle morte, ni méthode vide. La règle rejoindra
  `EventUpdateRequest` quand la table existera. Un plafond nul signifie « pas de limite » (D-30).
- Coordonnées absentes : accepté, la carte affiche alors uniquement le tracé GPX.
- Un participant qui tente de modifier l'événement : refus d'autorisation.

## Impacts techniques

Le statut de l'événement conditionne l'ouverture des inscriptions et le démarrage de la
course. Une transition prématurée bloquerait des inscriptions encore attendues, une
transition tardive empêcherait de valider des boucles déjà courues.

## Tâches

- [x] **T1** — Migration et modèle `Event`, énumération de statut castée `2 pts`
- [x] **T2** — Factory et seeder de l'événement d'anniversaire `1 pt`
- [x] **T3** — Service de transition de statut avec ses garde-fous `2 pts`
- [x] **T4** — Contrôleur, Form Requests et Policy pour la configuration `2 pts`
- [x] **T5** — Écran de configuration côté gérant et page publique d'information `2 pts`
- [x] **T6** — Tests des transitions autorisées et refusées `2 pts`
