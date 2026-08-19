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

**Constat de BR-03 :** il n'y a pas de Node sur l'hôte de développement actuel. Les commandes npm
passent donc par Sail elles aussi (`./vendor/bin/sail npm run …`, Node 24 dans l'image). Deux
pièges à connaître : un `npm install` lancé dans le conteneur réécrit le champ `name` du
`package-lock.json` en `html`, d'après son répertoire de travail, et la CLI shadcn-vue en profite
pour remonter des dépendances non demandées. Vérifier `git diff package.json package-lock.json`
après toute commande npm qui touche aux manifestes.

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

**Corrigé le 2026-08-19 par BR-03 : ce paragraphe était faux depuis le début.** `config/app.php`
codait `'timezone' => 'UTC'` en dur — la valeur du squelette Laravel — donc `APP_TIMEZONE` du
`.env` n'avait jamais eu le moindre effet et `now()` rendait de l'UTC, deux heures derrière la
course. Personne ne l'avait vu : aucune donnée temporelle métier n'existait avant `first_start_at`.

La ligne lit désormais `env('APP_TIMEZONE', 'UTC')`, `phpunit.xml` et le workflow d'intégration
fixent `APP_TIMEZONE=Europe/Paris` — aucun des deux ne le faisait, la CI aurait donc divergé du
poste en silence — et `tests/Unit/ApplicationTimezoneTest.php` épingle à la fois le fuseau effectif
et le fait que la configuration le lise dans l'environnement.

C'est exactement le décalage contre lequel BR-04 met en garde. Le corriger à l'arrivée de la
première heure métier évitait d'avoir à migrer des horaires déjà saisis.

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

## D-29 — Le cycle de vie de l'événement vit dans des classes d'état

Arrêté le 2026-08-19 par BR-03, avec le propriétaire du projet.

`draft → registration → running → finished` est un vrai cycle de vie persisté. Les skills maison
`laravel:status-lifecycle` et `design-patterns:state` en font une **règle dure** pour un cycle
neuf : une classe par statut, les transitions illégales qui lèvent. On l'applique.

**Le raisonnement de D-26 ne se transpose pas.** `RunnerStatus` est sans transitions parce qu'il
est **dérivé** — jamais persisté, jamais franchi. `EventStatus` est l'inverse : une colonne que le
gérant fait avancer. Le danger que D-26 combat n'est pas « mettre les transitions quelque part »,
c'est **les déclarer deux fois**. Ici elles ne le sont qu'une : dans les états.

**Variante linéaire, pas la forme canonique du skill.** La chaîne n'a aucun branchement, donc
chaque état porte un seul `advance()` plutôt que trois méthodes de transition nommées dont neuf
n'existeraient que pour lever. Ce que ça achète, et qui est le cœur de la story : le saut et le
retour arrière ne sont pas *rejetés*, ils sont **inexprimables** — aucun appelant n'a d'API pour
demander `running → registration`. Le `match` de `EventLifecycleFactory` est le seul du dépôt sur
`EventStatus`, et il est exhaustif : un statut ajouté sans son état échoue à l'analyse statique,
pas en production.

Coût mesuré face à l'option « enum enrichi + service » : +5 fichiers, +170 lignes triviales,
écrites une fois. L'option courte aurait listé les statuts dans cinq ou six `match` dont aucun
n'est exhaustif, et le garde-fou le plus important du produit aurait reposé sur une vérification
qu'on peut oublier. D-20 ne renverse pas l'arbitrage : ce n'est pas de la dette à amortir sur
trois ans, c'est le prix d'une soirée où une course lancée par erreur ne se dé-lance pas.

**Interdiction expresse** : ne jamais ajouter `next()` ni `canTransitionTo()` sur `App\Enums\EventStatus`.
Ce serait la deuxième déclaration de la chaîne, et donc la dérive que tout ceci évite.

## D-30 — Schéma de l'événement : unités entières et instant unique

Arrêté le 2026-08-19 par BR-03.

- **`first_start_at` est une seule colonne datetime**, pas une paire date + heure. BR-04 calcule
  `premier départ + (N−1) × durée` : il lui faut un instant, et une paire ne dirait pas si le tour
  12 tombe le 15 ou le 16. L'écran garde deux contrôles, fusionnés côté serveur.
- **Distance en mètres entiers, durée en minutes entières.** La boucle canonique vaut 6 706 m, un
  nombre rond en mètres et pas en kilomètres ; un `decimal` se caste en *string* en JSON et ferait
  de la vitesse de BR-09 une division de flottants. Les deux colonnes sont `unsigned` avec un
  minimum de 1, donc la distance nulle et la durée négative sont refusées par la colonne
  elle-même, pas seulement par une règle.
- **Coordonnées `decimal(10,7)` en base, castées `float`** : le cast `decimal:7` rendrait
  `"45.7640000"`, que la carte de BR-19 recevrait tel quel. Vérifié par aller-retour en base :
  aucune perte de précision, un `double` portant 15 à 17 chiffres significatifs pour 10 stockés.
- **La date et l'heure se tiennent l'une l'autre à la saisie.** L'écran envoie deux contrôles pour
  une seule colonne ; chacun est obligatoire dès que l'autre est rempli, et vider les deux efface
  l'heure de premier départ. Sans cette réciprocité, une date saisie sans heure n'atteignait
  aucune règle et disparaissait derrière un message de succès.
- **`max_participants` nul signifie « pas de limite »**, jamais zéro. BR-05 doit le lire ainsi.
- **Aucun index hors clé primaire** : la table porte une ligne (D-20). Ce n'est pas un oubli.
- **`status` est absent de `#[Fillable]`**, et un test l'affirme : c'est la seule chose entre une
  requête forgée et une course déclarée terminée. Le modèle porte aussi `draft` en attribut par
  défaut — le défaut de colonne seul laissait un `firstOrNew()` non enregistré sans statut, et
  l'écran de configuration tombait sur une base vierge.

## D-31 — Un seul événement, donc un routage singleton

Arrêté le 2026-08-19 par BR-03.

Pas de segment `{event}`, pas de route model binding : `GET /event`, `GET|PUT /manage/event`,
`POST /manage/event/advance`. L'événement est résolu à l'appel par `firstOrNew()` (l'écran de
configuration doit fonctionner sur une base jamais semée) ou `firstOrFail()`.

**Création et modification passent par le même `PUT`.** Un seul formulaire, une seule Form Request,
un seul test. `updateOrCreate()` a été écarté : il court-circuite `#[Fillable]` et la Policy.

**L'unicité est tenue par la base, pas par la convention.** Une revue adverse a relevé que tout le
code lit l'événement avec `sole()` ou `firstOrFail()` — l'invariant était supposé partout et garanti
nulle part, et deux premiers enregistrements concurrents auraient créé deux lignes. La table porte
donc une colonne `singleton` à valeur unique, dont le seul rôle est de rendre la seconde ligne
impossible.

## D-32 — Un refus de transition est une erreur de validation, pas une exception rendue

Arrêté le 2026-08-19 par BR-03.

`NoTryCatchRule` interdit d'attraper l'exception de transition pour la convertir en erreur de
formulaire, et Q-02 rappelle qu'aucune page d'erreur Inertia n'existe : un 409 sortirait sur la
page Symfony par défaut, en anglais, hors de la SPA.

D'où deux canaux, **une seule règle** :

- le gérant qui clique reçoit un **422** — `EventAdvanceRequest::after()` interroge l'état et
  ajoute les motifs aux erreurs de validation, en français, dans l'écran ;
- tout appel hors formulaire (seeder, BR-20, onglet périmé côté serveur) reçoit un **409** —
  `EventTransitionRefusedException` étend `ConflictHttpException`.

La règle n'est pas dupliquée : la Form Request pose la question, l'état y répond, et `advance()`
lève sur la même condition. L'exception est le filet, pas le canal.

**L'écriture est conditionnelle, et c'est ce qui ferme la fenêtre.** La revue adverse a démontré
qu'entre la vérification de la Form Request et l'écriture, une requête concurrente pouvait déplacer
l'événement : le gérant demandait `registration`, la validation approuvait `registration`, et
l'événement se retrouvait en `running`. `AdvanceEventStatus` reçoit désormais le `to` validé et
écrit avec `where('status', <statut quitté>)` : zéro ligne touchée signifie que quelqu'un est passé
avant, et le refus est levé. Aucune transition n'étant réversible, c'était le défaut le plus grave
de la branche.

Le champ `to` de la route d'avancement n'est pas décoratif : il nomme l'étape que le gérant croyait
franchir, donc un double clic ou un onglet périmé est refusé au lieu de pousser la course un cran
trop loin.

**BR-03 ne ferme pas Q-02 et ne l'aggrave pas** : restent les 403 et 409 des chemins d'abus.

## D-33 — `running → finished` est posée par BR-03, la clôture reste à BR-20

Arrêté le 2026-08-19 avec le propriétaire du projet, en écart assumé au « Exclu » de BR-03.

BR-03 rend `finished` atteignable et met l'événement en lecture seule, ce dont BR-17, BR-18 et
BR-19 ont besoin pour leur règle « consultable dès que l'événement sort de `draft` ». Sans cela,
`FinishedEventState` resterait sans couverture de bout en bout jusqu'à BR-20 et la Policy devrait
être rouverte.

Ce qui reste à BR-20, et qui est le vrai contenu de cette story : la confirmation explicite et le
**classement figé**. BR-03 ne produit aucun classement.

La transition vers `finished` exige `finish-event` ; les deux précédentes exigent `manage-event`.
`finish-event`, créée sans consommateur par BR-01, en a donc un — sans changement de comportement,
le rôle gérant portant les neuf permissions. Un test le vérifie avec un utilisateur ne portant que
`manage-event`, faute de quoi aucun test bâti sur le rôle ne saurait distinguer les deux.

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

## D-24 — Direction artistique « Corral » : tokens Tailwind, aucune librairie ajoutée

Arrêtée le 2026-08-19 avec le propriétaire du projet.

L'interface est un **tableau de chronométrage**, pas un back-office : dans un Backyard Ultra
le tour est l'unité, on referme la boucle dans l'heure ou on est sorti.

- **Surfaces** — clair : blanc pur, encre presque noire à reflet bleu, pour tenir le plein
  soleil. Sombre : ardoise bleu profond, **jamais de noir pur** (le noir pur fait halo sur du
  texte fin à la frontale).
- **Un seul accent, l'outremer**, choisi délibérément *hors* du jeu sémantique : les quatre
  statuts consomment déjà le vert, le rouge, l'ambre et l'ardoise.
- **Une seule famille variable, Archivo**, trois voix par son axe de largeur (Expanded Black
  pour les nombres, largeur normale pour le texte, Expanded capitales pour les micro-libellés).
  **Auto-hébergée** depuis `resources/fonts/` via le fournisseur `local()` du plugin Vite :
  les fournisseurs distants ne servent que l'axe de graisse, et l'événement se déroule dans un
  champ sur une connexion dégradée. Aucune requête tierce ne subsiste.
- **Élément signature : la bande de tour comme plaque typographique.** Pas de jauge, pas de
  barre, pas d'horloge — voir D-25. Le numéro de tour et le nombre de coureurs restants sont
  posés en corps d'affichage, en chiffres tabulaires.
- **La note d'anniversaire** est un unique séparateur en rangée de points façon guirlande,
  d'un seul ton en paliers d'opacité — aucun token supplémentaire, aucune collision sémantique.
- **Aucune librairie de composants ajoutée** : les 22 dossiers shadcn-vue déjà présents
  suffisent. Aucune primitive n'a eu besoin d'être ajoutée.
  **Amendé le 2026-08-19 par BR-03 :** la 23ᵉ primitive, `textarea`, est arrivée avec le premier
  champ multiligne du produit. « Aucune librairie ajoutée » reste vrai — la CLI shadcn-vue copie
  de la source et n'ajoute rien à `package.json` ; le textarea n'utilise que `useVModel`, déjà
  présent. La recopier à la main aurait dupliqué les quarante utilitaires d'`Input.vue`, dans un
  fichier soumis au lint alors que `components/ui/*` en est exempté par contrat.
- **Notation oklch**, pour viser AA au lieu de le deviner. La palette vit dans **trois**
  endroits : `:root`, `.dark`, et le `<style>` anti-flash de `resources/views/app.blade.php`
  que Blade ne peut pas lire depuis le CSS. Un test affirme que les trois concordent.
- **L'accent tient désormais AA en texte normal.** Il n'était affirmé qu'à 3:1 (grand texte),
  n'ayant servi qu'à du corps d'affichage ; la puce « inscriptions ouvertes » de BR-03 le porte en
  `text-sm`. `primary` sur `background` et sur `card` sont passés à 4,5:1 dans le test, et la
  palette est passée sans retouche — la valeur du token n'a pas bougé.
- **AA est vérifié par un test, pas par la revue** : `tests/Unit/DesignSystem/PaletteContrastTest.php`
  analyse les tokens, convertit oklch → sRGB et contrôle 65 couples dans les deux thèmes, plus
  la présence de chaque token dans les deux (un token absent de `.dark` se figerait en silence
  sur sa valeur claire). Aucune dépendance, aucune étape de CI en plus.
- **Cibles tactiles** : 44 px de plancher, 72 px pour la validation d'une boucle. Pas de token —
  l'intention vit dans les variantes nommées d'`ActionButton`.
- Les tokens `--chart-1..5` sont **supprimés** : D-16 exclut tout graphique.

Mesuré sur un viewport réel de 375 px : aucun débordement horizontal, aucune cible sous 44 px.
La lisibilité en niveaux de gris est portée par le **pictogramme et le libellé**, pas par la
couleur — les quatre encres doivent rester dans la bande AA, qui est trop étroite pour que la
clarté suffise à les distinguer.

## D-25 — Pas de compte à rebours, et pas de barre de progression

Arrêté le 2026-08-19. D-15 excluait déjà le « compte à rebours intégré », BR-04 et BR-13 le
répètent. Le propriétaire a confirmé : **un chronomètre physique s'en charge**.

Étendu à la demande : **aucune barre de progression**, sous aucune forme. L'élément signature
de la direction artistique a donc été réancré sur la typographie, et le lime haute visibilité —
qui n'existait que pour cette barre — est sorti de la palette.

Conséquences : **aucune horloge côté client** nulle part, donc aucune dérive avec l'heure
serveur et aucun écran qui s'anime pendant quinze heures de nuit. Les horaires sont affichés
comme des faits fixes venus du serveur.

Seule exception, assumée : le **filet de chargement d'Inertia** en haut de l'écran, fourni par
le starter kit. Ce n'est pas un élément de mise en page mais le seul retour visuel quand le
réseau traîne, et le cas limite « jamais un écran figé » de BR-02 le réclame. Il est repeint en
couleur de marque.

## D-26 — Contrat de statut coureur : quatre statuts d'affichage, déclarés deux fois et vérifiés

Arrêté le 2026-08-19, après lecture des stories aval.

Les quatre statuts de BR-02 (en course, éliminé, abandon, terminé) sont un **jeu de
présentation, pas une colonne** :

- BR-08 ne modélise que deux états du participant ;
- BR-10 et BR-11 aboutissent **tous deux** à `eliminated`, distingués par le seul motif —
  « L'abandon et l'élimination automatique aboutissent au même statut mais pas au même motif » ;
- le `finished` de BR-20 est le statut de l'**événement**, pas du coureur.

Donc `App\Enums\RunnerStatus` est un enum **dérivé**, jamais persisté, et **strictement de
données** : pas de `canTransitionTo()`, pas de transitions — le cycle de vie appartient à
BR-08 → BR-11. La dérivation `RunnerStatus::for(Participant)` est à écrire **dans BR-08** :
aucun modèle `Participant` n'existe encore. **BR-08 ne doit pas redéclarer un jeu concurrent.**

Le motif de sortie (`Timeout` / `Withdrawal`) reste à BR-10/BR-11.

### Pourquoi la carte est dupliquée côté TS

Deux faits techniques ferment la discussion :

- **Tailwind 4 ne génère que les classes présentes dans les sources analysées.** Une chaîne de
  classe arrivant du PHP à l'exécution ne produit aucun CSS — les puces s'afficheraient nues.
  Un fichier généré *gitignoré* ne serait pas analysé non plus.
- **Une icône Lucide est un composant Vue**, importé statiquement pour être secoué à l'arbre.

La répartition ne duplique donc **aucun fait** : PHP porte le jeu de valeurs et la clé de
libellé, TypeScript porte l'icône et les classes de couleur. Un statut nouveau touche deux
fichiers — ce n'est pas littéralement « un seul endroit », et aucune option ne l'est. Ce qui est
acquis, c'est que la divergence **échoue en CI dans les deux sens** :

- `tests/Unit/Enums/RunnerStatusParityTest.php` épingle les deux jeux de valeurs ;
- `satisfies Record<RunnerStatus, …>` fait rejeter une carte incomplète par `vue-tsc`.

Les deux ont été **vérifiés en les cassant volontairement**. Ne pas « corriger » cette
duplication : le générateur Artisan est rejeté sur D-20 (une commande, un mode `--check` et une
étape de CI pour maintenir quatre lignes qui ne changeront plus).

Deux écarts délibérés aux skills maison, à ne pas rectifier :

- **pas de `color()` ni d'`icon()` sur l'enum** — aucun consommateur PHP n'existe (tout est
  Inertia + Vue), ce serait une troisième déclaration morte ;
