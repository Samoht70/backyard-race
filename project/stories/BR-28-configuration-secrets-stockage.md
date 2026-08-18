# BR-28 — Configurer l'environnement, les secrets et le stockage objet

| | |
|---|---|
| **Epic** | 7 — Déploiement |
| **Statut** | À faire |
| **Estimation** | 5 pts |
| **Dépend de** | BR-27 |

## User story

En tant que **développeur**,
Je veux **que la production se configure uniquement par variables d'environnement**,
Afin de **changer d'hébergeur ou de mot de passe sans reconstruire l'image ni toucher au code**.

## Contexte

L'image de BR-27 est la même partout : seule la configuration change. Les photos et documents
déposés pendant l'événement doivent survivre à un redémarrage, ce qui exclut le disque local
du conteneur et impose un stockage objet.

## Périmètre fonctionnel

**Inclus**
- L'inventaire des variables nécessaires en production, et lesquelles sont des secrets.
- Le stockage objet compatible S3 en production, avec son bucket et ses accès.
- La mise en cache de la configuration, des routes et des vues au démarrage.
- Un `.env.example` qui documente le nécessaire sans jamais porter de valeur réelle.

**Exclu**
- Le déploiement de MySQL et Redis : BR-29.
- La gestion d'un second environnement.

**Dépendances** — BR-27.

## Règles métier

- Aucun secret n'entre dans le dépôt, ni dans l'image, ni dans un fichier de configuration :
  ils vivent exclusivement dans les variables d'environnement gérées par Dokploy.
- `APP_ENV=production`, `APP_DEBUG=false`. Une trace d'erreur ne doit jamais s'afficher à un coureur.
- `APP_KEY` est généré une fois et conservé : le perdre rend illisibles les sessions et les
  données chiffrées existantes.
- Le code ne connaît que le disque `s3` : passer de RustFS local au stockage de production ne
  change que des variables d'environnement, conformément à D-08.
- Les fichiers déposés ne sont **jamais** stockés sur le disque du conteneur, ni sur celui du
  VPS : ils partent sur un stockage objet compatible S3, hors de la machine (voir D-19).
- Sessions et cache passent par Redis, jamais par le système de fichiers du conteneur.
- Le fuseau reste `Europe/Paris` et la locale `fr`, comme en développement.

## Critères d'acceptation

```gherkin
Étant donné la production configurée
Lorsqu'une erreur applicative survient
Alors aucune trace technique n'est affichée à l'utilisateur
Et l'erreur est journalisée côté serveur

Étant donné un document déposé par le gérant en production
Lorsque l'application est redéployée
Alors le document est toujours téléchargeable

Étant donné le dépôt du projet
Lorsqu'on en inspecte l'historique et le contenu
Alors aucun mot de passe, clé ou jeton réel n'y figure

Étant donné deux conteneurs web servant la même application
Lorsqu'un coureur se connecte sur l'un puis navigue sur l'autre
Alors sa session est conservée

Étant donné la production démarrée
Lorsqu'on affiche l'heure d'un tour
Alors elle est exprimée en heure de Paris
```

## Cas limites et erreurs

- Variable obligatoire absente : l'application refuse de démarrer plutôt que de servir un comportement dégradé.
- `APP_KEY` régénéré par erreur : les sessions sont invalidées, c'est le risque à documenter.
- Stockage objet injoignable : le dépôt échoue proprement et l'incident est journalisé.
- Disque du VPS pris pour cible par erreur : un fichier y survivrait au redéploiement mais pas à une recréation de la machine, et n'entrerait dans aucune sauvegarde.
- Configuration mise en cache alors qu'une variable a changé : le cache doit être reconstruit au déploiement.

## Impacts techniques

La bascule du stockage local vers le stockage objet est le seul changement de comportement
réel entre développement et production. Un fichier déposé pendant la course et perdu au
redémarrage suivant serait irrécupérable, sans message d'erreur.

## Tâches

- [ ] **T1** — Inventorier les variables de production et distinguer les secrets `1 pt`
- [ ] **T2** — Créer le bucket objet de production hors du VPS et brancher le disque `s3` `2 pts`
- [ ] **T3** — Mise en cache de configuration, routes et vues au démarrage `1 pt`
- [ ] **T4** — Mettre `.env.example` à jour et vérifier l'absence de secret dans le dépôt `1 pt`
- [ ] **T5** — Vérifier la persistance d'un fichier après redémarrage `1 pt`
