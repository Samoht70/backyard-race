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

## Priorité actuelle — lot 4 : tenir une adresse annoncée

Le lot 1 a ouvert les inscriptions, le lot 2 a mis le site en ligne le 2026-08-22, le lot 3 a rendu
son exploitation tenable par une personne seule. Les trois gestes du lot 3 ont été faits sur la
machine le même jour : la purge est passée, l'adresse de l'organisateur est configurée, l'événement
est relu.

Le lot 4 est arrêté le 2026-08-22, juste avant que l'adresse circule pour de vrai, et il répond à une
seule question posée au propriétaire : **est-ce que l'inscription est finie et déployable ?** Elle
l'est — le parcours va de l'accueil public au code d'accès reçu par mail, sans trou. Ce que le lot 4
ramasse, c'est ce qui casse quand ce parcours rencontre du monde : une file qui cesse d'être
consommée sans le dire, un coureur qui perd son code, une adresse mal tapée, une ouverture faite trop
tôt.

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

### Lot 2 — mettre en ligne · en ligne le 2026-08-22

L'epic 7 de bout en bout, BR-26 → BR-32, plus BR-35 et la reprise R-05, 52 pts. Le lot 1 n'avait
aucune valeur tant que rien n'était joignable depuis l'extérieur ; c'est la mise en ligne qui
transforme « l'inscription est développée » en « les inscriptions sont ouvertes ». D-19 rappelle
qu'une plateforme managée aurait fait tomber une vingtaine de ces points pour 30 à 60 €.

| Ordre | ID | Story | Pts | Où ça se joue | Reste |
|-------|----|-------|-----|----------------|-------|
| ✅ | [BR-27](stories/BR-27-image-docker-production.md) | Image et Compose de production | 9 | Dans le dépôt | — |
| ✅ | R-05 | Course publique, portée par [D-60](DECISIONS.md) | 5 | Dans le dépôt | — |
| ✅ | [BR-26](stories/BR-26-provisionner-vps-dokploy.md) | VPS et Dokploy | 9 | Sur la machine | — |
| ✅ | [BR-29](stories/BR-29-donnees-managees-sauvegardes.md) | MySQL, Redis, sauvegardes | 8 | Sur la machine | T6, la veille de la course |
| 🚧 | [BR-28](stories/BR-28-configuration-secrets-stockage.md) | Environnement, secrets, stockage objet | 5 | Les deux | T3, T4 |
| ✅ | [BR-30](stories/BR-30-workers-horizon-scheduler.md) | Files, Horizon, planificateur | 5 | Les deux | L'exercice à froid, avec BR-31 T5 |
| 🚧 | [BR-31](stories/BR-31-domaine-https-supervision.md) | Domaine, HTTPS, supervision | 5 | Sur la machine | T4, T5 |
| 🚧 | [BR-32](stories/BR-32-deploiement-dokploy.md) | Déploiement depuis Dokploy | 3 | Les deux | T3, T5 |
| ↪ | [BR-35](stories/BR-35-compte-organisateur-en-commande.md) | Compte organisateur en une commande | 3 | Dans le dépôt | Passe au lot 3, entière |

**52 pts, dont 36 livrés — et l'objectif du lot est atteint.** Le domaine répond en HTTPS, un compte
organisateur existe, l'événement est public, un coureur s'inscrit et reçoit son code par mail depuis
un worker qui tourne pour de vrai, les fichiers atterrissent dans un bucket hors machine, la base est
sauvegardée chaque jour et la restauration a été rejouée une fois. Le déploiement part de `main`,
migrations comprises, et le retour à la version précédente a été essayé.

Ce qui reste ne met plus rien en ligne : **ça prévient quand ça casse, ou ça dit ce qui tourne.**

- **BR-31 T4 — notification des erreurs applicatives.** La supervision externe couvre la machine
  morte, pas l'application qui répond faux.
