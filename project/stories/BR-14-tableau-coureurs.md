# BR-14 — Consulter la situation de tous les coureurs

| | |
|---|---|
| **Epic** | 3 — Interface de course |
| **Statut** | À faire |
| **Estimation** | 8 pts |
| **Dépend de** | BR-13 |

## User story

En tant que **participant comme gérant**,
Je veux **voir où en est chaque coureur**,
Afin de **savoir qui court encore, qui est sorti et à quel tour**.

## Contexte

Pendant la course, personne ne cherche un classement : on cherche à savoir qui est encore
debout. Le tableau répond à cette question, sans prétendre trancher un ordre d'arrivée.

## Périmètre fonctionnel

**Inclus**
- Quatre vues : tous, en course, sortis, tour courant.
- Par coureur : prénom, nom, dossard, statut, nombre de boucles validées.
- Pour les coureurs sortis : dernière boucle validée, distance totale, heure de sortie, motif.
- Le détail des boucles d'un coureur, déplié dans sa ligne (BR-16).

**Exclu**
- Tout classement pendant la course (voir D-15).
- Les données personnelles des coureurs : téléphone, date de naissance, contact d'urgence
  n'apparaissent jamais ici.

**Dépendances** — BR-13.

## Règles métier

- Le tableau est accessible à tout utilisateur connecté.
- La vue « tour courant » ne liste que les coureurs concernés par la boucle en cours.
- Le nombre de boucles d'un coureur est son nombre de boucles `validated`, rien d'autre.
- La distance totale d'un coureur est la somme des distances de ses boucles validées.
- Aucune donnée personnelle n'est exposée à un autre participant.
- Le tableau n'affiche jamais de position ni de rang tant que l'événement n'est pas terminé.

## Critères d'acceptation

```gherkin
Étant donné un coureur en course avec 7 boucles validées et une coureuse éliminée avec 5 boucles
Lorsqu'un participant ouvre la vue "tous"
Alors les deux apparaissent avec leur statut et leur nombre de boucles

Étant donné cette même liste
Lorsque le participant ouvre la vue "en course"
Alors seuls les coureurs actifs sont listés

Lorsque le participant ouvre la vue "sortis"
Alors chaque coureur sorti affiche sa dernière boucle validée, sa distance totale, son heure de sortie et son motif

Étant donné un événement en course sur le tour 6
Lorsque le participant ouvre la vue "tour courant"
Alors seuls les coureurs ayant une boucle sur le tour 6 sont listés

Étant donné un participant qui consulte le tableau
Lorsqu'il regarde la ligne d'un autre coureur
Alors ni son téléphone, ni sa date de naissance, ni son contact d'urgence n'apparaissent

Étant donné un événement en course
Lorsque le tableau est affiché
Alors aucun rang n'est affiché
```

## Cas limites et erreurs

- Aucun coureur dans une vue : un message explicite, pas un tableau vide.
- Coureur confirmé mais jamais parti : il apparaît avec zéro boucle.
- Distance de boucle corrigée par le gérant en cours d'événement : les totaux affichés suivent la nouvelle valeur.

## Impacts techniques

Les compteurs par coureur doivent être agrégés en base : recalculer 40 totaux en PHP à chaque
rafraîchissement pendant quinze heures de course est exactement ce qu'il faut éviter.

## Tâches

- [ ] **T1** — Requêtes agrégées : boucles validées et distance par coureur `2 pts`
- [ ] **T2** — Les quatre vues et leur filtrage côté serveur `2 pts`
- [ ] **T3** — Écran de tableau responsive avec bascule entre vues `3 pts`
- [ ] **T4** — Tests : contenu par vue, agrégats justes, aucune donnée personnelle exposée `2 pts`
