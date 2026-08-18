# Décisions d'architecture

Chaque entrée est un choix arrêté. Si on revient dessus, on modifie l'entrée et on note pourquoi.

## D-01 — Runtime : PHP 8.4 via Sail

Laravel 13.26 sur **PHP 8.4** (image `sail-8.4`), MySQL 8.4, Redis, RustFS.
Arbitré le 2026-08-18 : PHP 8.4 plutôt que 8.5, alors que Laravel 13 accepte les deux.

Le plancher déclaré est `^8.4.1` — c'est la version minimale réellement exigée par les
dépendances Symfony du projet — et `config.platform.php` est aligné sur l'image Sail (8.4.24).

MySQL, Redis et RustFS ne vivent que dans les conteneurs : **toutes** les commandes
`artisan` / `composer` / `phpstan` / `pint` passent donc par `./vendor/bin/sail`, même si le
PHP de l'hôte satisferait la contrainte de version.

Les commandes npm tournent sur l'hôte (Node 22).

## D-02 — Starter kit Vue officiel plutôt qu'un montage manuel

Le projet est né de `laravel new --vue`, qui livre déjà Inertia 3, Vue 3 + TypeScript,
Tailwind 4, Vite, Laravel Fortify (auth headless), Wayfinder (routes typées côté TS),
passkeys et 2FA. Breeze n'existe plus depuis Laravel 12.

Conséquence : l'authentification, les pages de réglages et le layout de base sont acquis.
On construit le métier par-dessus au lieu de le recâbler.

## D-03 — Pas d'OSDD, layout `app/` classique

Arbitré par le propriétaire du projet le 2026-08-18 : pas de découpage en couches
`functional/` + `technical/`, alors que `xefi/laravel-osdd` est le défaut maison.

Raison : périmètre volontairement réduit (un événement, ~40 participants, 6 semaines de
fonctionnalités), et front intégralement Inertia — aucune couche à réutiliser ailleurs.
La logique métier reste centralisée dans `app/Services` et `app/Actions`, pas dans les
contrôleurs ni dans les composants Vue.

`xefi/laravel-osdd` a été installé puis retiré ; les couches créées ont été supprimées.

## D-04 — Pas de Lomkit

Demandé explicitement dans le cahier des charges : ni `lomkit/laravel-rest-api`,
ni `lomkit/laravel-access-control`, alors que les deux sont obligatoires par défaut chez Xefi.

Conséquence : **Policies Laravel natives** dans `app/Policies`, et aucun endpoint REST CRUD
générique. Tout passe par des contrôleurs Inertia et des Form Requests.
Cohérent avec le besoin : il n'y a pas d'API publique à exposer.

## D-05 — Permissions Spatie, jamais de test sur le nom du rôle

`spatie/laravel-permission` stocke deux rôles (`manager`, `participant`) et les permissions
granulaires. Le code **ne teste jamais un nom de rôle** — ni `hasRole('manager')`,
ni comparaison de chaîne : uniquement `can('manage-laps')` et les Policies.

Les rôles ne sont qu'un paquet de permissions à l'attribution.

## D-06 — Le backend est la seule source de vérité de la course

Aucune règle de course ne vit côté Vue. Le front affiche, le back décide :

- l'heure de validation d'une boucle est **l'heure serveur**, jamais une saisie ;
- l'élimination à expiration est produite par un job, pas par un écran ouvert ;
- le classement final est figé par une action serveur, pas recalculé à l'affichage.

## D-07 — Redis pour les queues, Horizon pour la supervision

`QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`. Horizon est installé et sert à surveiller
le job d'élimination automatique, les conversions média et le traitement du GPX.

## D-08 — Stockage : RustFS en local, S3 en production

`FILESYSTEM_DISK=s3` et `MEDIA_DISK=s3` pointent sur RustFS via Sail
(`AWS_ENDPOINT=http://rustfs:9000`, bucket `backyard`, path-style activé).
Le code ne connaît que le disque `s3` : passer en production ne change que le `.env`.

Le bucket a été créé au bootstrap. Console RustFS : http://localhost:9001.

