# BR-18 — Mettre les documents utiles à disposition

| | |
|---|---|
| **Epic** | 4 — Informations événement |
| **Statut** | ✅ Terminé |
| **Estimation** | 4 pts |
| **Révisée** | 2026-08-20 — réduite de 8 à 4 pts, absorbe le GPX de BR-19 (voir D-47) |
| **Dépend de** | BR-03 |

## User story

En tant que **gérant**,
Je veux **déposer les documents de l'événement**,
Afin que **les coureurs trouvent seuls le règlement, les consignes et les infos logement**.

## Contexte

Règlement, consignes, informations pratiques, logement, repas : autant de fichiers que le
gérant veut déposer une fois et ne plus avoir à envoyer. Les fichiers passent par Media
Library.

Deux choses ont changé le 2026-08-20. La **visibilité par document et la date de publication
tombent** : tout le monde ici est inscrit, il n'y a personne à qui cacher le règlement, et ce
mini-CMS coûtait la moitié de la story pour trois ou quatre PDF. Et la story **reçoit le GPX**
abandonné avec BR-19 — un fichier de plus dans la liste, plus une capture du tracé, à la place
d'une carte interactive.

## Périmètre fonctionnel

**Inclus**
- Dépôt et suppression d'un document par le gérant.
- Par document : un titre, une description et un fichier.
- Consultation et téléchargement par les participants.
- Le GPX de la boucle et une capture du tracé sont déposés comme des documents ordinaires.

**Exclu**
- L'édition du contenu des fichiers dans l'application.
- Les documents nominatifs adressés à un seul coureur.
- La visibilité par document et la date de publication : tout document déposé est consultable
  par les inscrits (voir D-47).
- La carte interactive du parcours : abandonnée avec BR-19.

**Dépendances** — BR-03.

## Règles métier

- Seul le porteur de la permission `manage-documents` dépose ou supprime.
- Un document déposé est consultable par tout utilisateur connecté dès que l'événement sort de
  `draft`, comme le briefing.
- Les fichiers acceptés sont limités par type et par taille, contrôlés côté serveur sur le type
  réel et non sur l'extension.
- Le téléchargement passe par une route contrôlée : jamais par une URL de stockage publique.
- Chaque document appartient à une collection Media Library nommée, un seul fichier par document.
- Le GPX n'est qu'un document : rien ne l'analyse, aucune donnée n'en est extraite, et la
  distance de boucle reste celle que le gérant saisit en BR-03 (voir D-17).

## Critères d'acceptation

```gherkin
Étant donné un document déposé
Lorsqu'un participant ouvre la liste des documents
Alors le document apparaît avec son titre et sa description
Et il peut le télécharger

Étant donné un fichier GPX déposé comme document
Lorsqu'un participant ouvre la liste des documents
Alors il peut télécharger la trace de la boucle

Étant donné le gérant sur l'écran de dépôt
Lorsqu'il dépose un fichier d'un type non autorisé
Alors le dépôt est refusé
Et aucun fichier n'est stocké

Étant donné un fichier renommé en ".pdf" mais d'un autre type réel
Lorsque le gérant le dépose
Alors le dépôt est refusé sur le type réel

Étant donné un document présent dans la liste
Lorsque le gérant le supprime
Alors il disparaît de la liste
Et le fichier stocké est supprimé avec lui

Étant donné un participant connecté
Lorsqu'il tente de déposer un document
Alors l'action est refusée

Étant donné un événement au statut "draft"
Lorsqu'un participant tente d'ouvrir la liste des documents
Alors l'accès est refusé
```

## Cas limites et erreurs

- Fichier dépassant la taille maximale : refus explicite, sans page d'erreur brute.
- Suppression d'un document : le fichier stocké est supprimé avec lui.
- Stockage indisponible au moment du dépôt : le document n'est pas créé à moitié.
- Aucun document déposé : la page le dit plutôt que d'afficher une liste vide.

## Impacts techniques

Le téléchargement devait être servi par une route contrôlée. Il l'est finalement par une **URL
temporaire signée**, produite uniquement dans un contrôleur ayant déjà passé la Policy. L'écart à
D-08 — une URL présignée reste un accès anonyme, borné à sept jours — est arbitré et documenté en
[D-52](../DECISIONS.md), avec sa conséquence en développement : le conteneur et le navigateur
doivent désigner le stockage par le même `hôte:port`, sinon SigV4 rejette la signature. Sous WSL,
l'entrée `rustfs` va dans le fichier hosts de Windows, dont la distribution hérite.

## Tâches

- [x] **T1** — Migration et modèle `Document`, collection Media Library dédiée `1 pt`
- [x] **T2** — Validation stricte des fichiers : type réel, taille, extension `1 pt`
- [x] **T3** — Écran gérant de dépôt et suppression, page de consultation participant,
  téléchargement par URL signée sous Policy `1 pt`
- [x] **T4** — Tests : dépôt, fichier invalide, suppression, refus participant, refus en `draft` `1 pt`
