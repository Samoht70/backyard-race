# BR-43 — Prévenir le coureur quand son inscription est traitée

| | |
|---|---|
| **Epic** | 1 — Fondations |
| **Statut** | ✅ Terminé |
| **Estimation** | 3 pts |
| **Créée** | 2026-08-22 — relevé au moment d'ouvrir les inscriptions au public, lot 4 |
| **Dépend de** | BR-06, BR-36 |

## User story

En tant que **coureur inscrit**,
Je veux **recevoir un mail quand le gérant traite mon inscription**,
Afin de **savoir si je cours, sans avoir à revenir voir la page tous les jours**.

## Contexte

Une inscription naît en attente et le gérant l'arbitre plus tard : parfois le soir même, parfois
trois semaines après. Entre les deux, le coureur n'apprend rien. L'application affiche bien son
statut, mais il faut y aller pour le voir — et un refus n'a aucune raison d'être découvert par
hasard, en se connectant pour vérifier une troisième fois.

BR-06 avait explicitement exclu ce mail, en s'appuyant sur D-15. Le propriétaire demande son ajout,
ce qui rouvre l'entrée pour la deuxième fois après D-45.

Le point de départ n'est pas vierge : deux mails existent déjà, le lien d'inscription et le code
d'accès. Ce dernier s'appelle `RegistrationConfirmed` et annonce « C'est officiel : tu cours la
Backyard Race » alors qu'il partait à la création du compte, sur une inscription **en attente**.
Il promettait une validation qui n'avait pas eu lieu, et son nom était celui dont cette story a
besoin.

## Périmètre fonctionnel

**Inclus**
- Un mail au coureur à chacun des quatre traitements que le gérant peut appliquer à son inscription.
- Le mail du code d'accès renommé, et sa promesse corrigée : il accuse réception, il ne valide rien.

**Exclu**
- Le numéro de dossard dans le mail. Le mail annonce la validation et renvoie vers la page
  d'inscription, où le numéro se lit ; il ne le recopie pas.
- Toute notification sur la course elle-même : ni élimination, ni tour validé, ni classement. D-15
  tient pour tout le reste.
- Un canal autre que le mail : pas de SMS, pas de notification en base, pas de cloche dans
  l'interface.
- Une relance : le mail part une fois, au moment du traitement, et l'application n'en reparle pas.
- Un mail au gérant. Personne ne le prévient de ses propres gestes.
- Une préférence de désabonnement. Quatre mails sur la vie d'une inscription ne se règlent pas.

**Dépendances** — BR-06 pour les transitions, BR-36 pour le gabarit de mail.

## Règles métier

- Le coureur est prévenu à chaque changement de statut de son inscription, pas seulement à la
  validation. Un refus qui ne se dit pas laisse le coureur attendre indéfiniment.
- **Le sens du mail se lit sur la transition et l'état quitté, jamais sur le statut d'arrivée
  seul.** Une annulation depuis « en attente » est un refus ; la même annulation depuis
  « confirmée » retire une place déjà acquise. Même statut d'arrivée, deux nouvelles différentes.
  Ce qui les distingue est la place occupée, pas le nom du statut : la question est posée à l'état.
- Les quatre mails ramènent le coureur sur sa page d'inscription, qui embranche déjà sur le statut
  réel. Un lien calculé à l'envoi vers un écran d'édition se referme si le gérant confirme avant le
  clic.
- Les quatre traitements donnent donc quatre mails : validation, refus, annulation, remise en
  attente.
- **Aucun mail ne porte le numéro de dossard.** La validation dit où le lire, l'annulation dit qu'il
  reste attribué sans le nommer. Un numéro recopié dans un mail est un numéro qui se périme dans une
  boîte de réception, et la page d'inscription le porte déjà.
- La remise en attente dit au coureur qu'il peut de nouveau corriger sa fiche, et que celle-ci
  repassera devant le gérant.