## D-09 — Spatie Media Library pour tous les fichiers

Documents, photos de la galerie et fichier GPX passent par Media Library, avec des
collections nommées. Aucune table de fichiers maison.

## D-10 — Polling, pas de WebSocket

Le dashboard gérant se rafraîchit par polling léger côté Inertia. Pas de Reverb,
pas de Pusher, pas de broadcasting. La fréquence est un paramètre unique et central.

## D-11 — PHPUnit, jamais Pest

Tests en classes PHPUnit avec attributs `#[Test]` sur des méthodes `it_*`.
`pestphp/pest` ne doit pas apparaître dans `composer.json`.

## D-12 — Qualité outillée, gate à zéro erreur

- **Larastan niveau 7** + `xefi/phpstan-xefi-rules` (9 règles bloquantes).
- **Pint** pour le formatage PHP, **ESLint + Prettier + vue-tsc** côté front.
- `composer test` enchaîne lint, analyse statique et tests.

Seule exemption au fichier `phpstan.neon` : la règle de longueur de méthode ne s'applique
pas aux migrations, qui sont exclues par convention maison.

## D-13 — `xefi/faker-php-laravel` à la place de `fakerphp/faker`

Le faker amont a été retiré du projet. Les factories utilisent l'helper `faker()`.

## D-14 — Langue : interface en français, code en anglais

Modèles, colonnes, routes, méthodes, commits : anglais. Tout ce que lit un utilisateur :
français, via les fichiers de traduction. `APP_LOCALE=fr`, `APP_TIMEZONE=Europe/Paris`.

Le fuseau applicatif est Paris : les horaires de boucles sont manipulés en heure locale,
ce qui évite un décalage de deux heures sur « premier départ 13:00 ».

## D-16 — Aucun graphique : la page statistiques n'affiche que des chiffres

Arbitré le 2026-08-18, en deux temps.

D'abord le malentendu : le cahier des charges demandait « utiliser Graphify » pour les
graphiques statistiques, alors que Graphify est un outil de **graphe de connaissances du
codebase**, à l'usage de l'assistant, sans rapport avec la dataviz.

Puis l'arbitrage : **pas de graphiques du tout**. La page statistiques présente les indicateurs
et les compteurs par tour sous forme de chiffres et de tableau. Aucune librairie de graphiques,
et pas de SVG dessiné à la main non plus.

Graphify garde son usage réel : construire le graphe du codebase pour s'y repérer. Sans intérêt
sur un squelette fraîchement généré, utile dès que le métier existe.

## D-17 — Une seule distance de boucle, saisie par le gérant

Arbitré le 2026-08-18. La distance d'une boucle est **fixe pour tout l'événement** et
renseignée par le gérant dans la configuration (BR-03).

Elle n'est donc portée ni par le tour de course, ni par la boucle d'un participant : ces deux
objets la lisent depuis l'événement. Une distance par tour aurait couvert le cas « on raccourcit
la boucle de nuit », qui n'est pas au programme, au prix d'une colonne recopiée partout.

Conséquence sur le calcul de vitesse (BR-09) : `vitesse = distance de l'événement ÷ durée`.
Si le gérant corrige la distance en cours d'événement, les vitesses déjà affichées se
recalculent — c'est le comportement attendu d'une valeur unique.

La distance extraite du fichier GPX (BR-19) est une information de parcours, affichée telle
quelle. Elle ne remplace jamais la distance saisie par le gérant.

## D-18 — Mailpit en développement, aucun mail métier