- **BR-31 T5** — l'alerte n'a pas été éprouvée sur une extinction volontaire, seulement installée.
- **BR-32 T3 et T5** — la version déployée n'est pas lisible de l'extérieur, et rien n'empêche une
  poussée sur `main` de reconstruire l'image pendant la nuit de course.
- **BR-28 T3 et T4** — le cache de configuration ne se construit pas au démarrage, et `.env.example`
  ignore les variables que la production a fait apparaître.

**BR-35 sort du lot sans avoir été faite.** Le compte organisateur a été créé à la main le
2026-08-22, par la séquence `tinker` que la story voulait remplacer. Elle ne bloque donc plus rien,
et elle garde sa valeur entière : le code d'accès n'existe plus en clair, aucun geste ne le regénère,
et la prochaine installation rejouerait les quatre pièges que son contexte nomme. Elle passe au
lot 3.

BR-27 est passée avant BR-26 : elle était la seule story du lot à s'écrire et se vérifier entièrement
dans le dépôt, sans machine. R-05 a suivi la même logique, et le lien devait cesser d'ouvrir un
formulaire de connexion avant de circuler.

### Lot 3 — tenir la production à la main · clos le 2026-08-22

Trois stories, 11 pts, dont aucune ne touche au produit de course. Le lot 2 a rendu l'application
joignable ; le lot 3 rend son exploitation tenable par une personne seule, avant que l'adresse
circule pour de vrai.

| Ordre | ID | Story | Pts | Pourquoi dans le lot |
|-------|----|-------|-----|----------------------|
| ✅ | [BR-35](stories/BR-35-compte-organisateur-en-commande.md) | Compte organisateur en une commande | 3 | La porte, et le filet de BR-37 |
| ✅ | [BR-36](stories/BR-36-gabarit-de-mail.md) | Habiller les mails aux couleurs de la course | 3 | Le mail est le seul chemin de création de compte (D-45) |
| ✅ | [BR-37](stories/BR-37-purge-des-inscriptions.md) | Purger les inscriptions et les comptes coureurs | 5 | La production porte des données d'essai |

**11 pts, tous livrés.** L'ordre n'est pas indifférent :

- **BR-35 d'abord, close le 2026-08-22**, parce qu'elle est le filet de BR-37. Une commande qui
  supprime des comptes se lance plus sereinement quand une autre sait recréer celui de
  l'organisateur : `race:manager-account` crée le compte et regénère son code d'accès ([D-62](DECISIONS.md)).
- **BR-36 ensuite, close le 2026-08-22**, avant le premier vrai inscrit. Chaque coureur reçoit ce
  mail une fois, et cette fois-là ne se rattrape pas : qui s'inscrit avant garde le gabarit de
  démonstration du paquet. Aucune autre story du backlog n'a cette propriété d'irréversibilité.
- **BR-37 en dernier, close le 2026-08-22**, parce qu'éprouver BR-36 en production y crée de
  nouvelles inscriptions d'essai. Purger avant reviendrait à purger deux fois.
  `race:purge-registrations` est livrée et couverte ([D-64](DECISIONS.md)), et la purge a été passée
  sur la machine le 2026-08-22 : la production repart à zéro inscription.

**R-07 a suivi BR-37 le même jour.** Il n'y aura jamais qu'un seul manager, donc son adresse est une
donnée d'installation : `RACE_ORGANISER_EMAIL` épargne désormais un compte à la purge, et sert de
défaut à `race:manager-account`. Elle vient **en plus** du rôle, pas à sa place, pour qu'une variable
absente ou fautive retombe sur le comportement de BR-37 au lieu de fermer la porte
([D-65](DECISIONS.md)).

**BR-36 a été recalée en la prenant.** Son contexte s'appuyait sur D-46 — « un bouton bleu de
démonstration », la palette de D-46 à aplatir en hexadécimal — alors que R-06 avait révoqué le parti
monochrome et que D-61 avait posé la charte de l'instrument. Le travail demandé n'a pas changé ;
c'est la palette du site, thème jour, qui est passée dans le mail, et [D-63](DECISIONS.md) porte ce
que la livraison a appris — dont deux affirmations du contexte qui étaient périmées.

