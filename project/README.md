# Backyard Race — pilotage projet

Mini-outil de gestion de projet, en fichiers Markdown versionnés avec le code.
Pas de Jira, pas d'outil externe : le backlog vit dans le repo.

## Comment ça marche

- Une **story** = un fichier `stories/BR-XX-slug.md`, au format Xefi (8 sections + découpage en tâches).
- Le **board** ci-dessous est la seule source de vérité pour l'avancement.
- Les **tâches** sont des cases à cocher dans le fichier de la story. On coche au fur et à mesure.
- Estimation : `1 pt = 1 h` pour un dev autonome, majorée par la complexité et l'incertitude.

Statuts : `À faire` · `En cours` · `En revue` · `Terminé` · `Bloqué` · `Abandonné`

Une story du lot prioritaire porte `🔥 Lot N` à la place de `À faire` : c'est le même statut, signalé. Voir la section « Priorité actuelle » ci-dessous.

Quand une story passe à `Terminé`, on met à jour la ligne du board **et** l'entête du fichier story.

Une story `Abandonné` garde son fichier : il porte la raison, sous un titre « Pourquoi cette story
n'est pas faite ». Ses points sortent du total — un backlog qui perd une story sans dire pourquoi la
voit revenir.

Le travail demandé en cours de route, qui refait ce qu'une story avait déjà livré, n'a pas de
fichier de story : il vit dans une **décision** et prend une ligne dans le tableau des reprises.

## Priorité actuelle — lot 2 : mettre en ligne

Arrêtée le 2026-08-20 avec le propriétaire. L'objectif n'est plus de finir le produit dans l'ordre
des epics, mais de **mettre en ligne le parcours d'inscription au plus tôt**, pour pouvoir dire aux
coureurs de s'inscrire pendant que le moteur de course s'écrit.

### Lot 1 — ouvrir les inscriptions · clos le 2026-08-20

Le socle était déjà là : BR-05 avait livré l'inscription, BR-06 sa gestion, BR-07 le dossard, et
BR-17 le briefing. Ce qui manquait tenait en quatre entrées : trois stories et une reprise.

| Ordre | ID | Story | Pts | Pourquoi dans le lot |
|-------|----|-------|-----|----------------------|
| ✅ | [BR-17](stories/BR-17-briefing.md) | Briefing éditable | 2 | Dit comment se déroule l'événement |
| ✅ | [BR-18](stories/BR-18-documents.md) | Documents de l'événement, GPX compris | 4 | Règlement, consignes, logement, trace |
| ✅ | [BR-34](stories/BR-34-numero-pps.md) | Numéro PPS demandé à l'inscription | 2 | Le dernier champ qui manque au formulaire |
| ✅ | R-04 | Formulaire d'inscription en étapes | 5 | Reprise de l'écran de D-45, portée par [D-50](DECISIONS.md) |
| ✅ | [BR-33](stories/BR-33-acces-parcours-coureur.md) | Accès du coureur à son inscription | 5 | Branche le tout dans la navigation |

**18 pts, tous livrés.** BR-34 est passée avant R-04 pour que le découpage en étapes place un
formulaire déjà complet, plutôt que d'avoir à loger un champ de plus après coup. BR-33 a fermé le
lot plutôt qu'elle ne l'a ouvert : c'est elle qui pose les entrées de navigation vers le briefing,
les documents et l'inscription, et il serait absurde d'y revenir trois fois.

**Ce que le lot ne contenait pas** — le moteur de course (epic 2), les écrans de course (epic 3) et
le dashboard participant BR-24, qui dépend de BR-08. Un coureur inscrit consulte son inscription et
les informations de la course ; il ne voit encore aucune boucle, parce qu'aucune n'existe.

### Lot 2 — mettre en ligne · en cours

L'epic 7 de bout en bout, BR-26 → BR-32, plus BR-35 et la reprise R-05, 52 pts. Le lot 1 n'a
aucune valeur tant que rien n'est joignable depuis l'extérieur ; c'est la mise en ligne qui
transforme « l'inscription est développée » en « les inscriptions sont ouvertes ». D-19 rappelle
qu'une plateforme managée ferait tomber une vingtaine de ces points pour 30 à 60 €.

