# BR-32 — Déployer depuis Dokploy, avec une branche `develop`

| | |
|---|---|
| **Epic** | 7 — Déploiement |
| **Statut** | 🚧 En cours |
| **Estimation** | 3 pts |
| **Dépend de** | BR-31 |

## User story

En tant que **développeur**,
Je veux **mettre en production d'un clic, depuis une branche qui ne contient que du code prêt**,
Afin de **choisir quand je livre, sans monter un pipeline pour un site qui sert une nuit**.

## Contexte

Pas de déploiement automatique : le déclenchement est manuel depuis Dokploy (voir D-21). Ce qui
rend ce choix tenable, c'est la séparation des branches — au moment du clic, `main` ne contient
que du code déjà passé par l'intégration continue et jugé prêt.

L'intégration continue existe déjà dans le dépôt. Cette story arrange ce qui l'entoure : les
branches, le geste de déploiement, et la marche arrière.

## Périmètre fonctionnel

**Inclus**
- Le flux `feature → develop → main`.
- L'intégration continue sur `main`, sur `develop` et sur chaque pull request.
- La protection de `main` : rien n'y entre sans passer par une pull request au CI vert.
- Le déploiement déclenché à la main depuis Dokploy, depuis `main`.
- Le retour à la version précédente, essayé une fois.
- Le gel des déploiements pendant l'événement.

**Exclu**
- Tout déclenchement automatique du déploiement à la fusion.
- Le déploiement de branches ou d'environnements éphémères.
- Le déploiement progressif par tranches de trafic.

**Dépendances** — BR-31.

## Règles métier

- `develop` est la branche de travail, `main` est ce qui part en production.
- Une fonctionnalité passe par une pull request vers `develop`. `main` ne reçoit que `develop`.
- **Rien n'entre sur `main` sans une intégration continue verte** : la protection de branche le
  garantit, ce n'est pas une discipline de mémoire.
- Le déploiement se fait depuis Dokploy, sur la branche `main`, sur décision explicite.
- Le déploiement exécute dans cet ordre : construction de l'image, migrations, bascule du
  conteneur, redémarrage des workers. Les migrations ne sont pas un geste manuel séparé — un
  déploiement qui les oublierait laisserait le nouveau code sur l'ancien schéma.
- Un échec à n'importe quelle étape laisse la version précédente en service.
- La version déployée est identifiable : on doit pouvoir dire quel commit tourne en production.
- Le retour à la version précédente est un déploiement antérieur rejoué depuis Dokploy, **sans
  reconstruction**, et il a été essayé au moins une fois avant l'événement.
- **Aucun déploiement pendant que l'événement est en cours**, sauf correction indispensable
  décidée par le propriétaire du projet.

## Critères d'acceptation

```gherkin
Étant donné une pull request vers develop dont les tests échouent
Lorsqu'on tente de la fusionner
Alors la fusion est refusée

Étant donné du code prêt sur develop, avec une intégration continue verte
Lorsqu'il est fusionné sur main puis déployé depuis Dokploy
Alors l'application en ligne sert le nouveau code
Et les migrations ont été appliquées
Et les workers ont redémarré

Étant donné un déploiement dont les migrations échouent
Lorsqu'il s'interrompt
Alors la version précédente reste en service
Et l'échec est visible dans Dokploy

Étant donné une version fraîchement déployée qui se révèle défaillante
Lorsqu'on rejoue le déploiement précédent depuis Dokploy
Alors l'application sert de nouveau la version précédente sans reconstruction

Étant donné l'application en ligne
Lorsqu'on interroge la version déployée
Alors le commit correspondant est identifiable

Étant donné un déploiement en cours
Lorsqu'un participant navigue dans l'application
Alors il ne voit pas d'erreur de service
```

## Cas limites et erreurs

- Clic sur « Deploy » sans avoir regardé le CI : c'est le risque assumé de D-21, et la protection de `main` est ce qui le borne.
- Migration non réversible déjà appliquée quand on revient en arrière : rejouer l'ancien déploiement ne suffit pas, la sauvegarde hors machine de BR-29 devient le vrai filet.
- Construction qui remplit le disque du VPS : la purge des images de BR-26 doit tourner, sinon le déploiement suivant échoue faute de place.
- Déploiement lancé par inadvertance pendant la course : le gel doit être un garde-fou explicite, pas une consigne orale.
- Correction urgente pendant l'événement : elle part de `main`, jamais d'une branche de travail non testée.

## Impacts techniques

Le vrai risque n'est pas le déploiement mais la migration : le code revient en arrière, une
migration destructive non. La règle de gel pendant l'événement et la sauvegarde manuelle
d'avant-course sont ce qui protège la soirée.

## Tâches

- [x] **T1** — Créer `develop` et protéger `main` : pull request obligatoire, CI vert exigé `1 pt`
- [x] **T2** — Configurer le déploiement Dokploy depuis `main` : construction, migrations, bascule, workers `1 pt`
- [ ] **T3** — Exposer la version déployée `1 pt`
- [x] **T4** — Essayer le retour à la version précédente une fois `1 pt`
- [ ] **T5** — Garde-fou de gel des déploiements pendant l'événement `1 pt`

## Ce qui reste au 2026-08-22

Dokploy construit et bascule depuis `main` — migrations comprises, workers redémarrés — et le retour
à la version précédente a été exécuté une fois pour de vrai, pas seulement supposé possible. `develop`
existe et porte le travail ; la protection de `main` est un réglage GitHub que le dépôt ne montre pas,
elle se relit dans l'interface.

- **T3** — La version déployée n'est pas lisible de l'extérieur. Devant un écran qui ne se comporte
  pas comme attendu, rien ne dit quel code tourne, et le premier réflexe — redéployer — efface la
  question.
- **T5** — Aucun garde-fou de gel. Rien n'empêche une poussée sur `main` de reconstruire l'image
  pendant la nuit de course, au moment où l'application est le moins remplaçable.
