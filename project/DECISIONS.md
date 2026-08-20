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

**Révision du 2026-08-20 (D-43) : l'application n'envoie plus aucun mail.** La vérification
d'adresse et la réinitialisation de mot de passe sont supprimées, donc Mailpit n'intercepte plus
rien. Il reste dans `compose.yaml` pour ne pas avoir à le recâbler si un mail apparaît, mais un
Mailpit vide est désormais le comportement normal, pas le symptôme d'une panne.

**Seconde révision du même jour (D-45) : le mail est de retour, et Mailpit redevient
load-bearing.** Le lien d'inscription est le seul mail de l'application, et c'est le seul chemin
pour créer un compte : si le mail ne part pas, personne ne s'inscrit. Mailpit n'est donc plus un
filet mais l'outil de vérification du parcours en développement. `.env.example` porte encore
`MAIL_MAILER=log` et `MAIL_PORT=2525` alors que Mailpit écoute sur `1025` — un poste qui veut voir
le mail doit passer à `smtp` et `1025`.

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

**Révision du 2026-08-20 (D-45) : « notifications » ne couvre plus le mail d'inscription.**
Le propriétaire a demandé une inscription en deux temps, dont le premier temps est un lien envoyé
par mail. Ce mail est le **seul** que l'application envoie, et il appartient au parcours de création
de compte, pas à la course. Tout le reste de la liste tient : aucune alerte d'élimination, aucun
récapitulatif, aucune relance.

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

**Remplacée le 2026-08-20 par D-46.** Cette entrée ne décrit plus le produit : ni la police, ni
l'accent, ni l'élément signature décrits ci-dessous ne sont dans le code. Elle est conservée
comme journal de la charte précédente, et pour les règles que D-46 reprend telles quelles.

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

## D-38 — L'identité du coureur vit sur le compte, scindée en prénom et nom

Arrêté le 2026-08-19 avec le propriétaire du projet, par BR-05.

Le « Inclus » de BR-05 liste prénom, nom et email parmi les champs du formulaire d'inscription.
Les recopier dans `participants` aurait donné deux sources pour la même donnée : un coureur qui
corrige son profil aurait laissé son ancien nom sur son dossard. `participants` porte donc le
`user_id` et les seules données de course — téléphone, date de naissance, contact d'urgence,
remarques — et l'identité se lit sur `users`.

**`users.name` est scindé en `first_name` / `last_name`.** `RunnerCard`, écrit en BR-02, attend
déjà `firstName` et `lastName` séparés, et BR-25 imprimera le dossard à partir des deux. Un nom
complet ne se recoupe pas de façon fiable — un « Marie Claire Dupont » se scinde de trois façons.
Un accesseur `name` (avec `$appends`) rend la concaténation, ce qui laisse l'avatar, le menu
utilisateur et `getInitials()` intacts.

La migration `create_users_table` a été **modifiée sur place** plutôt que doublée d'une migration
de scission : rien n'est déployé, une reprise n'aurait eu aucune ligne à convertir. C'est le
raisonnement qui avait déjà fait supprimer une migration en BR-04 (D-35).

`profileRules()` renvoie désormais `first_name`, `last_name` et `email` ; l'écran de réglages et
l'écran d'inscription y puisent tous les deux, donc ils ne peuvent pas diverger.

## D-39 — La capacité se compte sur les inscriptions confirmées, la vraie course à la place est en BR-06

Arrêté le 2026-08-19 avec le propriétaire du projet, par BR-05.

BR-05 dit « quand le nombre d'inscriptions **confirmées** atteint le maximum, le formulaire
n'accepte plus de nouvelle inscription ». On applique la lettre : `Event::isFull()` compte les
`confirmed`, et `max_participants === null` signifie « pas de limite » (D-30), jamais zéro.

**Conséquence assumée, à connaître avant d'écrire BR-06.** Comme une inscription naît en
`pending`, le plafond n'est jamais atteint par une inscription. Le cas limite « deux inscriptions
simultanées sur la dernière place » de BR-05 ne peut donc pas se produire ici : la vraie course à
la dernière place se joue à la **confirmation**, et c'est BR-06 qui devra la fermer — vraisemblablement par une écriture conditionnelle, comme `AdvanceEventStatus` en D-32.

La seule concurrence réelle de BR-05 est la double inscription d'un même compte, et elle est
fermée par la base : `unique(['event_id', 'user_id'])`. Même doctrine qu'en D-31 et D-37 —
l'invariant est tenu par le schéma, pas par un `if` qui lit puis écrit. Le 403 que reçoit un
doublon passé par le contrôleur est une politesse ; l'index est la garantie, et un test l'attaque
directement pour que ça reste vrai.

