# BR-06 — Confirmer ou annuler les inscriptions

| | |
|---|---|
| **Epic** | 1 — Fondations |
| **Statut** | ✅ Terminé |
| **Estimation** | 5 pts |
| **Dépend de** | BR-05 |

## User story

En tant que **gérant**,
Je veux **valider ou refuser chaque inscription et corriger les fiches**,
Afin d'**arriver au départ avec une liste de coureurs juste**.

## Contexte

Entre l'inscription en ligne et le départ, le gérant vérifie, relance, corrige une faute de
frappe et annule les désistements. C'est aussi lui qui fixe la liste définitive des partants.

## Périmètre fonctionnel

**Inclus**
- Liste des inscriptions filtrable par statut, avec le compteur de places.
- Confirmation et annulation d'une inscription.
- Modification par le gérant des informations d'un participant.

**Exclu**
- La suppression définitive d'un participant : on annule, on ne supprime pas.
- L'envoi d'un email de confirmation : hors périmètre (voir D-15).

**Dépendances** — BR-05, et BR-07 pour l'attribution du dossard à la confirmation.

## Règles métier

- Depuis `pending`, le gérant peut confirmer ou annuler.
- Une inscription confirmée peut encore être annulée : un coureur se désiste parfois la veille.
- Une inscription annulée peut être remise en `pending`, pas directement en `confirmed`.
- Confirmer déclenche l'attribution du numéro de dossard (BR-07).
- Annuler libère une place dans le décompte mais **ne libère pas le numéro de dossard**.
- Le gérant peut corriger toutes les informations d'une fiche, à tout moment.
- Le nombre d'inscriptions confirmées ne peut jamais dépasser le maximum de l'événement.

## Critères d'acceptation

```gherkin
Étant donné une inscription au statut "pending"
Lorsque le gérant la confirme
Alors son statut est "confirmed"
Et un numéro de dossard lui est attribué

Étant donné une inscription au statut "confirmed"
Lorsque le gérant l'annule
Alors son statut est "cancelled"
Et la place est de nouveau disponible
Et le numéro de dossard reste associé au participant

Étant donné un événement complet en inscriptions confirmées
Lorsque le gérant tente de confirmer une inscription supplémentaire
Alors l'action est refusée

Étant donné une inscription au statut "cancelled"
Lorsque le gérant tente de la passer directement en "confirmed"
Alors l'action est refusée

Étant donné un participant connecté
Lorsqu'il tente de confirmer sa propre inscription
Alors l'action est refusée
```

## Cas limites et erreurs

- Double clic sur « confirmer » : la seconde requête ne change rien et n'attribue pas un second dossard.
- Confirmation pendant que la course est lancée : autorisée, un retardataire arrive parfois.
- Modification de l'email vers une adresse déjà prise : refus à la validation.

## Impacts techniques

La confirmation est le point de bascule administratif : elle fige le numéro de dossard et
fait entrer le coureur dans les effectifs de la course.

## Tâches

- [x] **T1** — Actions de confirmation et d'annulation, avec leurs garde-fous de transition `2 pts`
- [x] **T2** — Écran gérant : liste filtrable, compteur de places, actions `2 pts`
- [x] **T3** — Modification d'une fiche participant par le gérant `1 pt`
- [x] **T4** — Tests : transitions valides et invalides, capacité, idempotence de la confirmation `2 pts`

## Ce qui a été tranché avant de commencer (Q-03, fermée le 2026-08-20)

L'inscription et la création de compte avaient fusionné : une inscription naît dans le parcours par
mail, et `registration/create` n'existe plus. Cette story est la première à rendre atteignable le cas
d'un compte sans inscription, puisqu'elle donne au gérant le pouvoir d'annuler.

**Branche retenue avec le propriétaire : la première combinée à la troisième.** Le coureur voit son
inscription annulée en lecture seule, avec un message qui dit ce qui s'est passé, et il ne regagne
aucun pouvoir de création. C'est le gérant qui remet l'inscription en attente, et rien de ce que le
coureur avait saisi n'est perdu dans l'aller-retour. Aucun écran de création ne revient côté
connecté : D-45 tient intégralement.

Voir [D-48](../DECISIONS.md).

## Ce que cette story ne livre pas

Le critère « et un numéro de dossard lui est attribué » appartient à BR-07, livrée juste après sur
sa propre branche. La confirmation pose le point de branchement ; l'attribution s'y greffe.

## Ce qui a changé depuis

L'exclusion « l'envoi d'un email de confirmation : hors périmètre » est levée le 2026-08-22 par
[BR-43](BR-43-notification-de-traitement.md) : les quatre traitements livrés ici préviennent
désormais le coureur par mail, et le sens du mail se lit sur le statut quitté autant que sur la
transition. Voir [D-71](../DECISIONS.md).
