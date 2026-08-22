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

## D-49 — Le dossard : verrou plutôt que réessai, unicité en base, formatage en PHP

Arrêté le 2026-08-20 par BR-07.

**Le numéro voyage dans le même `UPDATE` que le statut.** C'est ce qui rend un second dossard
impossible sans un second changement de statut : pas une vérification, l'atomicité de l'écriture.
Il est lu sous le verrou de ligne que `TransitionRegistration` tient déjà (D-48), donc deux
confirmations concurrentes ne peuvent pas tirer le même numéro.

**Les deux autres stratégies sont fermées par l'outillage, pas par le goût :**

- `max + 1` puis **réessai sur violation d'unicité** — `NoTryCatchRule` interdit le `try/catch`
  que ça exige. La règle n'admet pas d'exception, et c'est heureux : le réessai aurait masqué la
  vraie cause d'une collision.
- **insertion conditionnelle** — MySQL refuse une sous-requête portant sur la table cible d'un
  `UPDATE` (erreur 1093).

`unique(['event_id', 'bib_number'])`, sur le patron de `unique(['event_id','number'])` de `rounds`,
reste **la** garantie — BR-07 l'exige littéralement, « garanti en base et pas seulement dans le
code ». Un test l'attaque directement par une écriture forcée, au lieu de supposer qu'elle tient.

**La colonne est nullable, et c'est la sémantique, pas une tolérance.** Une inscription est numérotée
à la confirmation et jamais avant ; MySQL considère les `NULL` comme distincts, donc les vingt-huit
inscriptions en attente cohabitent sous l'index unique.

### `max + 1` égale « le plus petit non utilisé », mais seulement parce qu'on ne supprime jamais

BR-07 dit « le plus petit entier positif non encore utilisé » en règle métier et « le premier libre
au-delà du plus grand attribué » en critère d'acceptation. Les deux lectures coïncident **parce que
BR-06 interdit la suppression** : une inscription annulée garde sa ligne et son numéro, donc le plus
grand attribué est aussi le plus grand utilisé.

C'est un équilibre à connaître : une story qui supprimerait un participant casserait la règle métier
sans faire rougir un test. Le jour où ça arrive, c'est ici qu'il faut revenir.

### Le formatage vit en PHP, contrairement à la carte de statuts

Le raisonnement de D-26 — Tailwind ne voit pas une classe venue du PHP, une icône Lucide est un
composant Vue — **ne s'applique pas** à un remplissage de zéros. TypeScript ne porterait ici aucun
fait propre : ce serait une seconde déclaration gratuite, donc un second test de parité à écrire
pour rien.

`App\Support\BibNumber::label()` est donc l'unique déclaration, sur le patron d'`App\Support\AccessCode`,
et les Resources exposent **les deux** : `bib_number` pour trier et comparer, `bib_label` pour
afficher. Le cas limite « au-delà de la centaine » est gratuit. La planche de galerie perd son
`padStart` codé en dur, qui était le seul appelant concurrent.

Écarté au passage : un accessor sur `Participant` (`no-fat-models` — c'est de la présentation) et un
value object avec cast Eloquent (l'invariant est déjà tenu par la colonne et l'index ; le seul fait
partagé est le format, donc une fonction pure suffit).

### L'invariant « confirmé ⇒ numéroté » est celui de l'action, pas du schéma

`ParticipantFactory::confirmed()` numérote ce qu'elle crée, sinon la trentaine de tests qui
fabriquent un confirmé le feraient sans numéro et l'invariant serait faux dès le premier. L'état
`withBib()` reste prioritaire — la garde `bib_number !== null` est là pour ça, et deux tests
combinent les deux. `ParticipantSeeder` n'a rien à changer : il passe déjà par `confirmed()`, donc
la base de développement reste cohérente.

Ce que ça ne garantit pas : rien n'empêche une écriture à la main de laisser un confirmé sans
numéro. La colonne reste nullable, l'invariant vaut l'action. C'est dit plutôt que promis.

### Pas de test de concurrence réelle, et c'est délibéré

PHPUnit avec `RefreshDatabase` tourne sur une connexion unique, dans une transaction : un test à deux
connexions serait soit sauté, soit vert sans avoir rien exercé — le pire des cas, un garde-fou qui
rassure sans mesurer. Les deux mécanismes sont testés séparément et pour de vrai : l'écriture
conditionnelle par `it_refuses_a_registration_someone_else_already_moved`, l'index unique par
`it_lets_the_database_refuse_a_duplicate_number`.

## D-50 — Le formulaire d'inscription se remplit en étapes, sans rien stocker entre elles

Arrêté le 2026-08-20 par le propriétaire du projet. Porté par la reprise **R-04**.

D-45 a fusionné les deux formulaires en un seul écran, `auth/register/Complete.vue`, derrière le
lien signé. Le résultat empile quatre groupes de champs — identité, coureur, contact d'urgence,
remarques — soit une dizaine de saisies obligatoires d'affilée. Sur le téléphone où l'inscription se
fera réellement, le coureur voit une page qui n'en finit pas avant d'avoir tapé son prénom.

L'écran devient donc un formulaire en **quatre étapes** :

1. **Identité** — prénom, nom, l'adresse déjà prouvée affichée sans être modifiable.
2. **Coureur** — téléphone, date de naissance, numéro PPS (BR-34).
3. **Contact d'urgence** — nom et téléphone.
4. **Remarques** — le champ libre, puis la validation.

### Les étapes sont une mise en scène, pas un état

Elles vivent **côté client, pour une seule soumission**. D-45 avait fermé la porte à toute table
d'état intermédiaire — pas de `pending_registrations`, pas de purge, le lien signé porte tout. Un
POST par étape rouvrirait exactement cette porte : il faudrait stocker une inscription à moitié
saisie, décider de sa durée de vie et la nettoyer. `account.update` reçoit donc la totalité en une
fois et crée `User` et `Participant` dans la même transaction qu'aujourd'hui. Rien ne change côté
serveur, ni route, ni Form Request, ni transaction.

### Ce qui est réellement le travail : ramener le coureur sur son erreur

La validation serveur reste globale, et elle a le dernier mot. Une erreur 422 sur `birth_date`
pendant que le coureur est à l'étape 4 doit le **ramener à l'étape 2**, sur le champ fautif, focus
posé. Sans ça il voit une soumission refusée et aucune erreur : le message existe, il est simplement
sur un écran qu'il ne regarde pas. C'est le seul endroit où cette reprise peut échouer
silencieusement, et c'est là que les tests comptent.

L'erreur `event` — fenêtre fermée, dernières places parties entre le clic sur le lien et la
soumission (D-45) — n'appartient à aucune étape. Elle s'affiche là où le coureur se trouve et coupe
le parcours. Le compteur de places reste visible à toutes les étapes, pas seulement à la première.

Le `required` natif garde le passage à l'étape suivante : il évite l'aller-retour serveur sur un
champ vide, sans jamais remplacer la validation serveur, qui reste la seule autorité.

### La correction reste une page unique

`registration/Edit.vue` ne bouge pas. Les étapes servent la première saisie, où la longueur
intimide ; un coureur qui vient corriger son numéro de téléphone n'a pas à traverser quatre écrans
pour ça. `RegistrationFields` reste le composant partagé par les deux écrans — il rend ses groupes
d'affilée dans la correction, un par étape dans la saisie initiale.

C'est un écart assumé : deux présentations pour les mêmes champs. L'alternative — un parcours unique
partout — punissait le geste le plus fréquent pour uniformiser le plus rare.

### Ce que l'écran doit dire à chaque étape

La position (« 2 sur 4 »), un retour arrière qui ne perd rien de ce qui est déjà saisi, le focus
déplacé en tête d'étape au changement pour que le lecteur d'écran suive, et des cibles tactiles au
plancher de 44 px (D-46). Une étape qui avance sans annoncer où l'on en est est une page longue
déguisée.

## D-51 — Le briefing stocke du Markdown nettoyé, et le rend en HTML à chaque affichage

Arrêté le 2026-08-20 par BR-17, avec le propriétaire du projet pour les deux arbitrages d'écran.

Le briefing est le seul endroit du produit où du texte mis en forme entre par un formulaire et
ressort dans une page. D-47 avait réduit la story à 2 points en tranchant « Markdown dans un
`textarea` », en maintenant que « le nettoyage à l'entrée reste non négociable ». Voici comment.

### La colonne stocke la source, jamais le rendu

`events.briefing` porte le Markdown, et `Str::markdown()` produit le HTML à chaque affichage. Deux
raisons, et la seconde est la vraie :

- le gérant réédite le texte qu'il a tapé. Une colonne de HTML aurait exigé un dé-rendu
  HTML → Markdown, ou un `textarea` où le gérant relit `<h1>Consignes</h1>` ;
- une option de sécurité qui change demain couvre alors **tout** l'existant. Du HTML stocké est
  figé au jour de son écriture : plus filtrable, et plus rien à faire sans migration de données.

Pas de colonne `briefing_html`, pas de cache : une ligne, une page, quarante lecteurs.

### Deux barrières, deux rôles distincts

`Briefing::clean()` à l'entrée garantit ce que le critère d'acceptation demande littéralement — la
**colonne** ne contient aucune balise. `html_input=strip` et `allow_unsafe_links=false` au rendu
garantissent qu'aucun HTML ni URL exécutable ne **sort**, y compris sur les variantes que le
nettoyage d'entrée ne prétend pas voir (`JaVaScRiPt:` en casse mélangée, entités déjà encodées).
Le nettoyage rend `strip` sans effet sur les contenus neufs : c'est voulu, la barrière de rendu est
le filet, pas le mécanisme.

Trois détails du nettoyage sont load-bearing, chacun tenu par un test :

- l'élément à contenu brut part **avec son corps**. `strip_tags` seul rend `alert(1)` en texte
  visible — la balise disparaît, le script reste lisible dans la page ;
- la balise **non refermée** est couverte, sinon `<script>alert(1)` sans fermeture traverse ;
- une balise n'est reconnue que sur `<` suivi d'une **lettre**, donc `3 < 5`, `x <= 10` et `j <3`
  reviennent octet pour octet. Et les entités ne sont **jamais** décodées : décoder
  `&lt;script&gt;` refabriquerait la balise qu'on vient d'interdire.

Aucun paquet n'a été ajouté : `league/commonmark` arrive avec le framework, et le HTML autorisé est
l'ensemble vide — HTMLPurifier n'aurait rien à autoriser.

### Le nettoyage vit dans le Form Request, pas dans un cast

Un cast aurait couvert tous les chemins d'écriture, seeder et `tinker` compris. Il aurait aussi
nettoyé **après** la validation, donc `required` et `max:` auraient compté un contenu que personne
ne stocke. Dans `prepareForValidation()`, un envoi qui n'était *que* du script est refusé au lieu
d'être stocké vide — c'est cette règle, et non le repli d'affichage, qui empêche une page blanche
côté gérant. Le repli `Briefing::orDefault()` couvre l'autre moitié : un événement créé par l'écran
de configuration n'a pas de briefing du tout.

Le contenu initial vit donc dans `lang/fr/briefing.php`, lu par `EventFactory` — le seeder le livre
sans une ligne de changement — et comme repli. Ce groupe n'est **pas** partagé au front : c'est du
contenu résolu côté serveur, qui arrive par les props.

### La colonne reste hors `#[Fillable]`

Le jeu fillable de l'événement est le formulaire de configuration : `EventUpdateRequest::rules()` le
reflète, `EVENT_FIELDS` le reflète côté TypeScript, et `EventLifecycleTest` assère
`getFillable() === frozenAttributes()`. Le briefing répond à une **autre** permission ; l'y ajouter
aurait ouvert un chemin d'écriture par `fill()` depuis l'écran de configuration, et fait grossir la
liste des champs gelés d'un champ que ce formulaire ne rend pas. Il s'écrit donc par affectation
directe, comme `status`.

### Deux écrans, et le gel à « terminé »

Deux arbitrages du propriétaire, contre la lettre du critère d'acceptation (« le gérant sur la page
briefing ») :

- **deux écrans**, `/briefing` en lecture et `/manage/briefing` en édition, parce que le produit
  sépare partout la consultation de la gestion et qu'un écran mixte aurait été le seul du lot ;
