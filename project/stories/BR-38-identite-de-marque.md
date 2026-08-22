# BR-38 — Poser l'identité : la forme, le lockup et les icônes

| | |
|---|---|
| **Epic** | 1 — Fondations |
| **Statut** | ✅ Terminé |
| **Estimation** | 3 pts |
| **Créée** | 2026-08-22 — relevée en cours de route, hors lot 3 |
| **Livrée** | 2026-08-22 — hors lot |
| **Dépend de** | BR-02, BR-36, D-61 |

## User story

En tant que **coureur qui ouvre le site ou reçoit un mail**,
Je veux **reconnaître la course à sa marque, dans l'onglet, en haut de l'écran et dans mes mails**,
Afin de **savoir chez qui je suis avant d'avoir lu une ligne**.

## Contexte

Le site est en ligne depuis le 2026-08-22 et porte encore la marque du paquet de démarrage.
`AppLogoIcon.vue` contient le losange de Laravel, l'onglet et l'icône d'accueil iOS sortent du
starter kit, et `AppLogo.vue` — le seul composant qui associe une marque au nom de l'application —
n'est utilisé nulle part : il enferme le losange dans un carré `bg-primary` et attend.

Trois manques, dont deux se voient au premier coup d'œil :

- **Rien ne nomme le produit.** La bande du haut affiche le nom de l'événement, la navigation
  affiche ses entrées, et le nom de l'application n'apparaît à aucun endroit de l'interface. Un
  visiteur qui arrive par un lien ne sait pas où il est tombé.
- **L'onglet et l'écran d'accueil portent la marque d'un autre.** C'est le défaut le plus visible
  et le moins cher à corriger.
- **L'en-tête des mails est du texte seul.** BR-36 a habillé les deux notifications aux couleurs de
  la course, mais leur en-tête reste le nom de l'application en capitales monospace. Or D-45 fait du
  mail le seul chemin de création de compte : c'est le premier objet du produit qu'un coureur voit,
  et il décide s'il clique.

Le propriétaire a fourni le logo en cours de route, et ce qu'il a fourni a redéfini la story. Les
deux PNG livrés étaient des **captures de cartes d'interface** : bordure de 3 px, plaque à la
couleur `--card`, coins arrondis cuits dans le pixel, et la marque réelle occupant 24 % de la
largeur du fichier — le reste étant la marge de la carte. Posés tels quels dans un `<img>`, ils
auraient donné une dalle grise bordée avec un logo minuscule au milieu, et une zone cliquable
couvrant surtout du vide.

La deuxième précision a tranché la story : **le logo est la forme seule**, celle du favicon, et le
reste se construit avec du texte à partir de `config('app.name')`. Ce qui suit en découle.

## Périmètre fonctionnel

**Inclus**
- La forme du logo dans toute l'interface, dans les deux thèmes.
- Le lockup — la forme, un filet, puis `config('app.name')` en texte — en haut à gauche, dans le
  tiroir de menu mobile et sur les écrans d'authentification.
- Le logo présent que le visiteur soit connecté ou non, et qu'un événement soit visible ou non.
- La forme dans l'en-tête des deux mails, sans requête sortante à l'ouverture.
- Les icônes de navigateur et d'écran d'accueil, et le nom de l'application dans tout titre
  d'onglet.
- Deux PNG de la forme, encre jour et encre nuit, pour ce que le CSS n'atteint pas.

**Exclu**
- **Tout lockup en image.** Le nom vient de `config('app.name')` : un raster serait à ré-exporter
  au premier changement de nom, et il ne se sélectionne pas, ne se traduit pas, et pixellise.
- L'image Open Graph. Un partage du lien sort donc encore sans vignette.
- Un vrai conteneur `.ico` multi-tailles, et une icône d'accueil iOS opaque.
- Le mode sombre des clients de messagerie, qui en font ce qu'ils veulent.
- Le nom de l'événement dans la bande. Il sort avec ce travail : il reste sur la page Événement et
  dans le titre d'onglet.

**Dépendances** — BR-02 pour les tokens, D-61 pour la charte, BR-36 pour le gabarit de mail.

## La forme est de la géométrie, pas une image

Le favicon fourni est un PNG transparent de 384 px portant la forme seule, en deux encres qui sont
exactement deux tokens de la charte : `--foreground` pour le quart plein et le point central,
`--border` pour le cadran. Trois primitives, aucune courbe libre. Mesurée au demi-pixel dans une
boîte de 384 : centre (192, 192), rayon extérieur 155,5, intérieur 121, quart plein 138,25 — la
médiane de l'anneau — et point central 21,4. Reconstruite depuis ces nombres, elle colle au fichier
source à 0,0004 d'écart moyen d'opacité, sans un seul pixel au-delà de 0,30.

Trois conséquences, qui valent d'être écrites parce qu'elles évitent trois fichiers :

