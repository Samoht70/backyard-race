# BR-18 — Mettre les documents utiles à disposition

| | |
|---|---|
| **Epic** | 4 — Informations événement |
| **Statut** | À faire |
| **Estimation** | 8 pts |
| **Dépend de** | BR-03 |

## User story

En tant que **gérant**,
Je veux **déposer les documents de l'événement et choisir lesquels sont visibles**,
Afin que **les coureurs trouvent seuls le règlement, les consignes et les infos logement**.

## Contexte

Règlement, consignes, informations pratiques, logement, repas : autant de fichiers que le
gérant veut déposer une fois et ne plus avoir à envoyer. Les fichiers passent par Media
Library, avec une visibilité contrôlée.

## Périmètre fonctionnel

**Inclus**
- Dépôt, modification et suppression d'un document par le gérant.
- Par document : titre, description, fichier, date de publication, visibilité.
- Consultation et téléchargement par les participants des documents publiés.

**Exclu**
- L'édition du contenu des fichiers dans l'application.
- Les documents nominatifs adressés à un seul coureur.

**Dépendances** — BR-03.

## Règles métier

- Seul le porteur de la permission `manage-documents` dépose, modifie ou supprime.
- Un document est soit publié, soit masqué. Masqué, il est invisible et non téléchargeable par
  un participant, même avec son adresse directe.
- Un document avec une date de publication future n'est pas visible avant cette date.
- Les fichiers acceptés sont limités par type et par taille, contrôlés côté serveur.
- Le téléchargement passe par une route contrôlée : jamais par une URL de stockage publique.
- Chaque document appartient à une collection Media Library nommée, un seul fichier par document.

## Critères d'acceptation

```gherkin
Étant donné un document publié
Lorsqu'un participant ouvre la liste des documents
Alors le document apparaît avec son titre et sa description
Et il peut le télécharger

Étant donné un document masqué
Lorsqu'un participant ouvre la liste des documents
Alors ce document n'apparaît pas

Étant donné un document masqué et son adresse de téléchargement
Lorsqu'un participant demande directement cette adresse
Alors l'accès est refusé

Étant donné un document dont la date de publication est demain
Lorsqu'un participant ouvre la liste aujourd'hui
Alors ce document n'apparaît pas

Étant donné le gérant sur l'écran de dépôt
Lorsqu'il dépose un fichier d'un type non autorisé
Alors le dépôt est refusé
Et aucun fichier n'est stocké

Étant donné un participant connecté
Lorsqu'il tente de déposer un document
Alors l'action est refusée
```

## Cas limites et erreurs

- Fichier dépassant la taille maximale : refus explicite, sans page d'erreur brute.
- Fichier renommé en `.pdf` mais d'un autre type réel : refusé sur le type réel, pas sur l'extension.
- Suppression d'un document : le fichier stocké est supprimé avec lui.
- Stockage indisponible au moment du dépôt : le document n'est pas créé à moitié.

## Impacts techniques

Les fichiers déposés sont accessibles à une quarantaine de personnes extérieures à
l'organisation. Un document masqué doit l'être réellement, y compris pour qui connaît son
adresse : c'est le seul endroit du produit où une fuite est possible par simple partage de lien.

## Tâches

- [ ] **T1** — Migration et modèle `Document`, collection Media Library dédiée `2 pts`
- [ ] **T2** — Validation stricte des fichiers : type réel, taille, extension `2 pts`
- [ ] **T3** — Route de téléchargement contrôlée par Policy `2 pts`
- [ ] **T4** — Écran gérant de gestion des documents `2 pts`
- [ ] **T5** — Page de consultation participant `1 pt`
- [ ] **T6** — Tests : visibilité, accès direct refusé, date future, fichier invalide `2 pts`