- **le briefing se gèle quand l'événement est `finished`**, comme la configuration. `manage-event`
  et `manage-documents` obéissent alors à la même règle de fin de course, et l'écran d'édition rend
  le briefing au lieu du formulaire.

`manage-documents` a enfin un consommateur : elle ferme la route par middleware et la requête par
`EventPolicy::updateBriefing`, exactement comme `manage-event` sur la configuration. La lecture, en
revanche, n'a demandé aucune ligne d'autorisation : `EventPolicy::view` donnait déjà « refusé en
`draft`, visible au gérant ».

### Ce que le HTML rendu emporte, et ce qu'on ne bride pas

`Str::markdown` utilise le convertisseur GitHub : tables, autolinks et cases à cocher passent en
plus des « titres, listes, gras, émoji » de la story. Surensemble inoffensif, non bridé. Le rendu
est injecté par un composant unique, pour que l'unique `v-html` du front ait un seul point à
relire, et il est stylé par un bloc `@utility briefing` dans la feuille de style — un plugin
`@tailwindcss/typography` et sa configuration de thème ne se paient pas pour une page, et le bloc
parle les tokens du projet.

## D-52 — Les documents sont un modèle éditorial et une URL signée, sans route de téléchargement

Arrêté le 2026-08-20 par BR-18, avec le propriétaire du projet.

### Un modèle `Document`, malgré D-09

D-09 interdit « une table de fichiers maison ». La table `documents` n'en est pas une : elle porte
un titre et une description, jamais un chemin, une taille ni un type. Le fichier reste entièrement
à Media Library, dans une collection `file` en `singleFile()`.

Deux raisons ont fait renoncer à tout poser sur `Event` via `name` et `custom_properties`, qui était
l'option la plus économe :

- **le titre est éditorial**. « Règlement de la course » se lit dans une liste ; `reglement-v3-final.pdf`
  non. Les deux doivent coexister, et `usingName()` aurait fait porter au média un nom d'affichage
  en plus de son nom de fichier, ce qui brouille les deux ;
- **l'événement est un singleton**. Accrocher tous les médias dessus donne un sac plat, sans
  propriétaire par document, où la suppression d'un fichier vise un `Media` par identifiant plutôt
  qu'une ligne à soi.

### Pas de route de téléchargement : une URL signée, valable sept jours

La story écrivait « le téléchargement passe par une route contrôlée ». Elle ne l'est plus. Le média
signe son URL — `App\Models\Media` étend celui de Spatie et expose `temporary_url` et `download_url`,
le second ajoutant le `ResponseContentDisposition` qui fait enregistrer le fichier sous son nom
d'origine. L'URL n'est fabriquée que dans un contrôleur ayant déjà passé la policy : en `draft`, un
participant n'en obtient aucune.

**L'écart à D-08 est réel et assumé** : une URL présignée est un accès anonyme, borné dans le temps
et non devinable, là où D-08 demandait « aucun accès anonyme au bucket ». Ce qui l'achète, c'est que
l'application ne relaie plus d'octets qu'elle n'a pas besoin de toucher, et que la durée retenue —
sept jours, le plafond de SigV4 — couvre largement une page laissée ouverte.

**La conséquence à connaître avant de déboguer un lien mort.** SigV4 signe l'en-tête `Host`. Le
conteneur et le navigateur doivent donc désigner le stockage par le même `hôte:port`, sinon toute
signature est rejetée — RustFS répond alors `InvalidAccessKeyId`, ce qui envoie chercher un problème
de clés qui n'existe pas. En développement, cela demande deux choses : RustFS écoute désormais, dans
le réseau Docker, sur le port qu'il publie (`FORWARD_RUSTFS_PORT` pilote les deux), et **chaque poste
doit résoudre `rustfs` vers `127.0.0.1`**.

Cette entrée va dans le fichier hosts du **système qui fait tourner le navigateur** — sous WSL, celui
de Windows (`C:\Windows\System32\drivers\etc\hosts`), pas le `/etc/hosts` de la distribution.
WSL en hérite de toute façon : son `resolv.conf` pointe sur le proxy DNS de Windows, qui lit ce même
fichier. Le poste du propriétaire la portait déjà, posée par un projet antérieur, ce qui a fait
passer la contrainte inaperçue à la livraison.

En production, un endpoint public unique rend la question sans objet.

### Une règle de validation maison plutôt que `mimes:`

Le GPX est du XML sur le disque. `mimes:gpx` refuserait une trace légitime, `mimetypes:` seule
laisserait passer un XML renommé `.pdf`. `App\Rules\DocumentFile` porte donc une carte unique
`extension → types MIME réels admis`, lue deux fois : par la règle, et par la collection Media
Library qui refuse le reste. Le contrôle porte sur `getMimeType()`, résolu par `finfo` sur le
fichier temporaire — jamais sur l'extension annoncée.

Corollaire pour les tests : `Illuminate\Http\Testing\File::getMimeType()` renvoie le type qu'on lui
a déclaré, jamais celui qu'il détecte. Un `UploadedFile::fake()` ne peut donc pas prouver le critère
« fichier renommé ». Les fixtures du seeder — un vrai PDF, une vraie trace GPX — servent aussi de
fixtures de test, ce qui donne un seul jeu de fichiers à maintenir.

### Le dépôt gèle quand l'événement est terminé

La story ne le demandait pas. Le briefing le fait (D-51), et un document qui pourrait encore
apparaître après la course quand le briefing ne le peut plus aurait été une incohérence que personne
n'aurait su expliquer. `DocumentPolicy::create` exige donc la permission **et** `isEditable()`.

### La suppression passe par Eloquent

`onDelete('cascade')` est exclu : Media Library supprime le fichier stocké depuis l'événement
`deleted` du modèle propriétaire, qu'une cascade SQL ferait taire. Le fichier partirait alors de la
base sans partir du bucket.

### Le bucket se provisionne par commande

`storage:ensure-bucket` crée le bucket quand il manque et ne fait rien sinon. Ce n'est pas une
commande jetable : tout environnement neuf en a besoin, et BR-28 en aura besoin en production.
Elle entre dans `composer setup`, avant les migrations.

## D-53 — Le PPS est une forme contrôlée, pas une donnée vérifiée, et le gérant le lit sans le saisir

Arrêté le 2026-08-20 par BR-34.

### Le gérant lit, il ne saisit pas

La fiche gérant annonce « le gérant peut tout corriger, à tout moment », et il corrige de fait le
prénom, le nom, l'email et le téléphone. Le PPS échappe à cette règle : il est rendu verrouillé,
comme l'heure de départ d'un événement lancé, et `Manage\RegistrationUpdateRequest` marque le champ
`prohibited`.

Ce n'est pas de la prudence sur une donnée de santé — il n'y en a pas ici, le numéro n'est ni lu ni
vérifié. C'est que le PPS est une **déclaration** : un numéro corrigé par quelqu'un d'autre que son
déclarant ne veut plus rien dire, et le gérant qui le retape n'a par construction aucune source pour
le faire. Le champ verrouillé le dit à l'écran, ce qu'un champ désactivé n'aurait pas fait — et
`disabled` aurait en prime retiré la valeur de la soumission.

Corollaire à connaître : `EventField` verrouillé affiche son erreur. C'est ce qui rend le
`prohibited` visible quand un onglet resté ouvert avant le verrou poste quand même le champ.

### La normalisation vit dans la règle, jamais dans l'écran

`pps 1234 5678` collé depuis un mail doit devenir `PPS12345678`, espace insécable finale comprise.
Si cette mise en forme vivait dans l'écran, la saisie initiale et la correction divergeraient le jour
où l'une des deux change — et il y a trois écrans, pas deux.

`App\Support\PpsNumber` porte donc la forme (`PATTERN`) et la mise en forme (`normalise()`), et
`RegistrationValidationRules` les distribue aux Form Requests qui reçoivent le champ. Le trait porte
aussi le message d'erreur : `lang/fr/validation.php` n'a pas de clé `regex`, et un message générique
n'aurait pas rappelé la forme attendue, ce que le critère d'acceptation exige.

### Ce que le numéro ne fait pas

Aucune unicité en base, aucun appel à un service tiers, aucune date de validité, aucun blocage : une
inscription sans numéro est valide et reste confirmable. Un numéro inventé passe. C'est le périmètre
arrêté avec le propriétaire — la pièce jointe justificative avait été retirée le même jour, avant
l'implémentation, pour ne pas monter une collection Media Library, une route sous policy et une
purge autour d'un document que personne n'allait ouvrir.

## D-54 — Les quatre étapes sont un seul formulaire, et le remappage d'erreur est la seule chose testée

Arrêté le 2026-08-20 par la reprise **R-04**, qui met D-50 en œuvre. Deux points ont été arbitrés
avec le propriétaire : la stratégie de test front et la forme de l'indicateur d'étape.

### Rien n'est démonté, donc rien n'est perdu

`Complete.vue` n'a aucun état réactif, et ce n'est pas un oubli : le composant `<Form>` d'Inertia
sérialise les inputs natifs du DOM au moment du submit, et aucun écran du projet n'utilise
`useForm`. Les valeurs vivent donc dans le DOM, nulle part ailleurs.

Une étape masquée par `v-if` serait démontée, et la saisie partirait avec elle. Les quatre étapes
restent **montées en permanence** dans un `<Form>` unique et sont révélées par `v-show`. C'est ce qui
rend le retour arrière gratuit — il n'y a rien à restaurer — et c'est ce qui garde la soumission
unique qu'exige D-50 : les huit champs sont là, visibles ou non, quand le coureur valide.

L'alternative — un objet réactif et un `v-if` par étape — aurait dupliqué l'état de huit champs et
forcé `RegistrationFields` à fonctionner dans deux régimes, alors que trois écrans le partagent.

### `novalidate`, parce qu'un `required` masqué refuse la soumission sans le dire

Un contrôle `required` vide dans une étape en `display: none` fait échouer la validation native du
navigateur, qui ne peut pas y poser le focus : le submit est abandonné, la console reçoit un
avertissement, et l'écran ne dit rien. C'est exactement l'échec silencieux que D-50 désigne comme le
risque de cette reprise, et il serait arrivé par la porte de derrière.

Le formulaire porte donc `novalidate`, et le passage à l'étape suivante appelle `reportValidity()`
sur les seuls contrôles de l'étape courante. Le `required` natif garde le passage d'étape — il évite
l'aller-retour serveur sur un champ vide — et ne garde plus jamais la soumission. La validation
serveur reste la seule autorité, ce qui était déjà le contrat.

### Le remappage sort du composant, et c'est la seule chose testée

Une erreur 422 sur `birth_date` pendant que le coureur est à l'étape 4 doit le ramener à l'étape 2,
sur le champ fautif. `resources/js/lib/registrationSteps.ts` porte ce calcul en deux fonctions pures
— quelle étape, puis quel champ — et `Complete.vue` ne fait que les brancher sur le hook `@error`
du `<Form>`.

Le dépôt n'avait aucune infra de test front. Elle entre ici, réduite au strict nécessaire :
`vitest`, **sans jsdom et sans `@vue/test-utils`**, parce qu'il n'y a rien à monter. Le calcul
risqué est du TypeScript sans DOM ; ce qui l'entoure — `v-show`, focus, `reportValidity()` — demande
un harnais de rendu dont le coût ne se justifie pas pour un écran. `npm run test` rejoint
`ci:check` dans `composer.json`, donc la CI l'exécute sans que le workflow change.

Ce qui reste non couvert est nommé : le déplacement du focus et la révélation d'étape se vérifient
à la main.

### Des repères, pas une barre

La charte « Tableau des départs » interdit toute barre de progression. La position s'affiche donc en
quatre repères numérotés, portant `aria-current="step"` sur l'étape courante, doublés d'une ligne
« Étape 2 sur 4 ». Les étapes déjà franchies sont cliquables et ramènent en arrière ; les suivantes
sont inertes, parce qu'y sauter contournerait le seul garde-fou client.

Le titre long de chaque étape n'est pas répété dans l'indicateur : c'est l'en-tête `EventFieldset`
du groupe qui le porte, comme dans les deux écrans de correction.

