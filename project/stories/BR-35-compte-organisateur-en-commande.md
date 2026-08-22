# BR-35 — Créer le compte organisateur en une commande

| | |
|---|---|
| **Epic** | 7 — Déploiement |
| **Statut** | À faire |
| **Estimation** | 3 pts |
| **Créée** | 2026-08-21 — relevé pendant la mise en ligne, lot 2 |
| **Dépend de** | BR-01 |

## User story

En tant que **propriétaire de l'installation**,
Je veux **créer le compte organisateur et regénérer son code d'accès par une commande**,
Afin de **ne pas dépendre d'une session `tinker` copiée-collée pour ouvrir la production**.

## Contexte

Relevé en production le 2026-08-21, au moment exact où la base venait d'être migrée. Le lot 2 sait
tout faire sauf entrer dans l'application : `Permission` le dit dans son propre docblock — « il n'y
a pas d'écran d'administration des comptes » — et D-45 fait du lien envoyé par mail le seul chemin
de création de compte, chemin réservé aux coureurs. Le compte organisateur n'a donc **aucune porte**,
et le seul geste possible est une séquence `tinker` de dix lignes.

Cette séquence marche, mais elle est le mauvais outil pour le seul geste que personne ne peut se
permettre de rater :

- `tinker` sur l'image de production ouvre sur `Writing to directory /config/psysh is not allowed` :
  l'image FrankenPHP pose `XDG_CONFIG_HOME=/config` et le Dockerfile n'y donne à l'utilisateur
  `app` que `/config/caddy`. Avertissement bénin, mais il fait douter de la commande au pire moment.
- Le code d'accès n'existe en clair que dans la sortie de la session. Perdu, il faut reconstituer la
  séquence de mémoire.
- Rien ne rappelle que `FortifyServiceProvider` cherche l'adresse en minuscules : une majuscule
  saisie dans le `create()` produit un compte qui existe et ne peut pas se connecter.
- Rien ne rappelle non plus que le rôle `manager` doit exister avant qu'on puisse l'attribuer.

Quatre pièges dans un geste unique, joué une fois, sur une machine où il n'y a encore rien à
consulter pour comprendre l'échec. C'est exactement ce qu'une commande doit porter.

**Le compte a été créé à la main le 2026-08-22**, par cette séquence, et la production sert depuis.
La story ne bloque donc plus la mise en ligne : elle sort du lot 2 sans avoir été faite. Ce qu'elle
porte reste entier — le code d'accès n'a existé en clair que dans une sortie de terminal aujourd'hui
fermée, aucun geste ne le regénère, et la prochaine installation rejouerait les quatre pièges.

## Périmètre fonctionnel

**Inclus**
- Une commande Artisan qui crée un compte porteur du rôle `manager`, génère son code d'accès et
  l'affiche une fois.
- La normalisation de l'adresse comme le fait le formulaire d'inscription : minuscules, sans
  espaces autour.
- Un refus explicite si l'adresse est déjà prise, qui nomme l'option de regénération.
- Une option de regénération du code d'accès d'un compte existant.
- Un refus explicite si le rôle `manager` n'existe pas encore, qui nomme la commande de seed à
  lancer.

**Exclu**
- Tout écran d'administration des comptes : le périmètre reste la ligne de commande, l'exclusion de
  D-45 tient.
- L'envoi du code par mail. Le code s'affiche dans le terminal de celui qui lance la commande, qui
  est aussi celui qui en a besoin ; passer par le mail ajouterait une dépendance au worker et au
  fournisseur d'envoi pour le tout premier geste de la mise en ligne, avant qu'aucun des deux ne
  soit vérifié.
- La création de la course. L'écran d'organisation la fait déjà naître par son `firstOrNew()`.
- Le retrait d'un rôle ou la suppression d'un compte.

**Dépendances** — BR-01 pour les rôles et les permissions.

## Règles métier

- Le compte créé porte le rôle `manager`, et donc les neuf permissions que BR-01 lui attache.
- L'adresse est stockée en minuscules et sans espaces de bord, dans la même forme que celle que
  `FortifyServiceProvider` cherche à la connexion.
- Le code d'accès est produit par `AccessCode::generate()` — jamais saisi, jamais choisi.
- Le code n'est lisible qu'à l'instant où la commande l'affiche : `password` est haché par le cast
  du modèle, comme pour un coureur.
- La commande ne crée jamais deux comptes pour la même adresse.
- La regénération remplace le code et n'a aucun autre effet : ni le rôle, ni l'identité, ni
  l'inscription éventuelle ne changent.
- Le refus est un code de sortie non nul, pour qu'un script qui enchaîne s'arrête.

## Critères d'acceptation

```gherkin
Étant donné une base migrée dont les rôles sont seedés
Lorsque la commande est lancée avec une adresse libre
Alors un compte est créé avec le rôle manager
Et un code d'accès de douze caractères est affiché une fois
Et ce code permet de se connecter

Étant donné une adresse saisie avec des majuscules et des espaces autour
Lorsque la commande est lancée
Alors l'adresse est stockée en minuscules et sans espaces
Et la connexion avec l'adresse telle que saisie fonctionne

Étant donné une adresse déjà portée par un compte
Lorsque la commande est lancée sans option de regénération
Alors elle refuse avec un code de sortie non nul
Et son message nomme l'option de regénération

Étant donné un compte existant dont le code est perdu
Lorsque la commande est lancée avec l'option de regénération
Alors un nouveau code est affiché
Et l'ancien code ne permet plus de se connecter
Et le rôle du compte est inchangé

Étant donné une base migrée dont les rôles ne sont pas seedés
Lorsque la commande est lancée
Alors elle refuse avant toute écriture
Et son message nomme la commande de seed à lancer
```

## Cas limites et erreurs

- Adresse invalide : refus avant toute écriture, message qui dit ce qui est attendu.
- Compte existant portant déjà le rôle `manager` : la regénération ne duplique pas l'attribution.
- Compte existant qui est un coureur : la regénération de son code est acceptée — c'est le même
  mécanisme d'accès pour tout le monde — mais la commande ne lui ajoute pas le rôle `manager` sans
  qu'on le demande.
- Deuxième lancement avec la même adresse et l'option de regénération : idempotent du point de vue
  du compte, un nouveau code à chaque fois.

## Impacts techniques

La commande n'invente rien : `AccessCode::generate()`, le cast `hashed` de `password` et
`assignRole()` sont déjà ce que `RegisterRunner` enchaîne pour un coureur. La seule règle qui
n'existe nulle part hors du formulaire est la normalisation de l'adresse, aujourd'hui portée par
`AccountStoreRequest::prepareForValidation()`. Deux endroits qui doivent normaliser pareil : c'est
le moment de sortir la règle du FormRequest plutôt que de la recopier.

Le refus quand le rôle `manager` manque est préféré à un appel au seeder depuis la commande :
`RolesAndPermissionsSeeder` est la source unique de la correspondance rôle → permissions, et
l'attacher à une commande de création de compte en ferait une seconde porte d'entrée vers cette
correspondance.

La commande vit dans `app/Console/Commands`, à côté de `OpenDueRoundsCommand` et
`EnsureStorageBucketCommand`, et suit leur nommage `race:`.

## Tâches

- [ ] **T1** — Commande de création : normalisation de l'adresse, garde sur le rôle, garde sur
  l'unicité, code affiché une fois `1 pt`
- [ ] **T2** — Option de regénération du code d'accès `1 pt`
- [ ] **T3** — Tests : création, normalisation, refus sur doublon, refus sans rôles, regénération
  qui invalide l'ancien code `1 pt`
