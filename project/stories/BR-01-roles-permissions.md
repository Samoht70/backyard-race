# BR-01 — Cloisonner l'application entre gérant et participant

| | |
|---|---|
| **Epic** | 1 — Fondations |
| **Statut** | À faire |
| **Estimation** | 8 pts |
| **Dépend de** | BR-00 |

## User story

En tant que **gérant de l'événement**,
Je veux **que les actions d'administration soient réservées à mon compte**,
Afin qu'**aucun participant ne puisse valider un tour, éliminer quelqu'un ou modifier l'événement**.

## Contexte

Deux profils cohabitent sur la même application : le gérant, qui pilote la course, et le
participant, qui consulte. Le cloisonnement est posé une fois ici et toutes les stories
suivantes s'y raccrochent — d'où sa place en tête de backlog.

## Périmètre fonctionnel

**Inclus**
- Deux rôles : `manager`, `participant`.
- Les permissions granulaires de l'énoncé, attribuées au rôle `manager`.
- Un compte gérant et des comptes participants créés par le seeder.
- Un participant qui tente une action d'administration reçoit un refus, pas une page cassée.

**Exclu**
- L'écran de gestion des comptes : le gérant est créé par seeder, il n'y a pas d'administration des utilisateurs.
- La délégation de droits entre participants.

**Dépendances** — BR-00.

## Règles métier

- Permissions à créer : `manage-event`, `manage-participants`, `manage-laps`, `validate-laps`,
  `manage-documents`, `manage-route`, `manage-gallery`, `view-statistics`, `finish-event`.
- Le rôle `manager` porte les neuf. Le rôle `participant` n'en porte aucune : ses accès
  passent par les Policies, sur la base de « c'est ma propre fiche ».
- Un participant accède en lecture à l'événement, au briefing, aux documents publiés, au
  parcours, au tableau des coureurs, à la galerie et aux statistiques une fois publiées.
- Le contrôle d'accès s'exprime **toujours** en permission ou en Policy, jamais par un test
  sur le nom du rôle.
- Un utilisateur nouvellement inscrit reçoit le rôle `participant`.

## Critères d'acceptation

```gherkin
Étant donné un utilisateur porteur du rôle "participant"
Lorsqu'il appelle une route d'administration de la course
Alors la réponse est un refus d'autorisation
Et aucune donnée n'est modifiée

Étant donné un utilisateur porteur du rôle "manager"
Lorsqu'il appelle la même route
Alors la réponse est un succès

Étant donné un visiteur qui vient de créer son compte
Lorsque son inscription est enregistrée
Alors il porte le rôle "participant"
Et il ne porte aucune permission d'administration

Étant donné la base fraîchement migrée et seedée
Lorsqu'on inspecte les permissions
Alors les neuf permissions attendues existent
Et elles sont toutes rattachées au rôle "manager"
```

## Cas limites et erreurs

- Utilisateur non connecté sur une route protégée : redirection vers la connexion.
- Permission absente en base parce que le seeder n'a pas tourné : l'accès est refusé, jamais ouvert par défaut.
- Cache de permissions Spatie non vidé après un seed : le vider explicitement dans le seeder.

## Impacts techniques

Le partage Inertia doit exposer au front les capacités de l'utilisateur courant, pour que
les écrans n'affichent pas des boutons qui mèneraient à un refus. Cet affichage
conditionnel est un confort : la décision reste côté serveur.

## Tâches

- [ ] **T1** — Ajouter le trait `HasRoles` au modèle `User` `1 pt`
- [ ] **T2** — Écrire le seeder des rôles et des neuf permissions, idempotent, avec purge du cache Spatie `2 pts`
- [ ] **T3** — Seeder un compte gérant et quelques comptes participants pour le développement `1 pt`
- [ ] **T4** — Exposer les capacités de l'utilisateur dans le partage Inertia et les typer côté TypeScript `2 pts`
- [ ] **T5** — Tests : refus pour un participant, succès pour un gérant, rôle par défaut à l'inscription `2 pts`