### Ce que la reprise n'a pas touché, et ce qu'elle a débordé

Aucune ligne de serveur : ni route, ni contrôleur, ni Form Request, ni transaction. Aucun test PHP
modifié. `RegistrationFields` gagne une prop `section` **optionnelle** — sans valeur, il rend ses
trois groupes d'affilée comme avant, donc `registration/Edit.vue` et
`manage/registrations/Edit.vue` ne bougent pas.

Deux débords assumés, tous deux au service du même objectif — que le coureur lise son erreur.
`lang/fr/validation.php` déclarait le nom français de trois champs sur huit : les cinq manquants sont
ajoutés, faute de quoi le message ramené à l'écran parle de `emergency_contact_phone`. Et la
description de l'écran annonce désormais les quatre étapes, puisque c'est la première chose que le
coureur doit savoir avant de commencer.

## D-55 — La navigation lit une décision d'accès partagée, et la barre basse s'arrête à quatre entrées

Arrêté le 2026-08-20 par **BR-33**. Deux points ont été arbitrés avec le propriétaire : quelle
entrée cède sa place dans la barre basse, et le renommage de l'entrée d'accueil.

### Une prop d'accès, sur le patron de `auth.permissions`

La navigation devait apprendre deux faits qu'aucun écran ne portait : l'utilisateur connecté tient-il
une inscription, et l'événement est-il sorti de `draft`. Les deux partent en prop partagée, dans un
nœud `access`, construit exactement comme `auth.permissions` l'est depuis BR-01 — **chaque valeur est
le résultat du même `Gate` que le serveur applique**, jamais une relecture du statut. Une entrée
apparaît donc si et seulement si l'écran derrière elle serait autorisé, et les deux lectures ne
peuvent pas diverger.

L'alternative était d'exposer le statut brut de l'événement et de laisser `mainNavItems()` tester
`!== 'draft'`. C'est la deuxième déclaration de la règle que D-29 interdit : le jour où un état
supplémentaire cesse d'être visible aux participants, `EventPolicy::view()` le sait et la navigation
l'ignore.

`access` est un nœud **racine**, pas `auth.access` : `auth` porte l'identité et les capacités du
compte, quand ces trois valeurs dépendent du compte **et** de l'état de l'événement. Et le nœud ne
pouvait pas s'appeler `event` — `pages/Event.vue` et `pages/manage/Event.vue` reçoivent déjà une prop
de page de ce nom, qui écrase une prop partagée homonyme dans `page.props`. Le défaut aurait été
silencieux et local à deux écrans.

`documents` reste séparé de `event` bien que `DocumentPolicy::viewAny()` et `EventPolicy::view()` se
lisent identiquement aujourd'hui. Ce sont deux policies, et rien ne promet qu'elles restent en phase.

L'événement se lit avec `first()`, jamais `firstOrFail()` : une prop partagée ne doit pas
transformer une base vide en 404 sur toutes les pages. Un invité sort avant les deux requêtes, ce qui
laisse la page publique, l'écran de connexion et tout le parcours `account/*` à coût nul.

### La barre basse tient quatre entrées, et l'ordre décide du repli

`AppBottomNav` rendait toutes les entrées en `flex-1`. À l'échelle `text-label` — 10 px mono,
`letter-spacing: 0.14em`, `px-2` — une cellule portant « INSCRIPTION » demande ~95 px : un écran de
375 px en tient quatre, plus le bouton « … ». BR-33 en produit cinq pour un coureur et six pour un
gérant.

`BOTTOM_NAV_LIMIT` nomme le plafond et `AppBottomNav` y tronque, tandis que la sidebar et le tiroir
mobile continuent de rendre la liste entière : ce qui dépasse est **replié, jamais retiré**. Aucun
écran ne devient injoignable.

C'est « Événement » qui cède chez le coureur : l'accueil porte déjà son statut et le briefing porte
le déroulé de la nuit, donc `/event` n'ajoute que le nom, les places et le résumé. Et **la gestion
passe devant l'inscription du gérant** — un gérant qui court aussi garde son hub sous le pouce, la
story n'exigeant que « les deux jeux d'entrées », pas leur rang.

`BOTTOM_NAV_LIMIT` porte un test, parce que Q-04 est le procès-verbal de ce qui arrive à une valeur
de mise en page que rien ne garde : la charte l'a déplacée d'un tiers en silence.

### « Coureurs » est retirée, pas laissée à pointer nulle part

L'entrée pointait sur `dashboard()` faute de destination. Elle attend BR-14, et une entrée qui ne
mène pas où elle annonce apprend au coureur que les entrées ne mènent nulle part. Sa clé de
traduction reste. Même raisonnement en creux pour « Course », qui cède la place à « Accueil » :
l'écran ne parle pas de la course tant que le moteur n'existe pas, et BR-24 remplacera son contenu
sans retoucher le libellé. `ui.nav.race` reste en place pour BR-13.

### L'accueil quitte `Route::inertia`, et lit `first()` là où ses voisines lisent `firstOrFail()`

Une route sans contrôleur ne peut porter aucune prop : c'est ce qui maintenait l'écran à un libellé.
Elle gagne un contrôleur à action unique, sur le patron de `DesignSystemController`.

La projection est **plus étroite que `ParticipantResource`** : la ressource emporte téléphone, date
de naissance, numéro PPS et contact d'urgence, et rien de tout cela n'a de raison de voyager vers un
écran qui affiche un statut et un dossard. `EventResource` est écarté au même titre — l'accueil n'a
que faire de la latitude, de la longitude et de la capacité.

`/event`, `/briefing` et `/documents` renvoient 404 sur une base sans événement ; l'accueil répond
200. Ces trois écrans **parlent de** l'événement, donc son absence est bien un 404. L'accueil parle
de la personne, et il a quelque chose à dire avant que le gérant ait créé quoi que ce soit.

Aucune redirection non plus : `RegistrationController` renvoie déjà vers l'accueil l'utilisateur sans
inscription, donc un accueil qui pousserait vers l'inscription refermerait la boucle sur lui. La
branche annulée reprend la copie de `registration/Show` plutôt que de la réécrire — D-48 a arrêté ce
qu'on dit à un coureur dont l'inscription est retirée, et le redire en deux formulations est la façon
dont les deux se désalignent.

### Ce que BR-33 ne fait pas

Aucune action sur l'inscription depuis l'accueil : on y navigue, on n'y modifie rien. Aucun chiffre
de course — boucles, distance, prochain départ appartiennent à BR-24, qui hérite de cette route, de
ce contrôleur et de cette navigation au lieu de les reposer.

**Q-02 reste ouverte.** La navigation masque l'entrée qui serait refusée ; elle ne répare pas le
refus. Un participant qui atteint `/briefing` à la main sur un événement en `draft` reçoit toujours un
403 sur la page Symfony non traduite. BR-13 en reste le porteur.

Aucun composant nouveau : `RegistrationStatusBadge`, `EmptyState`, `StatCounter` et `Alert`
couvraient les quatre branches. En revanche la chaîne de classes du lien pleine largeur est
désormais déclarée dans **trois** écrans — `Event.vue`, `registration/Show.vue` et `Dashboard.vue`.
Le troisième exemplaire est le seuil où l'extraction se justifie ; elle n'est pas faite ici pour ne
pas déborder sur deux écrans qui ne sont pas de cette story.

## D-56 — L'image de production sert avec FrankenPHP, et son build embarque Node parce que Vite appelle PHP

Arrêté le 2026-08-21 par BR-27, avec le propriétaire du projet. C'est la première fois que le
projet produit un artefact déployable : jusqu'ici, « faire tourner l'application » voulait dire
Sail.

**Le conteneur web sert avec FrankenPHP**, en mode classique — le mode worker reste désactivé.
La branche écartée est nginx + php-fpm + supervisor, qui reproduit la forme de l'image Sail mais
demande trois briques et un superviseur pour les tenir. FrankenPHP est un seul binaire et un seul
processus : il n'y a ni socket FastCGI à câbler, ni superviseur à surveiller, et le Caddyfile
fourni par l'image convient tel quel (`{$SERVER_NAME}`, racine `public/`). Le mode worker aurait
gardé l'application en mémoire entre les requêtes ; on ne prend pas ce risque pour quarante
coureurs sur une nuit, alors qu'il change le cycle de vie des singletons.

**FrankenPHP ne sert que le HTTP.** Les files ne passent pas par lui : le worker et le
planificateur sont deux conteneurs de la même image, avec `php artisan horizon` et
`php artisan schedule:work` en guise de commande. Le point d'entrée reçoit un rôle — `web`,
`worker`, `scheduler` — et rien d'autre ne distingue les trois services.

### Le build des assets a besoin de PHP, ce qui décide du découpage

`vite.config.ts` charge `wayfinder({formVariants: true})`. Ce greffon exécute
`php artisan wayfinder:generate --with-form` dans son hook `buildStart` et fait échouer le build
s'il n'aboutit pas ; les dossiers qu'il produit sous `resources/js` sont hors dépôt. L'étape qui
lance `npm run build` doit donc disposer de PHP **et** des dépendances Composer.

D'où une étape de build unique où Node est copié depuis `node:22-bookworm-slim` dans une image qui
est déjà celle de PHP — même Debian des deux côtés, donc même glibc. Les deux contournements
possibles ont été écartés : neutraliser le greffon depuis `vite.config.ts` ferait dépendre le
fichier de build de l'application d'une variable propre à Docker, et poser un faux binaire `php`
laisserait passer un build dont les types de routes sont faux. Les deux échangent un problème
d'outillage contre un écart entre le développement et la production, ce que la story cherchait
précisément à supprimer.

### Cinq étapes, et une suppression qui n'est pas au bon endroit par hasard

`base` porte le runtime, `vendor` les dépendances Composer, `assets` la compilation front, `prune`
la suppression des sources front, `final` l'image livrée. La suppression vit dans son étape à elle
parce qu'un `rm` dans l'étape finale ne fait qu'ajouter une couche d'effacement **au-dessus** d'une
couche qui contient encore les fichiers : `docker export` les ressort. En supprimant dans `prune`,
le `COPY --from=prune` ne voit jamais `node_modules` ni `resources/js`. Vérifié sur le système de
fichiers aplati : zéro entrée.

### Non-root sans capability, en écoutant sur 8080

L'application tourne sous l'utilisateur `app`. Plutôt que de compter sur la capability
`CAP_NET_BIND_SERVICE` du binaire pour tenir le port 80, le serveur écoute sur **8080** : un port
au-dessus de 1024 n'en réclame aucune, ce qui laisse poser `no-new-privileges` sans y réfléchir.
Le port n'est de toute façon jamais publié — Traefik joint le conteneur par le réseau de Dokploy.
Caddy écrit dans `/data/caddy` et `/config/caddy` au démarrage : ces deux répertoires changent de
propriétaire avant le `USER app`, faute de quoi le conteneur s'arrête sur un refus d'écriture.

### Le nom de projet Compose est explicite, et ce n'est pas cosmétique

`compose.prod.yaml` déclare `name: backyard-race-production`. Sans lui, Compose déduit le nom du
répertoire — le même que celui de Sail — et monter la pile de production **remplace les conteneurs
MySQL et Redis du développement**. C'est arrivé pendant la vérification de la story. Les volumes
diffèrent (`sail-mysql` contre `mysql-data`), donc les données de développement ont survécu, mais
les conteneurs avaient bien été repris.

### Le planificateur n'a pas de contrôle de santé

L'image porte un `HEALTHCHECK` HTTP sur `/up`, juste pour le rôle `web`. Le worker le remplace par
`horizon:status`. Le planificateur, lui, le **désactive** : il n'écoute sur rien, et le contrôle
hérité le déclarerait mort en permanence. Lui inventer une sonde qui ne prouverait que le
démarrage d'`artisan` serait une garantie sans exécution derrière. Surveiller que le planificateur
tourne encore est un sujet de supervision, et il appartient à BR-30.

### Ce que BR-27 ne fait pas

Pas de `artisan optimize` au démarrage (BR-28), pas de migrations au déploiement (BR-29), pas
d'orchestration ni d'accès Horizon (BR-30), pas de domaine ni de HTTPS (BR-31). Le point d'entrée
valide l'environnement puis passe la main au rôle : c'est là que BR-28 branchera la mise en cache,
entre la validation et l'aiguillage.

