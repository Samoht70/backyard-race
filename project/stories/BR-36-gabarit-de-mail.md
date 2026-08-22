# BR-36 — Habiller les mails aux couleurs de la course

| | |
|---|---|
| **Epic** | 6 — Expérience participant |
| **Statut** | ✅ Terminé |
| **Estimation** | 3 pts |
| **Créée** | 2026-08-21 — relevé pendant la mise en ligne |
| **Livrée** | 2026-08-22 — lot 3 |
| **Dépend de** | BR-05, D-61 |

## User story

En tant que **coureur qui reçoit le lien d'inscription**,
Je veux **un mail qui ressemble à la course et qui soit entièrement en français**,
Afin de **reconnaître l'expéditeur et cliquer sans hésiter sur le seul lien qui me fasse entrer**.

## Contexte

Les deux notifications du produit — `RegistrationLink` et `RegistrationConfirmed` — sortent
aujourd'hui dans le gabarit de démonstration de Laravel. `resources/views/` ne contient que
`app.blade.php` : aucune vue de `notifications::email` n'est surchargée, et aucun composant de
`mail::` ne l'est non plus.

Deux conséquences, dont une est un défaut visible :

- **La version texte porte du balisage brut.** Le titre y sort en `# Encore une étape`, l'adresse
  de repli en `[https://…](https://…)`, et le code d'accès en `**K7QP-3M9X-RTBD**` : trois restes
  de markdown, dans le mail que lit un client qui refuse le HTML. L'habillage lui-même est déjà en
  français — `lang/fr.json` porte le repli sous le bouton et le pied depuis D-45 — mais trois
  chaînes du paquet restent non traduites (`Hello!`, `Whoops!`, `Regards,`), et la première
  notification qui omet salutation ou titre les fera apparaître.
- **La charte n'y est pas.** D-61 a posé la dalle gris-bleu, le filet d'un pixel autour de chaque
  fenêtre et l'arrondi de 4 px. Le mail ignore tout cela : carte blanche sur fond `#fafafa`, gris
  zinc du paquet, ombre portée. Le bouton, lui, est déjà l'encre presque noire du paquet — le
  défaut n'est pas une couleur fausse, c'est le gabarit de démonstration au complet.

Le second point n'est pas de la coquetterie : D-45 fait du lien envoyé par mail **le seul chemin de
création de compte**. Ce mail est la première chose que voit un coureur du produit, et il décide
s'il clique. Un mail qui ne ressemble à rien atterrit dans les indésirables et ne se distingue pas
d'un hameçonnage.

Un détail à reprendre au passage : `RegistrationConfirmed` passe le code d'accès dans une ligne
en gras markdown (`**:code**`). Le code est la seule information du mail que le coureur doit
recopier, et il n'est plus jamais réaffiché. Il mérite un bloc, pas un mot en gras au milieu d'un
paragraphe.

## Périmètre fonctionnel

**Inclus**
- Un habillage de mail propre au produit : en-tête, corps, bouton, pied.
- L'habillage traduit en français, y compris le paragraphe de repli sous le bouton et le pied.
- Un bloc dédié au code d'accès dans le mail de confirmation, lisible et sélectionnable d'un geste.
- Les deux notifications existantes rendues dans ce gabarit, sans changer leurs textes.
- Une version texte qui tient debout seule, lien compris.
- Des tests sur le rendu : le lien y est, le code y est, aucune chaîne d'habillage anglaise ne
  subsiste.

**Exclu**
- Tout nouveau mail. Le rappel de la veille, la convocation, la relance n'existent pas et ne sont
  pas créés ici.
- Toute police distante et toute image distante. D-46 interdit déjà la requête tierce dans
  l'interface ; dans un mail, elle est en plus un traceur et un motif de mise en indésirable.
- Un mode sombre garanti. Les clients de messagerie en font ce qu'ils veulent, certains inversent
  d'autorité.
- Un écran de prévisualisation. Mailpit est déjà dans la pile de développement et affiche le
  rendu HTML et texte côte à côte.
- Le code d'accès de l'organisateur, qui s'affiche dans un terminal (BR-35) et ne passe pas par le
  mail.

**Dépendances** — BR-05 pour les deux notifications, D-61 pour la charte.

## La charte ne se transpose pas telle quelle

Trois règles de D-61 ne survivent pas au passage au mail, et il vaut mieux l'écrire que le
redécouvrir :

