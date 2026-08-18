# BR-08 — Suivre les boucles courues par chaque participant

| | |
|---|---|
| **Epic** | 2 — Moteur de course |
| **Statut** | À faire |
| **Estimation** | 8 pts |
| **Dépend de** | BR-04, BR-06 |

## User story

En tant que **gérant**,
Je veux **qu'à chaque tour, une boucle soit ouverte pour chaque coureur encore en course**,
Afin de **savoir en permanence qui doit rentrer et qui est déjà rentré**.

## Contexte

Le tour de course est collectif, la boucle est individuelle. Cette story met en place l'objet
central du moteur : la boucle d'un coureur sur un tour donné. La validation, l'abandon et
l'élimination viennent ensuite s'appuyer dessus.

## Périmètre fonctionnel

**Inclus**
- Une boucle par participant actif et par tour de course.
- La boucle porte : participant, tour, numéro de tour, heure théorique de départ, heure
  limite, heure réelle de validation, durée, vitesse moyenne, statut. La distance n'y figure
  pas : elle est celle de l'événement (voir D-17).
- Le statut du participant dans la course : en course, éliminé.

**Exclu**
- L'acte de validation : BR-09.
- L'élimination automatique : BR-11.

**Dépendances** — BR-04, BR-06.

## Règles métier

- Statuts d'une boucle : `pending → validated` ou `pending → eliminated`.
- Une boucle naît en `pending` à l'ouverture du tour.
- Une boucle n'est ouverte que pour les participants **encore en course** : un coureur
  éliminé ou ayant abandonné ne reçoit plus de boucle.
- Un participant ne peut avoir qu'une seule boucle par tour.
- L'heure théorique de départ et l'heure limite de la boucle sont celles du tour.
- Durée et vitesse moyenne restent vides tant que la boucle n'est pas validée.
- Un participant est considéré actif tant qu'aucune de ses boucles n'est `eliminated` et
  qu'aucun abandon n'a été déclaré.

## Critères d'acceptation

```gherkin
Étant donné 24 participants en course et 8 participants éliminés
Lorsque le tour 6 est ouvert
Alors 24 boucles sont créées au statut "pending"
Et aucune boucle n'est créée pour les participants éliminés

Étant donné un participant en course sur le tour 6
Lorsqu'on tente de lui ouvrir une seconde boucle sur ce même tour
Alors l'opération est refusée

Étant donné un tour dont le départ théorique est 18:00 et la limite 19:00
Lorsque les boucles de ce tour sont créées
Alors chaque boucle porte 18:00 en départ théorique et 19:00 en heure limite

Étant donné une boucle au statut "pending"
Lorsqu'on la consulte
Alors sa durée et sa vitesse moyenne sont vides
```

## Cas limites et erreurs

- Ouverture d'un tour alors que tous les coureurs sont éliminés : aucune boucle créée, la course est de fait terminée.
- Ouverture rejouée deux fois sur le même tour : aucune boucle en double.
- Participant confirmé en cours de course : il entre au tour suivant, pas au tour en cours.

## Impacts techniques

C'est la table qui grossit le plus : 40 coureurs sur une nuit de course produisent quelques
centaines de lignes, ce qui reste modeste. En revanche les écrans de course la lisent en
boucle — les requêtes de comptage doivent être agrégées en base, jamais en PHP.

## Tâches

- [ ] **T1** — Migration et modèle `Lap`, énumération de statut, unicité participant + tour `2 pts`
- [ ] **T2** — Statut de course du participant et notion de coureur actif `2 pts`
- [ ] **T3** — Service d'ouverture des boucles d'un tour, rejouable sans effet de bord `2 pts`
- [ ] **T4** — Factory des boucles pour les tests et le développement `1 pt`
- [ ] **T5** — Tests : ouverture, exclusion des éliminés, unicité, idempotence `2 pts`
