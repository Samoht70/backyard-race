# BR-26 — Provisionner le VPS et installer Dokploy

| | |
|---|---|
| **Epic** | 7 — Déploiement |
| **Statut** | À faire |
| **Estimation** | 9 pts |
| **Dépend de** | BR-00 |

## User story

En tant que **propriétaire du projet**,
Je veux **un VPS commandé, durci et prêt à recevoir des déploiements**,
Afin de **mettre la course en ligne sur une machine que je maîtrise, sans y passer mes soirées**.

## Contexte

Il n'y a pas encore de machine. Cette story la commande et la prépare, avec Dokploy comme couche
de déploiement (voir D-19). C'est le point où l'on assume de tenir soi-même le système, donc le
point où l'on met en place ce qui évite d'avoir à y revenir.

## Périmètre fonctionnel

**Inclus**
- Le choix et la commande de la machine, en facturation mensuelle résiliable.
- Vérification des prérequis : virtualisation, système, ressources.
- Durcissement : pare-feu, accès SSH, mises à jour de sécurité automatiques.
- Installation de Dokploy et accès à son interface.
- Le nom de domaine pointé sur la machine, prêt pour BR-31.
- Une surveillance de l'espace disque.

**Exclu**
- Le déploiement de l'application : BR-27 et suivantes.
- La base de données et Redis : BR-29.
- Un second environnement.

**Dépendances** — BR-00.

## Règles métier

- La facturation est **mensuelle et résiliable**. Aucune offre à engagement de 24 ou 48 mois,
  quel qu'en soit le tarif d'appel : le site sert une fois (D-20).
- Le VPS doit être une virtualisation complète de type KVM : Docker n'est pas exploitable
  sur un hébergement mutualisé ou une virtualisation par conteneur du fournisseur.
- Compter **2 Go de mémoire au minimum** : Dokploy, Traefik, l'application, un worker, MySQL et
  Redis cohabitent sur la même machine.
- La machine est neuve et ne sert qu'à ce projet : rien d'autre ne doit occuper les ports 80 et 443.
- Système Linux récent et supporté, avec accès root ou sudo.
- Les ports 80 et 443 sont ouverts depuis Internet. **Tous les autres sont fermés**, en
  particulier le port de l'interface Dokploy, restreint à l'adresse de l'administrateur.
- L'accès SSH se fait par clé, jamais par mot de passe, et jamais directement en root.
- Les mises à jour de sécurité du système sont appliquées automatiquement.
- Aucun autre service ne doit occuper les ports 80 et 443 : le frontal Traefik de Dokploy doit
  pouvoir s'y attacher. Ce frontal est fourni par Dokploy — on n'en déclare pas un second
  (voir D-19).
- L'espace disque est surveillé : les images et les journaux Docker le remplissent avec le
  temps, et un disque plein arrête tout sans prévenir.
- Le domaine est en place **plusieurs jours avant** l'événement, le temps de la propagation DNS.

## Critères d'acceptation

```gherkin
Étant donné le VPS fraîchement préparé
Lorsqu'on tente une connexion SSH par mot de passe
Alors elle est refusée
Et la connexion par clé fonctionne

Étant donné le VPS préparé
Lorsqu'on scanne ses ports depuis Internet
Alors seuls 80 et 443 répondent

Étant donné Dokploy installé
Lorsque l'administrateur ouvre son interface depuis son adresse
Alors il y accède
Et depuis une autre adresse, l'accès est refusé

Étant donné le domaine configuré
Lorsqu'on résout son enregistrement DNS
Alors il pointe sur l'adresse du VPS

Étant donné la machine en fonctionnement
Lorsque l'espace disque descend sous un seuil défini
Alors une alerte est émise
```

## Cas limites et erreurs

- Offre alléchante mais engageante : le tarif de renouvellement d'une promotion pluriannuelle
  dépasse largement son tarif d'appel, pour un usage d'une nuit.
- Ressources trop justes : le worker est le premier processus que le système tue en cas de
  manque de mémoire, et cette panne est silencieuse (voir BR-30).
- Machine livrée avec un panneau de contrôle qui occupe déjà 80 et 443 : le désinstaller avant
  d'installer Dokploy.
- Port 25 sortant bloqué par l'hébergeur : sans effet ici, aucun mail métier n'est prévu (D-18).
- Redémarrage de la machine après une mise à jour du noyau : les conteneurs doivent repartir seuls.

## Impacts techniques

C'est la story qui matérialise le choix de D-19 : à partir d'ici, la disponibilité de
l'application dépend d'une machine dont personne d'autre ne s'occupe. Ce qui est mis en place
ici — pare-feu, mises à jour automatiques, alerte disque — est ce qui évite que la panne arrive
la nuit de la course.

## Tâches

- [ ] **T1** — Choisir et commander le VPS : KVM, 2 Go minimum, facturation mensuelle résiliable `2 pts`
- [ ] **T2** — Durcir la machine : pare-feu, SSH par clé sans root, mises à jour automatiques `2 pts`
- [ ] **T3** — Installer Dokploy et restreindre l'accès à son interface `2 pts`
- [ ] **T4** — Pointer le domaine sur le VPS `1 pt`
- [ ] **T5** — Surveillance de l'espace disque et purge périodique des images Docker `2 pts`