Mailpit est ajouté à Sail (interface sur http://localhost:8025) pour intercepter les mails que
Fortify envoie en développement : réinitialisation de mot de passe, vérification d'adresse.

Aucun mail métier n'est prévu — pas de confirmation d'inscription, pas d'alerte d'élimination,
conformément à D-15. Mailpit est un filet de développement, pas l'amorce d'une fonctionnalité.

## D-15 — Hors périmètre, définitivement

QR Code, export CSV/Excel, impression de listes, notifications, journal d'audit,
classement temps réel, WebSockets, compte à rebours intégré, validation d'un tour par le
participant, saisie manuelle de l'heure de fin.

Ces absences sont un choix, pas un oubli. Une demande d'ajout rouvre cette entrée.

## D-19 — Déploiement sur un petit VPS mensuel, avec Dokploy

Arbitré le 2026-08-18, après un premier passage fondé sur une prémisse fausse — il n'y a pas de
VPS existant, il faut en prendre un.

Le déploiement se fait sur **un VPS à facturation mensuelle résiliable**, avec **Dokploy** comme
couche de déploiement : dépôt git relié, stacks Docker Compose, Traefik en frontal avec
certificats Let's Encrypt automatiques.

**Une promotion sur 24 ou 48 mois est exclue.** Le site sert une fois (D-20), et le tarif de
renouvellement de ces offres dépasse largement leur tarif d'appel. Le critère est la résiliation
au mois, pas le prix affiché la première année.

Pourquoi cette branche plutôt qu'une plateforme managée :

- Dokploy déploie des stacks **Docker Compose**, donc la production a la même forme que le
  développement sous Sail, au lieu d'être recomposée en services managés distincts.
- Le coût total sur la durée de vie du projet est d'une quinzaine d'euros.

Le contre-argument a été posé et écarté en connaissance de cause : sur une plateforme managée,
BR-26 disparaîtrait presque entièrement et BR-29 se réduirait à une vérification, pour 30 à 60 €
au total. Le choix assume donc de **payer moins et de faire le travail**.

Ce que ce choix implique, assumé : c'est nous qui tenons le système, les mises à jour, le disque,
la base et les sauvegardes. Il n'y a **qu'une machine et qu'un disque**, alors que les données de
la course n'existent nulle part ailleurs — aucune trace papier.

D'où trois exigences non négociables, portées par les stories de l'epic 7 :

1. Les sauvegardes de la base partent **hors de la machine**, vers le bucket objet (BR-29).
   Une sauvegarde sur le même disque que la base n'est pas une sauvegarde.
2. La restauration est **réellement exécutée** avant l'événement, pas seulement configurée (BR-29).
3. Une surveillance **hébergée ailleurs que sur le VPS** alerte sur le téléphone du gérant, et une
   seconde alerte distingue « l'application est tombée » de « le worker ne consomme plus »
   (BR-30, BR-31).

Le stockage des photos et documents reste un service objet compatible S3 hors de la machine
(Cloudflare R2 ou équivalent), jamais le disque du VPS : conformément à D-08, le code ne connaît
que le disque `s3`.

### Deux fichiers Compose, et pas de Traefik à déclarer

`compose.yaml` est le fichier de **développement**, géré par Sail : image `sail-8.4` avec Xdebug,
code monté en volume, plus Mailpit et RustFS. **Il n'est jamais déployé.**

La production a son propre fichier Compose : application construite depuis le `Dockerfile` de
production, worker, planificateur, MySQL, Redis. Ni Mailpit, ni RustFS — les mails n'existent pas
(D-18) et le stockage objet est externe.

**Traefik n'est déclaré dans aucun des deux.** Dokploy fait tourner sa propre instance de Traefik
comme frontal partagé : il lit le domaine configuré dans son interface, obtient le certificat
Let's Encrypt et route vers le conteneur de l'application. Déclarer notre propre Traefik lui
disputerait les ports 80 et 443. Le service applicatif ne publie donc **aucun port** sur l'hôte :
il est simplement rattaché au réseau de Traefik.

## D-20 — Le site sert une fois, puis s'arrête

Précisé le 2026-08-18 : l'application couvre **un seul événement**. Après la course et le temps
que tout le monde ait regardé les résultats et les photos, elle n'a plus d'usage.

Ce n'est pas un détail d'exploitation, ça décide plusieurs choses :

- **Aucun engagement d'hébergement.** Facturation mensuelle ou à l'usage, résiliable. Une
  promotion sur 24 ou 48 mois pour un usage d'une nuit est un mauvais calcul, d'autant que son
  tarif de renouvellement dépasse largement son tarif d'appel.
- **La durée de vie utile est de quelques semaines** : le temps du développement, l'événement,
  puis une période de consultation. Le coût total se compte en dizaines d'euros, pas en
  abonnement annuel.
- **Pas de dette à amortir.** Aucune décision n'a à être défendue sur trois ans : ni montée de
  version, ni migration, ni reprise par quelqu'un d'autre. Ce qui justifie de rester simple, et
  de ne pas construire d'outillage dont le seul bénéfice serait sur la durée — d'où le
  déploiement manuel retenu en D-21.
- **Le multi-événement reste hors périmètre**, définitivement (déjà acté en BR-03).

Ce que cela ne change pas : les données de la course n'ont pas de double papier. Les sauvegardes
et la restauration testée de BR-29 restent exigées, même pour une application jetable — ce qui est
jetable, c'est l'hébergement, pas la soirée.

## D-21 — Déploiement manuel depuis Dokploy, avec une branche `develop`

Arbitré le 2026-08-18. Pas de pipeline de déploiement automatique : **on clique** dans Dokploy.

Le flux :

```text
feature → develop → main → clic « Deploy » dans Dokploy
```

- Les fonctionnalités arrivent sur `develop`, qui est la branche de travail.
- `main` ne reçoit que ce qui est **prêt à partir en production**.
- Le déploiement est déclenché à la main depuis Dokploy, depuis `main`, quand on le décide.

L'intégration continue **ne change pas** : elle tourne sur `main`, sur `develop` et sur chaque
pull request. Elle continue donc de bloquer les régressions avant qu'elles n'atteignent `main`.

Ce que ce choix déplace : avec un pipeline automatique, « le code déployé a passé le CI » est
garanti par construction. Ici, c'est à la personne qui clique de le vérifier. La branche `develop`
est précisément ce qui rend ce risque acceptable — au moment du clic, `main` ne contient que du
code déjà passé par le CI et jugé prêt.

Ce qui est conservé du pipeline complet : la règle de **gel des déploiements pendant l'événement**,
et une procédure de **retour à la version précédente** essayée au moins une fois. Dokploy conserve
les déploiements antérieurs, le retour arrière est donc un clic lui aussi — mais un clic qu'il faut
avoir essayé avant la nuit de la course, pas pendant.

## D-22 — `laravel/boost` : on attend

Boost est obligatoire sur les projets Laravel Xefi, mais toutes ses versions jusqu'à `v2.5.4`
exigent `guzzlehttp/guzzle ^7.9`, alors que le squelette Laravel 13 embarque Guzzle 8.0.2.

Décision : **attendre une version compatible**, et retenter à l'occasion d'un `composer update`.
On ne rétrograde pas une dépendance runtime pour un outil de développement.

## D-23 — Le dossier `.claude/` fait partie du projet

`.claude/` est versionné et compte comme livrable du socle (BR-00) : hooks, agent de revue,
skills et permissions y sont partagés avec le dépôt plutôt que laissés sur un poste.

Contenu : formatage Pint automatique après chaque édition, blocage des écritures sur les fichiers
de secrets, injection de contexte Graphify et installation de son hook de post-commit, rappel de
documentation Bruno, un agent de revue Laravel, et une liste de commandes autorisées.

`.claude/settings.local.json` reste hors du dépôt — il est ignoré globalement, et c'est voulu :
c'est le fichier de préférences personnelles.

Deux réserves relevées à l'intégration, corrigées le 2026-08-19 :

- Les commandes autorisées visaient `php artisan ...` et `vendor/bin/pint ...` en direct, alors que
  tout passe par Sail (D-01) : elles ne correspondaient à rien de ce qui est réellement lancé. Elles
  sont remplacées par les équivalents `./vendor/bin/sail ...`, plus les vérifications npm en lecture
  seule. Rien de destructeur n'y figure — ni `migrate:fresh`, ni `db:wipe`, ni `git push`.
- Le rappel de documentation Bruno ne s'appliquait pas ici : pas de collection `bruno/`, et son
  message renvoyait à `app/Rest/`, c'est-à-dire à Lomkit, écarté en D-04. Le hook et son script
  sont supprimés.
