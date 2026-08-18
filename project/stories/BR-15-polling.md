# BR-15 — Garder l'écran de course à jour sans le recharger

| | |
|---|---|
| **Epic** | 3 — Interface de course |
| **Statut** | À faire |
| **Estimation** | 5 pts |
| **Dépend de** | BR-13, BR-14 |

## User story

En tant que **gérant**,
Je veux **que mon écran reflète l'état réel de la course sans que j'y touche**,
Afin de **ne pas valider un tour périmé ni attendre un coureur déjà éliminé**.

## Contexte

Le tour change toutes les heures et les éliminations tombent côté serveur. Un écran resté
ouvert dix minutes ment. Un rafraîchissement périodique léger suffit : ni WebSocket, ni
infrastructure temps réel.

## Périmètre fonctionnel

**Inclus**
- Rafraîchissement périodique du dashboard gérant et du tableau des coureurs.
- Une fréquence unique, définie au même endroit pour toute l'application.
- Suspension du rafraîchissement quand l'onglet n'est pas visible.

**Exclu**
- Tout WebSocket ou diffusion d'événements (voir D-15).
- Le rafraîchissement des pages statiques : briefing, documents, parcours.

**Dépendances** — BR-13, BR-14.

## Règles métier

- L'intervalle de rafraîchissement est de l'ordre de quinze secondes et n'est écrit qu'à un
  seul endroit.
- Un rafraîchissement ne doit jamais faire perdre au gérant une action en cours ni sa position
  dans la liste.
- Le rafraîchissement s'arrête quand l'onglet passe en arrière-plan et reprend au retour.
- Sur les pages sans enjeu de fraîcheur, aucun rafraîchissement n'est mis en place.

## Critères d'acceptation

```gherkin
Étant donné le dashboard gérant ouvert sur le tour 6
Lorsque le tour 7 s'ouvre côté serveur
Alors l'écran affiche le tour 7 sans intervention du gérant

Étant donné le dashboard gérant ouvert
Lorsqu'un rafraîchissement survient pendant que le gérant fait défiler la liste
Alors sa position dans la liste est conservée

Étant donné le dashboard gérant ouvert
Lorsque l'onglet passe en arrière-plan
Alors aucune requête de rafraîchissement n'est émise
Et les requêtes reprennent au retour au premier plan

Étant donné la page du briefing
Lorsqu'elle reste ouverte
Alors aucune requête périodique n'est émise
```

## Cas limites et erreurs

- Réseau coupé : les tentatives échouent sans bloquer l'écran et reprennent au rétablissement.
- Session expirée pendant la nuit : le gérant est renvoyé vers la connexion plutôt que de voir un écran figé.
- Deux téléphones ouverts sur le même écran : chacun se rafraîchit de son côté, sans conflit.

## Impacts techniques

Quinze heures de course multipliées par deux ou trois écrans ouverts font beaucoup de
requêtes. La charge reste négligeable si les requêtes de course restent agrégées, ce qui
renvoie aux choix faits en BR-13 et BR-14.

## Tâches

- [ ] **T1** — Composable de rafraîchissement périodique, fréquence centralisée `2 pts`
- [ ] **T2** — Suspension sur onglet masqué, préservation de l'état de l'écran `2 pts`
- [ ] **T3** — Branchement sur le dashboard gérant et le tableau des coureurs `1 pt`
- [ ] **T4** — Tests : réseau coupé, session expirée, onglet masqué `1 pt`