- **`label()` en `match` à clés littérales** plutôt qu'en clé interpolée : le collecteur de
  Larastan n'inspecte que les littéraux, l'interpolation rendrait les clés invisibles à la
  vérification.

## D-27 — Traductions : groupes `lang/fr/`, livrés par un prop Inertia

Arrêté le 2026-08-19. Le projet n'avait aucun fichier de traduction alors que D-14 impose
l'interface en français « via les fichiers de traduction ».

**Fichiers groupés `lang/fr/*.php`** — et non un `lang/fr.json` à clés plates — pour garder la
porte ouverte à `lang/en/`.

Le front ne pouvant pas importer du PHP, le dictionnaire est **résolu sur la locale courante et
livré par un prop Inertia partagé** (`translations`, aplati en clés pointées par `Arr::dot`),
lu par un `t()` de dix lignes dans `resources/js/lib/i18n.ts`. Passer à `lang/en/` ne touchera
aucun fichier front. Aucun paquet npm, aucun runtime i18n.

Les groupes partagés avec le front sont **une liste explicite** (`ui`, `race`) : les groupes que
seul PHP rend — `validation`, mails — restent dehors, sinon chaque réponse embarquerait tous les
messages du framework.

**`checkMissingTranslations: true`** est activé dans `phpstan.neon` : une ligne, aucune étape de
CI en plus, et toutes les clés `__()` littérales deviennent vérifiées par l'analyse statique
déjà en place. Elle a immédiatement attrapé les deux toasts anglais hérités du starter kit,
désormais en clés françaises.