| Ordre | ID | Story | Pts | Où ça se joue |
|-------|----|-------|-----|----------------|
| 1 | [BR-26](stories/BR-26-provisionner-vps-dokploy.md) | VPS et Dokploy | 9 | Sur la machine, à la main du propriétaire |
| ✅ | [BR-27](stories/BR-27-image-docker-production.md) | Image et Compose de production | 9 | Dans le dépôt |
| 2 | [BR-35](stories/BR-35-compte-organisateur-en-commande.md) | Compte organisateur en une commande | 3 | Dans le dépôt |
| 3 | [BR-28](stories/BR-28-configuration-secrets-stockage.md) | Environnement, secrets, stockage objet | 5 | Les deux |
| 4 | [BR-29](stories/BR-29-donnees-managees-sauvegardes.md) | MySQL, Redis, sauvegardes | 8 | Sur la machine |
| 5 | [BR-30](stories/BR-30-workers-horizon-scheduler.md) | Files, Horizon, planificateur | 5 | Les deux |
| 6 | [BR-31](stories/BR-31-domaine-https-supervision.md) | Domaine, HTTPS, supervision | 5 | Sur la machine |
| 7 | [BR-32](stories/BR-32-deploiement-dokploy.md) | Déploiement depuis Dokploy | 3 | Les deux |
| ✅ | R-05 | Course publique, portée par [D-60](DECISIONS.md) | 5 | Dans le dépôt |

**52 pts, dont 14 livrés.** BR-27 est passée avant BR-26 : elle est la seule story du lot qui
s'écrive et se vérifie entièrement dans le dépôt, sans machine. Le reste attend un VPS.

BR-35 est entrée dans le lot le 2026-08-21, relevée sur la machine : la base était migrée et il
n'existait aucun moyen d'entrer dans l'application. Elle prend le rang 2 parce qu'elle bloque tout
ce qui se vérifie par l'écran — la course elle-même naît de l'écran d'organisation, donc derrière
l'authentification.

R-05 est entrée dans le lot le 2026-08-22, sur demande du propriétaire, et livrée le même jour.
Elle ne bloque pas la mise en ligne — elle décide de ce qu'on en fait : le lot 2 rend l'application
joignable, R-05 décide de ce que voit la personne à qui on envoie l'adresse. Elle est passée tôt
parce qu'elle se vérifie entièrement dans le dépôt, comme BR-27, et parce que le lien devait cesser
d'ouvrir un formulaire de connexion avant de circuler.

BR-30 est la story à ne pas bâcler. Le README le disait des éliminations automatiques ; c'est vrai
bien avant elles — `RegistrationLink` est `ShouldQueue`, donc **sans worker en production, personne
ne peut créer de compte**. Le lot 1 en dépend déjà.

## Board

### EPIC 0 — Socle technique

| ID | Story | Pts | Statut |
|----|-------|-----|--------|
| [BR-00](stories/BR-00-socle-technique.md) | Socle projet Laravel + Inertia + Sail | 9 | ✅ Terminé |

### EPIC 1 — Fondations

| ID | Story | Pts | Statut |
|----|-------|-----|--------|
| [BR-01](stories/BR-01-roles-permissions.md) | Rôles, permissions et policies | 8 | ✅ Terminé |
| [BR-02](stories/BR-02-design-system.md) | Direction artistique et design system mobile-first | 13 | ✅ Terminé |
| [BR-03](stories/BR-03-evenement.md) | Événement : modèle, statuts et configuration | 8 | ✅ Terminé |
| [BR-04](stories/BR-04-horaires-boucles.md) | Horaires des boucles calculés automatiquement | 5 | ✅ Terminé |
| [BR-05](stories/BR-05-inscription.md) | Inscription d'un participant | 8 | ✅ Terminé |
| [BR-06](stories/BR-06-gestion-inscriptions.md) | Gestion des inscriptions par le gérant | 5 | ✅ Terminé |
| [BR-07](stories/BR-07-numero-dossard.md) | Attribution automatique du numéro de dossard | 5 | ✅ Terminé |
| [BR-34](stories/BR-34-numero-pps.md) | Numéro PPS demandé à l'inscription | 2 | ✅ Terminé |

### EPIC 2 — Moteur de course