**BR-38 a été prise hors du lot**, et ses 3 pts ne s'y ajoutent pas. Le site tournait avec la marque
du paquet de démarrage : le losange de Laravel dans la barre, ses icônes dans l'onglet, et le nom du
produit nulle part. C'est le genre de défaut qui ne bloque personne et que tout le monde voit, donc
il ne méritait pas d'attendre la fin du lot 3 — mais il ne méritait pas non plus d'y entrer, parce
que le lot 3 parle des gestes d'exploitation et pas du produit. Elle a coûté un peu plus que son
habillage : le logo fourni était une capture de carte d'interface, et la forme a dû être remesurée
puis retracée en géométrie. [BR-38](stories/BR-38-identite-de-marque.md) porte le détail.

**Ce que le lot ne contenait pas** — le reliquat du lot 2. BR-30 T5, l'alerte sur file non consommée,
en est le seul point qui coûte dès aujourd'hui : elle prend la tête du lot 4. Le reste attend la
semaine de la course.

### Lot 4 — tenir une adresse annoncée · en cours

Trois stories et une tâche de reliquat, 11 pts. Aucune ne touche au moteur de course : le lot ferme
ce qui se voit dès qu'un inconnu arrive sur l'adresse, et il précède l'epic 2.

| Ordre | ID | Story | Pts | Pourquoi dans le lot |
|-------|----|-------|-----|----------------------|
| ✅ | [BR-30](stories/BR-30-workers-horizon-scheduler.md) T5 | Alerte sur file non consommée | 2 | Un worker mort emporte les mails de code en silence |
| ✅ | [BR-41](stories/BR-41-retour-en-brouillon.md) | Remettre l'événement en brouillon | 3 | Le demi-tour n'existe que tant qu'il n'y a personne |
| ✅ | [BR-39](stories/BR-39-suppression-inscription-et-compte.md) | Supprimer une inscription et son compte | 3 | La réponse à « j'ai perdu mon code » |
| 1 | [BR-40](stories/BR-40-page-erreur.md) | Rendre les refus et les erreurs dans le site | 3 | La dernière surface publique non habillée |

**11 pts, dont 8 livrés le 2026-08-22.** L'ordre suit ce que chaque entrée coûte si elle attend :

- **BR-30 T5 d'abord et close**, parce que c'était la seule qui coûtait déjà. Les deux mails du
  parcours — le lien signé et le code d'accès — sont mis en file, et un worker mort ne se signalait
  pas : le formulaire continuait de répondre « regarde tes mails » sans que personne ne reçoive rien.
  Deux signaux le disent maintenant, le sondage `up/queue` et le battement `race:queue-heartbeat`, et
  la surveillance externe les écoute ([D-67](DECISIONS.md)). C'était le reliquat du lot 2, il
  n'appartenait pas au lot 4, et il est passé devant.
- **BR-41 ensuite**, parce que sa condition d'usage est vraie aujourd'hui et fausse demain : le retour
  en brouillon exige zéro inscription, et l'adresse est sur le point de circuler. Ce n'est pas une
  fenêtre qui se referme définitivement — BR-39 et la purge savent recréer la condition — mais c'est
  le seul moment où le geste ne demande rien d'autre.
- **BR-39 ensuite et close**, parce que le premier « j'ai perdu mon code » arrive quelques jours
  après l'annonce. Le geste unitaire a fourni à la purge le second appelant qui lui manquait, et la
  logique de suppression a quitté la commande pour deux actions que les deux chemins partagent
  ([D-69](DECISIONS.md)). **BR-40 ferme le lot** : une adresse mal tapée ne coûte rien de mesurable.

**Ce que le lot ne contient pas** — le renvoi d'un code d'accès. C'était la voie évidente pour le
coureur qui a perdu son mail, et elle a été écartée : elle ouvre un second chemin
d'authentification, là où BR-39 se contente de libérer l'adresse et de laisser le parcours public
d'inscription refaire ce qu'il sait déjà faire ([D-66](DECISIONS.md)).

