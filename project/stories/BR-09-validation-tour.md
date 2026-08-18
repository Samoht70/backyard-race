# BR-09 — Valider une boucle d'un seul appui

| | |
|---|---|
| **Epic** | 2 — Moteur de course |
| **Statut** | À faire |
| **Estimation** | 8 pts |
| **Dépend de** | BR-08 |

## User story

En tant que **gérant posté à l'arrivée**,
Je veux **valider la boucle d'un coureur en appuyant sur un seul bouton**,
Afin d'**enregistrer son temps sans rien saisir, même avec dix coureurs qui rentrent en même temps**.

## Contexte

Fonctionnalité centrale du produit et geste le plus répété de la soirée. Le coureur ne valide
jamais lui-même : seul le gérant valide, et l'heure retenue est celle du serveur au moment de
l'appui.

## Périmètre fonctionnel

**Inclus**
- Un bouton de validation par coureur actif sur le tour courant.
- Enregistrement de l'heure de fin, calcul de la durée et de la vitesse moyenne côté serveur.
- Restitution immédiate du résultat : durée, distance, vitesse.

**Exclu**
- Toute saisie manuelle d'une heure de fin, par qui que ce soit.
- Toute validation par le participant.
- La validation après expiration du délai : BR-12.

**Dépendances** — BR-08.

## Règles métier

- Seul un utilisateur porteur de la permission `validate-laps` peut valider.
- L'heure de fin est **l'heure du serveur** au moment de la requête. Elle n'est ni transmise
  par le client ni modifiable.
- La boucle validée est celle du **tour courant**, déterminé par le serveur.
- `durée = heure de validation − heure théorique de départ`.
- `vitesse moyenne = distance de la boucle définie sur l'événement ÷ durée`, exprimée en km/h.
  Elle est informative et n'entre jamais dans le classement.
- Une boucle déjà `validated` ne peut pas être revalidée : la seconde tentative ne modifie rien.
- Une boucle `eliminated` ne peut pas être validée par ce geste.
- La validation est refusée si l'heure limite du tour est dépassée (voir BR-12).
- Valider ne change pas le statut du participant : il reste en course.

## Critères d'acceptation

```gherkin
Étant donné un événement dont la distance de boucle est 6 km
Et une boucle "pending" du tour 5, départ théorique 17:00
Et qu'il est 17:47:32 côté serveur
Lorsque le gérant valide cette boucle
Alors la boucle passe au statut "validated"
Et l'heure de validation enregistrée est 17:47:32
Et la durée enregistrée est 47 minutes et 32 secondes
Et la vitesse moyenne enregistrée est 7,57 km/h

Étant donné une boucle déjà au statut "validated"
Lorsque le gérant la valide de nouveau
Alors l'heure de validation initiale est conservée
Et aucune erreur n'est présentée au gérant

Étant donné un participant connecté
Lorsqu'il tente de valider sa propre boucle
Alors l'action est refusée

Étant donné une boucle du tour 5 dont l'heure limite 18:00 est dépassée
Lorsque le gérant tente de la valider
Alors l'action est refusée
Et le gérant est orienté vers la correction exceptionnelle

Étant donné une requête de validation transportant une heure de fin fournie par le client
Lorsqu'elle est traitée
Alors l'heure du serveur est retenue
Et l'heure transmise est ignorée
```

## Cas limites et erreurs

- Dix validations en quelques secondes : chacune enregistre sa propre heure, aucune n'écrase l'autre.
- Double appui accidentel sur le même coureur : la première heure est conservée.
- Perte de réseau au moment de l'appui : le gérant doit voir que l'action n'a pas abouti et pouvoir réappuyer.
- Distance de boucle non renseignée sur l'événement : la vitesse n'est pas calculée plutôt que de produire une division par zéro.
- Validation à la seconde exacte de l'heure limite : acceptée, la limite est inclusive.

## Impacts techniques

Le calcul du temps est la donnée que personne ne pourra recontester après la course : il n'y
a pas de trace papier. L'heure serveur est donc la seule référence acceptable, et le double
appui ne doit jamais produire deux temps différents pour une même boucle.

## Tâches

- [ ] **T1** — Action de validation d'une boucle : heure serveur, durée, vitesse `3 pts`
- [ ] **T2** — Garde-fous : permission, boucle déjà validée, délai dépassé, division par zéro `2 pts`
- [ ] **T3** — Contrôleur de validation et Policy associée `1 pt`
- [ ] **T4** — Retour immédiat du résultat au gérant après l'appui `1 pt`
- [ ] **T5** — Tests : calculs, double validation, refus participant, heure client ignorée, limite exacte `3 pts`