Réserve assumée : **aucune vérification statique des clés côté TS** — une faute de frappe
affiche la clé brute. C'est le prix du choix multi-locale, et il est faible.

**Complété le 2026-08-19 par BR-03**, sur deux points.

Un **troisième groupe, `event`**, rejoint `ui` et `race` dans la liste partagée. Le découpage est
par domaine — `ui` est le chrome, `race` la course vivante, `event` l'objet racine que reliront
BR-04, BR-13, BR-20 et BR-23. La liste explicite de D-27 existe précisément pour qu'on y ajoute un
groupe quand un domaine apparaît ; verser les quarante clés de BR-03 dans `ui` aurait fait du
groupe « chrome » le fourre-tout du produit dès la troisième story.

Et **`lang/fr/validation.php` existe enfin**. Le projet n'en avait aucun alors que
`APP_FALLBACK_LOCALE=en` : personne ne l'avait vu parce qu'aucun écran n'avait encore affiché une
erreur de règle. BR-03 en valide dix champs, et « The name field is required. » aurait été le
premier message anglais du produit, contre D-14. Le fichier porte les règles réellement utilisées
plus un bloc `attributes` avec les noms de champs français ; Laravel retombant sur l'anglais clé
par clé, il n'a pas à être exhaustif.

**Hors périmètre de BR-02** : les écrans hérités du starter kit (auth, réglages, 2FA, passkeys)
restent en anglais. Voir la question ouverte dans [QUESTIONS.md](QUESTIONS.md).

