# BR-37 — Purger les inscriptions et les comptes coureurs

| | |
|---|---|
| **Epic** | 7 — Déploiement |
| **Statut** | ✅ Terminé |
| **Estimation** | 5 pts |
| **Créée** | 2026-08-22 — relevé après la mise en ligne, lot 3 |
| **Livrée** | 2026-08-22 — lot 3 |
| **Dépend de** | BR-05, BR-07, BR-35 |

## User story

En tant que **propriétaire de l'installation**,
Je veux **effacer les inscriptions et les comptes coureurs par une commande, sans toucher à l'événement**,
Afin de **rendre la production à zéro inscription après mes essais, et de pouvoir le refaire si un essai suit**.

## Contexte

La production sert depuis le 2026-08-22 et elle porte des données d'essai : des inscriptions, et les
comptes coureurs qui vont avec, créés pour vérifier sur la machine ce que le dépôt disait déjà — que
le formulaire aboutit, que le mail part, que le gérant voit la ligne arriver. Ces lignes ne sont pas
des inscriptions. Elles fausseront le premier décompte, elles occupent des dossards, et elles
portent des adresses qui recevront tout ce que la course enverra.

Le geste évident, `migrate:fresh --seed`, est le mauvais outil, et il vaut mieux l'écrire une fois
que le redécouvrir sur la machine :

- il détruit **l'événement configuré**, son briefing rédigé et ses documents — tout le travail des
  lots 1 et 2 qui n'existe que dans cette base ;
- il détruit **le compte organisateur**, dont le code d'accès n'existe plus en clair (BR-35), donc
  il ferme la porte en même temps qu'il nettoie la pièce ;
- il laisse les fichiers des documents **orphelins dans le bucket** : le stockage objet est hors de
  la machine et hors de la base, aucune migration ne le rembobine.

Ce qui est demandé est plus étroit : **effacer les inscriptions et leurs comptes, garder le reste
intact.** Et c'est un geste répété, pas unique — un essai peut suivre celui-là, et la veille de la
course quelqu'un voudra peut-être repartir propre une dernière fois. C'est ce qui en fait une
commande plutôt qu'une séquence `tinker`, au même titre que BR-35 : le geste rare et destructeur est
exactement celui qu'on ne veut pas improviser.

## Périmètre fonctionnel

**Inclus**
- Une commande Artisan qui supprime toutes les inscriptions de l'événement et les comptes coureurs.
- Le décompte de ce qui va partir, affiché avant de toucher à quoi que ce soit.
- Une confirmation demandée, et une option pour s'en passer dans un contexte non interactif.
- La suppression des sessions des comptes purgés.
- Le refus de purger pendant que la course tourne.
- L'exclusion des comptes porteurs du rôle `manager`.

**Exclu**
- L'événement, son briefing, ses horaires et ses documents : ce ne sont pas des données d'essai, et
  les reperdre coûterait deux stories.
- Les rôles et les permissions : `RolesAndPermissionsSeeder` en est la source unique, une purge
  n'est pas une seconde porte vers cette correspondance.
- Les fichiers du stockage objet : aucun média n'est attaché à un compte ni à une inscription, seuls
  les documents en portent, et les documents restent.
- Une purge sélective, coureur par coureur. Le gérant annule déjà une inscription à l'écran (BR-06).
  Ici on efface, et on efface tout.
- Un écran. Cette commande détruit des comptes ; elle n'a pas sa place derrière un bouton.

**Dépendances** — BR-05 pour les inscriptions, BR-07 pour les dossards, BR-35 pour le compte qu'il
ne faut surtout pas emporter.

## Règles métier

- La commande **ne supprime jamais un compte porteur du rôle `manager`**. C'est la règle qui compte
  le plus : l'organisateur est le seul compte qu'on ne sache pas recréer sans BR-35.
- Un manager qui s'est inscrit perd son inscription et garde son compte. L'inscription est une
  donnée de course, le compte est une porte.
- La suppression va des `participants` vers les `users`, jamais l'inverse : `participants.user_id`
  est une clé étrangère contrainte et sans cascade, donc supprimer un compte d'abord échoue sur la
  contrainte.
- La suppression passe **par les modèles**, pas par `truncate` ni par une suppression en masse :
  `HasRoles` détache les rôles sur l'événement `deleting`, et une purge par requête laisserait des
  attributions orphelines dans `model_has_roles`.
- Tout se joue dans une transaction. Une purge à moitié faite laisse des comptes sans inscription,
  état qu'aucun écran ne sait montrer.
