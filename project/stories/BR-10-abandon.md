# BR-10 — Déclarer l'abandon volontaire d'un coureur

| | |
|---|---|
| **Epic** | 2 — Moteur de course |
| **Statut** | ✅ Terminé |
| **Estimation** | 5 pts |
| **Dépend de** | BR-08 |

## User story

En tant que **gérant**,
Je veux **enregistrer qu'un coureur arrête la course de son plein gré**,
Afin qu'**il disparaisse des coureurs à surveiller et que son résultat soit figé**.

## Contexte

Un coureur qui s'arrête vient le dire au gérant. Sans cet enregistrement, il serait éliminé
automatiquement à l'heure limite, ce qui donnerait une raison de sortie fausse et laisserait
le gérant l'attendre inutilement.

## Périmètre fonctionnel

**Inclus**
- Action d'abandon depuis la fiche du coureur et depuis le tableau de course.
- Écran de confirmation rappelant la dernière boucle validée et la distance parcourue.
- Sortie immédiate du coureur des effectifs actifs.

**Exclu**
- L'abandon déclaré par le coureur lui-même.
- L'annulation d'un abandon : traitée comme une correction exceptionnelle (BR-12).

**Dépendances** — BR-08.

## Règles métier

- Seul un utilisateur porteur de la permission `manage-laps` peut déclarer un abandon.
- Une confirmation explicite est obligatoire avant l'enregistrement.
- L'abandon sort le participant de la course et enregistre l'heure de sortie ainsi que le motif
  « abandon ». Son statut affiché est `withdrawn`, pas `eliminated`.
- La boucle en cours du coureur passe en `eliminated` : elle ne compte pas.
- Les boucles déjà validées sont conservées telles quelles.
- Un coureur ayant abandonné ne reçoit plus de boucle aux tours suivants et n'apparaît plus
  parmi les coureurs actifs.
- Un coureur déjà sorti ne peut pas abandonner une seconde fois.

## Critères d'acceptation

```gherkin
Étant donné un coureur en course avec 7 boucles validées sur des tours de 6 km
Lorsque le gérant ouvre l'écran d'abandon
Alors la dernière boucle validée affichée est la 7e
Et la distance affichée est 42 km

Étant donné cet écran de confirmation
Lorsque le gérant annule
Alors le coureur est toujours en course

Étant donné cet écran de confirmation
Lorsque le gérant confirme l'abandon
Alors le coureur passe au statut "withdrawn"
Et le motif enregistré est l'abandon
Et sa boucle en cours passe au statut "eliminated"
Et ses 7 boucles validées sont inchangées

Étant donné un coureur ayant abandonné
Lorsque le tour suivant est ouvert
Alors aucune boucle ne lui est créée
Et il n'apparaît pas parmi les coureurs actifs

Étant donné un participant connecté
Lorsqu'il tente de déclarer son propre abandon
Alors l'action est refusée
```

## Cas limites et erreurs

- Abandon déclaré alors que la boucle du coureur venait d'être validée : la boucle validée est conservée, l'abandon prend effet pour la suite.
- Abandon d'un coureur déjà éliminé automatiquement : l'action est refusée, le motif initial est conservé.
- Confirmation envoyée deux fois : le second envoi ne change rien.

## Impacts techniques

L'abandon et l'élimination automatique aboutissent au même statut mais pas au même motif.
Distinguer les deux est ce qui permet, après la course, de dire qui s'est arrêté et qui a été
rattrapé par le chrono.

## Ce que la livraison a changé

**Le coureur sorti se lit sur sa ligne, plus sur ses boucles.** BR-08 déduisait « en course » de
l'absence de boucle `eliminated` (D-74). Cette déduction ne survit pas au cas limite que la story
nomme : un coureur qui abandonne juste après avoir validé sa boucle n'a aucune boucle éliminée, et
serait resté dans les effectifs actifs. `exited_at` et `exit_reason` deviennent la seule vérité, le
scope `running` les lit, et `withRaceStatus()` disparaît — la colonne est déjà sur la ligne.

**Le statut affiché est `withdrawn`, et c'est le motif qui le décide.** La story écrivait
« passe au statut eliminated » avant que D-26 n'interdise de persister le statut et que D-74 ne
réserve `withdrawn` au motif de sortie de cette story. Le coureur est sorti de la course, comme
demandé ; ce qu'il affiche distingue l'arrêt volontaire du chrono, ce qui est exactement le but que
nomment les « Impacts techniques ».

**L'abandon se déclare depuis le tableau de course seulement.** La fiche du coureur est le périmètre
de BR-16, qui n'existe pas : l'action y sera posée avec elle. Le bouton n'apparaît que sur une boucle
non validée — un coureur déjà rentré sur ce tour s'arrête au tour suivant.

**Le double envoi est un refus, pas un retour silencieux.** C'est l'inverse de BR-09, et les deux
règles sont dans leur story respective : une boucle revalidée rend son premier temps, un abandon
répété est refusé et le motif initial est conservé.

**Les deux gestes du tableau ont été redimensionnés en les regardant.** Le gérant a jugé le bouton de
validation trop gros, puis a demandé que les gestes de course tiennent la taille des autres boutons
du site, puis qu'ils se réduisent à leur icône sur la carte du téléphone. La variante spéciale de
`ActionButton` disparaît, le libellé revient à partir de `sm`, et l'`aria-label` porte le sens quand
l'icône est seule.

[D-76](../DECISIONS.md) porte le détail, dont la fermeture de [Q-04](../QUESTIONS.md) : les 72 px de
D-24 ne reviennent pas, la taille retenue vit dans `lib/actionButton.ts` et un test la garde.

## Tâches

- [x] **T1** — Motif de sortie et heure de sortie sur le participant `1 pt`
- [x] **T2** — Action d'abandon : statut, motif, boucle en cours `2 pts`
- [x] **T3** — Écran de confirmation avec rappel des boucles et de la distance `2 pts`
- [x] **T4** — Tests : abandon nominal, double abandon, boucles validées préservées, refus participant `2 pts`
