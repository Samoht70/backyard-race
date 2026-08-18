# BR-02 — Poser l'identité visuelle et le design system mobile-first

| | |
|---|---|
| **Epic** | 1 — Fondations |
| **Statut** | En cours |
| **Estimation** | 13 pts |
| **Dépend de** | BR-00 |

## User story

En tant que **participant comme gérant**,
Je veux **une interface lisible en plein soleil, utilisable d'une main sur un téléphone**,
Afin de **comprendre l'état de la course en une seconde, sans lire un logiciel de gestion**.

## Contexte

L'application est utilisée dehors, sur un téléphone, par un gérant qui a autre chose à
faire que déchiffrer un tableau. L'identité doit évoquer la course, le défi, l'anniversaire
et la fête, pas un back-office. Cette story produit le vocabulaire visuel que toutes les
stories d'écran réutilisent — la faire tard reviendrait à repasser sur chaque page.

## Périmètre fonctionnel

**Inclus**
- Une direction artistique arrêtée : palette, typographie, échelle d'espacement, arrondis, ombres.
- Les composants récurrents du produit : carte de coureur, badge de statut, gros bouton d'action, compteur, entête de tour.
- Le layout mobile-first commun, et sa déclinaison tablette / desktop.
- Les états d'écran génériques : chargement, vide, erreur.
- Le mode sombre, utile en course de nuit.

**Exclu**
- Le contenu métier des écrans : chaque story d'écran l'apporte.
- Toute librairie de composants supplémentaire : Tailwind et les composants du starter kit suffisent.

**Dépendances** — BR-00.

## Règles métier

- Les statuts de course ont une couleur et un pictogramme **stables dans toute
  l'application** : en course, éliminé, abandon, terminé.
- Toute cible tactile primaire mesure au moins 44 px de haut. Le bouton de validation d'un
  tour est nettement plus grand : il est pressé des dizaines de fois, vite, en extérieur.
- Les couleurs de statut ne sont jamais le seul porteur d'information : un pictogramme ou un
  libellé les accompagne toujours.
- Contraste minimum AA sur le texte, en clair comme en sombre.

## Critères d'acceptation

```gherkin
Étant donné n'importe quel écran de l'application
Lorsqu'il est affiché sur une largeur de 375 px
Alors aucun débordement horizontal n'apparaît
Et les actions principales sont atteignables au pouce

Étant donné un coureur en course et un coureur éliminé
Lorsqu'ils sont affichés dans une même liste
Alors leurs statuts se distinguent par la couleur et par un pictogramme
Et l'information reste lisible en niveaux de gris

Étant donné le mode sombre activé sur le téléphone
Lorsqu'un écran est affiché
Alors les contrastes de texte restent conformes AA
```

## Cas limites et erreurs

- Nom de coureur très long : troncature propre, jamais de casse de mise en page.
- Liste de 40 coureurs sur un petit écran : le défilement reste fluide et l'entête de tour reste visible.
- Connexion dégradée sur le terrain : les états de chargement sont explicites, jamais un écran figé.

## Impacts techniques

Les couleurs et pictogrammes de statut deviennent une donnée partagée entre le back
(qui produit le statut) et le front (qui l'affiche). Un statut ajouté plus tard devra être
déclaré à un seul endroit.

## Tâches

- [x] **T1** — Arrêter la direction artistique avec le skill frontend-design, la valider avec le propriétaire du projet `3 pts`
- [x] **T2** — Traduire la palette et la typographie en tokens Tailwind, clair et sombre `2 pts`
- [x] **T3** — Construire le layout mobile-first commun et sa navigation `3 pts`
- [x] **T4** — Créer les composants récurrents : carte coureur, badge de statut, bouton d'action large, compteur, entête de tour `3 pts`
- [ ] **T5** — Créer les états de chargement, vide et erreur réutilisables `1 pt`
- [ ] **T6** — Vérifier contrastes et cibles tactiles, corriger les écarts `1 pt`