## D-28 — Contrat de permissions : neuf capacités, deux enums, une map partagée

Arrêté le 2026-08-19 par BR-01, qui applique D-05 pour la première fois.

`App\Enums\Permission` (neuf cases) et `App\Enums\Role` (deux cases) sont la **seule** déclaration
des noms : le seeder, le middleware de route et le partage Inertia les lisent tous. Aucun `label()`
sur ni l'un ni l'autre — les permissions ne sont jamais affichées et il n'y a pas d'écran
d'administration des comptes, ce serait la troisième déclaration morte contre laquelle D-26 met en
garde.

**Le contrôle d'accès passe par `can:`**, pas par les alias `role:` / `permission:` de Spatie.
`can` est déjà enregistré nativement par le framework et traverse le Gate que Spatie alimente via
`register_permission_check_method` : un seul mécanisme pour le middleware de route, les Policies à
venir et le prop partagé. Le refus est fermé par défaut — une capacité sans ligne en base n'a pas
de callback dans le Gate et l'exception « permission inconnue » de Spatie est avalée en `false`.

**Le front reçoit une map complète de booléens**, `auth.permissions`, et non la liste des
permissions accordées. Chaque valeur est le résultat du même `can()` par lequel le serveur
autorise : une Policy ajoutée plus tard ne peut pas faire diverger les boutons et les décisions.
Un invité reçoit les neuf clés à `false`, donc aucun écran ne branche sur une clé absente. Le
helper vit dans `resources/js/lib/permissions.ts`, à côté de `t()` — c'est une lecture pure d'un
prop partagé, pas une composable, qui n'existerait que si elle possédait un `ref`.

