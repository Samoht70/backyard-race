# BR-39 — Supprimer une inscription et le compte qui va avec

| | |
|---|---|
| **Epic** | 1 — Fondations |
| **Statut** | 🔥 Lot 4 |
| **Estimation** | 3 pts |
| **Créée** | 2026-08-22 — relevé au moment d'ouvrir les inscriptions au public, lot 4 |
| **Dépend de** | BR-06, BR-37 |

## User story

En tant que **gérant**,
Je veux **supprimer une inscription et le compte coureur qui la porte, depuis l'écran de gestion**,
Afin de **rendre son adresse disponible et laisser le coureur se réinscrire lui-même quand il a perdu son code d'accès**.

## Contexte

Un coureur qui perd le mail portant son code d'accès n'a aucun chemin de retour : l'application ne
propose ni réinitialisation ni renvoi de code, parce que D-43 a réduit l'authentification au seul
mot de passe et que D-45 a fait du mail le seul chemin de création de compte. Le seul recours
aujourd'hui est une commande lancée en SSH, dont le code s'affiche dans un terminal et se relaie à la
main.

Le geste demandé prend le problème par l'autre bout : plutôt que d'ouvrir un second chemin
d'authentification, le gérant efface l'inscription et son compte, ce qui libère l'adresse, et le
coureur reprend le parcours public d'inscription — celui qui envoie déjà un lien signé puis un code
neuf. Aucun écran nouveau, aucun mail nouveau, aucune porte nouvelle.

Le même geste sert au ménage : une inscription d'essai, un doublon, une adresse fautive saisie par le
coureur. C'est la version unitaire de la purge que BR-37 fait en masse.

## Périmètre fonctionnel

**Inclus**
- Le gérant peut supprimer une inscription depuis la gestion des inscriptions, quel que soit son
  statut.
- La suppression emporte le compte coureur qui portait l'inscription, ses rôles et ses sessions.
- Une confirmation est demandée avant l'effacement, et elle nomme le coureur concerné.
- Le décompte des places et les compteurs par statut reflètent la suppression immédiatement.

**Exclu**
- Le renvoi d'un code d'accès, et toute réinitialisation de mot de passe : c'est la voie que cette
  story remplace, pas celle qu'elle ouvre.
- La suppression du compte du gérant, et de tout compte épargné par la règle de BR-37 et de D-65.
- La récupération d'une suppression : rien n'est mis en corbeille, le coureur se réinscrit.
- La libération du numéro de dossard supprimé : les dossards ne rebouchent pas leurs trous (BR-37).
- Un geste de masse dans l'écran. Effacer tout le monde reste l'affaire de la commande de purge.

**Dépendances** — BR-06 pour l'écran de gestion et l'annulation, BR-37 pour l'ordre de suppression et
la règle du compte épargné.

## Règles métier

- La suppression est ouverte quel que soit le statut de l'inscription : en attente, confirmée ou
  annulée. Une inscription confirmée part avec son dossard ; la place se libère, le numéro ne se
  réattribue pas.
- La suppression va de l'inscription vers le compte, et le compte ne part que s'il n'est pas épargné.
  Un compte épargné — porteur du rôle `manager`, ou portant l'adresse de l'organisateur configurée —
  perd son inscription et garde son compte : l'inscription est une donnée de course, le compte est
  une porte.
- Les rôles du compte supprimé sont détachés, et ses sessions sont fermées : un cookie qui survit à
  la ligne est une trace, et une suppression qui laisse des traces n'a pas fini son travail.
- L'inscription et le compte partent ensemble ou pas du tout. Un compte sans inscription est un état
  qu'aucun écran ne sait montrer.
- La suppression est refusée pendant que la course tourne. À ce moment-là une inscription n'est plus
  une ligne de formulaire, c'est un coureur sur le terrain.
- Le coureur supprimé n'est pas prévenu. Il demande le geste, ou il n'a jamais couru ; un mail
  annonçant une suppression inquiéterait sans rien apprendre.
- Une fois l'adresse libérée, le parcours public d'inscription la reprend comme une adresse neuve.

## Critères d'acceptation

```gherkin
Étant donné une inscription en attente portée par un compte coureur
Lorsque le gérant confirme sa suppression
Alors l'inscription est supprimée
Et le compte coureur est supprimé, ses rôles détachés et ses sessions fermées

Étant donné une inscription confirmée portant le dossard 3, sur un événement plein
Lorsque le gérant confirme sa suppression
Alors l'inscription est supprimée
Et une place se libère
Et la prochaine confirmation ne reprend pas le dossard 3

Étant donné une inscription supprimée dont l'adresse était celle d'un coureur
Lorsque cette adresse recommence le parcours public d'inscription
Alors elle est traitée comme une adresse neuve et reçoit un lien signé

Étant donné une inscription portée par le compte de l'organisateur
Lorsque le gérant confirme sa suppression
Alors l'inscription est supprimée
Et le compte de l'organisateur et son rôle sont intacts

Étant donné un événement dont le statut est « en course »
Lorsque le gérant tente de supprimer une inscription
Alors la suppression est refusée
Et le message nomme le statut qui la bloque

Étant donné un participant qui n'est pas gérant
Lorsqu'il tente de supprimer une inscription
Alors il reçoit un refus d'autorisation
```

## Cas limites et erreurs

- **Un job en file pour le compte supprimé.** Le mail de confirmation d'inscription est mis en file
  avec son destinataire ; supprimé, le job échoue à son réveil. La suppression juste après une
  inscription est donc le cas le plus probable de job perdu.
- **Le dernier compte de la base.** Si le gérant supprime la seule inscription et que son propre
  compte est le seul autre, la base garde une porte : le compte épargné ne part jamais par ce geste.
- **Deux onglets.** Une inscription déjà supprimée dans un autre onglet doit produire un refus lisible
  et non une page cassée.
- **Le coureur connecté au moment de la suppression.** Sa session est fermée ; sa prochaine action
  le renvoie à la connexion, où son adresse n'existe plus. Il reprend l'inscription publique.

## Impacts techniques

Le geste unitaire et la purge de masse font le même travail sur une ligne : même ordre, même règle du
compte épargné, mêmes sessions à fermer. C'est le second appelant qui manquait à BR-37 — D-64 avait
noté qu'une classe d'action n'aurait déplacé qu'un nom faute d'appelant, et cette story fournit
l'appelant. La logique se factorise ici ; elle ne se duplique pas.

La suppression touche les places disponibles, les compteurs par statut de l'écran de gestion, et la
disponibilité de l'adresse dans le parcours public d'inscription. Elle ne touche ni les documents, ni
le briefing, ni l'événement.

Le refus pendant la course s'appuie sur le garde-fou de statut déjà porté par le cycle de vie de
l'événement.

## Tâches

- [ ] **T1** — Suppression d'une inscription et de son compte, factorisée avec la purge de masse
  `1 pt`
- [ ] **T2** — Geste dans l'écran de gestion : autorisation, confirmation nommant le coureur, refus
  en course `1 pt`
- [ ] **T3** — Tests : suppression d'une confirmée avec sa place et son dossard, compte épargné qui
  perd son inscription, adresse redevenue libre, refus en course, refus d'autorisation `1 pt`