**Ce que le lot ne contient pas non plus** — BR-24, le dashboard participant. Son périmètre est
entièrement de la course : boucles réalisées, distance totale, prochain départ. Ses deux seuls
éléments utiles avant la course, les raccourcis vers le briefing et les documents, sont déjà posés
par BR-33. Elle reste avec les écrans de course, dont elle dépend.

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
| [BR-38](stories/BR-38-identite-de-marque.md) | Identité : la forme, le lockup et les icônes | 3 | ✅ Terminé |
| [BR-39](stories/BR-39-suppression-inscription-et-compte.md) | Supprimer une inscription et le compte qui va avec | 3 | ✅ Terminé |
| [BR-40](stories/BR-40-page-erreur.md) | Rendre les refus et les erreurs dans le site | 3 | 🔥 Lot 4 |
| [BR-41](stories/BR-41-retour-en-brouillon.md) | Remettre l'événement en brouillon | 3 | ✅ Terminé |

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
| [BR-36](stories/BR-36-gabarit-de-mail.md) | Habiller les mails aux couleurs de la course | 3 | ✅ Terminé |
| [BR-25](stories/BR-25-mon-dossard.md) | ~~Dossard imprimable~~ | — | ⛔ Abandonné |

### EPIC 7 — Déploiement

| ID | Story | Pts | Statut |
|----|-------|-----|--------|
| [BR-26](stories/BR-26-provisionner-vps-dokploy.md) | Provisionner le VPS et installer Dokploy | 9 | ✅ Terminé |
| [BR-27](stories/BR-27-image-docker-production.md) | Image et Compose de production | 9 | ✅ Terminé |
| [BR-28](stories/BR-28-configuration-secrets-stockage.md) | Configurer environnement, secrets et stockage objet | 5 | 🚧 En cours |
| [BR-29](stories/BR-29-donnees-managees-sauvegardes.md) | MySQL, Redis et sauvegardes hors machine | 8 | ✅ Terminé |
| [BR-30](stories/BR-30-workers-horizon-scheduler.md) | Files, Horizon et planificateur en production | 5 | ✅ Terminé |
| [BR-31](stories/BR-31-domaine-https-supervision.md) | Domaine, HTTPS et supervision | 5 | 🚧 En cours |
| [BR-32](stories/BR-32-deploiement-dokploy.md) | Déploiement depuis Dokploy et branche develop | 3 | 🚧 En cours |
| [BR-35](stories/BR-35-compte-organisateur-en-commande.md) | Créer le compte organisateur en une commande | 3 | ✅ Terminé |
| [BR-37](stories/BR-37-purge-des-inscriptions.md) | Purger les inscriptions et les comptes coureurs | 5 | ✅ Terminé |

Les quatre stories `En cours` sont en production et il leur manque une ou deux tâches, nommées dans
leur fichier sous « Ce qui reste au 2026-08-22 » et récapitulées dans le lot 2. Leurs points ne
comptent pas encore comme livrés : une story qui sert sans se signaler quand elle tombe n'est pas
finie.

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
| R-07 | Adresse de l'organisateur en configuration, en plus du rôle `manager` | 2 | ✅ Livrée | [D-65](DECISIONS.md) |

**Total : 38 stories actives + 7 reprises · 262 pts · 160 pts livrés (61 %)**

**Hors périmètre : 4 stories abandonnées, 32 pts non engagés** — voir [D-47](DECISIONS.md).

## Ordre conseillé

**Lot 1 — ouvrir les inscriptions (18 pts) — clos le 2026-08-20.**

**Lot 2 — mettre en ligne (52 pts) — objectif atteint le 2026-08-22.** L'ordre suivi : BR-27 → R-05
→ BR-26 → BR-29 → BR-30, les cinq entrées closes. Reste, dans cet ordre : BR-31 T4 et T5 →
BR-32 T3 et T5 → BR-28 T3 et T4.