## D-57 — L'application fait confiance au proxy, sinon un seul coureur épuise le quota de tous

Arrêté le 2026-08-21 par BR-27. `bootstrap/app.php` n'avait aucun `trustProxies`, et Laravel 11+
ne fait confiance à aucun proxy par défaut.

Derrière le Traefik de Dokploy, qui termine TLS, l'application voit `http` là où le coureur voit
`https`, et voit l'adresse du frontal à la place de celle du coureur. Le dégât mesurable est le
second : `RateLimiter::for('registration')` compte `Limit::perMinute(6)->by($request->ip())`.
Toutes les inscriptions partageant la même adresse, **le septième coureur d'une même minute est
refusé, quel que soit son poste**. Le soir de l'ouverture, c'est une panne. Le limiteur de
connexion, lui, mêle l'adresse à l'e-mail, donc il ne tombe pas — il devient seulement moins fin.

Les deux tests de `TrustedProxyTest` gardent exactement ces deux points : le lien d'inscription
part en `https`, et deux coureurs derrière le même frontal ont chacun leur quota. Retirer le
`trustProxies` fait passer les deux au rouge, dont le second sur un 429.

`at: '*'` est retenu plutôt qu'une liste d'adresses. Le conteneur ne publie aucun port et n'est
joignable que depuis le réseau de Dokploy : le seul client possible est Traefik. Une liste
d'adresses aurait à suivre celle d'un frontal qu'on ne maîtrise pas.

**Ce que cette décision ne règle pas** : `SESSION_SECURE_COOKIE` n'est pas posé, donc Laravel le
déduit du schéma de la requête. La déduction est désormais juste, mais le rendre explicite
appartient à BR-28. En revanche, ce qu'on craignait sur les liens signés n'existait pas : sans
`trustProxies`, la signature était produite et vérifiée sur le même schéma, donc cohérente. Le lien
partait simplement en clair dans le mail.

## D-58 — Le grand format : la coquille passe du téléphone élargi au panneau replié

Arrêtée le 2026-08-21 avec le propriétaire du projet, après maquettage de la refonte
(`project/design/grand-format.html`). Elle ne touche ni la palette, ni les familles, ni les quatre
statuts : D-46 tient en totalité. Ce qui change est l'écran de référence.

**Le mobile-first coûtait au bureau, et la cause tenait en une ligne.** `ActionButton` portait
`w-full` dans sa base `cva`, donc ses quatre-vingt-douze usages s'étiraient jusqu'au bord sur tout
écran. Là où il fallait un lien plutôt qu'un bouton, `Dashboard.vue` l'avait contourné en recopiant
`primaryLinkClasses` et `outlineLinkClasses` — le symptôme, pas la maladie. La base perd `w-full`
au profit de `w-full sm:w-auto`, et le composant gagne `as-child` sur la primitive `Primitive` de
reka-ui : un `<Link>` d'Inertia entre désormais dans un `ActionButton` sans recopier une classe.
Le contournement disparaît, et avec lui l'imbrication `<button>` dans `<a>` de `Welcome.vue`, qui
était du HTML invalide.

**La coquille SaaS sort, le panneau la remplace.** `BoardBand` porte les faits de l'événement,
partagés par Inertia via `BoardResource` — un seul `Event::query()->first()` mémoïsé sert le
bandeau et `access()`. `BoardRail` porte les six destinations de `mainNavItems()`, ce qui règle
`BOTTOM_NAV_LIMIT` : le rail défile au téléphone au lieu de tronquer à quatre, et son test change
de sujet plutôt que de disparaître. Sortent `AppSidebar`, `AppSidebarHeader`, `AppSidebarLayout`,
`AppShell`, `AppContent`, `AppBottomNav`, `NavMain`, `NavFooter`, `NavUser`, `Breadcrumbs`, et les
habillages `ui/sidebar`, `ui/breadcrumb`, `ui/card`. Les tokens `--sidebar-*` partent avec eux, des
deux blocs de `app.css` et des deux paires de `PaletteContrastTest`.

**Aucune primitive reka-ui n'est perdue dans l'opération, et une est gagnée.** `ui/sidebar`
n'était pas une primitive — reka-ui n'en a pas — mais un assemblage shadcn empruntant `Sheet`,
`Tooltip`, `Separator` et `Primitive`, tous conservés. `breadcrumb` et `card` ne prenaient que
`Primitive`. Le rail est monté directement sur `NavigationMenuRoot / List / Item / Link` plutôt que
sur l'habillage `ui/navigation-menu`, dont les `rounded-sm`, `bg-accent` et le viewport des
sous-menus auraient été annulés ligne à ligne pour une charte sans arrondi ni sous-menu.
`AlertDialog` est ajouté et remplace le `Dialog` d'`EventStatusPanel` : une transition
irréversible mérite le focus sur l'action sûre et pas de fermeture au clic extérieur.

**Trois règles portées par des composants, pas par des classes recopiées.** La largeur du bouton
vit dans `ActionButton` et `ActionBar`. Les points de rupture vivent dans `BoardColumns` (5/7 au
bureau, deux colonnes égales en tablette, une au téléphone) et dans `EventFieldset`, passé en
grille de six colonnes où chaque `EventField` déclare son `span` — la naissance en prend deux,
l'e-mail quatre, les remarques six. La largeur de lecture du briefing est bornée à 68 caractères
sur tous les écrans. La densité suit le pointeur : 44 px au doigt, 40 à la souris, par
`sm:min-h-10` et `sm:[&_input]:h-10`. *Corrigé par D-59 : les trois seuils étaient posés à `sm`,
soit 640 px, et la grille de champs se déclenchait sur la largeur de l'écran au lieu de la sienne.*

**L'échelle typographique gagne un sixième cran, `marquee`** (5,5 rem). D-46 impose qu'une taille
se nomme au lieu de se choisir ; le dossard héroïque du bureau n'entrait dans aucun des cinq.
`BibDisplay` pose chaque chiffre sur sa latte et étend `animate-flip` — jusqu'ici cantonné à
`SlatCell` — aux chiffres qui font autorité, décalés de 60 ms, une fois à l'affichage,
`motion-reduce` respecté. C'est le seul mouvement ajouté, et D-25 tient : pas d'horloge, pas de
barre de progression.

**Ce que cette décision ne règle pas.** Le master/detail de la console — la liste des quarante qui
garde le détail à droite au lieu de changer d'écran — n'est pas livré : il déplace un flux, pas une
mise en page. La liste reste une page, la fiche une autre. *Corrigé par D-59 : la raison invoquée
ici — « demande une visite partielle Inertia » — était fausse, l'index expédie déjà la fiche
complète de chaque inscrit.* L'écart des 72 px relevé par D-46 sur la variante `validate` n'est
pas tranché non plus : il
appartient toujours à BR-09 ou BR-13, qui posent le bouton en situation réelle. La refonte n'y
touche pas, mais elle en rend le choix plus net — une règle conditionnelle au pointeur
(`pointer: coarse`) rendrait les 72 px au doigt sans imposer la même hauteur à la souris.

## D-59 — Le retour de terrain sur le grand format

Arrêtée le 2026-08-21 après essai du panneau sur téléphone et sur ordinateur. Six écarts relevés
par le propriétaire du projet, tous portés par un composant plutôt que par un écran.

**Le master/detail de la console arrive, et il ne coûte rien au serveur.** D-58 l'avait écarté au
motif qu'il demandait une visite partielle Inertia. C'était faux :
`Manage\RegistrationController@index` sérialise déjà la `RegistrationResource` entière de chaque
inscrit, coordonnées d'urgence et transitions autorisées comprises. La sélection est donc un `ref`
local, la fiche une dérivation de la liste déjà en mémoire, et le back ne bouge pas d'une ligne.
Au-dessus de 1024 px la liste tient cinq colonnes sur douze et `RegistrationDossier` occupe les
sept autres en `sticky`. En dessous, la même fiche s'ouvre dans un `Sheet` monté sur le `Dialog`
de reka-ui, glissé par la droite.

**Le glissement demandé sur la latte est rendu par le volet, pas par un geste.** La question posée
était : faire glisser la carte à gauche ou à droite pour révéler ses actions. Un tel geste n'a pas
de primitive dans reka-ui, ne s'annonce pas à l'œil, et n'a pas d'équivalent au clavier. Le volet
qui glisse tient le même besoin — plus de gros bouton dans la ligne, donc plus de bord droit
rogné — en restant découvrable et accessible au clavier. La latte perd son `#cell` sur cet écran
et devient un `button` entier, marqué à la sélection par un filet gauche et le fond `accent`.

**La navigation du téléphone cesse de cacher sa fin.** Le rail défilait sans le dire : les
destinations passées la sixième n'existaient pas pour qui ne devinait pas le geste. En dessous de
768 px le rail laisse la place à un `Sheet` gauche qui liste les destinations verticalement, une
par ligne, l'active marquée par `aria-current`. Au-dessus, le rail horizontal reste : six libellés
en `text-label` mono tiennent sous 700 px, contrôles compris. `scroll-rail` reste en garde-fou.

**Un dossard absent ne se dessine pas avec un tiret.** `BibDisplay` traçait la pliure de la latte
en `after:top-1/2`, et le `—` du dossard non attribué tombait pile dessus : la pliure coupait le
tiret en deux. Trois lattes vides remplacent le tiret. C'est ce que montre un panneau de gare qui
n'a rien à afficher, et la pliure redevient ce qu'elle est.

**Deux actions du même groupe ont désormais la même largeur.** Une transition qui demande
confirmation posait l'`ActionButton` directement dans le groupe flex ; une transition directe
l'enveloppait dans un `<Form>` sans largeur, qui se réduisait à son contenu. D'où « Confirmer »
plus étroit qu'« Annuler » au téléphone, pour une raison purement structurelle. Le `<Form>` prend
`w-full sm:w-auto`, la largeur que l'`ActionButton` porte déjà dans l'autre branche.

**La fiche gagne son retour, et l'accueil du gérant cesse de lui parler d'inscription.**
`manage/registrations/{id}/edit` s'atteint depuis le volet et depuis un lien direct : sans retour,
la seule sortie était le rail. Sur l'accueil, un compte qui tient `manage-event` sans inscription
lisait « Tu n'es pas encore inscrit » — une invitation adressée à quelqu'un qui ne court pas. Il
lit maintenant son poste de gestion. La branche s'appuie sur `auth.permissions.manage-event`, déjà
partagé, et `DashboardTest` verrouille le contrat côté serveur.

**Le seuil des deux dimensions passe de 640 px à la largeur réellement disponible.** Deuxième
tour du même essai : sur téléphone et sur tablette, le formulaire d'inscription et la fiche du
coureur tronquaient. Trois causes, un seul défaut de raisonnement — la mise en page à deux
dimensions se déclenchait à `sm`, c'est-à-dire 640 px, qui est un grand téléphone et pas un
bureau. `BoardColumns` posait ses deux colonnes égales dès 640 px, si bien qu'une tablette lisait
la fiche dans la moitié de sa largeur ; il attend maintenant `lg`. `BoardRow` mettait l'étiquette
et la valeur sur la même ligne au même seuil ; il les empile jusqu'à `lg`, l'étiquette au-dessus
comme sur le bandeau, et la valeur peut désormais se couper (`min-w-0`, `break-words`).

**La grille de champs interroge son conteneur, pas la fenêtre.** C'est le cas qui tranche le
débat : `auth/register/Complete.vue` — le formulaire d'inscription du coureur — vit dans la carte
`max-w-sm` d'`AuthSimpleLayout`, soit 384 px, à toutes les tailles d'écran. Un seuil de fenêtre y
posait trois champs de front dans 384 px dès qu'on ouvrait la page sur un écran large. Aucune
valeur de `sm`, `md` ou `lg` ne pouvait avoir raison, parce que la question n'est pas la taille de
l'écran mais celle de la place disponible. `EventFieldset` devient donc un `@container` et les
`span` d'`EventField` des variantes de conteneur, franchies à 52 rem. La carte d'inscription reste
en colonne partout, `registration/Edit` et les écrans de gestion en `max-w-4xl` (56 rem) passent à
six colonnes au bureau, et la fiche imbriquée dans le volet 7/12 de `manage/registrations/Edit`
reste en colonne tant que ce volet ne dépasse pas 52 rem. Le seuil est posé sous les 56 rem des
conteneurs réels pour ne pas se jouer sur une égalité stricte. `Profile.vue` suit la même règle et
passe de `max-w-3xl` à `max-w-4xl` pour y entrer.