La liste des neuf noms est **dupliquée côté TS**, mais pas pour la raison de D-26 : TypeScript ne
porte ici aucun fait propre, c'est un miroir acheté pour l'autocomplétion. Ce qui justifie le
`tests/Unit/Enums/PermissionParityTest.php`, c'est le mode de panne : renommer une capacité côté
PHP seul compile, résout `undefined`, tombe en falsy et **fait disparaître le bouton du gérant la
nuit de la course**. Les deux sens ont été vérifiés en les cassant.

**`hasRole()` n'apparaît qu'à un seul endroit du dépôt** : les assertions d'inscription de
`tests/Feature/Auth/RegistrationTest.php`. Le critère d'acceptation dit littéralement « il porte le
rôle "participant" » ; un test doit pouvoir énoncer ce fait. Partout ailleurs, D-05 s'applique sans
exception.

**La purge du cache Spatie est en tête du seeder, pas en queue**, et c'est la ligne porteuse :
`Permission::findOrCreate()` résout contre le snapshot mémoïsé du registrar, pas contre la table.
Un snapshot pris table vide fait insérer des doublons au second passage et casse sur l'index unique
`(name, guard_name)`. `Role::findOrCreate` n'a pas ce défaut, il interroge directement — asymétrie
Spatie à connaître. La purge utilise `PermissionRegistrar::forgetCachedPermissions()` et non un
`Cache::forget('spatie.permission.cache')`, qui laisserait la propriété en mémoire du registrar
pleine et taperait le mauvais store le jour où `permission.cache.store` sera explicite.