`EventUpdateRequest` plafonne enfin `max_participants` par le bas au nombre de confirmés, la règle
que BR-03 n'avait pas pu écrire faute de table `participants`.

## D-40 — `RegistrationStatus` nomme les états, BR-06 possède les transitions

Arrêté le 2026-08-19 par BR-05. Même partage qu'entre `EventStatus` et `App\Services\EventLifecycle`
(D-29) : l'énumération porte les trois états persistés et leurs libellés, rien d'autre.

Qui a le droit de passer `pending → confirmed` ou `pending → cancelled`, et par quel canal, est le
sujet de BR-06. L'écrire ici aurait produit une règle sans consommateur, donc sans
test, donc fausse au premier usage. La permission `manage-participants` existe depuis BR-01 et
reste sans consommateur pour la même raison.

Côté participant, la Policy dit la seule règle que BR-05 possède : une inscription `confirmed`
n'est plus modifiable par son propriétaire.

## D-41 — Le routage de l'inscription est un singleton, comme l'événement

Arrêté le 2026-08-19 avec le propriétaire du projet, par BR-05.

Un compte porte au plus une inscription sur l'unique événement : l'URL n'a donc pas d'identifiant
à porter. `Route::singleton('registration', RegistrationController::class)->creatable()` produit
exactement les cinq routes voulues — `registration/create`, `POST registration`, `registration`,
`registration/edit`, `PUT registration` — sans segment `{registration}`.

`Route::resource()` avait été proposé d'abord. Il aurait généré `GET /registration/{registration}`,
donc un route model binding sur l'identifiant d'un autre coureur, à refuser ensuite par Policy.
Le singleton supprime la question au lieu de la traiter : le contrôleur résout la fiche depuis
`$request->user()->participant`, et il n'existe aucune URL désignant celle de quelqu'un d'autre.

`destroy` est exclu : un coureur ne s'annule pas lui-même, c'est le gérant qui annule (BR-06).

**Révision du 2026-08-20 (D-45) : `creatable()` tombe, le singleton se réduit à
`->only(['show', 'edit', 'update'])`.** L'inscription ne se crée plus depuis un compte connecté
mais dans le parcours public d'inscription ; il ne reste ici que la consultation et la correction.
Le raisonnement sur `resource()` et le binding tient inchangé pour les trois routes restantes.

La même déclaration a été passée sur l'événement, public comme géré : `Route::singleton('event', …)`
remplace les trois routes unitaires qui les déclaraient, aux mêmes noms de route.

## D-42 — Q-01 se ferme à moitié : les 7 écrans d'authentification passent en français

Arrêté le 2026-08-19 avec le propriétaire du projet, par BR-05.

Q-01 constatait qu'un coureur voit `/login` et `/register` en anglais avant d'atteindre le moindre
écran BR-02. BR-05 est le foyer naturel du parcours d'inscription : les 7 pages `pages/auth/*`
passent donc en français, via un nouveau groupe `lang/fr/auth.php` ajouté aux groupes partagés.

Ce groupe porte **aussi** les clés que Laravel et Fortify lisent eux-mêmes — `failed`, `password`,
`throttle` — donc un mot de passe erroné répond désormais en français, ce qu'aucune story n'avait
prévu.

Deux points techniques valent d'être connus :

- le titre de la page d'authentification passe de `defineOptions({ layout: … })` à
  `setLayoutProps()`. Une traduction lue à l'évaluation du module n'a pas encore de props de page
  à lire — le titre serait sorti en clé brute. `TwoFactorChallenge` utilisait déjà `setLayoutProps`.
- `t()` accepte désormais des remplacements (`t('registration.seats.counted', { count, max })`),
  ce dont le compteur de places avait besoin.

**Q-01 reste ouverte** pour les 3 pages `pages/settings/*` et les composants passkeys / 2FA, qui
n'ont toujours pas de propriétaire.

## D-43 — L'authentification se réduit à un mot de passe, les réglages à un profil

Arrêté le 2026-08-19 par le propriétaire du projet.

Le starter kit livrait quatre briques que le produit n'utilisera pas : 2FA (TOTP + codes de
secours), passkeys (WebAuthn), vérification d'adresse email, et une page « Security » portant le
changement de mot de passe. Toutes sont retirées.