**La densité suit le pointeur pour de bon.** D-58 énonçait 44 px au doigt et 40 à la souris, puis
plaçait la bascule à 640 px — où l'on est encore au doigt. `ActionButton` et la hauteur des champs
d'`EventField` passent à `lg`. Ce qui reste à `sm` est la largeur des boutons : un bouton
dimensionné par son texte ne tronque rien, et c'est la demande d'origine.

## D-60 — La course est publique : l'accueil porte l'événement, les documents s'ouvrent aux invités

Arrêté le 2026-08-22 par le propriétaire du projet. C'est une reprise (R-05) : elle rouvre ce que
BR-33 avait branché (D-55) et ce que BR-18 avait fermé, sans qu'aucune story ne le demande.

**Le constat qui la déclenche.** Tout est derrière `auth` sauf `/` et `/design-system`. Or `/` est un
splash de deux boutons : le lien qu'on partage à un coureur ouvre un formulaire de connexion, pas la
course. Personne ne peut savoir quand, où, sur quelle boucle et pour combien de places il s'engage
avant d'avoir un compte.

### Ce qui s'ouvre, et ce qui ne s'ouvre pas

**Public :** l'événement — nom, description, date et heure du premier départ, distance et durée de
boucle, adresse, coordonnées, places — et les documents.

**Fermé, inchangé :** l'accueil du coureur, le briefing, l'inscription, `/manage`.

**L'accueil du coureur n'était pas le bon écran**, alors que c'est par lui que la demande est
arrivée. `DashboardController` ne projette que du nominatif : statut d'inscription, dossard, date de
dépôt, modifiable ou non. Ouvert à un invité, il n'affiche que son propre état vide — les trois
branches d'`EmptyState` se réduisent à « pas d'inscription ». L'écran qui porte les informations de
la course est `Event.vue`, et c'est lui qui devient public.

**Les documents s'ouvrent parce qu'ils décident, ils n'exploitent pas.** Règlement, consignes,
logement et trace GPX sont ce qu'on lit *avant* de s'engager. Le briefing reste fermé pour la raison
inverse : il dit comment se déroule la nuit à quelqu'un qui y sera. Conséquence assumée, sur D-52 :
l'URL signée de cinq minutes devient joignable sans compte, donc le fichier est publiquement
téléchargeable pendant sa fenêtre. Le disque n'est pas public et rien ne s'énumère ; c'est le
document lui-même qu'on accepte de rendre public, ce qui est exactement la demande.

### `/` porte l'événement, et `/event` disparaît

Il y a une seule course, donc une seule page pour la décrire. `Welcome.vue` et `pages/Event.vue`
disaient deux moitiés de la même chose ; `/` porte la page, `/event` quitte le routage avec son
entrée de navigation, et `BareLayout` perd son seul écran — `app.ts` perd la branche `Welcome` de sa
résolution de layout. La reprise **supprime deux écrans au lieu d'en ajouter un**.

D-55 avait posé que `/event` répond 404 sur une base sans événement, parce qu'un écran qui parle de
l'événement a raison de disparaître avec lui. La règle ne survit pas au déménagement : **l'accueil
d'un site répond 200**, y compris avant que le gérant ait créé la course, et y compris quand elle est
en `draft`. Il lit donc l'événement avec `first()`, comme l'accueil du coureur, et montre un état
vide quand il n'y a rien à annoncer. Un événement en `draft` est traité comme une absence : le
brouillon reste invisible sans passer par un refus.

### Les deux entrées de l'invité

**« Mon inscription » est un bouton de connexion nommé par sa destination.** Il n'existe aucun moyen
de voir une inscription sans compte — statut et dossard sont nominatifs — donc l'entrée pointe sur
`/login`. La nommer par ce qu'elle apporte plutôt que par le geste qu'elle demande est le seul écart
avec la navigation connectée, et il est volontaire.

**« S'inscrire » n'apparaît que si l'événement accepte les inscriptions.** D-45 a fait remonter la
fenêtre — statut `registration` et capacité non atteinte — sur la création de compte : hors fenêtre,
`/account/create` affiche un refus et le compteur de places. Une entrée toujours visible enverrait
donc dans un cul-de-sac.

**Connecté, les deux entrées ne se comportent pas pareil.** « S'inscrire » disparaît — le groupe
`account` est en `guest`, la garder ne produirait qu'une redirection. « Mon inscription » ne reste
que si `access.registration` est vrai : un gérant qui ne court pas n'a pas d'inscription, et une
inscription annulée renvoie sur l'accueil (D-48, Q-03).

### Ce que la reprise retourne dans le code

**`access()` cesse de sortir avant les requêtes pour un invité.** C'est le point de D-55 que cette
décision renverse : « un invité sort avant les deux requêtes, ce qui laisse la page publique et
l'écran de connexion à coût nul » n'est plus tenable quand la page publique *est* la course. Les deux
requêtes deviennent le prix de l'accueil. `EventPolicy::view()` et `DocumentPolicy::viewAny()`
prennent un `?User` — sans ça le `Gate` refuse l'invité avant d'entrer dans la méthode, et l'ouverture
ne se voit nulle part. `access.registration` reste `false` pour un invité : pas de compte, pas
d'inscription. Le patron de D-55 ne change pas — chaque valeur reste le résultat du `Gate` que le
serveur applique.

**`board` suit la même visibilité que l'événement.** La prop est aujourd'hui partagée sans condition,
donc le JSON de `/login` porte déjà le nom de l'événement, les places et l'heure du premier départ —
même en `draft`. Aucun écran ne l'affiche, et c'est précisément ce qui l'a laissé passer. Puisque la
course devient publique, la fuite devient une décision : `board` est nul tant que l'événement n'est
pas visible, et le bandeau disparaît avec lui.

**`mainNavItems()` devient réactif, et c'est un bug latent qu'on solde.** La liste est calculée une
fois au montage de `BoardRail` et de `BoardMenu`, or `BoardLayout` persiste entre les visites
Inertia : après connexion, le menu resterait celui de l'invité jusqu'à un rechargement complet.
Invisible aujourd'hui — un invité ne voit jamais la coquille — et garanti dès qu'on la lui ouvre.
La liste passe en `computed`. L'entrée d'accueil, elle, cesse de pointer inconditionnellement sur
`dashboard()` : elle mène l'invité sur la course et le connecté sur son accueil.

**Q-02 monte d'un cran sans changer de porteur, et elle change de porte.** Un invité qui tape
`/dashboard` ou `/briefing` est redirigé sur la connexion — le middleware `auth` passe avant la
policy. Mais `/documents` n'a plus de middleware : sur un événement en `draft`, un invité y reçoit
un 403 sur la page Symfony non traduite. C'est le premier refus qu'une adresse publique peut
produire, et une adresse publique attire les curieux et les robots là où une application fermée
n'en voyait aucun. BR-13 reste le porteur ; l'exposition, elle, n'est plus théorique.

### Quatre points relevés à l'implémentation

**`access` gagne une quatrième clé, `register`.** La navigation doit savoir si l'écran de création de
compte a un sens, et ni `event` ni `documents` ne le disent : la fenêtre d'inscription (D-45) est une
autre condition que la visibilité. La clé vaut vrai pour un invité quand l'événement accepte les
inscriptions — statut `registration` et capacité non atteinte — et faux pour tout compte connecté,
ce qui reproduit exactement les deux gardes de la route : le middleware `guest` et la fenêtre.

**Le bouton « S'inscrire » revient sur l'écran d'événement, pour les invités seulement.** D-45
l'avait retiré en fusionnant les deux inscriptions. Il revient parce que la page est désormais la
première qu'un coureur voit, et qu'une page d'accueil sans porte d'entrée est une impasse. Ce que
D-45 a fermé reste fermé : un compte connecté sans inscription n'a toujours aucun écran pour en
créer une.

**`auth.user` était typé non-nullable côté TypeScript, et c'était faux.** `BoardAccount` testait déjà
`v-if="user"` sur une valeur que le type promettait présente. Le type passe à `User | null`, ce que
la navigation invité exige, et `pages/Profile.vue` — le seul écran qui lisait les champs du compte
sans garde — porte maintenant un `v-if`.

**`wayfinder:generate` sans `--with-form` casse la vérification de types.** Le plugin Vite déclare
`formVariants: true` ; la commande, elle, ne les génère que sur demande. Régénérer les routes après
un changement de routage sans ce drapeau retire `.form()` de tous les helpers et produit une
vingtaine d'erreurs `vue-tsc` sans rapport avec le changement.

## D-61 — La charte de l'instrument, et le starter kit remplacé par les primitives reka-ui

Arrêté le 2026-08-22, sur demande du propriétaire, et mis en œuvre par la reprise R-06. Elle
**révoque deux points de D-46** et en laisse tout le reste debout.

### Ce que D-46 disait, et ce qui change

D-46 avait conclu que « la couleur ne sert plus qu'aux quatre statuts, un écran de course est noir et
blanc partout où aucun statut ne parle », et posait un rayon nul. Les deux points tombent, à la
demande du propriétaire : le produit lisait comme une maquette, pas comme une marque.

**Le premier arbitrage a été un accent de marque seul, et il n'a pas tenu.** `--primary` est passé à
un ambre brûlé `oklch(0.48 0.11 62)`, tenu AA sur les cartes. Le propriétaire l'a renvoyé en trois
mots : « jaune moutarde ». Le balayage a montré que ce n'était pas un défaut de dosage mais une
limite du gamut — **au-delà de `C 0.12`, un orange à `L 0.52` sort de sRGB**. Un orange moyen-sombre
ne *peut pas* être saturé : il lira moutarde quelle que soit la main. Un ambre franc exige `L 0.70`,
et à cette clarté il ne porte plus de texte sur fond clair, ni ne fait un anneau de focus visible sur
du blanc. La piste est close par la colorimétrie, pas par le goût — c'est ce qui la rend inutile à
rouvrir.

**La palette retenue est la troisième charte de `project/design/chartes-alternatives.html`**,
l'instrument à cristaux liquides. Le jour, une dalle gris-bleu sans rétroéclairage — et donc, tout à
fait délibérément, **aucune couleur d'accent** : `--primary` vaut l'encre `#14181B`. La nuit, le
rétroéclairage s'allume et `--primary` devient le cyan `#4FD8E8`. La couleur ne vient plus de
l'accent mais du **corps** : la dalle est teintée, et les surfaces de statut sont teintées dans le
gris de la dalle au lieu d'être des pastels web. C'est une réponse différente à la même demande, et
c'est celle que le propriétaire a arrêtée charte en main.

**L'inversion qui compte.** Jusqu'ici le fond était blanc et la carte grise. Désormais la dalle
`oklch(0.8382)` est le fond et la carte `oklch(0.8819)` est **plus claire** que lui : une latte est
une fenêtre éclairée dans un boîtier, pas une zone assombrie sur une page.

**La charte donne vingt couleurs, la palette en exige trente.** `--secondary`, `--muted` et
`--accent` n'y figurent pas et sont dérivés : les deux premiers sur `#BFC7CD`, en retrait de la dalle
comme un creux d'appareil ; le troisième sur `#B4BCC2` pour l'état survolé. C'est le seul endroit où
cette entrée invente, et c'est là que se trouve la marge la plus fine de toute la palette —
`muted-foreground` sur `--muted`, à 4,59:1. Un futur ajustement de `--muted` doit repasser par le
test avant d'être écrit.

**Les soixante paires de contraste passent**, dans les deux thèmes, avec `PaletteContrastTest`
inchangé. Le filet de chargement d'Inertia suit `--primary` sans intervention ; son secours codé en
dur dans `app.ts` valait encore `#2f43c8`, un bleu d'avant D-46, et passe à `#14181B`.

### Le rayon n'est plus nul