| ID | Story | Pts | Statut |
|----|-------|-----|--------|
| [BR-08](stories/BR-08-tours-et-laps.md) | Tours de course et boucles des participants | 8 | À faire |
| [BR-09](stories/BR-09-validation-tour.md) | Validation d'une boucle par le gérant | 8 | À faire |
| [BR-10](stories/BR-10-abandon.md) | Abandon volontaire déclaré par le gérant | 5 | À faire |
| [BR-11](stories/BR-11-elimination-automatique.md) | Élimination automatique à expiration du tour | 8 | À faire |
| [BR-12](stories/BR-12-correction-tour.md) | Correction exceptionnelle d'une boucle | 5 | À faire |

### EPIC 3 — Interface de course

| ID | Story | Pts | Statut |
|----|-------|-----|--------|
| [BR-13](stories/BR-13-dashboard-gerant.md) | Dashboard gérant mobile-first | 13 | À faire |
| [BR-14](stories/BR-14-tableau-coureurs.md) | Tableau des coureurs et filtres | 8 | À faire |
| [BR-15](stories/BR-15-polling.md) | Rafraîchissement léger de l'état de course | 2 | À faire |
| [BR-16](stories/BR-16-detail-participant.md) | Détail d'un coureur, déplié dans le tableau | 2 | À faire |

### EPIC 4 — Informations événement

| ID | Story | Pts | Statut |
|----|-------|-----|--------|
| [BR-17](stories/BR-17-briefing.md) | Briefing éditable | 2 | ✅ Terminé |
| [BR-18](stories/BR-18-documents.md) | Documents de l'événement, GPX compris | 4 | ✅ Terminé |
| [BR-19](stories/BR-19-parcours-gpx.md) | ~~Parcours GPX et carte~~ | — | ⛔ Abandonné |

### EPIC 5 — Après-course

| ID | Story | Pts | Statut |
|----|-------|-----|--------|
| [BR-20](stories/BR-20-fin-evenement-classement.md) | Fin de l'événement et classement final | 8 | À faire |
| [BR-21](stories/BR-21-statistiques.md) | ~~Statistiques de l'événement~~ | — | ⛔ Abandonné |
| [BR-22](stories/BR-22-galerie-photos.md) | ~~Galerie photos~~ | — | ⛔ Abandonné |
| [BR-23](stories/BR-23-page-resultats.md) | Page de résultats et chiffres de l'événement | 8 | À faire |

### EPIC 6 — Expérience participant

| ID | Story | Pts | Statut |
|----|-------|-----|--------|
| [BR-33](stories/BR-33-acces-parcours-coureur.md) | Accès du coureur à son inscription | 5 | ✅ Terminé |
| [BR-24](stories/BR-24-dashboard-participant.md) | Dashboard participant | 8 | À faire |
| [BR-36](stories/BR-36-gabarit-de-mail.md) | Habiller les mails aux couleurs de la course | 3 | À faire |
| [BR-25](stories/BR-25-mon-dossard.md) | ~~Dossard imprimable~~ | — | ⛔ Abandonné |

### EPIC 7 — Déploiement

| ID | Story | Pts | Statut |
|----|-------|-----|--------|
| [BR-26](stories/BR-26-provisionner-vps-dokploy.md) | Provisionner le VPS et installer Dokploy | 9 | 🔥 Lot 2 |
| [BR-27](stories/BR-27-image-docker-production.md) | Image et Compose de production | 9 | ✅ Terminé |
| [BR-28](stories/BR-28-configuration-secrets-stockage.md) | Configurer environnement, secrets et stockage objet | 5 | 🔥 Lot 2 |
| [BR-29](stories/BR-29-donnees-managees-sauvegardes.md) | MySQL, Redis et sauvegardes hors machine | 8 | 🔥 Lot 2 |
| [BR-30](stories/BR-30-workers-horizon-scheduler.md) | Files, Horizon et planificateur en production | 5 | 🔥 Lot 2 |
| [BR-31](stories/BR-31-domaine-https-supervision.md) | Domaine, HTTPS et supervision | 5 | 🔥 Lot 2 |
| [BR-32](stories/BR-32-deploiement-dokploy.md) | Déploiement depuis Dokploy et branche develop | 3 | 🔥 Lot 2 |
| [BR-35](stories/BR-35-compte-organisateur-en-commande.md) | Créer le compte organisateur en une commande | 3 | 🔥 Lot 2 |

### Reprises hors backlog

Refontes demandées en cours de route, qui reprennent ce qu'une story avait déjà livré. Elles n'ont
pas de fichier de story : c'est la décision qui les porte, et elles prennent une ligne ici.

