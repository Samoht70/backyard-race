# BR-22 — Partager les photos de l'événement

| | |
|---|---|
| **Epic** | 5 — Après-course |
| **Statut** | ⛔ Abandonné |
| **Estimation** | 8 pts, non engagés |
| **Dépend de** | BR-03 |

## Pourquoi cette story n'est pas faite

Abandonnée le 2026-08-20, à l'élagage du backlog (voir D-47).

C'est la seule story du backlog qu'un outil gratuit fait mieux. Le dépôt était réservé au gérant
et les participants ne pouvaient pas contribuer : un album partagé reçoit les photos de tout le
monde, sans vignettes à convertir, sans stockage objet à payer, sans conversion Horizon à
surveiller la semaine où le worker sert à éliminer les coureurs.

Un lien vers l'album depuis la page de résultats remplace les huit points. BR-23 perd donc
l'accès « aux photos » de son périmètre et gagne ce lien.

## User story

En tant que **participant**,
Je veux **revoir les photos de la course**,
Afin de **retrouver la soirée et la partager avec les autres coureurs**.

## Contexte

Les photos arrivent pendant et après l'événement. Le gérant les dépose, tout le monde les
consulte. C'est la page qui fera revenir les gens sur l'application une fois la course finie.

## Périmètre fonctionnel

**Inclus**
- Dépôt de plusieurs photos à la fois par le gérant, et suppression.
- Grille responsive, ouverture d'une photo en grand, navigation entre les photos.
- Génération de vignettes pour l'affichage en grille.

**Exclu**
- Le dépôt de photos par les participants.
- Les albums, les tags, les commentaires, l'identification de personnes.

**Dépendances** — BR-03.

## Règles métier

- Seul le porteur de la permission `manage-gallery` dépose ou supprime des photos.
- Tout utilisateur connecté consulte la galerie dès que l'événement sort de `draft`.
- Seules les images sont acceptées, avec un contrôle du type réel et de la taille côté serveur.
- Les vignettes sont générées en tâche de fond : le dépôt n'attend pas la conversion.
- Une photo supprimée l'est aussi du stockage, vignettes comprises.
- L'ordre d'affichage est celui du dépôt, de la plus récente à la plus ancienne.

## Critères d'acceptation

```gherkin
Étant donné le gérant sur l'écran de la galerie
Lorsqu'il dépose cinq images valides d'un coup
Alors les cinq photos sont enregistrées
Et leurs vignettes sont générées en tâche de fond

Étant donné une galerie de vingt photos
Lorsqu'un participant l'ouvre sur un téléphone
Alors les photos s'affichent en grille sans défilement horizontal
Et il peut ouvrir une photo en grand puis passer à la suivante

Étant donné un fichier qui n'est pas une image
Lorsque le gérant le dépose
Alors le dépôt est refusé

Étant donné une photo présente dans la galerie
Lorsque le gérant la supprime
Alors elle disparaît de la galerie
Et le fichier et ses vignettes sont supprimés du stockage

Étant donné un participant connecté
Lorsqu'il tente de déposer une photo
Alors l'action est refusée
```

## Cas limites et erreurs

- Photo lourde prise au téléphone : acceptée dans la limite de taille, et affichée via sa vignette en grille.
- Conversion de vignette en échec : la photo reste consultable en taille d'origine.
- Galerie vide : la page l'indique clairement.
- Dépôt interrompu en cours : les photos déjà envoyées sont conservées, les autres non créées.

## Impacts techniques

Quarante personnes qui consultent une galerie non optimisée depuis un téléphone en 4G, c'est
plusieurs dizaines de mégaoctets par visite. L'affichage en grille doit passer par les
vignettes, jamais par les originaux.

## Tâches

- [ ] **T1** — Collection Media Library de la galerie et conversions de vignettes `2 pts`
- [ ] **T2** — Dépôt multiple avec validation stricte des images `2 pts`
- [ ] **T3** — Grille responsive et visionneuse avec navigation `3 pts`
- [ ] **T4** — Suppression d'une photo, fichiers et conversions compris `1 pt`
- [ ] **T5** — Tests : dépôt multiple, fichier invalide, suppression, refus participant `2 pts`