D-46 posait zéro, au motif qu'un tableau des départs n'a pas de coins arrondis. La charte de
l'instrument pose **4 px** : un boîtier a des angles adoucis, une dalle non. `--radius` passe de
`0px` à `4px`.

Changer le token ne suffisait pas. **Le front ne portait que deux `rounded-md`** dans tout
`resources/js`, ce qui est la conséquence logique d'un rayon nul — personne n'écrit `rounded` quand
il ne produit rien. L'arrondi a donc été *posé* là où la charte le met : champs de lecture, lattes,
boutons, saisies, pastilles, volets des dossards, surfaces flottantes, filtres de vue, encarts.

**Les cartes gagnent un filet.** Dans la charte, chaque fenêtre est cernée d'un pixel de `--line` ;
les lattes et les cartes n'en avaient aucun et se détachaient du fond par leur seule valeur. Avec un
fond désormais proche en clarté, le filet devient nécessaire et non décoratif.

**Les lattes gagnent la signature de la charte** : une barre de statut de 4 px au bord gauche, que
l'`overflow-hidden` de la latte clipe à l'arrondi. Elle double le pictogramme, elle ne le remplace
pas — la règle de D-46, « couleur, pictogramme et libellé, jamais la couleur seule », tient. Sur la
latte d'inscription cette barre prend la place que tenait le filet gauche de l'état sélectionné, qui
passe sur le filet complet et le fond `accent` ; `aria-current` ne bouge pas.

**Ce que cette charte n'apporte pas encore : sa typographie.** Elle demande Overpass et Overpass
Mono là où le projet tient Instrument Sans et Martian Mono. Le propriétaire a demandé la couleur, les
cartes, les boutons et l'arrondi — pas les fontes. Le mono porte déjà toutes les lectures, donc la
structure est compatible ; le remplacement reste entier et n'est pas fait.

Ce qui ne bouge pas de D-46 : notation oklch ; palette déclarée en trois endroits avec un test qui
affirme leur concordance ; AA vérifié par `PaletteContrastTest` et non par la revue ; aucun
graphique ; plancher tactile de 44 px.

### `components/ui/` n'existe plus

Le constat qui la déclenche : vingt-et-un dossiers shadcn, dont **sept sans un seul import** —
`badge`, `collapsible`, `input-otp`, `navigation-menu`, `select`, `skeleton`, `tooltip` — pendant que
des primitives reka-ui utiles étaient réécrites à la main. La dépendance `vue-input-otp` sort avec
`input-otp` : aucun écran ne saisit le code d'accès, il n'est qu'affiché une fois.

**Les habillages disparaissent, les primitives montent au point d'appel.** `DialogRoot`,
`DropdownMenuRoot`, `AlertDialogRoot`, `Separator`, `Label`, `Primitive` sont désormais importés
depuis `reka-ui` dans les composants métier. Ce que l'habillage portait — les classes de la surface —
vit là où ce projet met déjà ses classes partagées : deux feuilles, `lib/fieldClasses.ts` et
`lib/overlayClasses.ts`. **Aucune variante n'est recopiée dans les quarante appels** ; c'est ce que le
retrait de la couche menaçait de produire, et la feuille de classes est ce qui l'évite.

**Le tiroir latéral n'était pas une primitive.** `ui/sheet` était un `Dialog` habillé ; les deux
écrans qui l'utilisaient montent `DialogRoot` avec `overlayRail` ou `overlayDrawer`. `ui/sonner`
n'était pas reka non plus — c'est `vue-sonner` — il devient `components/Toaster.vue`, maison.

**Trois composants du starter kit sont morts sans remplaçant un-pour-un.** `ui/alert` et ses trois
sous-composants deviennent un seul `Notice` à `tone` et `title`. `UserMenuContent` et `UserInfo`
n'existaient que pour découper un menu à deux entrées : ils rentrent dans `BoardAccount`.
`ui/button` avait six appels quand `ActionButton` en avait quatre-vingt-douze : `ActionButton` gagne
une taille `icon`, un ton `ghost`, et reste le seul bouton. Il quitte `components/race/` — il n'a
jamais été propre à la course.

**Le bouton du starter kit portait une garde qui n'avait pas été reportée.** `ui/button` avait
`whitespace-nowrap` ; `ActionButton` ne l'a jamais eu, et le propriétaire a signalé « Voir mon
inscription » sur deux lignes en desktop. La cause n'est pas la longueur du libellé mais le
rétrécissement : dans une rangée `ActionBar` en `sm:flex-row`, un bouton en `w-auto` garde
`flex-shrink: 1` et est comprimé sous la largeur de son texte dès que la rangée est chargée. Le
correctif porte sur les trois pièces plutôt que sur le point d'appel : `shrink-0` sur le bouton, pour
qu'une commande ne cède jamais ; `min-w-0` sur la note, pour que la prose cède à sa place ;
`sm:flex-wrap` sur la rangée, pour qu'elle passe à la ligne au lieu de déborder — la rangée à trois
boutons du tableau de bord ne gardait que quarante-cinq pixels de marge au point de rupture `lg`.

### Les champs : reka-ui en a, ils ne s'appellent pas « Input »

`reka-ui` ne fournit pas d'`Input`, et c'est ce qui avait fait conclure à tort qu'il n'avait rien pour
les formulaires. Il a `NumberField`, `DateField`, `TimeField`, `PinInput`, `Select`, `Checkbox`.

**Ce qui monte sur une primitive** : les cinq champs numériques (distance, durée, capacité, latitude,
longitude) sur `NumberField` avec ses pas ; les deux dates (naissance, premier départ) sur
`DateField` ; l'heure du premier départ sur `TimeField` ; la case « se souvenir de moi » sur
`Checkbox`. Les repères d'étape de l'inscription montent sur `Stepper`, qui remplace un `<ol>` de
boutons faits main.

**Ce qui reste en HTML, faute de primitive** : texte, email, téléphone, mot de passe, zone de texte,
fichier. Ils vivent dans `components/form/` et partagent `fieldClasses` — même hauteur, même bordure,
même arrondi, même filet au focus — donc ils ne se distinguent pas à l'œil des champs montés sur
reka-ui.

**Trois primitives ont été écartées après examen, et c'est délibéré.** `Tooltip` sur le motif de gel
d'un champ **retirerait** l'information au doigt : le motif est un texte visible, il le reste.
`ToggleGroup` sur les filtres de vue casserait la navigation — chaque filtre est une URL, avec
`aria-current="page"`, et un groupe de bascules n'est pas un jeu de liens. `Select` et `PinInput`
n'ont aucun consommateur. Brancher une primitive sans besoin est exactement le défaut que cette
reprise corrige.

### Deux effets de bord assumés

**`start_time` accepte les secondes.** `TimeField` soumet `HH:MM:SS` là où `<input type="time">`
envoyait `HH:MM` ; la règle passe de `date_format:H:i` à `date_format:H:i,H:i:s`, et un test nomme le
cas. Le contrat de l'écran change parce que le contrôle change — c'est la formulation honnête, plutôt
que de tronquer en silence dans `prepareForValidation`.

**`@internationalized/date` devient une dépendance directe.** C'était une dépendance transitive de
reka-ui ; `lib/temporal.ts` l'importe pour construire les valeurs des deux champs, et s'appuyer sur le
graphe d'un tiers pour un import direct est fragile. Ses deux fonctions rendent `undefined` sur une
valeur illisible plutôt que de lever : une date malformée venue du serveur laisserait sinon un écran
blanc, et un champ vide est le pire acceptable. Un `.spec.ts` épingle les deux sens.

**Zéro couleur Tailwind brute subsiste dans le front.** Les pages d'authentification traînaient encore
`bg-red-50`, `text-amber-700`, `text-green-600`, `decoration-neutral-300` — la palette du starter kit,
qui ignorait les tokens et donc les deux thèmes. Elles passent sur `destructive`, `status-running` et
`primary`. Les `shadow-xs` hérités partaient avec : la charte de l'instrument n'a pas d'ombre, un
appareil n'en projette pas.

### Le retour depuis les pages d'authentification manquait

Signalé par le propriétaire pendant la reprise : arrivé sur la connexion ou l'inscription, on ne peut
plus revenir à l'événement. La page de l'événement *est* `home`, et le logo y menait déjà — mais son
libellé était `sr-only` et valait le titre de la page, donc le lien était à la fois invisible et mal
nommé. Un lien explicite, libellé et de 44 px se pose en haut à gauche, en miroir de la bascule de
thème qui occupait déjà le haut à droite. Le logo n'est pas touché.

**Ce que cette reprise ne vérifie pas.** Les contrôles compilent, passent `vue-tsc`, `eslint`, les
soixante paires de contraste et les 394 tests PHP, et `lib/temporal.ts` est couvert. Mais **aucun
rendu navigateur n'a été observé** : le dépôt n'a ni `@vue/test-utils` ni pilote de navigateur. Donc
ni la dalle gris-bleu, ni le rétroéclairage cyan, ni l'arrondi de 4 px, ni les barres de statut, ni
les segments de `DateField` et de `TimeField`, ni la mise en forme française de `NumberField`, ni le
passage au clavier du `Stepper` n'ont été vus. Tout cela reste à regarder à l'écran — la galerie
`/design-system` est faite pour ça — avant de considérer R-06 close.

## D-62 — Une seule commande pour le compte organisateur, et la normalisation d'adresse sort du formulaire

Arrêté le 2026-08-22 pendant BR-35, première entrée du lot 3. La séquence `tinker` de dix lignes
devient `php artisan race:manager-account`, et trois arbitrages la façonnent.

**Une commande, deux gestes.** Créer un compte et regénérer son code sont le même geste vu deux
fois : la même adresse, le même `AccessCode::generate()`, la même unique occasion de lire le code.
Deux commandes auraient dupliqué la normalisation, les gardes et l'affichage pour se distinguer par
une seule branche. La signature est donc `race:manager-account {email} {first-name?} {last-name?}
{--regenerate}` — les noms ne sont exigés que sur le chemin de création, parce qu'une regénération
ne touche pas à l'identité. Le nom de la commande dit le compte, pas le verbe, faute de quoi
`--regenerate` mentirait sur un `create`.

**La garde sur le rôle passe avant toute écriture, y compris pour une regénération.** Le rôle
`manager` n'est pourtant nécessaire qu'à la création. La garde reste inconditionnelle parce qu'elle
répond à une question — « cette base est-elle seedée ? » — qui vaut avant n'importe quel geste sur
les comptes, et parce que la seule autre forme possible était de la déplacer dans la branche de
création, où elle aurait laissé passer un `--regenerate` sur une base à moitié installée. Elle nomme
`db:seed --class=RolesAndPermissionsSeeder` et non `DatabaseSeeder` : celui-ci crée deux comptes
d'essai, ce qui est exactement ce que le lot 3 s'apprête à purger.

**La regénération n'attribue aucun rôle.** Un coureur qui a perdu son code le récupère par cette
commande — c'est le même mécanisme d'accès pour tout le monde — et il en ressort coureur. Élever un
compte au rôle `manager` reste un geste qu'on demande explicitement, et aucune option ne le fait
aujourd'hui.

**`EmailAddress::normalise()` devient la forme unique de l'adresse.** La règle vivait dans
`AccountStoreRequest::prepareForValidation()`, et `FortifyServiceProvider` la recopiait au moment de
chercher le compte à la connexion. La commande aurait été le troisième exemplaire : c'est un de trop
pour une règle dont l'écart ne se voit pas — un compte qui existe et ne peut pas se connecter. Les
trois appelants passent par `App\Support\EmailAddress`.

**Ce qui reste ouvert.** `ProfileUpdateRequest` ne normalise pas l'adresse qu'un utilisateur saisit
pour lui-même : une majuscule y suffit à se fermer la porte, puisque la connexion cherche en
minuscules. Le défaut est antérieur à BR-35 et hors de son périmètre ; il se corrige désormais par
un appel de plus à `EmailAddress::normalise()`.

## D-63 — Le mail se compose en composants du paquet, et sa palette reste sous test

Arrêté le 2026-08-22 pendant BR-36, deuxième entrée du lot 3. La story avait tranché la voie —
surcharger `notifications::email` plutôt qu'écrire une vue par notification — et laissé trois
questions ouvertes derrière elle.