**Lot 3 — tenir la production à la main (11 pts) — les trois stories closes le 2026-08-22**, et les
trois gestes faits sur la machine le même jour.

**Lot 4 — tenir une adresse annoncée (11 pts) — en cours.** L'ordre : BR-30 T5 → BR-41 → BR-39 →
BR-40. C'est le dernier lot avant le moteur, et le seul dont une entrée coûtait déjà quelque chose :
BR-30 T5 est close le 2026-08-22, sondage et battement livrés, surveillance inscrite. BR-41 est close
le même jour, avant que l'adresse ne circule : le demi-tour existe dans l'application, sous la seule
condition de zéro inscription. BR-39 est close le même jour aussi, et la logique de suppression est
désormais partagée avec la purge. **BR-40 est la dernière entrée du lot.**

**Le reste du reliquat du lot 2 avant le moteur, ou après ?** Après. BR-30 T5 est passée dans le lot 4
parce qu'une file qui cesse d'être consommée coûtait des coureurs dès aujourd'hui ; la version exposée
et le garde-fou de gel, eux, ne coûtent que la nuit de course, et il reste du temps.

**Ensuite, le moteur et les écrans de course** — BR-08 → BR-09 → BR-10 → BR-11 → BR-12 → BR-13 →
BR-14 → BR-15 → BR-16 → BR-24 → BR-20 → BR-23

Dix remarques sur cet ordre :

- Les deux premiers lots livrent un produit **incomplet mais utile**, et il est en ligne : un coureur
  s'inscrit, lit le briefing, télécharge le règlement et déclare son numéro PPS. Rien de la nuit de
  course n'existe encore, et ça n'a pas empêché d'ouvrir les inscriptions.
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
- BR-13 reste le porteur naturel de **Q-04**, l'écart de cible tactile relevé en D-46 : le bouton de
  validation a perdu un tiers de sa hauteur. **Q-02**, en revanche, ne l'attend plus : la page
  d'erreur est devenue publique le jour où l'adresse a circulé, et elle est sortie en story propre
  dans le lot 4 ([D-66](DECISIONS.md)).
- BR-35 a perdu son urgence sans rien perdre de sa valeur : le compte organisateur a été créé à la
  main le 2026-08-22, donc elle ne barre plus la porte d'entrée. Elle reprend la tête du lot 3 pour
  une autre raison — BR-37 supprime des comptes, et on lance mieux un balai quand on sait recoller
  ce qu'il emporterait par erreur.
- BR-36 ne dépendait d'aucune autre et se laissait avancer à n'importe quel moment. L'argument pour
  la prendre tôt n'était pas esthétique : le mail de lien est le seul chemin de création de compte
  (D-45), il partait en production dans le gabarit de démonstration du paquet, et c'est lui qui
  décide si un coureur clique.
- BR-37 ferme le lot 3 et non l'inverse, parce que les essais de BR-36 en production créeront eux
  aussi des comptes. Elle est aussi la seule story du backlog qui **détruise** : d'où le garde-fou
  sur le rôle `manager` dans ses règles métier, et d'où sa dépendance à BR-35.
- BR-30 était la story du lot 2 à ne pas bâcler, et elle a d'abord été à moitié entendue : le worker
  tournait, les mails de code partaient, et T5 est restée sur le carreau jusqu'au lot 4. Elle a fini
  par coûter plus que son intitulé — la voie évidente, la notification d'Horizon, est émise par le
  processus qu'elle devrait surveiller — et par couvrir une panne que personne n'avait nommée : le
  planificateur mort, qui arrête les éliminations de BR-11 sans que rien ne l'affiche
  ([D-67](DECISIONS.md)).

## Voir aussi

- [DECISIONS.md](DECISIONS.md) — choix d'architecture arrêtés et écarts assumés
- [QUESTIONS.md](QUESTIONS.md) — points ouverts qui attendent un arbitrage