**Dette assumée : le seed des rôles est une étape de déploiement obligatoire.** L'inscription
appelle `assignRole`, qui lève si le rôle manque : un `migrate --force` sans `db:seed` fait tomber
la première inscription en 500. L'alternative — les rôles en migration de données de référence,
comme le suggère la convention maison de seeder — a été écartée : la story demande un seeder, et on
perdrait le test « permissions absentes en base → refus », qui est le garde-fou du cas limite le
plus dangereux. **BR-32 doit porter l'étape de seed dans son runbook.**

**`finish-event` a obtenu son premier consommateur le 2026-08-19** (BR-03) :
`EventPolicy::advance` l'exige pour la transition vers `finished`, les deux étapes précédentes se
contentant de `manage-event`. Aucun changement de comportement — le rôle gérant porte les neuf —
mais la distinction est désormais réelle, et un test la vérifie avec un utilisateur ne portant que
`manage-event`, faute de quoi aucun test bâti sur le rôle ne saurait la voir.

Enfin, les tests seedent **dans le corps de chaque test** qui en a besoin, jamais via
`protected $seed`. `RefreshDatabase` ne lance `migrate:fresh --seed` qu'une fois, gardé par un
static : si la première classe exécutée ne demande pas de seed, plus aucune ne seedera. Le résultat
dépendrait de l'ordre des classes.

## D-34 — Le tour de course s'appelle `Round`, la boucle individuelle restera `Lap`

Arrêté le 2026-08-19 par BR-04. Le français dit « tour » pour l'objet collectif et « boucle » pour
la performance d'un coureur ; l'anglais du code devait trancher, aucune story ne l'avait fait.

`Round` porte le numéro, l'heure de départ et l'heure limite, communs à tout le monde. `Lap` reste
réservé à BR-08 : une boucle par participant et par tour. Les colonnes de l'événement gardent leur
nom — `lap_distance_meters`, `lap_duration_minutes` — où « lap » désigne la boucle canonique,
celle que tout le monde court : deux mots pour deux niveaux, ce qui est l'intention.

Conséquence immédiate : `LapHeader.vue`, livré par BR-02, affichait un tour et non une boucle. Il
est renommé `RoundHeader.vue` et le bloc `race.lap.*` de `lang/fr/race.php` devient `race.round.*`,
sans qu'un seul libellé français change. Le renommage était gratuit tant que le composant ne vivait
que dans la galerie ; dès BR-08 il aurait été un piège permanent. `race.lap.*` est désormais libre
pour les boucles individuelles.

## D-35 — Les horaires de tour sont stockés en UTC, et une boucle dure une heure réelle

Arrêté le 2026-08-19 par BR-04. C'est la décision la plus lourde de la story, et elle corrige un
défaut qu'aucun test n'aurait attrapé par hasard.