**Un composant, deux formats : c'est le seul endroit où le HTML et le texte divergent.** Le même
`notifications::email` est rendu deux fois, une fois avec les composants de `mail/html`, une fois
avec ceux de `mail/text`. La vue ne peut donc pas se brancher sur le format — mais un composant, si.
Les trois restes de markdown de la version texte se règlent chacun par un composant qui a un jumeau
texte : `heading` (le `#` du titre), `link` (le `[url](url)` sous le bouton), `header` (le
`Nom: http://…` de l'en-tête). Écrire ces phrases en dur dans la vue aurait été l'autre voie ; elle
duplique l'habillage à la première notification suivante.

**Le code d'accès voyage comme vue, pas comme chaîne.** `->line(view('mail.access-code', …))` :
`SimpleMessage::formatLine()` rend tout `Htmlable` tel quel, et `Illuminate\View\View` en est un.
Le bloc atterrit donc exactement là où la prose le place — entre « Ton code d'inscription : » et
« Garde-le en sécurité ». Le passer par le gabarit, en donnée de vue, était la voie évidente et
elle est fausse : le gabarit n'a que deux positions à offrir, avant ou après le bouton, et les deux
séparent le code de la phrase qui le désigne. Le fragment ne porte aucune ligne vide, faute de quoi
CommonMark refermerait le bloc HTML brut au milieu du tableau.

**La quatrième déclaration de la palette n'échappe pas au test.** La story annonçait qu'aplatir
`oklch` en hexadécimal ferait sortir le mail du test de concordance. C'est faux : la conversion
oklch → sRGB est exacte au canal près sur les six couleurs employées, vérifiée avant d'écrire le
test. `MailTemplateTest` compare donc l'hexadécimal du mail **rendu** au token de `app.css` converti
— pas un hexadécimal figé à la main. La conversion sort de `Tests\Support\Contrast`, qui n'en
gardait que la moitié linéaire, vers `Tests\Support\Srgb`.

**Ce que le thème du mail ne style plus.** Il couvre ce que les deux notifications rendent, plus les
boutons `success` et `error` — sans eux, une notification de niveau erreur afficherait un bouton
sans fond, donc du texte clair sur une surface claire. Il ne style ni `x-mail::panel` ni
`x-mail::table` : la story qui s'en servira les habillera.

**Deux affirmations du contexte de BR-36 étaient périmées**, et le sont restées jusqu'à ce que le
mail soit rendu pour de vrai. L'habillage n'était pas en anglais — `lang/fr.json` portait déjà le
repli sous le bouton et le pied depuis D-45 ; il restait `Hello!`, `Whoops!` et `Regards,`, que
personne n'affichait puisque les deux notifications posent titre et salutation. Et le bouton du
paquet n'est plus bleu depuis longtemps : il vaut `#18181b`, à deux pas de l'encre de la charte. Le
défaut visible était le gabarit de démonstration au complet — carte blanche, fond `#fafafa`, gris
zinc, ombre portée — pas une couleur fausse.

**Ce qui reste ouvert.** Le mail annonce `color-scheme: light` et rien d'autre : un client qui
inverse d'autorité affichera ce qu'il veut, et c'était déjà hors périmètre. La coupure de mot sur
tous les liens de la fenêtre (`.inner-body a`) est retirée du thème du paquet, où elle cassait aussi
les libellés de bouton en portrait ; seule l'adresse de repli la porte, par sa classe.

## D-64 — La purge nomme le rôle `manager`, et refuse plutôt que de supposer une réponse

Arrêté le 2026-08-22 pendant BR-37, dernière entrée du lot 3. La story avait tranché la définition —
un compte coureur est un compte qui ne porte pas le rôle `manager` — et laissé la mécanique ouverte.

**L'exception à `laravel:permissions-not-roles` est nommée, pas subie.** `race:purge-registrations`
teste un nom de rôle, ce qu'aucune décision d'accès du projet ne fait. Elle ne laisse entrer
personne : elle choisit qui elle n'emporte pas, et cette question n'a pas d'autre réponse ici. La
définir par la présence d'une inscription était l'autre voie, plus étroite et fausse : elle épargne
les comptes orphelins, que `RegisterRunner` ne crée jamais et qui sont donc exactement ce qu'une
purge doit balayer.

**Repris le même jour par [R-07](README.md) :** le rôle n'est plus la seule règle. L'adresse de
l'organisateur, en configuration, épargne un compte elle aussi — en plus du rôle et jamais à sa
place. Voir D-65.

**Le rôle se teste par son nom en sous-requête, pas par le scope `withoutRole()` du paquet.** Le
scope résout d'abord le rôle par `Role::findByName()`, qui lève `RoleDoesNotExist` quand la table
des rôles n'a pas été semée. Une commande de purge qui casse sur une base incomplète est pire
qu'inutile — elle doit pouvoir compter, dire ce qu'elle voit, et refuser. `whereDoesntHave('roles',
…)` sur le nom ne dépend que de la ligne de jointure, et inclut au passage les comptes sans aucun
rôle.

**Il ne reste plus de manager quand tous les comptes sont des comptes coureurs.** Le décompte des
coureurs et celui des comptes suffisent : leur égalité est l'avertissement, et il tombe avant la
confirmation. Une seconde requête sur le rôle aurait dit la même chose deux fois.

**Symfony ne devine plus l'absence de terminal.** `Application::configureIO()` ne teste plus
`posix_isatty` : seul `--no-interaction` — ou `-n` — coupe l'interactivité. S'en remettre à la valeur
par défaut de `confirm()` reviendrait donc à répondre « non » sans le dire dans un cas, et à
attendre une entrée qui ne viendra pas dans l'autre. La commande lit `isInteractive()` elle-même,
refuse en nommant `--force`, et sort en échec : une purge non faite est un échec, pas un succès
silencieux.

**Une seule écriture ne passe pas par un modèle.** Les inscriptions puis les comptes partent ligne à
ligne, parce que `HasRoles` détache les attributions sur l'événement `deleting` et qu'une
suppression en masse laisserait `model_has_roles` peuplé de références mortes. Les sessions, elles,
n'ont pas de modèle : `DB::table('sessions')->whereIn('user_id', …)` ferme la porte des comptes
emportés, dans la même transaction.

**Pas de classe d'action, pas d'écran.** La logique vit entière dans la commande, seul appelant
qu'elle aura jamais : la story exclut le bouton, et une action extraite pour un appelant unique
n'aurait déplacé que le nom.

## D-65 — L'adresse de l'organisateur en configuration, mais en plus du rôle et jamais à sa place

Arrêté le 2026-08-22, juste après BR-37, et porté par la reprise R-07. Le constat du propriétaire :
il n'y aura jamais qu'un seul manager, donc son adresse est une donnée d'installation et non quelque
chose à retaper à chaque geste. La proposition initiale allait plus loin — purger tous les comptes
sauf cette adresse, et retirer l'argument des deux commandes.

**La liste blanche est une disjonction, pas un remplacement.** Un compte est épargné s'il porte le
rôle `manager` **ou** si son adresse est celle configurée. Faire de la configuration la source unique
était la voie demandée, et elle déplace le rempart de la commande la plus destructrice du dépôt
depuis une ligne en base vers un fichier non versionné : une variable absente, une faute de frappe ou
un cache de configuration périmé — BR-28 T3, encore ouverte — et la purge emporte tous les comptes et
se ferme la porte derrière elle. En disjonction, la même erreur retombe sur le comportement de D-64
au lieu d'ouvrir la trappe.

**`OrganiserAddress::configured()` est le seul lecteur, et il valide.** Le fichier de configuration
lit et donne un défaut, il ne répare pas : le défaut de `env()` ne couvre que la clé absente, une
valeur vide ou illisible passe. Le lecteur normalise donc par `EmailAddress` et filtre par
`filter_var`, et rend `null` — pas la chaîne vide, qui aurait produit un `where('email', '!=', '')`
épargnant zéro ligne tout en ayant l'air d'épargner quelqu'un.

**La purge dit à voix haute que la porte ne tient qu'à une ligne.** Quand l'adresse n'est pas
configurée, l'avertissement tombe avant la confirmation, à côté de celui qui signale qu'aucun compte
ne serait épargné. Elle ne refuse pas pour autant : BR-37 demandait qu'elle le dise avant, pas
qu'elle s'arrête.

**`race:manager-account` garde son argument, la configuration n'en est que le défaut.** L'adresse
explicite reste la voie de la première installation et celle des tests ; le geste courant devient
`race:manager-account --regenerate`, sans rien à taper. `RACE_ORGANISER_EMAIL` entre dans
`.env.example` au passage, ce que BR-28 T4 reproche justement aux variables nées en production.

## D-66 — Le code perdu se règle en libérant l'adresse, et la page d'erreur sort en story propre

Arrêté le 2026-08-22 en ouvrant le lot 4, à la question « l'inscription est-elle finie et
déployable ? ». Elle l'est, et l'examen a fait remonter quatre choses qui cassent dès qu'un inconnu
arrive sur l'adresse. Deux d'entre elles demandaient un arbitrage.

**Un coureur qui perd son code ne reçoit pas un second chemin d'authentification.**
`config/fortify.php` porte `'features' => []` : ni réinitialisation, ni renvoi de code, parce que
D-43 a réduit l'authentification au seul mot de passe et D-45 a fait du mail le seul chemin de
création de compte. Le réflexe — ajouter un renvoi de code — rouvre exactement ce que ces deux
décisions avaient fermé, et il faudrait le protéger comme une porte : limitation de débit, lien
signé, expiration, énumération d'adresses. Le geste retenu est plus étroit et n'ouvre rien : le
gérant **supprime l'inscription et le compte**, l'adresse redevient libre, et le coureur reprend le
parcours public d'inscription — qui envoie déjà un lien signé puis un code neuf. BR-39 le porte.

**La suppression est ouverte quel que soit le statut de l'inscription.** Exiger une annulation
d'abord aurait fait du geste destructeur un geste en deux temps, ce qui se défend ; mais le cas
courant est un coureur confirmé qui a perdu son mail, et l'aller-retour n'ajoute aucune sécurité que
la confirmation à l'écran n'apporte pas déjà. Une confirmée part donc avec son dossard : la place se
libère, le numéro laisse un trou, conformément à BR-37 — les dossards ne rebouchent pas leurs trous.

**Q-02 est fermée : la page d'erreur ne suit plus BR-13.** La question laissait le choix entre une
story dédiée dans l'epic 1 et un rattachement à BR-13, premier écran où un refus serait banal. La
mise en ligne a tranché : le besoin est devenu public — un lien recopié, un lien d'inscription
expiré, un onglet resté ouvert la nuit — alors que BR-13 est un écran de gérant qui n'arrive pas
avant le moteur de course. C'est aussi la dernière surface publique que BR-38 n'a pas habillée.
BR-40 la porte, dans l'epic 1. Q-04 reste sur BR-13, qui pose le bouton de validation en situation.

**Le retour en brouillon exige zéro inscription, annulées comprises.** BR-03 avait livré une chaîne à
sens unique, et c'était juste : aucune de ces transitions ne s'annule une fois qu'un coureur a couru.
La première marche est l'exception, parce qu'une ouverture prématurée n'a rien produit tant que
personne ne s'est inscrit. Ne compter que les inscriptions actives aurait laissé un « brouillon »
porter des lignes annulées et les comptes qui vont avec — un brouillon qui garde des traces n'en est
pas un. La règle stricte est tenable précisément parce que BR-39 donne l'outil pour atteindre la
condition. BR-41 le porte.

## D-67 — La file se surveille par un second point de contrôle, parce qu'Horizon ne peut pas signaler sa propre mort

Arrêté le 2026-08-22 en implémentant BR-30 T5. La question était de savoir qui émet l'alerte quand la
file cesse d'être consommée, et la réponse évidente — la notification de longue attente d'Horizon,
`Horizon::routeMailNotificationsTo()` — est celle qui ne peut pas marcher.

**Horizon surveille les attentes depuis son propre processus maître.** `MonitorWaitTimes` est un
écouteur du superviseur : il ne tourne que tant que le superviseur tourne. Un worker mort n'émet donc
rien, et c'est exactement la panne à couvrir. Le second chemin écarté est le mail : les deux mails du
parcours passent par la file, et un mail d'alerte émis depuis une file arrêtée reste dans la file.
L'alerte doit venir d'un observateur qui ne dépend ni du worker ni de la file.

**Le sondage est une seconde URL, sur le patron de `/up`.** `up/queue` répond `200 {"queue":
"consuming"}` quand la file est consommée, `503` sinon, et la surveillance déjà installée par BR-31 T3
appelle les deux : `/up` dit si l'application répond, `up/queue` dit si le worker travaille. C'est la
distinction que D-19 exigeait, tenue par deux points de contrôle plutôt que par deux mécanismes, et
l'observateur reste hébergé hors du VPS — une machine éteinte fait tomber les deux.

**Trois états valent refus, et le troisième est celui d'Horizon.** Aucun maître qui bat (absent
depuis quinze secondes, la durée de vie de son enregistrement Redis), un maître en pause, ou une
attente estimée au-delà du seuil de `horizon.waits` — le même seuil que la notification écartée, lu au
même endroit, pour ne pas installer un second réglage à côté du premier. Un seuil à `0` tait la file,
comme chez Horizon.

