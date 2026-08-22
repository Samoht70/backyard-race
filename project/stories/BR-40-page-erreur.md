# BR-40 — Rendre les refus et les erreurs dans le site

| | |
|---|---|
| **Epic** | 1 — Fondations |
| **Statut** | 🔥 Lot 4 |
| **Estimation** | 3 pts |
| **Créée** | 2026-08-22 — tranche Q-02, ouverte par BR-01 le 2026-08-19, lot 4 |
| **Dépend de** | BR-02, BR-38 |

## User story

En tant que **visiteur, coureur ou gérant**,
Je veux **qu'un lien mort ou un accès refusé m'affiche une page du site, en français**,
Afin de **savoir où je suis et comment revenir, plutôt que de tomber sur un écran technique qui ressemble à une panne**.

## Contexte

Q-02 est ouverte depuis BR-01 : l'application ferme ses routes d'administration mais ne rend aucune
page d'erreur. Un refus, un lien périmé ou une adresse mal tapée sort donc sur la page du framework,
en anglais, hors du site — même typographie, même marque, rien du tout. Tant que l'adresse ne
circulait pas, personne ne le voyait.

L'adresse circule maintenant. Un lien recopié à la main, un lien d'inscription expiré, un onglet
resté ouvert la nuit : les trois se produiront, et les trois affichent aujourd'hui un écran qui dit à
un coureur que le site est cassé. C'est la dernière surface publique qui n'a pas été habillée par
BR-38.

Q-02 laissait le porteur ouvert entre une story dédiée et un rattachement à BR-13. La mise en ligne a
tranché : le besoin est public et immédiat, BR-13 est un écran de gérant qui n'arrivera pas avant le
moteur de course.

## Périmètre fonctionnel

**Inclus**
- Les refus et les erreurs sont rendus dans le site, en français : ressource introuvable, accès
  refusé, page expirée, erreur du serveur.
- Le visiteur peut, depuis cette page, revenir à un endroit qui existe.
- La page dit ce qui s'est passé sans exposer de détail technique.

**Exclu**
- Le contenu du diagnostic : aucune trace d'exception, aucun chemin de fichier, aucune requête. Ce
  qu'il faut pour enquêter part ailleurs (BR-31 T4).
- Les réponses aux appels qui attendent du JSON : elles restent du JSON.
- La page de maintenance, qui appartient au déploiement.
- Une page distincte par statut : une seule page rend les quatre situations.
- Toute remontée d'erreur vers le gérant ou vers l'organisateur.

**Dépendances** — BR-02 pour la charte et les composants, BR-38 pour la marque.

## Règles métier

- Quatre situations sont rendues dans le site : la ressource qui n'existe pas, l'accès refusé, la
  page expirée, l'erreur du serveur. Toute autre situation retombe sur le comportement par défaut.
- Chaque situation a son propre libellé en français. Un accès refusé et un lien mort ne disent pas la
  même chose au visiteur, et les confondre l'envoie chercher au mauvais endroit.
- La page ne révèle jamais si une ressource existe : un accès refusé ne se distingue pas d'une
  ressource absente par ce que le visiteur peut en déduire.
- Le retour proposé dépend de qui regarde : un visiteur revient à l'accueil, un coureur connecté à son
  espace. La page ne propose jamais un chemin fermé à celui qui la lit.
- Une erreur du serveur reste une erreur du serveur : elle ne se déguise pas en page normale, et le
  statut renvoyé n'est pas modifié pour faire joli.

## Critères d'acceptation

```gherkin
Étant donné un visiteur non connecté
Lorsqu'il demande une adresse qui n'existe pas
Alors il reçoit une page du site en français, avec le statut « ressource introuvable »
Et cette page lui propose de revenir à l'accueil

Étant donné un coureur connecté
Lorsqu'il demande une adresse réservée au gérant
Alors il reçoit une page du site en français annonçant un accès refusé
Et cette page lui propose de revenir à son espace

Étant donné un formulaire laissé ouvert jusqu'à l'expiration de la session
Lorsqu'il est envoyé
Alors le visiteur reçoit une page du site annonçant que la page a expiré

Étant donné une erreur inattendue du serveur
Lorsqu'un visiteur la déclenche
Alors il reçoit une page du site sans aucun détail technique
Et le statut de la réponse reste celui d'une erreur du serveur

Étant donné un appel qui attend du JSON
Lorsqu'il échoue
Alors la réponse reste du JSON et non une page
```

## Cas limites et erreurs

- **L'erreur pendant le rendu de la page d'erreur.** Si la page elle-même échoue, il faut que quelque
  chose s'affiche : le repli du framework reste le dernier filet et ne doit pas être supprimé.
- **Une erreur sur la première visite.** Le visiteur n'a chargé aucune page du site : la page d'erreur
  doit s'afficher seule, sans dépendre de ce qu'une page précédente aurait posé.
- **Le mode développement.** L'écran de diagnostic détaillé reste disponible en développement, sinon
  la story rend le débogage plus difficile qu'avant.
- **Un lien d'inscription expiré.** Ce cas-là a déjà son propre message dans le parcours
  d'inscription ; il ne doit pas se mettre à sortir sur la page d'erreur.

## Impacts techniques

La story touche le point unique où l'application décide quoi renvoyer en cas d'échec, et une page du
site s'y ajoute. Elle ne modifie aucune règle d'autorisation : ce qui était refusé reste refusé, seule
la forme du refus change.

Deux surfaces sont concernées au-delà du visiteur : les appels attendant du JSON, qui ne doivent pas
se mettre à recevoir de la page, et le mode développement, qui doit garder son écran de diagnostic.

Q-02 notait que ces choix engagent le reste du site : quatre libellés français entrent dans les
traductions, et la page hérite de la charte comme n'importe quel autre écran.

## Tâches

- [ ] **T1** — Rendu des quatre situations dans le site, JSON et mode développement préservés `1 pt`
- [ ] **T2** — Page du site : libellés français par situation, retour adapté à qui regarde `1 pt`
- [ ] **T3** — Tests : les quatre statuts, le retour du coureur connecté et du visiteur, le JSON qui
  reste du JSON `1 pt`