Ce qui reste du parcours d'authentification : inscription et connexion par mot de passe.
`config/fortify.php` ne déclare plus que `registration()`, et `User` n'implémente plus
`PasskeyUser` ni les traits Fortify associés.

**Révision du 2026-08-20 (D-45) : `config/fortify.php` ne déclare plus aucune feature.**
L'inscription quitte Fortify pour un parcours en deux temps porté par l'application, et le mot de
passe devient un code d'inscription généré. Le reste de cette entrée ne bouge pas.

Trois conséquences valent d'être connues :

- **le changement de mot de passe connecté disparaît**, arbitré explicitement. C'était la seule
  fonctionnalité de « Security » qui n'était ni 2FA ni passkey, et elle n'a pas été déplacée dans
  le profil.
- **`email_verified_at` quitte la table `users`**, et le middleware `verified` quitte les routes.
  Les migrations `add_two_factor_columns_to_users_table` et `create_passkeys_table` sont supprimées
  plutôt que compensées par une migration de retrait : aucune base de production n'existe encore.
- **plus de section « Settings »** : `routes/settings.php` devient `routes/profile.php`, le
  contrôleur remonte de `App\Http\Controllers\Settings\` à `App\Http\Controllers\`, la page passe
  de `pages/settings/Profile.vue` à `pages/Profile.vue`, et le sous-layout à onglets
  (`layouts/settings/Layout.vue`) disparaît avec ses deux autres onglets.

Le routage suit D-41 : `Route::singleton('profile', …)->destroyable()->only([…])`, donc
`/profile/edit`, `PATCH /profile`, `DELETE /profile`.

**Q-01 se ferme entièrement.** Les écrans qui la maintenaient ouverte sont soit supprimés, soit
traduits : `pages/Profile.vue` et `DeleteUser` lisent un nouveau groupe `ui.profile`, et le menu
utilisateur passe par `ui.nav`. Plus un seul écran du produit n'est en anglais.

**Révision du 2026-08-20 : la réinitialisation par email part aussi.** L'entrée disait d'abord que
la récupération passerait par « mot de passe oublié » ; le propriétaire a ensuite retiré ce chemin.
Sont donc supprimés `Features::resetPasswords()`, l'action `ResetUserPassword`, les deux vues
Fortify (`ForgotPassword`, `ResetPassword`), le lien de la page de connexion, la table
`password_reset_tokens`, le broker `passwords` de `config/auth.php` et `config/fortify.php`, les
groupes de traduction `auth.forgot` / `auth.reset`, et `PasswordResetTest`.

**Conséquence assumée : un mot de passe perdu n'a plus aucune récupération en libre-service.** Ni
changement connecté, ni lien par email — il faut une intervention en base ou un nouveau compte. Le
produit tient sur un événement d'une soirée et ~40 participants, ce qui rend le coût acceptable,
mais c'est le seul point du parcours d'authentification qui n'a pas de porte de sortie.

Reste debout sans utilisateur : les routes Fortify `user/confirm-password` et la page
`auth/ConfirmPassword.vue`. Fortify les enregistre inconditionnellement, et plus aucune route
n'utilise `RequirePassword` depuis que « Security » a disparu.

## D-44 — Le thème vit dans la navbar, plus dans une page de réglages

Corollaire de D-43, arrêté le même jour.

La page « Appearance » et ses trois onglets (clair / sombre / système) sont remplacés par un bouton
unique dans l'en-tête de l'application (`AppSidebarHeader`), présent sur tous les écrans connectés.

Le bouton **bascule entre clair et sombre**, il ne propose plus « système ». `system` reste
néanmoins la valeur par défaut du composable et du cookie : un visiteur qui n'a jamais cliqué suit
sa préférence OS, et le premier clic fige un choix explicite. `useAppearance()` expose désormais
`toggleAppearance()`, qui lit `resolvedAppearance` — donc le premier clic depuis `system` inverse ce
que l'utilisateur voit, pas ce que le cookie contient.

Le rendu serveur ne change pas : `HandleAppearance` partage toujours le cookie `appearance` et
`app.blade.php` pose la classe `dark` avant le premier octet de JS.

## D-45 — L'inscription se fait par mail, et le mot de passe est un code généré

Arrêté le 2026-08-20 par le propriétaire du projet.

L'inscription en un formulaire (nom, prénom, email, mot de passe, confirmation) est remplacée par le
parcours des sites d'inscription à une course : **on saisit son adresse, on reçoit un lien, on
remplit son inscription, et on reçoit un code**. Ce code est le mot de passe du compte.

**Une seule inscription, pas deux.** BR-05 avait livré un second formulaire, atteignable une fois
connecté, pour la participation elle-même — téléphone, date de naissance, contact d'urgence,
remarques. Le coureur passait donc par trois moments : créer un compte, se connecter, s'inscrire.
Les deux formulaires fusionnent : le lien du mail ouvre **un** écran qui porte l'identité et la
participation, et sa validation crée `User` et `Participant` dans une transaction.

Ce que ça achète : plus aucun mot de passe choisi par le coureur, donc plus de mot de passe faible,
plus de mot de passe réutilisé, et une adresse email prouvée avant que le compte n'existe — sans
remettre en service la vérification d'adresse supprimée par D-43, puisque la preuve est *dans* le
parcours au lieu de le suivre.

**Le parcours ne quitte pas Fortify pour la connexion, seulement pour l'inscription.**
`config/fortify.php` ne déclare plus aucune feature ; `Features::registration()` disparaît avec
l'action `CreateNewUser`. La connexion reste Fortify, mais passe par
`Fortify::authenticateUsing()` — voir la normalisation plus bas.

### Les cinq étapes tiennent dans un seul singleton

Conformément à D-41, le parcours est déclaré en une ligne de ressource et non en liste de verbes :

```php
Route::singleton('account', AccountController::class)->creatable()->only([…])
```

Les cinq verbes du singleton portent exactement les cinq étapes : `create` (saisir l'adresse),
`store` (envoyer le lien), `edit` (le formulaire d'inscription, derrière signature), `update`
(créer le compte et l'inscription), `show` (afficher le code). Le préfixe est `account` et non
`register` pour ne pas cohabiter avec `registration`, qui garde la consultation et la correction
d'une inscription existante.

**La fenêtre d'inscription remonte sur la création de compte.** Un compte n'existe que pour courir,
donc les gardes qui protégeaient l'ancien formulaire — événement en statut `registration`, capacité
non atteinte — gardent maintenant l'étape 1. Hors fenêtre ou complet, l'écran d'adresse affiche un
refus et le compteur de places au lieu du champ, et `POST /account` refuse aussi côté serveur. Le
coureur apprend le refus avant d'avoir un compte, au lieu de le découvrir après.

Le refus voyage en erreur de validation sur un champ `event`, comme D-32 l'a établi, et non en 403.
`Event::acceptsRegistrations()` réunit les deux conditions ; `refuseOutsideRegistrationWindow()`
dans `RegistrationValidationRules` les applique aux deux requêtes qui en ont besoin — l'étape 1 et
l'étape finale. La seconde vérification n'est pas redondante : entre le clic sur le lien et l'envoi
du formulaire, les dernières places peuvent partir, et un test couvre ce cas.

**Ce que la fusion supprime.** `registration/create` et `POST registration` quittent le routage,
avec `RegistrationStoreRequest`, la page `registration/Create.vue`, le groupe de traduction
`registration.create` et le bouton « S'inscrire » de l'écran d'événement.
`ParticipantPolicy::create()` disparaît aussi, faute d'appelant : sa moitié « pas déjà inscrit »
est désormais portée par la contrainte d'unicité sur `users.email`, et sa moitié « inscriptions
ouvertes » par la fenêtre ci-dessus.

**Un compte sans inscription n'a plus d'écran pour en créer une.** Le cas n'existe aujourd'hui que
si BR-06 annule une inscription ; `registration.show` et `registration.edit` renvoient alors sur le
tableau de bord. C'est un trou connu, laissé à BR-06 — voir Q-03.

Tout le groupe est en `guest`. **Le nouveau coureur n'est pas connecté automatiquement** : il
termine sur son code et s'en sert immédiatement pour se connecter. C'est le seul moment où le
produit peut lui apprendre à quoi sert ce code, et le passage par le formulaire de connexion permet
à un gestionnaire de mots de passe de l'enregistrer.

### Aucune table de jetons

Le lien est une `URL::temporarySignedRoute` de 48 heures portant l'adresse en paramètre, comme la
vérification d'adresse de Laravel. Pas de table `pending_registrations`, pas de purge, pas de
modèle. Le rejeu est fermé par la contrainte d'unicité sur `users.email`, pas par un jeton consommé.

`edit` vérifie la signature **dans le contrôleur** (`hasValidSignature()`) au lieu du middleware
`signed`. Le middleware répond 403, donc la page d'erreur Symfony en anglais que Q-02 décrit ;
le contrôleur renvoie sur l'étape 1 avec un message français. Un lien périmé est le cas normal
d'un mail vieux de trois jours, pas un abus.

Entre `edit` et `update`, l'adresse voyage **en session**, pas dans le formulaire : `update` n'a
aucun champ email, donc aucune requête ne peut créer un compte sur une adresse non prouvée.

### Le code : 12 caractères, sans ambiguïté, normalisé à la lecture

`App\Support\AccessCode` tire 12 caractères dans un alphabet de 32 (`ABCDEFGHJKLMNPQRSTUVWXYZ`
plus `23456789` — ni `I`, ni `O`, ni `0`, ni `1`) et les groupe par quatre : `ABCD-EFGH-JKLM`.
Soit 60 bits, face à un limiteur de 5 tentatives par minute et par couple adresse/IP.

**`AccessCode::normalise()` est la raison du `Fortify::authenticateUsing()`.** Un code recopié
depuis une capture d'écran arrive en minuscules, sans tirets, ou les deux ; un `Auth::attempt` brut
le refuserait. La normalisation remet en majuscules, retire tout ce qui n'est pas dans l'alphabet et
regroupe par quatre, des deux côtés — à la connexion et dans `ProfileDeleteRequest`, qui demande le
code pour supprimer le compte.

Le code n'est **jamais renvoyé** : `users.password` n'en contient que le hash, et l'écran qui
l'affiche lit un flash de session, donc un rechargement le perd. Combiné à D-43, qui a retiré la
réinitialisation, **un code perdu est un compte perdu** — le coureur doit se réinscrire avec une
autre adresse, ou le propriétaire intervenir en base. C'est la conséquence assumée de la demande
« un code qu'il ne doit pas perdre » ; l'envoyer aussi par mail est un changement d'une ligne si
l'arbitrage change.

### Le mail est une Notification, pas un Mailable

Premier envoi du projet, donc premier arbitrage. C'est `Notification::route('mail', $email)` avec
un `AnonymousNotifiable` : le destinataire n'a pas encore de compte, et c'est exactement la forme
que Laravel utilise pour ses propres `VerifyEmail` et `ResetPassword`. Le `MailMessage` évite
d'écrire une vue Blade, et le test assure sur `actionUrl` plutôt que sur du HTML rendu.

La copie vit dans `lang/fr/mail.php`, hors des groupes partagés à Inertia. `lang/fr.json` traduit
les deux chaînes que le gabarit du framework pose lui-même (« All rights reserved. » et le
sous-texte du bouton), sans quoi un mail français se termine en anglais.

## D-46 — Direction artistique « Tableau des départs », qui remplace « Corral »

Arrêtée le 2026-08-20 par le propriétaire du projet, après maquettage de trois chartes complètes
(`project/design/*.html`). Elle remplace D-24 en totalité, et le code était livré avant que cette
entrée n'existe — c'est la correction de cet écart.

**La métaphore est le panneau des départs d'une gare.** Le coureur n'est plus une carte mais une
**latte** : `RunnerSlat` et `SlatCell` remplacent `RunnerCard`, empilées par l'utilitaire `slats`
à 6 px, et un changement d'état s'annonce par `animate-flip` — un `scaleY` de 130 ms, le seul
mouvement du produit. D-25 tient : pas d'horloge cliente, pas de barre de progression, et le filet
de chargement d'Inertia reste la seule autre animation. `FestoonDivider` et la note d'anniversaire
en guirlande sont supprimés.

**Deux familles aux rôles séparés, plus une seule à trois voix.** Instrument Sans porte le texte,
Martian Mono porte les chiffres en `tabular-nums` — c'est le monospace qui aligne les colonnes d'un
tableau de départs, ce qu'un axe de largeur ne faisait pas. Les deux sont auto-hébergées dans
`resources/fonts/` ; Archivo est retirée du dépôt. Aucune requête tierce, comme en D-24.

**L'échelle typographique est nommée, en cinq crans** : `readout` (2,75 rem, le nombre qu'on lit de
loin), `figure`, `title`, `data`, `label` (0,625 rem, capitales espacées). Une taille ne se choisit
plus à l'usage, elle se nomme.

**L'accent outremer disparaît, et c'est le changement le plus lourd.** `--primary` est désormais
l'encre presque noire, donc la couleur ne sert plus **qu'aux quatre statuts**, chacun en triplet
`ink / surface / foreground` — vert « en course », rouge « éliminé », ardoise « abandon », ambre
« terminé ». Un écran de course est noir et blanc partout où aucun statut ne parle. Ce qui était le
compromis de D-24 — un accent choisi hors du jeu sémantique pour ne pas le percuter — devient sans
objet : il n'y a plus d'accent à placer.

**Ce qui ne bouge pas de D-24** : notation oklch ; palette déclarée en trois endroits (`:root`,
`.dark`, et le `<style>` anti-flash de `app.blade.php`) avec un test qui affirme leur concordance ;
AA vérifié par `PaletteContrastTest` et non par la revue ; aucune librairie de composants ajoutée ;
aucun graphique (D-16) ; plancher tactile de 44 px, porté par la variante `touch` d'`ActionButton`.

**Un écart à surveiller, relevé en écrivant cette entrée.** D-24 exigeait 72 px pour la validation
d'une boucle ; la variante `validate` d'`ActionButton` mesure aujourd'hui 50 px de haut sur 90 px
de large. Le geste le plus répété de la nuit a donc perdu un tiers de sa hauteur, et aucun test ne
le garde — les cibles n'ont jamais eu de token, leur intention vivait dans les variantes. À
trancher dans BR-09 ou BR-13, qui sont les stories qui posent le bouton en situation réelle : soit
la variante remonte, soit la règle des 72 px tombe explicitement.

## D-47 — Élagage du backlog : quatre stories abandonnées, cinq redimensionnées

Arrêté le 2026-08-20 avec le propriétaire du projet, après revue du backlog restant au regard de
D-20 — un événement, une nuit, quarante coureurs, puis l'arrêt.

Le critère appliqué n'est pas « est-ce utile » mais « est-ce que ça vaut ses heures pour un usage
unique, sachant qu'un outil gratuit ou une feuille de papier fait parfois le travail ». Quatre
stories ne passent pas ce filtre, cinq en sortent redimensionnées. **42 points quittent le
périmètre**, sur les 185 qui restaient : 32 points de stories abandonnées, 13 points de réductions,
moins les 3 points que BR-23 gagne en absorbant BR-21.

**Abandonnées.** Leur fichier reste dans `stories/`, au statut `⛔ Abandonné`, et porte la raison :
un backlog qui perd une story sans dire pourquoi la voit revenir.

- **BR-22 — Galerie photos (8 pts).** La seule story qu'un outil gratuit fait mieux : le dépôt
  était réservé au gérant, donc les coureurs ne pouvaient pas contribuer, alors qu'un album partagé
  reçoit les photos de tout le monde. Elle emportait des vignettes, des conversions Horizon et du
  volume de stockage objet, la semaine où le worker sert à éliminer les coureurs.
- **BR-19 — Parcours GPX et carte (8 pts).** Leaflet, tuiles tierces, analyse XML d'un fichier
  fourni de l'extérieur et extraction de dénivelé en tâche de fond, pour une boucle unique et
  balisée que personne ne consulte en courant. Le GPX devient un document de BR-18.
- **BR-25 — Dossard imprimable (8 pts).** Quarante impressions sur autant d'imprimantes
  familiales, avec un fond calibré pour le papier : le produit maîtrisait un rendu qu'il ne voit
  jamais. D-15 excluait déjà l'impression de listes.
- **BR-21 — Statistiques (8 pts).** Doublon de BR-23, qui annonçait déjà les mêmes chiffres
  collectifs. Deux pages d'après-course pour un événement qui sert une fois.

**Redimensionnées.**

- **BR-23 : 5 → 8 pts.** Absorbe les indicateurs et le tableau par tour de BR-21, et devient la
  seule page d'après-course. Ses dépendances tombent à BR-20 seul.
- **BR-18 : 8 → 4 pts.** La visibilité par document et la date de publication sont retirées : tout
  le monde ici est inscrit, il n'y a personne à qui cacher le règlement. Reçoit en échange le GPX
  et une capture du tracé, comme deux fichiers ordinaires. La route de téléchargement contrôlée
  reste, non plus pour cacher un document mais pour que le bucket n'ait aucun accès anonyme (D-08).
- **BR-15 : 5 → 2 pts.** Chaque validation recharge déjà la page par Inertia ; ce qui manquait
  n'est qu'un `router.reload()` périodique, suspendu sur onglet masqué.
- **BR-16 : 5 → 2 pts.** Le détail d'un coureur se déplie dans le tableau de BR-14 au lieu d'être
  un écran et une route de plus. Une main occupée à 4 h du matin ne quitte pas la liste.
- **BR-17 : 5 → 2 pts.** Le briefing se saisit en Markdown dans un `textarea`. Il ne changera pas
  dix fois ; le nettoyage à l'entrée, lui, reste non négociable.

**Ce que cet élagage ne touche pas.** Le moteur de course (BR-06 à BR-14) est intégral, **BR-12**
compris — le filet contre l'appui perdu est la story la moins visible et la plus rentable de la
nuit. BR-29 garde sa restauration réellement exécutée et BR-30 ses trois processus : sans worker ni
planificateur, les éliminations ne tombent pas et rien ne le signale.

**L'epic 7 n'est pas arbitré ici.** D-19 reconnaît qu'une plateforme managée ferait presque
disparaître BR-26 et réduirait BR-29 à une vérification, soit environ 20 points de moins pour 30 à
60 € — le meilleur rapport du backlog. Le choix « payer moins et faire le travail » reste celui du
propriétaire et n'est pas rouvert par cette entrée ; il est simplement chiffré une fois de plus,
pour qu'il soit tenu en connaissance de cause plutôt que par inertie.

## D-48 — Le cycle de vie de l'inscription est un graphe, et la confirmation est le point de bascule

Arrêté le 2026-08-20 par BR-06, avec le propriétaire du projet pour la branche de Q-03.

D-40 avait laissé les transitions à cette story. Elles vivent dans
`App\Services\RegistrationLifecycle`, jamais sur `App\Enums\RegistrationStatus` — même interdiction
expresse que pour `EventStatus`.

**La forme canonique du State s'applique ici, pas la variante linéaire de D-29.** D-29 justifiait
son `advance()` unique par l'absence de branchement : la chaîne de l'événement n'en a aucun. Le
graphe de l'inscription en a trois — `pending → confirmed`, `pending → cancelled`,
`confirmed → cancelled`, `cancelled → pending`, et `cancelled → confirmed` interdit. Chaque état
porte donc `confirm()`, `cancel()` et `reopen()`, et les cinq transitions illégales lèvent. Ce que
ça achète est le même bénéfice qu'en D-29, obtenu par l'autre bout : un appelant ne peut pas
demander une confirmation directe depuis `cancelled`, la méthode n'a pas d'autre corps qu'un
`throw`.

`allowedTransitions()` s'ajoute aux trois méthodes parce que **deux appelants réels** doivent
énumérer ce qu'une ligne permet : la Form Request qui refuse en français, et les boutons du gérant.
Trois prédicats booléens auraient été la deuxième déclaration du graphe que D-29 interdit. Le risque
résiduel — la liste et les méthodes qui divergent dans un même état — n'est pas promis mais
**vérifié** : `it_agrees_with_its_own_allowed_transitions` confronte les deux lectures sur les neuf
couples état/transition. `RegistrationTransition::apply()` porte un `match`, mais il ne sait pas ce
qui est légal : il traduit un nom d'action HTTP en appel de méthode.

### La course à la dernière place se joue ici, et le verrou n'est pas un confort

D-39 l'avait annoncé : une inscription naît `pending`, donc le plafond n'est jamais atteint par une
inscription. `TransitionRegistration` prend un verrou de ligne sur l'événement, compte les confirmés
dessous, puis écrit **conditionnellement** sur le statut quitté — patron d'`AdvanceEventStatus`
(D-32). Zéro ligne touchée signifie que quelqu'un est passé avant.

Les deux alternatives sont fermées par l'outillage, pas par le goût :

- **réessayer sur violation d'unicité** — `NoTryCatchRule` interdit le `try/catch` que ça exige ;
- **une sous-requête conditionnelle** — MySQL refuse une sous-requête sur la table cible d'un
  `UPDATE` (erreur 1093), et le compteur de capacité n'est pas sur la ligne écrite.

Le verrou porte sur une table d'une seule ligne, pour une quarantaine de confirmations en une
soirée. **Ce qu'il ne fait pas** : garantir en base que les confirmés ne dépassent jamais le
plafond. Aucune contrainte SQL ne l'exprime ici ; la garantie vaut la transaction, pas plus, et
c'est dit plutôt que promis.

**Le double clic voit deux choses selon le cas, et les deux canaux restent d'accord** : deux clics
séquentiels sont refusés en 422 par la Form Request, deux clics concurrents par l'écriture
conditionnelle en 409. Dans les deux cas rien n'est écrit deux fois. Le 409 reste sur le chemin
d'onglet périmé que Q-02 couvre déjà pour l'événement — BR-06 ne l'aggrave pas.

**Écart assumé avec BR-05 :** la confirmation ne consulte pas `allowsRegistration()`. BR-06 exige
qu'un retardataire soit confirmable course lancée, alors que le parcours d'inscription se ferme dès
`running`. Deux chemins, deux règles, volontairement — un test tient chacune, pour que
l'incohérence apparente ne se fasse pas « corriger ».

### Première ressource plurielle, et deux groupes de permissions frères

L'argument de D-31 et D-41 — « pas d'identifiant à porter » — ne se transpose pas côté gérant : il
regarde quarante fiches, l'identifiant *est* l'information. `Route::resource('registrations', …)`
avec `->parameters(['registrations' => 'participant'])` garde le style déclaratif et le nom du
modèle dans l'URL.

Le groupe `manage` se scinde en **deux groupes frères** plutôt qu'imbriqués : le hub et l'événement
sous `manage-event`, les inscriptions sous `manage-participants`. Imbriquer aurait exigé les deux
capacités et laissé la distinction de D-28 invisible aux tests. `manage-participants` a enfin son
premier consommateur depuis BR-01, et un test le voit avec un utilisateur ne portant que lui.

`ParticipantPolicy` gagne `manage()` et ne touche pas à `update()`. Les deux règles sont des
conditions contradictoires sur le même verbe — « le propriétaire, tant que c'est en attente » contre
« n'importe qui d'habilité, à tout moment ». Fusionnées dans un `||`, la garantie « une inscription
confirmée est en lecture seule pour son coureur » serait devenue la branche gauche d'une
alternative, et le `canEdit` de l'écran coureur aurait dépendu d'une permission qui ne le concerne
pas. Le `status === Pending` de la Policy laisse place à `isEditableByRunner()` : c'était le dernier
test de statut hors des états.

### Q-03 se ferme sur la branche 1 plus la branche 3, sans ressusciter d'écran

Le coureur voit son inscription annulée **en lecture seule**, avec un message qui dit ce qui s'est
passé ; il ne regagne aucun pouvoir de création. Le **gérant** peut la remettre en `pending`, et
rien de ce que le coureur avait saisi n'est perdu dans l'aller-retour — un test le vérifie sur le
téléphone et le contact d'urgence.

D-45 tient donc intégralement : le second formulaire qu'il avait supprimé ne revient pas. Ce que la
fusion avait laissé ouvert était un compte sans écran, pas un compte sans données.

Défaut de copie corrigé au passage : l'annulation étant jusqu'ici inatteignable, une inscription
annulée tombait sur `registration.show.locked`, qui annonce « ton inscription est confirmée ».
L'écran a maintenant trois branches explicites.

### Le filtre voyage dans l'URL

Trois raisons, par poids décroissant : BR-14 exige un filtrage serveur pour le tableau des coureurs,
donc un filtre client ici tiendrait deux idiomes pour un geste ; une transition redirige, donc le
gérant qui vide la vue « en attente » y reste sans une ligne de code ; les compteurs sont des
vérités serveur qu'un filtre client dédoublerait. Un statut trafiqué **retombe sur la liste
complète** au lieu d'un 422 : ça arrive sur une navigation ordinaire, où une erreur de validation
laisserait le gérant sur la page d'erreur non traduite de Q-02.

`RegistrationSlat` est un frère de `RunnerSlat`, pas sa généralisation : D-26 sépare les deux jeux
de statut, et les deux lattes évoluent en sens opposés — l'une vers le détail des boucles et
l'animation de validation, l'autre vers les transitions. Ce qu'elles partagent vraiment est déjà
factorisé : l'utilitaire `slats`, la cellule, l'échelle typographique.

**Deux défauts d'accessibilité évités, à ne pas réintroduire.** Le lien n'entoure que le bloc
d'identité : une latte entière en `<Link>` avec un bouton de transition dedans est du HTML invalide
et un piège au tap comme au lecteur d'écran. Et le bouton de sortie du dialogue d'annulation
s'appelle « ne rien changer » — deux « Annuler » de sens opposés dans la même boîte se cliquent de
travers à 4 h du matin. Chaque formulaire de ligne porte son propre sac d'erreurs, sans quoi un
refus sur une ligne s'affiche sous les quarante boutons.

`BoardFilter` ne connaît ni `RegistrationStatus` ni `RunnerStatus` : il reçoit des options déjà
construites par la page. BR-14 aura besoin de la même forme à quatre vues.
