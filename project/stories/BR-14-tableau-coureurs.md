# BR-14 — Chercher un coureur

| | |
|---|---|
| **Epic** | 3 — Interface de course |
| **Statut** | À faire |
| **Estimation** | 6 pts |
| **Révisée** | 2026-09-04 — de 8 à 6 pts, et l'écran passe du tableau à quatre vues à une recherche : les vues, leurs filtres et la pagination quittent le périmètre, un champ de recherche et une coquille de dépliage arrivent |
| **Dépend de** | BR-13 |

## User story

En tant que **participant comme gérant**,
Je veux **retrouver un coureur par son nom ou son dossard**,
Afin de **savoir où il en est sans faire défiler quarante lignes**.

## Contexte

Pendant la course, la question qu'on pose à un écran est « où en est Untel ». Les deux chiffres
qui comptent — combien courent encore, combien sont sortis — se lisent en un coup d'œil sans rien
saisir ; le reste se cherche.

Cet écran remplace le tableau à quatre vues que la story annonçait. Une liste de quarante
coureurs qu'on déroule pour en trouver un est le geste que la recherche supprime, et les vues
« en course », « sortis » et « tour courant » disparaissent avec elle : leur réponse tient
désormais dans les compteurs et dans le statut porté par chaque résultat.

Ce que cette révision retire, il faut le dire : la liste complète de l'effectif n'existe plus
nulle part. [D-79](../DECISIONS.md) l'avait promise ici quand BR-13 a retiré les coureurs sortis
de son tableau de validation.

## Périmètre fonctionnel

**Inclus**
- Les deux compteurs de course — coureurs en course, coureurs sortis — visibles à l'ouverture.
- Un champ de recherche sur le prénom, le nom et le dossard.
- Les résultats en lattes : prénom, nom, dossard, statut, nombre de boucles validées.
- Pour un coureur sorti : son heure de sortie sur la latte.
- La coquille du panneau dépliable, portant distance totale et dernière boucle validée.

**Exclu**
- Les quatre vues et leur filtrage, la pagination, tout tri autre que le dossard croissant.
- Tout classement et tout rang pendant la course (voir D-15).
- Les données personnelles des coureurs : téléphone, date de naissance, numéro PPS et contact
  d'urgence n'apparaissent jamais ici.
- Le détail boucle par boucle et les actions gérant : ils remplissent le panneau en BR-16.
- Le rafraîchissement automatique : la page se consulte à la demande, BR-15 reste sur l'écran
  du gérant.

**Dépendances** — BR-13.

## Règles métier

- L'écran est accessible à tout utilisateur connecté que la policy de l'événement autorise à le
  voir. Sans événement en base il répond 404, comme le briefing et les documents.
- La recherche ne porte que sur les inscriptions **confirmées** : une inscription en attente,
  refusée ou annulée reste invisible, son existence étant une donnée personnelle.
- La recherche se déclenche à partir de deux caractères, ou d'un seul s'il est numérique — un
  chiffre est une saisie de dossard complète.
- Une saisie numérique cherche un dossard par **égalité** : « 7 » remonte le 007 et lui seul.
- Une saisie textuelle cherche dans le prénom et le nom, sans distinction de casse.
- Les résultats sont ordonnés par dossard croissant. Jamais par nombre de boucles : ce serait un
  classement.
- Le nombre de boucles d'un coureur est son nombre de boucles `validated`, rien d'autre.
- Sa distance totale est ce nombre multiplié par la distance de boucle de l'événement (D-17).
- Le motif de sortie est porté par le statut affiché, sans mention supplémentaire : `withdrawal`
  s'affiche « Abandon » et `timeout` « Éliminé », chacun avec son pictogramme (D-76).
- Les compteurs sont un agrégat calculé en base, jamais dérivé des résultats affichés (D-79).
- Aucune donnée personnelle n'est exposée, à personne, sur cet écran.

## Critères d'acceptation

```gherkin
Étant donné un événement en course avec 24 coureurs en course et 13 sortis
Lorsqu'un participant ouvre l'écran des coureurs
Alors les compteurs affichent 24 en course et 13 sortis
Et aucun coureur n'est listé
Et l'écran invite à chercher un coureur par son nom ou son dossard

Étant donné cet écran
Lorsque le participant saisit une seule lettre
Alors aucune recherche n'est lancée et l'invitation reste affichée

Étant donné un coureur en course nommé Marchand, dossard 012, avec 7 boucles validées
Lorsque le participant saisit "mar"
Alors Marchand apparaît avec son dossard, son statut et ses 7 boucles

Étant donné ce même coureur
Lorsque le participant saisit "12"
Alors le dossard 012 apparaît seul, et non les dossards 112 ou 120

Étant donné une coureuse éliminée hors délai, sortie à 03:12, avec 5 boucles de 6 km
Lorsque le participant la trouve par son nom
Alors sa latte affiche son statut "Éliminé", ses 5 boucles et son heure de sortie 03:12
Et son panneau déplié affiche 30 km et sa dernière boucle validée

Étant donné un participant qui a trouvé un autre coureur
Lorsqu'il regarde sa latte et son panneau déplié
Alors ni son téléphone, ni sa date de naissance, ni son numéro PPS, ni son contact d'urgence
  n'apparaissent
Et aucun rang n'est affiché

Étant donné une saisie qui ne correspond à personne
Lorsque la recherche aboutit
Alors l'écran dit qu'aucun coureur ne correspond, et le dit autrement que son invitation initiale

Étant donné une base sans aucun événement
Lorsqu'un utilisateur connecté ouvre l'écran des coureurs
Alors la réponse est 404

Étant donné un événement en brouillon
Lorsqu'un participant ouvre l'écran des coureurs
Alors l'accès est refusé
```

## Cas limites et erreurs

- Coureur confirmé mais jamais parti : il apparaît avec zéro boucle et zéro kilomètre.
- Distance de boucle non renseignée par le gérant : la distance totale ne s'affiche pas, elle ne
  vaut pas zéro.
- Distance de boucle corrigée en cours d'événement : les totaux suivent la nouvelle valeur, sans
  recalcul — la distance est celle de l'événement (D-17).
- Saisie remplacée avant que la précédente réponde : c'est la dernière saisie qui gagne.
- Saisie trafiquée dans l'URL : la page revient à son invitation, elle ne rend pas une erreur de
  validation.

## Impacts techniques

Les deux compteurs et les agrégats par coureur se lisent en base, jamais coureur par coureur en
PHP. La dernière boucle validée est un `max` sur le numéro de tour des boucles validées, posé en
sous-requête sur la requête de recherche : une passe, quel que soit le nombre de résultats.

La saisie voyage dans l'URL et la recherche part côté serveur, mais le rechargement ne doit ni
vider le champ, ni déplacer le curseur, ni annuler une recherche en vol.

L'écran est une colonne centrée, sur le patron du dashboard gérant. La latte de résultat est le
composant existant, sans variante nouvelle.

## Tâches

- [ ] **T1** — Requête de recherche et agrégats par coureur en une passe : boucles validées,
      dernière boucle validée `2 pts`
- [ ] **T2** — Route, contrôleur Inertia, Form Request du seuil de saisie, entrée de
      navigation `1 pt`
- [ ] **T3** — Écran : compteurs, champ de recherche débouncé, lattes de résultats, coquille du
      panneau dépliable `2 pts`
- [ ] **T4** — Tests : 404 et 403, seuil de saisie, recherche par nom et par dossard, agrégats
      justes, aucune donnée personnelle exposée `1 pt`