**Le piège, vérifié en base.** Une colonne `DATETIME` MySQL stocke une horloge murale sans décalage.
La nuit du 25 octobre, l'heure locale 02:00 est vécue deux fois : les tours 14 et 15 écrivent tous
les deux `"02:00:00"`, et le cast `immutable_datetime` par défaut relit le premier **une heure trop
tard**. Mesuré : timestamp attendu 1792886400, relu 1792890000, soit exactement 3600 secondes. Un
coureur hors délai aurait gagné une heure et l'élimination de BR-11 serait fausse, en silence, la
seule nuit où ça compte.

D'où `App\Casts\UtcDateTime` sur `rounds.starts_at` et `rounds.deadline_at` : écriture en UTC,
lecture dans le fuseau applicatif. Un test le prouve en repassant par la base
(`it_stores_an_ambiguous_round_start_without_losing_an_hour`) — il est rouge avec le cast par
défaut, vert avec celui-ci.

**Faux problème écarté :** `addMinutes()` et `addRealMinutes()` sont identiques pour les unités
infra-journalières, `DateTimeImmutable::add()` travaillant sur le timestamp. On garde `addMinutes()`,
réellement typée, plutôt que la méthode magique dont le nom rassure sans rien garantir. La sémantique
est épinglée par un test sur les timestamps, pas par un nom de méthode.

**Sémantique arrêtée :** la boucle dure une heure **réelle**, l'affichage est l'horloge murale, et
l'horloge murale a le droit de se répéter. Le 25 octobre, l'entête affichera « Départ 02:00 — Limite
02:00 » sur un tour, puis « Départ 02:00 — Limite 03:00 » sur le suivant. C'est correct : les
coureurs partis à 02:00 heure d'été sont rentrés quand le chronomètre du gérant affichait de nouveau
02:00. L'alternative — des boucles d'une heure murale, donc de 0 ou 120 minutes réelles — n'est pas
une Backyard. On accepte l'entête ambigu plutôt qu'une mention conditionnelle pour un cas qui
survient une fois dans la vie du produit.

**La règle vaut pour tout instant métier, `events.first_start_at` comprise.** La première rédaction
de cette entrée exemptait cette colonne — un départ à 02:30 la nuit de la bascule étant absurde. La
revue de la story a renversé l'arbitrage, et elle avait raison sur les deux plans. Sur le fond : la
garantie « les horaires de tour survivent à la bascule » n'était pas absolue mais **conditionnelle à
son origine**, puisque `RoundSchedule::fromEvent()` lit `first_start_at` — et rien dans le code ne le
disait. Sur le coût : **il est nul**. La colonne reste `DATETIME`, seule son interprétation change,
et rien n'est déployé — il n'y a aucune donnée à reprendre. Une migration de reprise avait d'abord
été écrite puis supprimée pour cette raison : elle n'aurait jamais eu de ligne à convertir.

La règle est donc sans exception : **`UtcDateTime` sur tout instant métier**, `immutable_datetime`
réservé aux `created_at` / `updated_at` que le framework possède. BR-08 (`laps.validated_at`) et
BR-11 (l'heure d'élimination) n'ont plus à jouer leur cast à pile ou face.

Un piège à connaître avant de toucher au cast : `set()` accepte **aussi une chaîne**, et ce n'est pas
de la complaisance. `EventUpdateRequest::prepareForValidation()` fusionne les deux contrôles de
l'écran en `"2026-09-12 13:00"` avant de remplir le modèle. Restreindre le cast à `DateTimeInterface`
a été essayé : trois tests de BR-03 sont passés au rouge, la date arrivant `null` en base.

## D-36 — La fenêtre d'un tour est semi-ouverte, la validation d'une boucle ne l'est pas

Arrêté le 2026-08-19 par BR-04. Le tour N couvre `[départ(N), départ(N+1))` : à 14:00:00 pile, le
tour courant est déjà le 2, pas le 1. C'est la lecture directe de « le tour N se termine au départ
du tour N + 1 », et elle garantit qu'aucun instant n'appartient à deux tours.

**Attention BR-09** : sa règle « validation à la seconde exacte de l'heure limite : acceptée » porte
sur la **boucle** (`heure serveur <= limite de la boucle`, inclusif), pas sur le tour courant. À
14:00:00, la boucle du tour 1 est encore validable alors que le tour courant est déjà le 2. Ce n'est
pas une contradiction, ce sont deux prédicats distincts : BR-09 devra chercher la boucle par son
tour, jamais par « le tour courant ». Un test de BR-04 nomme la borne pour que BR-09 la trouve.

