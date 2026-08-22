# BR-41 — Remettre l'événement en brouillon

| | |
|---|---|
| **Epic** | 1 — Fondations |
| **Statut** | 🔥 Lot 4 |
| **Estimation** | 3 pts |
| **Créée** | 2026-08-22 — relevé au moment d'ouvrir les inscriptions au public, lot 4 |
| **Dépend de** | BR-03, BR-39 |

## User story

En tant que **gérant**,
Je veux **refermer l'événement en brouillon tant qu'aucune inscription n'existe**,
Afin de **corriger une ouverture prématurée sans avoir à toucher à la base de données**.

## Contexte

Le statut de l'événement n'avance que dans un sens : BR-03 a livré une chaîne brouillon →
inscriptions → en course → terminé, et rien pour revenir. C'était le bon parti — aucune de ces
transitions ne s'annule une fois qu'un coureur a couru.

Mais la première marche est différente des autres. Ouvrir les inscriptions est un geste qu'on peut
faire trop tôt : la configuration n'est pas finie, le briefing n'est pas rédigé, les documents ne sont
pas déposés. Tant que personne ne s'est inscrit, l'ouverture n'a rien produit et refermer ne détruit
rien. Aujourd'hui ce demi-tour n'existe pas dans l'application, et le seul recours est une écriture à
la main dans la base.

La story ne rouvre pas la chaîne : elle ajoute une seule marche descendante, la première, sous la
seule condition qui la rende sans conséquence.

## Périmètre fonctionnel

**Inclus**
- Le gérant peut ramener l'événement de « inscriptions ouvertes » à « brouillon ».
- Le geste n'est proposé que lorsqu'il est possible, et son refus est expliqué quand il ne l'est pas.
- L'événement redevient invisible aux participants et cesse d'accepter les inscriptions.

**Exclu**
- Tout autre retour en arrière : ni « en course » vers « inscriptions », ni « terminé » vers « en
  course ». Une boucle courue ne se décourt pas.
- La suppression de l'événement, de son briefing, de ses documents ou de ses horaires : refermer
  n'efface rien.
- La suppression des inscriptions pour rendre le retour possible. C'est le geste de BR-39, et il
  reste distinct : on ne détruit pas des comptes par effet de bord d'un changement de statut.

**Dépendances** — BR-03 pour le cycle de vie et l'écran de configuration, BR-39 pour le geste qui
permet d'atteindre la condition.

## Règles métier

- Le retour en brouillon n'est possible que depuis « inscriptions ouvertes ».
- Il exige **zéro inscription en base**, quel que soit son statut — les annulées comptent. Une
  inscription annulée porte encore une ligne et un compte : l'événement ne redevient pas un brouillon
  vierge tant qu'elle existe.
- Quand la condition n'est pas remplie, le refus dit combien d'inscriptions le bloquent. Le gérant
  doit savoir quoi supprimer, pas seulement qu'il ne peut pas.
- Le retour en brouillon rend l'événement invisible aux participants et referme les inscriptions.
  C'est exactement l'état d'avant l'ouverture, et rien de configuré n'est perdu.
- Le geste s'appuie sur le statut que le gérant croyait quitter : si l'événement a bougé entre
  l'affichage de l'écran et l'envoi, le retour est refusé plutôt qu'appliqué à un autre état.
- Le geste est réservé à qui peut déjà faire avancer l'événement. Il n'introduit aucune autorisation
  nouvelle.

## Critères d'acceptation

```gherkin
Étant donné un événement dont les inscriptions sont ouvertes et qui ne porte aucune inscription
Lorsque le gérant le remet en brouillon
Alors son statut est « brouillon »
Et il n'est plus visible des participants
Et le parcours public d'inscription est fermé
Et son briefing, ses documents et ses horaires sont intacts

Étant donné un événement dont les inscriptions sont ouvertes et qui porte deux inscriptions annulées
Lorsque le gérant tente de le remettre en brouillon
Alors le retour est refusé
Et le message nomme les deux inscriptions qui le bloquent

Étant donné un événement en course
Lorsque le gérant tente de le remettre en brouillon
Alors le retour est refusé

Étant donné un événement déjà en brouillon
Lorsque le gérant tente de le remettre en brouillon
Alors le retour est refusé

Étant donné un participant qui n'est pas gérant
Lorsqu'il tente de remettre l'événement en brouillon
Alors il reçoit un refus d'autorisation
```

## Cas limites et erreurs

- **Deux onglets.** L'événement passé en course dans un autre onglet ne doit pas recevoir un retour
  en brouillon calculé sur un écran périmé.
- **Une inscription créée entre l'affichage et l'envoi.** Le décompte lu pour proposer le geste peut
  être périmé : la condition doit être revérifiée au moment d'écrire, pas seulement pour afficher.
- **Aucun événement en base.** Le geste n'a pas d'objet et ne doit pas produire d'erreur technique.
- **Un coureur en train de remplir le formulaire public** au moment du retour en brouillon : son envoi
  arrive sur un événement fermé et doit être refusé comme n'importe quelle inscription hors période.

## Impacts techniques

La story ouvre la première marche descendante du cycle de vie de l'événement, qui n'en avait aucune.
C'est une addition à la chaîne existante, pas une réécriture : les trois autres transitions gardent
leur sens unique, et la condition de zéro inscription est ce qui rend celle-ci réversible sans perte.

Le retour en brouillon change deux choses visibles de l'extérieur : l'événement disparaît des écrans
des participants, et le parcours public d'inscription se referme. Rien d'autre n'est touché — ni les
documents, ni le briefing, ni les horaires calculés.

Le décompte affiché pour proposer ou refuser le geste et la vérification faite au moment d'écrire
doivent être la même règle, sinon l'écran promet un geste que l'écriture refuse.

## Tâches

- [ ] **T1** — Marche descendante « inscriptions ouvertes » → « brouillon », condition de zéro
  inscription vérifiée à l'écriture `1 pt`
- [ ] **T2** — Geste dans l'écran de configuration : proposé quand il est possible, refus qui compte
  les inscriptions bloquantes `1 pt`
- [ ] **T3** — Tests : retour réussi sur base vide, refus sur une annulée qui traîne, refus depuis les
  autres statuts, refus d'autorisation, fermeture du parcours public `1 pt`