- Un traitement refusé n'envoie rien : ni une transition illégale, ni un événement complet, ni la
  seconde requête d'un double clic.
- Le mail ne part qu'une fois le changement écrit en base. Un mail qui annonce un statut que la base
  n'a pas encore est un mensonge, même de quelques millisecondes.
- Le mail ne bloque pas la réponse du gérant : la file s'en charge, et un serveur de mail muet ne
  doit pas empêcher de confirmer une inscription.
- Le mail du code d'accès n'annonce plus une validation. Il accuse réception d'une inscription en
  attente et dit qu'elle passera devant le gérant.

## Critères d'acceptation

```gherkin
Étant donné une inscription en attente
Lorsque le gérant la confirme
Alors le coureur reçoit un mail qui annonce sa validation
Et ce mail ne recopie pas son numéro de dossard

Étant donné une inscription en attente
Lorsque le gérant l'annule
Alors le coureur reçoit un mail qui annonce un refus

Étant donné une inscription confirmée
Lorsque le gérant l'annule
Alors le coureur reçoit un mail qui annonce l'annulation de sa place
Et ce mail n'est pas celui du refus

Étant donné une inscription annulée
Lorsque le gérant la remet en attente
Alors le coureur reçoit un mail qui l'invite à corriger sa fiche

Étant donné un événement complet
Lorsque le gérant tente de confirmer une inscription supplémentaire
Alors aucun mail ne part

Étant donné une inscription en attente déjà confirmée par une première requête
Lorsque la seconde requête d'un double clic arrive
Alors aucun second mail ne part

Étant donné un coureur qui vient de créer son compte
Lorsqu'il reçoit le mail de son code d'accès
Alors ce mail ne lui annonce pas qu'il court
Et il lui dit que son inscription passera devant le gérant
```

## Cas limites et erreurs

- **Le double clic.** La garde de BR-06 refuse la seconde écriture ; le mail doit être accroché à
  l'écriture, pas à la requête, sinon le coureur reçoit deux fois la même nouvelle.
- **La transaction annulée après l'envoi.** Un mail mis en file depuis l'intérieur de la transaction
  peut être traité avant le commit, et lire un statut périmé.
- **Le va-et-vient du gérant.** Annuler puis remettre en attente par erreur envoie deux mails
  contradictoires. C'est le comportement voulu : le coureur voit ce que le gérant a fait, et deux
  mails valent mieux qu'un état affiché sans explication.
- **La confirmation pendant la course.** Un retardataire confirmé alors que la course tourne reçoit
  le même mail que les autres.
- **Un serveur de mail indisponible.** Le traitement de l'inscription réussit quand même ; c'est le
  job qui échoue et qui se rejoue.

## Impacts techniques

Les quatre mails ne sont pas quatre habillages : c'est le même mail, dont seule la copie change. Le
choix de la copie est une lecture du couple (statut quitté, transition), et cette lecture est la
seule règle nouvelle de la story. Elle ne redéclare pas la chaîne des transitions, qui reste où elle
vit depuis BR-06.

L'envoi se place après la transaction d'écriture, comme le fait déjà la création de compte. Les
connexions de file ne sont pas en `after_commit`, donc l'emplacement de l'appel est ce qui garantit
que le mail lit un statut commité.

Le renommage du mail du code d'accès touche ses tests et sa clé de traduction. Il n'est pas
cosmétique : sans lui, deux mails du projet portent la même promesse et un seul la tient.

## Tâches

- [x] **T1** — Lecture du couple (statut quitté, transition) en une issue nommée, et envoi après
  commit `1 pt`
- [x] **T2** — Le mail des quatre issues et sa copie `1 pt`
- [x] **T3** — Renommage du mail du code d'accès et correction de sa promesse `0,5 pt`
- [x] **T4** — Tests : les quatre issues, le silence sur un traitement refusé, l'absence de dossard
  dans les quatre mails, la table du couple `0,5 pt`
