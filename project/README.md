# Backyard Race — pilotage projet

Mini-outil de gestion de projet, en fichiers Markdown versionnés avec le code.
Pas de Jira, pas d'outil externe : le backlog vit dans le repo.

## Comment ça marche

- Une **story** = un fichier `stories/BR-XX-slug.md`, au format Xefi (8 sections + découpage en tâches).
- Le **board** ci-dessous est la seule source de vérité pour l'avancement.
- Les **tâches** sont des cases à cocher dans le fichier de la story. On coche au fur et à mesure.
- Estimation : `1 pt = 1 h` pour un dev autonome, majorée par la complexité et l'incertitude.

Statuts : `À faire` · `En cours` · `En revue` · `Terminé` · `Bloqué`

Quand une story passe à `Terminé`, on met à jour la ligne du board **et** l'entête du fichier story.

## Board

### EPIC 0 — Socle technique

| ID | Story | Pts | Statut |
|----|-------|-----|--------|
| [BR-00](stories/BR-00-socle-technique.md) | Socle projet Laravel + Inertia + Sail | 9 | ✅ Terminé |

### EPIC 1 — Fondations

| ID | Story | Pts | Statut |
|----|-------|-----|--------|
| [BR-01](stories/BR-01-roles-permissions.md) | Rôles, permissions et policies | 8 | À faire |
| [BR-02](stories/BR-02-design-system.md) | Direction artistique et design system mobile-first | 13 | À faire |
| [BR-03](stories/BR-03-evenement.md) | Événement : modèle, statuts et configuration | 8 | À faire |
| [BR-04](stories/BR-04-horaires-boucles.md) | Horaires des boucles calculés automatiquement | 5 | À faire |
| [BR-05](stories/BR-05-inscription.md) | Inscription d'un participant | 8 | À faire |
| [BR-06](stories/BR-06-gestion-inscriptions.md) | Gestion des inscriptions par le gérant | 5 | À faire |
| [BR-07](stories/BR-07-numero-dossard.md) | Attribution automatique du numéro de dossard | 5 | À faire |

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
| [BR-15](stories/BR-15-polling.md) | Rafraîchissement léger de l'état de course | 5 | À faire |
| [BR-16](stories/BR-16-detail-participant.md) | Détail d'un participant et historique | 5 | À faire |

### EPIC 4 — Informations événement

| ID | Story | Pts | Statut |
|----|-------|-----|--------|
| [BR-17](stories/BR-17-briefing.md) | Briefing éditable | 5 | À faire |
| [BR-18](stories/BR-18-documents.md) | Documents de l'événement | 8 | À faire |
| [BR-19](stories/BR-19-parcours-gpx.md) | Parcours GPX et carte | 8 | À faire |

### EPIC 5 — Après-course

| ID | Story | Pts | Statut |
|----|-------|-----|--------|
| [BR-20](stories/BR-20-fin-evenement-classement.md) | Fin de l'événement et classement final | 8 | À faire |
| [BR-21](stories/BR-21-statistiques.md) | Statistiques de l'événement | 8 | À faire |
| [BR-22](stories/BR-22-galerie-photos.md) | Galerie photos | 8 | À faire |
| [BR-23](stories/BR-23-page-resultats.md) | Page de résultats publique | 5 | À faire |

### EPIC 6 — Expérience participant

| ID | Story | Pts | Statut |
|----|-------|-----|--------|
| [BR-24](stories/BR-24-dashboard-participant.md) | Dashboard participant | 8 | À faire |
| [BR-25](stories/BR-25-mon-dossard.md) | Dossard imprimable | 8 | À faire |

### EPIC 7 — Déploiement


| ID | Story | Pts | Statut |
|----|-------|-----|--------|
| [BR-26](stories/BR-26-provisionner-vps-dokploy.md) | Provisionner le VPS et installer Dokploy | 9 | À faire |
| [BR-27](stories/BR-27-image-docker-production.md) | Image et Compose de production | 9 | À faire |
| [BR-28](stories/BR-28-configuration-secrets-stockage.md) | Configurer environnement, secrets et stockage objet | 5 | À faire |
| [BR-29](stories/BR-29-donnees-managees-sauvegardes.md) | MySQL, Redis et sauvegardes hors machine | 8 | À faire |
| [BR-30](stories/BR-30-workers-horizon-scheduler.md) | Files, Horizon et planificateur en production | 5 | À faire |
| [BR-31](stories/BR-31-domaine-https-supervision.md) | Domaine, HTTPS et supervision | 5 | À faire |
| [BR-32](stories/BR-32-deploiement-dokploy.md) | Déploiement depuis Dokploy et branche develop | 3 | À faire |

**Total : 33 stories · 236 pts · 9 pts livrés**

## Ordre conseillé

**Métier** — BR-01 → BR-02 → BR-03 → BR-04 → BR-05 → BR-06 → BR-07 → BR-08 → BR-09 → BR-10 →
BR-11 → BR-12 → BR-13 → BR-14 → BR-15 → BR-16 → BR-17 → BR-18 → BR-19 → BR-20 → BR-21 →
BR-22 → BR-23 → BR-24 → BR-25

**Déploiement** — BR-26 → BR-27 → BR-28 → BR-29 → BR-30 → BR-31 → BR-32

Trois remarques sur cet ordre :

- BR-02 (design system) est volontairement tôt : toutes les stories d'écran s'appuient dessus.
- BR-09 et BR-11 sont le cœur métier — c'est là que les tests comptent le plus.
- L'epic 7 n'attend pas la fin du métier. BR-26 à BR-29 peuvent être menées dès que le moteur
  de course tourne (fin d'epic 2) : mettre en ligne tôt évite de découvrir les surprises
  d'hébergement la semaine de l'événement. BR-30 est la story à ne pas bâcler — sans worker en
  production, les éliminations automatiques ne tombent pas, et rien ne le signale à l'écran.

## Voir aussi

- [DECISIONS.md](DECISIONS.md) — choix d'architecture arrêtés et écarts assumés
- [QUESTIONS.md](QUESTIONS.md) — points ouverts qui attendent un arbitrage
