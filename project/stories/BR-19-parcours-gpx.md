# BR-19 — Afficher le parcours sur une carte

| | |
|---|---|
| **Epic** | 4 — Informations événement |
| **Statut** | À faire |
| **Estimation** | 8 pts |
| **Dépend de** | BR-03 |

## User story

En tant que **participant**,
Je veux **voir le tracé de la boucle sur une carte depuis mon téléphone**,
Afin de **savoir où je vais avant de partir et de reconnaître le parcours de nuit**.

## Contexte

Le gérant dispose d'une trace GPX de la boucle. Elle sert à la fois d'information avant la
course et de repère pendant. La carte doit être utilisable sur un téléphone, dehors.

## Périmètre fonctionnel

**Inclus**
- Dépôt d'un fichier GPX par le gérant.
- Une page « Parcours » affichant la carte, le tracé, le point de départ, la distance et le
  dénivelé quand la trace le fournit.
- Téléchargement du GPX par les participants.

**Exclu**
- L'édition du tracé dans l'application.
- Le suivi de position en temps réel des coureurs.
- Plusieurs parcours différents.

**Dépendances** — BR-03.

## Règles métier

- Seul le porteur de la permission `manage-route` dépose ou remplace le GPX.
- Un seul parcours actif par événement : un nouveau dépôt remplace le précédent.
- Le fichier est validé comme GPX réel avant d'être exploité, pas seulement sur son extension.
- Distance et dénivelé sont extraits de la trace **une seule fois**, à l'issue du dépôt, et
  conservés : la page n'analyse pas le fichier à chaque affichage.
- La distance extraite du GPX est une information de parcours. Elle ne remplace pas la distance
  de boucle saisie par le gérant, qui reste seule à servir aux calculs de vitesse (voir D-17).
- Si la trace ne porte pas d'altitude, le dénivelé n'est pas affiché plutôt que d'afficher zéro.
- La carte est consultable par tout utilisateur connecté dès que l'événement sort de `draft`.
- Le fond de carte est OpenStreetMap, affiché via Leaflet, avec l'attribution requise.

## Critères d'acceptation

```gherkin
Étant donné le gérant sur l'écran du parcours
Lorsqu'il dépose un fichier GPX valide
Alors le parcours est enregistré
Et sa distance est extraite de la trace

Étant donné un parcours enregistré
Lorsqu'un participant ouvre la page Parcours sur un téléphone
Alors la carte s'affiche avec le tracé et le point de départ
Et la distance est affichée
Et l'attribution OpenStreetMap est visible

Étant donné une trace GPX sans données d'altitude
Lorsque la page Parcours est affichée
Alors aucun dénivelé n'est affiché

Étant donné un fichier qui n'est pas un GPX valide
Lorsque le gérant le dépose
Alors le dépôt est refusé
Et le parcours précédent est conservé

Étant donné un parcours déjà en place
Lorsque le gérant dépose un nouveau GPX valide
Alors le nouveau tracé remplace l'ancien

Étant donné un participant connecté
Lorsqu'il tente de déposer un GPX
Alors l'action est refusée
```

## Cas limites et erreurs

- GPX très volumineux : le traitement d'extraction se fait en arrière-plan, sans bloquer l'écran.
- GPX contenant plusieurs traces : la première est retenue.
- Fond de carte indisponible : le tracé et les informations restent affichés.
- Aucun parcours déposé : la page l'indique sans afficher une carte vide.

## Impacts techniques

Un fichier GPX est un fichier XML fourni de l'extérieur : son analyse doit se faire sans
autoriser de références externes. L'extraction se fait une fois pour toutes, en tâche de
fond, pour ne pas relire le fichier à chaque visite.

## Tâches

- [ ] **T1** — Dépôt du GPX en collection Media Library, un seul parcours actif `2 pts`
- [ ] **T2** — Validation du GPX comme XML réel, analyse sans référence externe `2 pts`
- [ ] **T3** — Job d'extraction de la distance, du dénivelé et du point de départ `2 pts`
- [ ] **T4** — Page Parcours avec Leaflet, tracé et attribution, responsive `3 pts`
- [ ] **T5** — Tests : dépôt valide, fichier invalide, remplacement, trace sans altitude `2 pts`