- **`oklch` n'est pas lisible par les clients de messagerie.** La palette doit être aplatie en
  hexadécimal dans le gabarit : c'est une quatrième déclaration. Elle n'échappe pas pour autant au
  contrôle — la conversion oklch → sRGB est exacte au canal près sur les six couleurs employées,
  donc un test compare l'hexadécimal du mail rendu au token converti de `app.css` au lieu de le
  figer à la main.
- **Les polices auto-hébergées ne s'affichent pas.** Instrument Sans et Martian Mono viennent de
  `resources/fonts/` par le fournisseur `local()` de Vite ; un mail n'a pas accès à ces fichiers, et
  les charger depuis un tiers est exclu. Le gabarit s'appuie donc sur des piles système, avec une
  pile monospace pour le code d'accès et les libellés en capitales — l'intention de D-61 est
  conservée, la police exacte ne l'est pas.
- **Pas de flex, pas de grid.** Un mail se compose en tableaux et en styles en ligne. Le gabarit ne
  partage aucun code avec le design system de l'interface, et c'est normal.

L'accent est un non-problème : le thème jour de D-61 n'en a pas — `--primary` y vaut l'encre — et
aucun des deux mails n'affiche de statut. Ces mails sont donc gris-bleu et encre, ce qui est aussi
la façon la plus sûre de traverser un client de messagerie intact.

## Règles métier

- Aucun texte de l'habillage n'est en anglais.
- Le lien d'inscription apparaît en clair dans le mail, en plus du bouton : un client qui refuse le
  HTML doit laisser au coureur un chemin praticable.
- Le code d'accès s'affiche dans une pile monospace, en un seul bloc, sans césure possible.
- La version texte contient le lien et le code, sans balisage résiduel.
- Aucune requête sortante n'est déclenchée à l'ouverture : ni police, ni image, ni pixel.
- Les textes de `lang/fr/mail.php` ne changent pas ; seule leur mise en forme change.

## Critères d'acceptation

```gherkin
Étant donné un coureur qui demande son lien d'inscription
Lorsque le mail est rendu
Alors son habillage est entièrement en français
Et le lien d'inscription y figure en clair sous le bouton
Et le rendu ne contient aucune requête vers un domaine tiers

Étant donné une inscription confirmée
Lorsque le mail de confirmation est rendu
Alors le code d'accès apparaît dans un bloc dédié
Et il est en un seul morceau, sans césure ni espace ajouté

Étant donné un client de messagerie qui n'affiche pas le HTML
Lorsqu'il rend la version texte du mail de lien
Alors le lien y est utilisable
Et aucun reste de balisage n'y apparaît

Étant donné les deux notifications du produit
Lorsque leurs mails sont rendus
Alors aucune chaîne d'habillage du paquet Laravel n'y subsiste
```

## Cas limites et erreurs

- Code d'accès coupé en deux par un client qui replie les lignes longues : c'est le défaut à ne pas
  laisser passer, puisque le coureur recopie ce code à la main.
- Adresse de lien très longue : elle doit rester cliquable et lisible, pas déborder de la colonne.
- Mail ouvert sur un téléphone en portrait : une seule colonne, bouton à portée du pouce.
- Client qui inverse les couleurs d'autorité : le mail reste lisible, même si ce n'est pas le
  rendu prévu.

## Impacts techniques

Deux voies existent. Surcharger les vues de `notifications::email` garde intact le code fluide des
deux notifications et donne un habillage unique à tous les mails futurs ; écrire une vue Blade par
notification donne un contrôle total mais duplique l'habillage. La première est préférable ici :
les deux notifications sont déjà écrites, leurs textes sont validés, et rien ne justifie de les
réécrire pour changer leur emballage. Le bloc du code d'accès est le seul élément qui demande un
composant en plus.

La version texte est produite par le même rendu markdown que le HTML. La surcharge des vues HTML ne
la casse pas, mais elle ne l'améliore pas non plus : c'est un point à vérifier explicitement, pas à
supposer.

Les chaînes d'habillage se traduisent en publiant les traductions du paquet plutôt qu'en recopiant
les phrases dans les vues, pour que `lang/fr` reste le seul endroit où le français se relit.

## Tâches

- [x] **T1** — Habillage du mail : en-tête, corps, bouton, pied, palette aplatie et piles système
  `1 pt`
- [x] **T2** — Habillage traduit en français, repli sous le bouton et pied compris `1 pt`
- [x] **T3** — Bloc du code d'accès, et vérification de la version texte des deux mails `1 pt`