## D-37 — Les tours sont ouverts par une tâche planifiée, mais le tour affiché est recalculé

Arrêté le 2026-08-19 par BR-04. Deux mécanismes distincts, et c'est délibéré.

**L'écriture est planifiée.** `App\Actions\OpenDueRounds` matérialise tous les tours dus, et
`race:open-rounds` l'appelle chaque minute (`routes/console.php`, premier planificateur du projet).
La matérialisation paresseuse à la lecture a été écartée, et c'était la vraie tentation : dès BR-08,
ouvrir un tour crée des boucles. Si une simple consultation de page ouvrait le tour N+1 **avant**
que la tâche de BR-11 ait éliminé les retardataires du tour N, on donnerait une boucle à des coureurs
qui doivent sortir. L'ordre élimination → ouverture est une règle de course : il ne peut pas dépendre
de qui a rafraîchi son écran.

**L'idempotence tient sur la base, pas sur la lecture.** La contrainte unique `(event_id, number)`
ferme la fenêtre — même raisonnement qu'en D-32 : la vérification qui précède l'écriture est toujours
périmée. `firstOrCreate` délègue à `createOrFirst`, qui attrape lui-même la violation d'unicité et
relit la ligne gagnante ; le `try/catch` est dans le framework, pas dans notre code. Le rattrapage
après une queue arrêtée tombe alors tout seul : les tours manquants sont créés **avec leurs horaires
calculés, jamais l'heure d'exécution**, ce que BR-11 exige littéralement.

**Écart assumé à `laravel:no-queries-in-loops`** : la boucle d'ouverture itère sur les tours
*manquants*, donc zéro ou un en régime normal. L'alternative en une passe (`insertOrIgnore`,
`upsert`) court-circuiterait le cast `UtcDateTime`, c'est-à-dire la correction de D-35 elle-même.

**La lecture, elle, ne touche pas la base du tout.** `ResolveCurrentRound` recalcule le tour courant
depuis l'heure serveur et rend un objet valeur `CurrentRound`, jamais un modèle. Quatre raisons : la
règle métier dit « déterminé à partir de l'heure serveur », pas « depuis une table » ; un affichage
lu en base serait en retard de 0 à 60 s à chaque changement de tour, sur le seul écran que le gérant
regarde quinze heures durant ; le calcul répond avant que le planificateur ait jamais tourné ; et
surtout un affichage lu en base **cacherait la panne** — planificateur mort, l'écran afficherait
sereinement le tour 12 pendant que la course en est au 15. Recalculé, l'écran dit la vérité et
l'écart avec `max(number)` devient le symptôme observable. Les deux entrées étant gelées en
`running`, ligne persistée et calcul ne peuvent pas diverger ; un test l'épingle.

**Pourquoi un objet valeur et pas un modèle `Round` non sauvegardé**, qui était la première écriture.
Un modèle porte `save()`, `update()` et ses relations : BR-08 voudra « le tour courant » pour y
rattacher des boucles, et un `$round->laps()->create(...)` sur un parent non sauvegardé produit soit
une clé nulle, soit une sauvegarde implicite — qui ouvrirait un tour dans le dos de l'élimination.
On avait fermé la porte de la lecture paresseuse et laissé la fenêtre ouverte. L'objet valeur rend
le `save()` **inexprimable** plutôt que déconseillé : le raisonnement que D-29 applique déjà aux
transitions. Bénéfice accessoire, la lecture ne coûte plus la requête que faisait le `firstOrNew`,
et il n'y a plus deux sources pour la même valeur.

**Reprise obligatoire par BR-11.** Sa tâche appellera l'élimination **puis** `OpenDueRounds`, et la
ligne `Schedule::command(OpenDueRoundsCommand::class)` devra être **retirée** de `routes/console.php` :
deux planificateurs indépendants qui écrivent des tours ne garantissent plus l'ordre. L'action est
l'unité réutilisable, la commande est jetable par conception.

**Ce qui arrive si le planificateur est mort la nuit de la course** : aucun tour matérialisé, donc
aucune boucle ouverte par BR-08 et aucune élimination par BR-11, sans que rien ne l'affiche. C'est
la panne silencieuse que BR-30 nomme. BR-04 y répond dans son budget par le rattrapage contigu et
par l'affichage recalculé qui rend l'écart visible ; l'alerte reste à BR-30.