- Un compte sans inscription est purgé lui aussi, dès lors qu'il ne porte pas le rôle `manager` :
  `RegisterRunner` crée toujours le compte et l'inscription ensemble, donc un compte seul est déjà
  une anomalie, et la purge n'a pas à la préserver.
- La commande refuse quand l'événement est en course. À ce moment-là, une inscription n'est plus une
  ligne de formulaire, c'est un coureur sur le terrain.
- La commande est idempotente : lancée sur une base déjà propre, elle le dit et sort en succès.

## Critères d'acceptation

```gherkin
Étant donné une production portant un compte organisateur et quatre inscriptions d'essai
Lorsque la commande de purge est confirmée
Alors les quatre inscriptions sont supprimées
Et les quatre comptes coureurs sont supprimés
Et le compte organisateur est intact, rôle compris
Et l'événement, son briefing et ses documents sont intacts

Étant donné une purge qui vient d'aboutir
Lorsqu'un coureur s'inscrit
Alors son dossard est le numéro 1

Étant donné un compte organisateur qui s'est aussi inscrit à la course
Lorsque la commande de purge est confirmée
Alors son inscription est supprimée
Et son compte et son rôle manager sont intacts

Étant donné un événement dont le statut est « en course »
Lorsque la commande de purge est lancée
Alors elle refuse avant toute écriture
Et son message nomme le statut qui la bloque

Étant donné une base sans aucune inscription
Lorsque la commande de purge est lancée
Alors elle le dit et sort en succès sans rien écrire

Étant donné une commande lancée sans terminal interactif et sans option de confirmation
Lorsqu'elle demande la confirmation
Alors elle refuse plutôt que de supposer une réponse
```

## Cas limites et erreurs

- **Un job en file pour un compte purgé.** `RegistrationConfirmed` et `RegistrationLink` sont
  sérialisés avec leur destinataire ; purgé, le job échoue à son réveil. Le décompte affiché avant
  la purge doit donc nommer la file en attente, ou la purge se fait file vide.
- **Une session encore ouverte.** `sessions.user_id` n'a pas de contrainte : le cookie d'un compte
  purgé survit à la ligne. L'utilisateur n'est plus authentifié — l'identifiant ne résout plus — mais
  la ligne reste, et une purge qui laisse des traces n'a pas fini son travail.
- **Une purge partielle par le passé.** Les dossards repartent de `max + 1`, pas de 1 : si une vraie
  inscription porte le dossard 7 et que les essais 1 à 6 partent, les suivants prennent 8. Les trous
  ne se rebouchent pas, et c'est préférable à deux coureurs portant le même numéro.
- **Aucun compte manager dans la base.** La commande purge alors tous les comptes et se ferme la
  porte derrière elle. Elle doit le dire avant, pas après.

## Impacts techniques

La commande vit dans `app/Console/Commands`, avec le préfixe `race:` de `OpenDueRoundsCommand`, et
n'a besoin d'aucun modèle nouveau : `Participant` et `User` portent déjà tout, et
`EventLifecycleState::isRacing()` porte déjà le garde-fou de statut.

Le seul point qui demande une décision est l'endroit où vit la règle « qui est un compte coureur ».
La définir dans la commande par une négation du rôle `manager` est correct et se lit ; la définir par
la présence d'une inscription serait plus étroit et laisserait passer les orphelins. La négation du
rôle gagne, mais elle touche `laravel:permissions-not-roles` : aucune décision d'accès ne teste un
nom de rôle. Ici il ne s'agit pas d'accès — la commande ne laisse entrer personne, elle choisit qui
elle n'emporte pas — et l'exception mérite d'être écrite plutôt que subie.

`Illuminate\Support\Facades\DB::transaction()` enveloppe le tout, et les sessions se ferment par une
suppression sur la table, seule écriture de la story qui ne passe pas par un modèle : `sessions` n'en
a pas.

## Tâches

- [x] **T1** — Commande de purge : décompte affiché, confirmation, transaction, ordre participants
  puis comptes `2 pts`
- [x] **T2** — Garde-fous : refus en course, exclusion du rôle `manager`, avertissement quand aucun
  manager ne subsisterait, option non interactive `1 pt`
- [x] **T3** — Suppression des sessions des comptes purgés `1 pt`
- [x] **T4** — Tests : purge complète, organisateur épargné, inscription d'un manager supprimée,
  rôles détachés, refus en course, base déjà propre, dossard qui repart à 1 `2 pts`
