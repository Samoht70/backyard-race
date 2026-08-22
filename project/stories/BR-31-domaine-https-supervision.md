# BR-31 — Publier en HTTPS et surveiller la production

| | |
|---|---|
| **Epic** | 7 — Déploiement |
| **Statut** | 🚧 En cours |
| **Estimation** | 5 pts |
| **Dépend de** | BR-30 |

## User story

En tant que **participant**,
Je veux **ouvrir une adresse simple et arriver sur la course**,
Afin de **consulter l'application sans installer quoi que ce soit**.

## Contexte

Quarante personnes vont ouvrir le lien depuis leur téléphone, souvent en 4G, parfois dans un
champ. L'adresse doit être courte, en HTTPS, et le gérant doit savoir que l'application est
tombée avant qu'un coureur ne le lui dise. Sur une machine dont personne d'autre ne s'occupe
(D-19), la surveillance externe n'est pas un confort.

## Périmètre fonctionnel

**Inclus**
- Le domaine servi en HTTPS par le frontal de Dokploy, certificat renouvelé automatiquement.
- Les journaux applicatifs consultables.
- Une surveillance **externe** de disponibilité, qui alerte le gérant sur son téléphone.
- Le rapport d'erreurs applicatives.

**Exclu**
- Un CDN et l'optimisation fine des performances : le trafic est de quarante personnes.
- Une page de maintenance sur mesure.

**Dépendances** — BR-30, et BR-26 pour le domaine déjà pointé sur la machine.

## Règles métier

- Tout le trafic est en HTTPS : une requête en clair est redirigée.
- Le certificat se renouvelle sans intervention, et son échéance est surveillée : un certificat
  expiré est une panne totale et silencieuse.
- Les journaux applicatifs sortent sur la sortie standard du conteneur, pour être collectés par
  Dokploy plutôt qu'écrits dans un fichier qui disparaît au redéploiement.
- Les journaux sont soumis à une rotation : sans elle, ils remplissent le disque de la machine.
- Aucune donnée personnelle de participant n'est écrite dans les journaux.
- La surveillance est **hébergée ailleurs que sur le VPS** : une supervision installée sur la
  machine qu'elle surveille ne signale pas sa panne.
- Elle interroge le point de contrôle de santé et alerte sur un canal que le gérant regarde la nuit.
- Une erreur applicative en production est notifiée, jamais seulement enterrée dans un journal.

## Critères d'acceptation

```gherkin
Étant donné le domaine configuré
Lorsqu'un participant ouvre l'adresse en HTTP
Alors il est redirigé en HTTPS
Et le certificat est valide

Étant donné l'application en ligne
Lorsqu'on interroge le point de contrôle de santé
Alors il répond favorablement

Étant donné le VPS éteint
Lorsque la surveillance externe interroge le point de contrôle plusieurs fois de suite
Alors une alerte parvient au gérant sur son téléphone

Étant donné une erreur applicative en production
Lorsqu'elle survient
Alors elle est journalisée et notifiée
Et aucune donnée personnelle de participant n'apparaît dans le journal

Étant donné l'application redéployée
Lorsqu'on consulte les journaux
Alors les journaux précédents sont toujours accessibles
```

## Cas limites et erreurs

- Propagation DNS incomplète le jour de la course : le domaine doit être en place plusieurs jours avant (BR-26).
- Renouvellement de certificat en échec parce que le port 80 a été fermé : la validation Let's Encrypt en a besoin.
- Alerte envoyée sur un canal que le gérant ne regarde pas la nuit : le canal doit être choisi en conséquence.
- Journaux non tournés : le disque se remplit et la base cesse d'écrire, ce qui renvoie à l'alerte disque de BR-26.

## Impacts techniques

Le gérant sera dehors, la nuit, sans ordinateur. La supervision n'a de valeur que si l'alerte
arrive sur le téléphone qu'il a en main, et si elle distingue « l'application est tombée » de
« le worker ne consomme plus » (BR-30) — les deux pannes n'ont pas le même effet sur la course.

## Tâches

- [x] **T1** — Servir le domaine en HTTPS via le frontal Dokploy, vérifier le renouvellement `1 pt`
- [x] **T2** — Journalisation sur la sortie standard, avec rotation, sans donnée personnelle `1 pt`
- [x] **T3** — Surveillance externe du point de contrôle de santé, alerte sur téléphone `2 pts`
- [ ] **T4** — Notification des erreurs applicatives `1 pt`
- [ ] **T5** — Vérifier redirection, certificat et alerte de bout en bout, VPS éteint compris `1 pt`

## Ce qui reste au 2026-08-22

Le domaine est servi en HTTPS avec renouvellement, les journaux partent sur la sortie standard, et un
service tiers interroge le point de contrôle de santé et alerte sur téléphone. La machine qui tombe
se sait donc.

- **T4** — Rien ne remonte les erreurs applicatives. Une exception de production reste dans les
  journaux du conteneur, et il faut déjà savoir qu'elle a eu lieu pour aller la lire. La supervision
  couvre la machine morte, pas l'application qui répond faux.
- **T5** — La vérification de bout en bout, VPS éteint compris, n'a pas été conduite comme un
  exercice. Que l'alerte parte n'a pas été observé sur une extinction volontaire.
