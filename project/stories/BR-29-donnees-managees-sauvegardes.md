# BR-29 — Faire tourner MySQL et Redis, et savoir les restaurer

| | |
|---|---|
| **Epic** | 7 — Déploiement |
| **Statut** | ✅ Terminé |
| **Estimation** | 8 pts |
| **Dépend de** | BR-28 |

## User story

En tant que **propriétaire du projet**,
Je veux **une base sauvegardée hors de la machine, et une restauration que j'ai déjà faite une fois**,
Afin de **ne pas perdre la course de la nuit à cause d'un disque, d'une commande ou d'une fausse manipulation**.

## Contexte

Sur le VPS, MySQL et Redis sont nos conteneurs, sur notre disque (voir D-19). Les données de la
course n'existent nulle part ailleurs : pas de feuille de papier, pas de double saisie. C'est la
story la plus importante de l'epic, et la seule dont l'échec est irréversible.

Une sauvegarde n'a de valeur que si la restauration a été essayée **avant** d'en avoir besoin.

## Périmètre fonctionnel

**Inclus**
- MySQL et Redis déployés sur le VPS via Dokploy, avec leurs volumes persistants.
- L'exécution des migrations au déploiement.
- Une sauvegarde quotidienne automatique de la base, **envoyée hors de la machine**.
- Une restauration réellement exécutée, sur une copie.
- Un instantané de la machine et une sauvegarde manuelle la veille de la course.

**Exclu**
- La réplication et la haute disponibilité : hors de proportion pour cet usage, et impossible
  sur une machine unique.
- La sauvegarde du stockage objet : les photos sont reproductibles, la course non.

**Dépendances** — BR-28.

## Règles métier

- MySQL est en version 8.4, la même qu'en développement.
- Redis est **persistant** : Horizon y conserve l'état des files et des jobs planifiés.
- Ni MySQL ni Redis ne sont joignables depuis Internet : ils ne parlent qu'au réseau interne
  des conteneurs.
- Leurs données vivent dans des volumes qui survivent à un redéploiement de l'application.
- Les migrations s'exécutent au déploiement, en mode forcé, jamais pendant la construction de
  l'image ni à la première requête d'un visiteur.
- La sauvegarde est **quotidienne, automatique, et déposée sur le stockage objet** — pas sur le
  disque du VPS. Une sauvegarde sur le même disque que la base disparaît avec elle.
- Rétention d'au moins sept jours.
- La procédure de restauration est écrite **et exécutée au moins une fois** sur une base de test.
- La veille de la course : une sauvegarde manuelle et un instantané de la machine.

## Critères d'acceptation

```gherkin
Étant donné un déploiement sur le VPS
Lorsqu'il se termine
Alors les migrations ont été appliquées
Et aucune migration n'a été jouée pendant la construction de l'image

Étant donné l'application redéployée
Lorsqu'elle redémarre
Alors les données de la base et les files Redis sont intactes

Étant donné la sauvegarde quotidienne configurée
Lorsqu'elle s'exécute
Alors le fichier produit se trouve sur le stockage objet, hors de la machine

Étant donné une sauvegarde de la veille
Lorsqu'on la restaure sur une base de test
Alors les participants, les tours et les boucles sont retrouvés à l'identique

Étant donné la base de production
Lorsqu'on tente de s'y connecter depuis Internet
Alors la connexion est refusée

Étant donné le worker Horizon redémarré
Lorsqu'il reprend
Alors les jobs en attente dans Redis n'ont pas été perdus
```

## Cas limites et erreurs

- Migration qui échoue en production : le déploiement s'arrête et l'ancienne version continue de servir.
- Disque du VPS plein : la base cesse d'écrire. C'est le scénario que l'alerte disque de BR-26 doit devancer.
- Redis vidé pendant la course : les jobs en attente sont perdus, ce qui retarderait des éliminations — d'où l'exigence de persistance.
- Sauvegarde présente mais illisible : c'est précisément ce que le test de restauration doit révéler avant l'événement.
- Sauvegarde automatique silencieusement en échec depuis trois jours : l'échec doit alerter, pas seulement être journalisé.

## Impacts techniques

Sur une plateforme managée, cette story serait deux cases à cocher. Ici c'est nous qui tenons la
seule copie des données, donc le livrable qui compte n'est pas « les sauvegardes sont activées »
mais « j'ai restauré une sauvegarde et j'ai revu les boucles de la course ».

## Tâches

- [x] **T1** — Déployer MySQL 8.4 et Redis persistant via Dokploy, volumes et réseau interne `2 pts`
- [x] **T2** — Fermer tout accès externe à la base et à Redis `1 pt`
- [x] **T3** — Exécuter les migrations au déploiement, à l'abri des exécutions concurrentes `2 pts`
- [x] **T4** — Sauvegarde quotidienne vers le stockage objet, avec rétention et alerte en cas d'échec `2 pts`
- [x] **T5** — Écrire la procédure de restauration et l'exécuter une fois `2 pts`
- [ ] **T6** — Sauvegarde manuelle et instantané de la machine la veille de la course `1 pt`

**T6 attend une date, pas du travail.** La sauvegarde manuelle et l'instantané de la machine se
prennent la veille de la course : c'est un geste de calendrier. La story est close parce
que le mécanisme quotidien tourne et qu'il a été restauré une fois — une sauvegarde jamais rejouée
n'est pas une sauvegarde, celle-ci l'a été.
