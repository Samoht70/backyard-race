# BR-27 — Construire l'image Docker de production

| | |
|---|---|
| **Epic** | 7 — Déploiement |
| **Statut** | À faire |
| **Estimation** | 9 pts |
| **Dépend de** | BR-26 |

## User story

En tant que **développeur**,
Je veux **une image Docker qui contient l'application prête à servir**,
Afin de **déployer exactement ce que j'ai testé, sur n'importe quel hébergeur Docker**.

## Contexte

Sail sert au développement, pas à la production : son image embarque Xdebug, Node, les outils
de développement et monte le code en volume. La production a besoin d'une image autonome,
avec les dépendances installées et les assets déjà compilés.

## Périmètre fonctionnel

**Inclus**
- Un `Dockerfile` de production distinct de l'image Sail.
- Un fichier Compose de production distinct de `compose.yaml`, qui reste le fichier de développement.
- Compilation des assets front pendant la construction, sans embarquer Node dans l'image finale.
- Dépendances PHP installées sans les paquets de développement.
- Un point d'entrée qui prépare l'application au démarrage.
- Une image utilisable telle quelle pour les trois rôles : web, worker, planificateur.

**Exclu**
- La configuration propre à l'hébergeur : BR-28.
- L'orchestration des processus : BR-30.

**Dépendances** — BR-26.

## Règles métier

- L'image tourne sur **PHP 8.4**, la même version que le développement.
- La construction se fait en plusieurs étapes : l'étape de compilation des assets ne laisse ni
  Node, ni `node_modules`, ni sources front dans l'image finale.
- Les dépendances PHP sont installées avec l'autoloader optimisé et sans les paquets de développement.
- OPcache est actif, avec la validation des horodatages désactivée.
- L'application ne tourne **pas** en root dans le conteneur.
- Aucun secret, aucun `.env`, aucun fichier de configuration mis en cache n'est copié dans l'image.
- Le code n'est pas monté en volume : l'image est immuable.
- Le `compose.yaml` de Sail n'est jamais déployé : il embarque Xdebug, Mailpit et RustFS, et monte le code depuis le disque.
- Le service applicatif ne publie aucun port sur l'hôte : le frontal de Dokploy s'en charge (voir D-19).
- Les migrations ne sont **pas** exécutées pendant la construction de l'image.
- Un point de contrôle de santé HTTP est exposé.

## Critères d'acceptation

```gherkin
Étant donné le dépôt à un commit donné
Lorsque l'image de production est construite
Alors la construction réussit sans accès à une base de données
Et l'image contient les assets compilés
Et l'image ne contient ni Node, ni node_modules, ni dépendances PHP de développement

Étant donné l'image construite
Lorsqu'un conteneur est démarré avec une configuration valide
Alors le point de contrôle de santé répond favorablement
Et l'application est servie

Étant donné l'image construite
Lorsqu'on inspecte l'utilisateur du processus applicatif
Alors ce n'est pas root

Étant donné l'image construite
Lorsqu'on inspecte son contenu
Alors aucun fichier .env ni aucun secret n'y figure

Étant donné un conteneur démarré
Lorsqu'on vérifie la configuration PHP
Alors OPcache est actif et ne revalide pas les fichiers
```

## Cas limites et erreurs

- Échec de la compilation front : la construction s'arrête, aucune image incomplète n'est publiée.
- Variable d'environnement manquante au démarrage : le conteneur échoue avec un message explicite plutôt que de servir une page blanche.
- Image reconstruite au même commit : le résultat est identique.

## Impacts techniques

C'est le point où le développement et la production cessent de partager la même image. Tout ce
qui n'existe que dans Sail — Xdebug, outils, volume monté — disparaît, et les écarts de
comportement se manifestent ici plutôt qu'en production si l'image est testée localement.

## Tâches

- [ ] **T1** — `Dockerfile` de production multi-étapes : dépendances, assets, image finale `3 pts`
- [ ] **T2** — Configuration PHP de production : OPcache, limites, journalisation `2 pts`
- [ ] **T3** — Point d'entrée de démarrage et point de contrôle de santé `2 pts`
- [ ] **T4** — `.dockerignore` : exclure secrets, tests, node_modules, vendor `1 pt`
- [ ] **T5** — Fichier Compose de production : application, worker, planificateur, MySQL, Redis `1 pt`
- [ ] **T6** — Vérifier l'image localement : santé, absence de root, absence de secrets `2 pts`
