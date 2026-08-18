# BR-00 — Mettre en place le socle technique du projet

| | |
|---|---|
| **Epic** | 0 — Socle technique |
| **Statut** | ✅ Terminé |
| **Estimation** | 9 pts |
| **Dépend de** | — |

## User story

En tant que **développeur du projet**,
Je veux **un projet Laravel + Inertia opérationnel avec toutes les dépendances imposées**,
Afin de **pouvoir démarrer le métier sans arbitrage d'outillage en cours de route**.

## Contexte

Story de bootstrap. Elle est terminée : elle sert de trace de ce qui existe déjà et de ce
qui a été délibérément écarté.

## Périmètre fonctionnel

**Inclus**
- Projet Laravel 13 avec le starter kit Vue officiel (Inertia 3, Vue 3 + TS, Tailwind 4, Vite, Fortify).
- Sail avec MySQL 8.4, Redis, RustFS.
- Horizon, Media Library, Spatie Permission, faker Xefi, Leaflet.
- Chaîne qualité : PHPUnit, Larastan 7 + règles Xefi, Pint, ESLint, Prettier, vue-tsc.
- Dépôt git initialisé, remote `origin` positionné.
- Dossier `.claude/` versionné : hooks, agent de revue, skills et permissions (voir D-23).

**Exclu**
- Toute table métier, tout écran métier, toute permission métier : traités par les stories suivantes.
- `laravel/boost` : incompatible Guzzle 8 (voir Q-02).

**Dépendances** — aucune.

## Règles métier

n/a — story technique.

## Critères d'acceptation

```gherkin
Étant donné un poste avec Docker démarré
Lorsque le développeur lance "./vendor/bin/sail up -d"
Alors les services laravel.test, mysql, redis et rustfs sont sains

Étant donné le projet fraîchement cloné et les conteneurs démarrés
Lorsque le développeur lance "./vendor/bin/sail composer test"
Alors le formatage, l'analyse statique et les tests passent sans erreur
```

## Cas limites et erreurs

- Le port 80 déjà pris sur l'hôte : surcharger `APP_PORT` dans `.env`.
- MySQL, Redis et RustFS n'existent que dans les conteneurs : une commande `artisan` lancée hors Sail ne trouve pas la base.

## Impacts techniques

Le stockage par défaut n'est plus le disque local mais RustFS en S3. Un fichier déposé
n'est donc plus visible dans `storage/app` mais dans le bucket `backyard`.

## Tâches

- [x] **T1** — Scaffolder Laravel 13 avec le starter kit Vue, MySQL et PHPUnit `1 pt`
- [x] **T2** — Installer Sail avec MySQL, Redis et RustFS, configurer `.env` et `.env.example` `2 pts`
- [x] **T3** — Installer Horizon, Media Library, Spatie Permission, publier configs et migrations `2 pts`
- [x] **T4** — Remplacer `fakerphp/faker` par `xefi/faker-php-laravel` `1 pt`
- [x] **T5** — Brancher les règles PHPStan Xefi, ramener l'analyse à zéro erreur `1 pt`
- [x] **T6** — Créer le bucket RustFS, installer Leaflet, positionner le remote git `1 pt`
- [x] **T7** — Versionner `.claude/` : hooks, agent de revue, skills, permissions `1 pt`