- **Un SVG inline plutôt qu'une image.** La forme est nette à toutes les tailles, ne coûte aucune
  requête, et ses deux teintes sont liées aux tokens — donc **aucune variante clair/sombre n'est
  nécessaire dans l'interface** : elle suit le thème comme le reste.
- **Le mot-marque est du texte.** Le lockup se compose au lieu de s'exporter. Il suit le nom de
  l'application, se tronque proprement quand la place manque, et reste lisible par un lecteur
  d'écran.
- **Les PNG ne servent qu'au mail.** Un client de messagerie ne rend ni SVG inline ni variable CSS.
  D'où deux fichiers, et deux seulement, dans les deux encres.

## Règles métier

- Le nom affiché à côté de la forme est toujours `config('app.name')`.
- La forme ne porte aucune couleur en dur : ses deux teintes sont `--foreground` et `--border`.
- Le logo s'affiche pour un visiteur anonyme comme pour un coureur connecté, et même quand aucun
  événement n'est visible.
- Aucune requête sortante n'est déclenchée à l'ouverture d'un mail : la forme voyage **dans** le
  message.
- Sur téléphone, la bande du haut garde ses deux chiffres et rien d'autre : le logo y vit dans la
  navigation.
- Le nom de l'application termine tout titre d'onglet, et le repli « Laravel » du paquet
  disparaît.

## Critères d'acceptation

```gherkin
Étant donné un visiteur anonyme, sans aucun événement visible
Lorsqu'il ouvre le site sur un écran large
Alors la forme et le nom de l'application s'affichent en haut à gauche

Étant donné un visiteur qui passe le site en thème sombre
Lorsqu'il regarde le logo
Alors ses deux teintes ont suivi le thème, sans changer de fichier

Étant donné un visiteur sur téléphone
Lorsqu'il ouvre le tiroir de navigation
Alors le lockup y remplace le titre écrit
Et le tiroir garde un nom accessible

Étant donné un coureur qui reçoit son lien d'inscription
Lorsque le mail s'ouvre
Alors la forme s'affiche à gauche du nom de l'application
Et aucune requête n'est partie vers un domaine, tiers ou non
```

## Cas limites et erreurs

- **Un client de messagerie qui ignore les bordures sur un élément inline** (Outlook et son moteur
  Word) : le filet du lockup disparaît, la forme et le nom restent alignés.
- **Un nom d'application long** : le lockup se tronque, il ne pousse pas les chiffres de la bande
  hors de l'écran.
- **Aucun événement visible sur téléphone** : la bande ne s'affiche pas du tout, plutôt que de
  laisser une barre vide.
- **iOS aplatit la transparence** de l'icône d'accueil sur du noir : une forme à encre sombre y est
  presque invisible. Le défaut est nommé, pas corrigé ici.

## Impacts techniques

L'interface n'a besoin d'aucun fichier : la forme est un SVG inline dont les deux teintes passent
par les utilitaires `fill-foreground` et `stroke-border`, disponibles parce que les tokens sont
exposés dans le bloc `@theme`. Un `fill="var(--border)"` en attribut de présentation n'aurait pas été
un équivalent : `var()` n'y est pas garanti d'un navigateur à l'autre.

`AppLogo` laisse le `display` à l'appelant. Sans ça, `hidden` et `flex` se disputent la même
propriété au même niveau de spécificité, et le gagnant dépend de l'ordre d'émission de Tailwind.

Côté mail, la forme est embarquée dans le message sous un CID, et non servie par URL. Ce n'est pas
une préférence : BR-36 interdit toute requête à l'ouverture, et une image distante — même sur son
propre domaine — est un traceur d'ouverture que la plupart des clients bloquent par défaut. Elle est
attachée par un listener sur `MessageSending` plutôt que par les deux notifications, parce que
l'en-tête est partagé : une troisième notification hériterait de la référence CID et afficherait une
image cassée si elle oubliait de l'embarquer.

Le lockup du mail se compose en éléments inline, pas en tableau. Une table dans l'ancre est **sortie
de l'ancre** par le passage d'inlining CSS, qui parse en `DOMDocument` : le lien se vidait et le nom
perdait la pile monospace de l'en-tête.

Le test de BR-36 sur la requête tierce a dû bouger : son assertion interdisait tout `src=`, ce qui
interdit aussi une image embarquée. Elle devient « aucun `src` qui ne soit pas un `cid:` », et un
test de plus vérifie que le message envoyé porte bien une partie `image/png` en disposition
`inline`. Ce nom de partie est load-bearing — Symfony résout `cid:xxx` sur le **nom** de la partie,
pas sur son content-id auto-généré.

## Tâches

- [x] **T1** — Icônes de navigateur et d'écran d'accueil, nom de l'application dans le titre
  d'onglet `1 pt`
- [x] **T2** — La forme en SVG inline, le lockup avec `config('app.name')`, et ses trois
  emplacements `1 pt`
- [x] **T3** — La forme dans l'en-tête des deux mails, embarquée dans le message `1 pt`
