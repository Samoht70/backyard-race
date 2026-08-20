# BR-17 — Publier le briefing de la course

| | |
|---|---|
| **Epic** | 4 — Informations événement |
| **Statut** | À faire |
| **Estimation** | 2 pts |
| **Révisée** | 2026-08-20 — réduite de 5 à 2 pts (voir D-47) |
| **Dépend de** | BR-03 |

## User story

En tant que **gérant**,
Je veux **rédiger et modifier le briefing depuis l'application**,
Afin que **chaque coureur connaisse les règles sans que j'aie à les répéter quarante fois**.

## Contexte

Le briefing dit l'essentiel : une boucle d'environ 6 km, un départ toutes les heures, tout le
monde repart ensemble, pas de départ en retard, boucle non terminée égale élimination, lampe
frontale pour la nuit. Le ton est celui d'une fête entre amis, pas d'un règlement de
compétition.

## Périmètre fonctionnel

**Inclus**
- Une page briefing consultable par tous les participants.
- Édition du contenu par le gérant, dans un champ de texte, en Markdown restreint.
- Un contenu initial reprenant les règles ci-dessus.

**Exclu**
- Tout éditeur riche : le briefing se saisit en Markdown dans un `textarea` (voir D-47).
- L'historique des versions du briefing.
- Toute règle interdisant l'alcool pendant la course : elle ne doit pas figurer au briefing.

**Dépendances** — BR-03.

## Règles métier

- Le briefing est unique pour l'événement.
- Seul le porteur de la permission `manage-documents` peut le modifier.
- Il est consultable par tout utilisateur connecté dès que l'événement sort du statut `draft`.
- Le contenu accepte du Markdown restreint : titres, listes, gras, émoji.
- Le contenu soumis est nettoyé avant enregistrement : aucun script, aucun HTML arbitraire.

## Critères d'acceptation

```gherkin
Étant donné un événement au statut "registration"
Lorsqu'un participant ouvre la page briefing
Alors il lit le contenu publié par le gérant

Étant donné le gérant sur la page briefing
Lorsqu'il modifie le contenu et enregistre
Alors le nouveau contenu est visible par les participants

Étant donné un participant connecté
Lorsqu'il tente de modifier le briefing
Alors l'action est refusée

Étant donné un contenu de briefing contenant une balise de script
Lorsqu'il est enregistré
Alors le script est retiré du contenu stocké

Étant donné un événement au statut "draft"
Lorsqu'un participant tente d'ouvrir le briefing
Alors l'accès est refusé
```

## Cas limites et erreurs

- Briefing jamais rédigé : la page affiche le contenu initial, jamais une page blanche.
- Contenu très long : la page reste lisible sur téléphone.
- Enregistrement concurrent par deux onglets du gérant : le dernier enregistrement gagne.

## Impacts techniques

Le briefing est un contenu libre saisi par un humain et affiché à tous : c'est le seul endroit
du produit où du texte mis en forme entre par un formulaire et ressort dans une page. Le
nettoyage à l'entrée n'est pas optionnel.

## Tâches

- [ ] **T1** — Colonne de briefing sur l'événement, nettoyage à l'enregistrement, contenu
  initial en seeder `1 pt`
- [ ] **T2** — Page de consultation, `textarea` d'édition, et tests : consultation, édition,
  refus participant, nettoyage `1 pt`
