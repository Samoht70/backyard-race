# BR-33 — Donner au coureur un chemin vers son inscription

| | |
|---|---|
| **Epic** | 6 — Expérience participant |
| **Statut** | À faire |
| **Estimation** | 5 pts |
| **Créée** | 2026-08-20 — lot « inscription en ligne d'abord » |
| **Dépend de** | BR-05, BR-17, BR-18 |

## User story

En tant que **coureur inscrit**,
Je veux **atteindre l'événement, mon inscription et les informations pratiques depuis la
navigation**,
Afin de **relire ce que j'ai saisi sans taper une URL à la main**.

## Contexte

BR-05 a livré la page d'inscription, BR-06 son statut et ses trois branches. Aucune de ces pages
n'est reliée à la navigation : `mainNavItems()` pointe **deux entrées vers `dashboard()`**,
`Dashboard.vue` n'affiche qu'un libellé, `/event` n'est lié depuis nulle part, et le seul lien vers
`/registration` de toute l'application vit sur `/event` — donc sur une page elle-même injoignable.

Le parcours existe entièrement côté serveur. Il est simplement sans porte. C'est la dernière chose
qui manque pour ouvrir les inscriptions au public.

## Périmètre fonctionnel

**Inclus**
- Une entrée de navigation vers l'événement, disponible à tout utilisateur connecté.
- Une entrée vers sa propre inscription, dès que l'utilisateur en porte une.
- Une entrée vers le briefing et les documents, une fois l'événement sorti de `draft`.
- Un écran d'accueil connecté qui dit où en est le coureur — inscription en attente, confirmée
  avec son dossard, ou annulée — et pointe vers l'action suivante.
- La présence d'une inscription exposée en prop partagée Inertia, comme les permissions.

**Exclu**
- Les chiffres de course — boucles, distance, prochain départ : ils appartiennent à BR-24, qui
  remplace le contenu de cet accueil dès que le moteur de course existe.
- Toute action sur l'inscription depuis l'accueil : on y navigue, on n'y modifie rien.
- L'entrée « Coureurs », qui reste sans destination tant que BR-14 n'a pas livré le tableau.

**Dépendances** — BR-05, BR-17, BR-18.

## Frontière avec BR-24

Les deux stories occupent le même écran à deux moments différents. **BR-33 est l'accueil
d'avant-course** : il vit pendant `registration`, ne connaît que le statut d'inscription et sert de
carrefour. **BR-24 est l'écran de course** : il arrive avec BR-08, remplace le contenu par les
chiffres du coureur, et hérite de la navigation posée ici. Aucune des deux ne refait le travail de
l'autre — c'est la condition pour qu'elles ne finissent pas comme BR-21 et BR-23 (D-47).

## Règles métier

- Un utilisateur connecté qui porte une inscription voit toujours une entrée vers elle.
- Un utilisateur connecté sans inscription est orienté vers l'événement, jamais vers un écran vide.
- Les entrées briefing et documents n'apparaissent que si l'événement est sorti de `draft`, en
  accord avec les règles d'accès de BR-17 et BR-18.
- L'entrée de gestion reste réservée au porteur de `manage-event`, comme aujourd'hui.
- La navigation ne décide d'aucun droit : elle masque ce qui serait refusé, le refus reste porté
  par les Policies et le middleware.
- Le libellé de l'entrée d'inscription ne dépend pas du statut : « Mon inscription » couvre les
  trois branches, c'est l'écran qui dit laquelle.

## Critères d'acceptation

```gherkin
Étant donné un coureur connecté dont l'inscription est en attente
Lorsqu'il ouvre l'application
Alors la navigation lui propose "Mon inscription"
Et son accueil annonce que son inscription est en attente de confirmation

Étant donné un coureur connecté dont l'inscription est confirmée
Lorsqu'il ouvre son accueil
Alors il y lit son numéro de dossard

Étant donné un utilisateur connecté sans inscription
Lorsqu'il ouvre son accueil
Alors aucune entrée "Mon inscription" ne lui est proposée
Et il est invité à s'inscrire à l'événement

Étant donné un événement au statut "draft"
Lorsqu'un participant ouvre la navigation
Alors aucune entrée vers le briefing ni vers les documents ne lui est proposée

Étant donné un participant sans la permission manage-event
Lorsqu'il ouvre la navigation
Alors aucune entrée de gestion ne lui est proposée
```

## Cas limites et erreurs

- Inscription annulée : l'entrée reste, l'écran dit ce qui s'est passé — jamais une entrée qui
  disparaît sans explication (D-48).
- Utilisateur portant `manage-event` **et** une inscription : il voit les deux jeux d'entrées.
- Navigation mobile : les entrées tiennent dans la barre basse sans repli en « … » pour un coureur
  ordinaire.
- Événement absent en base : l'accueil ne casse pas.

## Impacts techniques

La prop partagée d'inscription se pose dans `HandleInertiaRequests`, à côté de `auth.permissions`
qui sert déjà `can()`. Une requête par rendu, jamais une par entrée de menu.

`mainNavItems()` devient conditionnel sur deux axes — l'inscription et le statut de l'événement —
là où il ne l'était que sur les permissions. C'est le moment de le tester, faute de quoi la première
entrée qui fuite vers un écran refusé ne se verra qu'en production.

## Tâches

- [ ] **T1** — Prop partagée : présence d'une inscription et statut de l'événement `1 pt`
- [ ] **T2** — Refonte de `mainNavItems()` : événement, mon inscription, briefing, documents,
  gestion `1 pt`
- [ ] **T3** — Écran d'accueil d'avant-course : statut, dossard si confirmé, raccourcis `2 pts`
- [ ] **T4** — Tests : entrées selon inscription, permission et statut d'événement `1 pt`