| ID | Reprise | Pts | Statut | Décision |
|----|---------|-----|--------|----------|
| R-01 | Authentification réduite au mot de passe, réglages réduits au profil, thème dans la navbar | 5 | ✅ Livrée | [D-43](DECISIONS.md), D-44 |
| R-02 | Inscription par mail en cinq étapes, code d'accès en guise de mot de passe | 8 | ✅ Livrée | [D-45](DECISIONS.md) |
| R-03 | Direction artistique « Tableau des départs » à la place de « Corral » | 8 | ✅ Livrée | [D-46](DECISIONS.md) |
| R-04 | Formulaire d'inscription en quatre étapes au lieu d'une page scrollable | 5 | ✅ Livrée | [D-50](DECISIONS.md), D-54 |
| R-05 | Course publique : l'accueil porte l'événement, les documents s'ouvrent aux invités | 5 | ✅ Livrée | [D-60](DECISIONS.md) |
| R-06 | Charte de l’instrument, et primitives reka-ui à la place du starter kit | 5 | ✅ Livrée | [D-61](DECISIONS.md) |

**Total : 33 stories actives + 6 reprises · 243 pts · 119 pts livrés (49 %)**

**Hors périmètre : 4 stories abandonnées, 32 pts non engagés** — voir [D-47](DECISIONS.md).

## Ordre conseillé

**Lot 1 — ouvrir les inscriptions (18 pts) — clos.**

**Lot 2, en cours — mettre en ligne (52 pts)** — BR-27 (faite) → BR-26 → BR-35 → BR-28 → BR-29 →
BR-30 → BR-31 → BR-32, R-05 (faite)

**Ensuite, le moteur et les écrans de course** — BR-08 → BR-09 → BR-10 → BR-11 → BR-12 → BR-13 →
BR-14 → BR-15 → BR-16 → BR-24 → BR-20 → BR-23

Neuf remarques sur cet ordre :

- Les deux premiers lots livrent un produit **incomplet mais utile** : un coureur s'inscrit, lit le
  briefing, télécharge le règlement et déclare son numéro PPS. Rien de la nuit de course n'existe
  encore, et ça ne l'empêche pas d'ouvrir les inscriptions.
- BR-33 est venue en dernier de son lot parce qu'elle branche la navigation vers ce que BR-17,
  BR-18 et BR-34 ont posé, et vers ce que R-04 a remis en forme.
- R-04 est une **reprise**, pas une story : elle refait l'écran que D-45 avait livré, donc elle vit
  dans une décision (D-50, mise en œuvre par D-54) et dans le tableau des reprises, comme R-01 à
  R-03. R-05 suit la même règle : elle rouvre la navigation de BR-33 et les documents de BR-18, et
  c'est D-60 qui la porte. R-06 aussi : elle révoque le parti monochrome de D-46 et retire la couche
  `components/ui/`, portée par D-61.
- BR-24 sort de la fin du backlog pour rejoindre les écrans de course : elle dépend de BR-08 et
  reprend l'accueil que BR-33 a livré.
- BR-09 et BR-11 restent le cœur métier — c'est là que les tests comptent le plus.
- BR-13 est le porteur naturel de **Q-02** (page d'erreur Inertia) et de **Q-04**, l'écart de cible
  tactile relevé en D-46 : le bouton de validation a perdu un tiers de sa hauteur.
- BR-35 passe tôt parce qu'elle est la porte d'entrée : sans compte organisateur, aucun écran du
  lot 1 ne se vérifie en production, et la course n'existe pas — c'est l'écran d'organisation qui
  la crée. Elle tient dans le dépôt, comme BR-27, donc elle n'attend rien.
- BR-36 ne dépend d'aucune autre et se laisse avancer à n'importe quel moment. L'argument pour la
  prendre tôt n'est pas esthétique : le mail de lien est le seul chemin de création de compte
  (D-45), il part déjà en production avec un habillage anglais, et c'est lui qui décide si un
  coureur clique.
- BR-30 est la story du lot 2 à ne pas bâcler — sans worker en production, les éliminations
  automatiques ne tombent pas, et rien ne le signale à l'écran.

## Voir aussi

- [DECISIONS.md](DECISIONS.md) — choix d'architecture arrêtés et écarts assumés
- [QUESTIONS.md](QUESTIONS.md) — points ouverts qui attendent un arbitrage
