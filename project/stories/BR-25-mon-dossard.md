# BR-25 — Imprimer son dossard

| | |
|---|---|
| **Epic** | 6 — Expérience participant |
| **Statut** | À faire |
| **Estimation** | 8 pts |
| **Dépend de** | BR-02, BR-07 |

## User story

En tant que **participant**,
Je veux **imprimer mon dossard depuis mon navigateur**,
Afin d'**arriver au départ avec mon numéro, sans que le gérant ait à les préparer**.

## Contexte

Le dossard est un objet festif autant qu'un identifiant : il porte le nom de l'événement, le
prénom, le nom et le numéro, sur un fond sportif. Il doit sortir d'une imprimante familiale
sans réglage.

## Périmètre fonctionnel

**Inclus**
- Une page « Mon dossard » avec un aperçu fidèle du rendu imprimé.
- Nom de l'événement, prénom, nom, numéro de dossard, mention de la Backyard.
- Un fond graphique sportif et festif, adapté à l'impression.
- Un déclenchement d'impression depuis la page.

**Exclu**
- Le QR Code (voir D-15).
- La génération d'un PDF côté serveur : l'impression navigateur suffit.
- L'impression en masse de tous les dossards par le gérant (voir D-15).

**Dépendances** — BR-02, BR-07.

## Règles métier

- La page n'est accessible qu'au coureur concerné, et seulement si son inscription est
  confirmée — donc s'il porte un numéro de dossard.
- Le gérant peut consulter et imprimer le dossard de n'importe quel coureur confirmé.
- Le dossard imprimé tient sur une page A4, en portrait.
- À l'impression, la navigation, les boutons et les décors d'interface disparaissent : seul le
  dossard sort.
- Le numéro est affiché sur trois chiffres, dans la plus grande taille de la page.

## Critères d'acceptation

```gherkin
Étant donné un coureur confirmé prénommé Thomas portant le dossard 12
Lorsqu'il ouvre la page "Mon dossard"
Alors il voit un dossard portant le nom de l'événement, son prénom, son nom et "012"

Étant donné cette page
Lorsqu'il lance l'impression
Alors le dossard occupe une page A4 en portrait
Et ni la navigation ni les boutons n'apparaissent sur la page imprimée

Étant donné un utilisateur dont l'inscription est "pending"
Lorsqu'il tente d'ouvrir la page "Mon dossard"
Alors l'accès est refusé

Étant donné un participant A
Lorsqu'il tente d'ouvrir le dossard du participant B
Alors l'accès est refusé

Étant donné le gérant
Lorsqu'il ouvre le dossard d'un coureur confirmé
Alors il peut le consulter et l'imprimer
```

## Cas limites et erreurs

- Nom ou prénom très long : la taille de la police s'adapte, le dossard ne déborde pas de la page.
- Impression depuis un téléphone : le rendu reste correct, en A4.
- Impression en noir et blanc : le numéro et le nom restent lisibles sans les couleurs.
- Coureur éliminé : son dossard reste imprimable, il reste un souvenir.

## Impacts techniques

C'est le seul écran du produit destiné au papier plutôt qu'à l'écran. Ses contraintes de mise
en page sont donc les inverses des autres : format fixe, pas de défilement, pas d'interaction.

## Tâches

- [ ] **T1** — Page dossard avec aperçu fidèle et design festif `3 pts`
- [ ] **T2** — Feuille de style d'impression : A4 portrait, masquage de l'interface `2 pts`
- [ ] **T3** — Policy d'accès : le coureur concerné et le gérant `1 pt`
- [ ] **T4** — Adaptation de la taille du texte aux noms longs `1 pt`
- [ ] **T5** — Tests : accès concerné, refus des autres, refus si non confirmé, accès gérant `2 pts`
