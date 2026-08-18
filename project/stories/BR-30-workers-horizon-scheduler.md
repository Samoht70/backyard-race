# BR-30 — Faire tourner les files, Horizon et le planificateur en production

| | |
|---|---|
| **Epic** | 7 — Déploiement |
| **Statut** | À faire |
| **Estimation** | 5 pts |
| **Dépend de** | BR-29 |

## User story

En tant que **gérant**,
Je veux **que les éliminations automatiques tombent même quand personne n'a l'application ouverte**,
Afin que **le résultat de la course soit juste sans que j'aie à surveiller un écran**.

## Contexte

L'élimination automatique (BR-11) est un job planifié. En production, cela suppose trois
processus : le web qui répond aux requêtes, un worker qui exécute les jobs, un planificateur qui
les déclenche à l'heure. Sans le worker ou sans le planificateur, l'application a l'air de
fonctionner mais la course n'élimine plus personne — c'est la panne la plus dangereuse du
produit, parce qu'elle est silencieuse.

Sur Dokploy, les trois sont des services de la même stack Compose, ce qui rapproche la
production de ce que fait déjà Sail en développement.

## Périmètre fonctionnel

**Inclus**
- Trois services dans le Compose de production : web, worker Horizon, planificateur.
- L'interface Horizon accessible au seul gérant.
- Le redémarrage propre des workers à chaque déploiement.
- Une alerte quand une file décroche.

**Exclu**
- La montée en charge automatique : une seule instance de worker suffit pour quarante coureurs.
- La supervision de disponibilité du site : BR-31.

**Dépendances** — BR-29.

## Règles métier

- Le worker et le planificateur utilisent **la même image** que le web, avec une commande différente.
- Les trois services redémarrent automatiquement si le conteneur s'arrête, et au redémarrage
  de la machine.
- À chaque déploiement, les workers sont redémarrés pour prendre le nouveau code.
- Un worker arrêté brutalement ne doit pas laisser un job à moitié appliqué : les jobs de course
  sont rejouables par construction (BR-11).
- L'interface Horizon est protégée : accès réservé au porteur de la permission `manage-event`,
  jamais ouverte publiquement.
- Le planificateur ne tourne qu'en un seul exemplaire, sous peine de déclencher deux fois la
  même tâche.

## Critères d'acceptation

```gherkin
Étant donné une production déployée et un tour arrivé à échéance
Lorsque personne n'a l'application ouverte
Alors les coureurs hors délai sont éliminés dans la minute qui suit l'échéance

Étant donné un déploiement en cours
Lorsqu'il se termine
Alors les workers ont redémarré et exécutent le nouveau code

Étant donné la machine redémarrée après une mise à jour du noyau
Lorsqu'elle repart
Alors le web, le worker et le planificateur sont de nouveau actifs sans intervention

Étant donné un worker tué pendant l'exécution du job d'élimination
Lorsqu'il redémarre
Alors le job est rejoué sans produire d'élimination en double

Étant donné le gérant connecté
Lorsqu'il ouvre l'interface Horizon
Alors il y accède

Étant donné un participant connecté, puis un visiteur anonyme
Lorsqu'ils tentent d'ouvrir l'interface Horizon
Alors l'accès est refusé dans les deux cas

Étant donné une file qui n'est plus consommée depuis plusieurs minutes
Lorsque la surveillance s'en aperçoit
Alors une alerte est émise
```

## Cas limites et erreurs

- Deux planificateurs actifs par erreur : les tâches se déclenchent en double, ce que la rejouabilité des jobs doit absorber sans fausser la course.
- Redis injoignable : les jobs s'accumulent sans être perdus et repartent au rétablissement.
- Job en échec répété : il finit en file d'échecs, visible dans Horizon, sans bloquer les suivants.
- Machine à court de mémoire : le worker est le premier à être tué par le système, sans que rien ne l'affiche à l'écran.

## Impacts techniques

Le web peut tomber dix minutes sans conséquence sur le résultat : le gérant réessaiera. Le
worker, lui, ne peut pas s'arrêter sans fausser la course, et rien ne le signale à l'écran.
C'est ce déséquilibre qui justifie une alerte dédiée sur les files, distincte de la surveillance
du site.

## Tâches

- [ ] **T1** — Déclarer web, worker et planificateur dans le Compose de production, même image, redémarrage automatique `2 pts`
- [ ] **T2** — Configurer Horizon en production : files, tentatives, délais `1 pt`
- [ ] **T3** — Protéger l'interface Horizon par permission `1 pt`
- [ ] **T4** — Redémarrage des workers au déploiement `1 pt`
- [ ] **T5** — Alerte sur file non consommée `2 pts`
- [ ] **T6** — Vérifier de bout en bout qu'une élimination tombe sans navigateur ouvert `1 pt`