**La route est déclarée hors du groupe `web`, à côté de `health:`.** C'est ce que fait le point de
contrôle du framework, et ce n'est pas cosmétique : dans le groupe `web`, chaque appel de la
surveillance ouvrirait une session et ferait travailler le partage de props d'Inertia, donc la base,
pour une réponse qui n'en a pas besoin. Un test le tient.

**Ce que le sondage ne fait pas** — il ne prévient personne. Il rend un état ; l'alerte est émise par
la surveillance externe, qui doit appeler `up/queue` comme elle appelle déjà `/up`, avec deux échecs
consécutifs avant de sonner : un déploiement laisse le maître absent le temps du redémarrage du
conteneur, et une alerte à chaque mise en ligne est une alerte qu'on finit par ignorer.

**Le sondage seul laissait le planificateur invisible, et un second signal le couvre.** Interrogé de
l'extérieur, `up/queue` dit tout du worker et rien du planificateur — qui n'a pas de contrôle de santé
non plus (D-56 l'assume), alors que la user story de BR-30 est précisément « les éliminations tombent
sans navigateur ouvert ». `race:queue-heartbeat` ferme ce trou par renversement : le planificateur
l'exécute chaque minute, la commande ping `HEALTHCHECKS_QUEUE_URL` **seulement si la file est
consommée**, et c'est l'absence de ping qui alerte. Un planificateur mort n'appelle plus, un worker
mort fait taire l'appel : les deux pannes de BR-30 tiennent dans un signal, et le VPS éteint aussi.

Le ping conditionnel est le motif déjà retenu pour la sauvegarde en BR-29, et pour la même raison :
ce qui doit alerter, c'est le silence. `up/queue` reste, parce que le battement dit qu'une des deux
choses a lâché sans dire laquelle, là où le sondage nomme l'état — worker absent, en pause, ou en
retard. Les deux ne se remplacent pas : l'un porte l'alerte, l'autre le diagnostic.

**La commande ne rattrape pas une URL illisible, et n'avale pas un hôte injoignable.** Une valeur qui
n'est pas une URL est traitée comme une absence, avec le nom de la variable dans la sortie : la
réparer silencieusement enverrait le ping nulle part en ayant l'air de fonctionner. Un hôte
injoignable laisse remonter `ConnectionException` — la règle maison interdit le `try`/`catch`, le
planificateur la journalise, et le check alerte de lui-même faute de ping.

## D-68 — Le demi-tour est une quatrième question posée à l'état, et l'écran cesse de promettre l'irréversible

Arrêté le 2026-08-22 en implémentant BR-41. D-66 avait fixé la règle métier — zéro inscription,
annulées comprises. Restait à décider où elle vit et ce qu'elle change à l'écran.

**Le retour est une méthode de plus sur `EventLifecycleState`, pas une seconde machine.** Chaque état
répond déjà pour lui-même à « quelle est la suite », « qu'est-ce qui la bloque », « avance ». Il
répond maintenant aussi à « quel est le retour », « qu'est-ce qui le bloque », « recule ». Trois états
sur quatre rendent `null`, `[]` et un refus : c'est la forme que la chaîne montante avait déjà, où
`refusals()` rend `[]` dans trois classes sur quatre. Un état ajouté sans son demi-tour échoue à
l'analyse statique, comme il échouait déjà sans sa suite — le `match` exhaustif de la fabrique reste
le seul endroit qui énumère les statuts.

**La règle vit dans l'état, et l'écriture la porte une seconde fois.** `revertRefusals()` compte les
inscriptions et produit le message que le gérant lit ; `revert()` refuse avant d'écrire. Le `update`
ajoute `whereDoesntHave('participants')` à côté du `where('status', $from)` déjà posé par D-32 sur la
transition montante, et pour la même raison : la vérification et l'écriture ne sont pas simultanées.
Ce n'est pas la même règle dite deux fois, c'est la même règle tenue à deux instants — le contrôle
nomme ce qui bloque, l'écriture interdit qu'une inscription se glisse entre les deux. Aucun test ne
couvre ce créneau : il ne s'ouvre que sous concurrence réelle, et le prétendre testé serait faux.

**L'exception refuse, le formulaire compte.** `registrationsExist()` porte un message sans chiffre,
parce que l'exception est le filet et non le canal — c'est ce que son propre docblock énonçait déjà.
Le décompte au pluriel arrive par l'erreur de validation, qui est le chemin que le gérant voit. Le
message est une forme plurielle de `trans_choice`, donc « une inscription » et « 2 inscriptions »
disent quoi supprimer, pas seulement qu'on ne peut pas.

**L'écran d'ouverture disait « on ne revient jamais en arrière », et c'était devenu faux.** La story
ne le demandait pas ; le laisser aurait mis un mensonge à côté du bouton qui le contredit. La
confirmation de l'ouverture annonce désormais que l'étape se referme tant qu'aucune inscription
n'existe. Ce qui décide entre les deux textes n'est pas dans le client : le serveur rend
`nextIsReversible`, calculé en demandant à l'état suivant s'il a un retour. Le client choisit une
phrase, il ne rejoue pas la règle.

**Le dialogue de confirmation est sorti du panneau.** Les deux gestes ont la même forme — bouton,
alerte de refus, dialogue, champ `to` caché, pied de page — et un second bloc copié aurait poussé
`EventStatusPanel.vue` au-delà de la limite de 200 lignes. `EventTransitionDialog.vue` prend les deux
appels ; le panneau garde la frise des étapes et ne fait plus que nommer les deux gestes. Le ton
distingue le sens de la marche : plein pour avancer, sobre pour refermer.

**Ce que le retour ne fait pas** — il n'efface rien. Le briefing, les documents et les horaires
calculés traversent le demi-tour intacts, et un test les nomme un par un. Le coureur qui remplissait
le formulaire public au moment du retour est refusé par le contrôle de période déjà en place : le
statut `brouillon` referme le parcours, aucun code neuf n'a été écrit pour ça.

## D-69 — La suppression unitaire fournit l'appelant qui manquait à la purge, et se refuse par le formulaire

Arrêté le 2026-08-22 pendant BR-39, troisième entrée du lot 4. D-64 avait laissé la logique de
suppression entière dans `race:purge-registrations`, en notant qu'une classe d'action n'aurait
déplacé qu'un nom faute de second appelant. L'écran en fournit un, donc la logique sort.

**Deux actions et non une, parce que la purge balaie plus que des inscriptions.**
`DeleteRegistration` prend une ligne — la fiche, puis son compte — et `DeleteAccount` prend un
compte seul. La purge appelle les deux : la première pour chaque inscription, la seconde pour les
comptes qui n'en portent aucune, que `RegisterRunner` ne crée jamais et qui sont donc exactement ce
qu'un balai doit emporter. Réduire la purge à une boucle sur les inscriptions les aurait laissés
derrière.

**La règle du compte épargné se dit deux fois parce qu'on l'interroge de deux endroits.**
`SparedAccount::runners()` est une contrainte de requête — la purge compte, annonce et itère ;
`SparedAccount::spares()` est un prédicat sur un compte déjà chargé — l'action décide ligne à ligne.
Ce sont deux formes de la même définition, dans une classe qui les tient ensemble, et non deux
définitions. Les deux exceptions à `laravel:permissions-not-roles` que D-64 et D-65 avaient nommées
vivent maintenant là, et nulle part ailleurs.

**Le décompte des sessions est pris avant la boucle, pas rendu par elle.** Une action qui rendrait
« combien de portes j'ai fermées » ferait porter à son type de retour un besoin d'affichage qui
n'appartient qu'à la commande. La purge compte les sessions des comptes condamnés avant de les
supprimer, dans la même transaction : le chiffre annoncé est celui qui part.

**Le refus en course vit dans la requête de formulaire, pas dans l'action.** La voie évidente était
une `RegistrationDeletionRefusedException`, sur le modèle de la transition. Elle rendrait un 409 là
où le gérant a besoin d'une phrase sous le bouton, et l'action serait alors le seul endroit à
connaître un statut d'événement — ce qui la rendrait inappelable par la purge, qui porte déjà son
propre garde-fou. `RegistrationDeletion::refusal()` produit la phrase, la requête l'ajoute aux
erreurs, l'écran désactive le bouton avec la même phrase en `aria-describedby`. La règle est écrite
une fois ; c'est son rendu qui a deux formes.

**Le geste n'a pas de garde-fou d'écriture concurrente, contrairement aux transitions.** D-32 et le
demi-tour de BR-41 ajoutent un `where` sur l'état quitté parce que deux requêtes peuvent se marcher
dessus. Ici la seconde suppression ne trouve plus la ligne : la liaison de modèle rend 404 avant
d'entrer dans le contrôleur, et c'est le refus lisible que demandait le cas du second onglet.

**Le bouton « Modifier la fiche » a été retiré du dossier, à la demande du propriétaire.** C'est le
seul chemin que BR-06 avait posé vers `manage/registrations/{id}/edit` ; l'écran et ses routes
restent, sans lien depuis l'application.

**L'annulation reste, et c'est la couleur qui sépare les deux gestes.** La question posée était de
retirer l'annulation, puisqu'on n'annule que pour ne plus rien faire. Elle reste pour une raison que
cette story a elle-même créée : la suppression est refusée pendant la course, alors qu'aucune
transition d'inscription ne l'est. La nuit de la course, un inscrit qui ne se présente pas ne peut
être qu'annulé. S'ajoutent le retour en arrière — `reopen` rend l'inscription au coureur avec ce
qu'il avait tapé — et le compte, qui survit à l'annulation et pas à la suppression. Le rouge plein ne
désigne donc plus que l'irréversible : `cancel` passe en ton sobre, `destroy` garde `danger`.

**La rangée d'actions descend en bas du tiroir par `order`, pas par un second bloc.** Les trois
gestes tiennent dans une seule section, placée en tête sur le tableau et en pied dans le tiroir :
comme les sections sont les items d'une même grille, `order-last` suffit à la déplacer. Dupliquer le
bloc derrière un point de rupture aurait laissé deux fois le même `id` dans le document, puisque la
colonne du tableau reste rendue sous le tiroir mobile. Le dossier reçoit tout de même `variant` —
`board` ou `drawer` — pour suffixer l'identifiant du refus et choisir l'ordre : le parent sait
laquelle des deux instances il rend, et le composant n'interroge aucune media query.

**Les boutons s'empilent sous 640 px et passent en ligne au-dessus.** La rangée est une grille à une
colonne sur mobile et un `flex flex-wrap` à partir de `sm`, ce qui reprend le point de rupture que
`ActionButton` utilise déjà pour passer de `w-full` à `w-auto`.

**La fermeture du dossier est un événement, pas un `DialogClose`.** Le tiroir mobile se fermait à la
touche d'échappement et au voile, la colonne du tableau ne se fermait pas du tout — aucune des deux
n'offrait de bouton. Le dossier émet `close` et le parent remet `selectedId` à `null` : le tiroir se
referme parce que son ouverture est dérivée de la sélection, et la colonne rend de nouveau son état
vide. Un `DialogClose` n'aurait servi qu'une des deux instances.
