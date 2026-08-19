# BR-05 — Permettre à un coureur de s'inscrire à l'événement

| | |
|---|---|
| **Epic** | 1 — Fondations |
| **Statut** | ✅ Terminé |
| **Estimation** | 8 pts |
| **Dépend de** | BR-01, BR-03 |

## User story

En tant que **coureur invité**,
Je veux **créer mon compte et m'inscrire à la course**,
Afin de **figurer parmi les participants et recevoir mon dossard**.

## Contexte

L'inscription est le seul parcours en libre accès de l'application. Elle collecte aussi les
informations dont le gérant a besoin en cas de problème sur le terrain : contact d'urgence
et particularités du coureur.

## Périmètre fonctionnel

**Inclus**
- Création de compte, puis formulaire d'inscription : prénom, nom, email, téléphone, date de
  naissance, contact d'urgence, téléphone du contact d'urgence, informations complémentaires.
- Consultation et correction de sa propre inscription tant qu'elle n'est pas confirmée.
- Affichage du nombre de places : « 32 / 40 participants ».

**Exclu**
- Le paiement.
- La confirmation ou l'annulation par le gérant : BR-06.
- Le numéro de dossard : BR-07.

**Dépendances** — BR-01, BR-03.

## Règles métier

- Statuts d'inscription : `pending → confirmed` ou `pending → cancelled`.
- Une inscription naît en `pending`.
- Un compte ne peut porter qu'une seule inscription.
- Les inscriptions ne sont ouvertes que si l'événement est en `registration`.
- Quand le nombre d'inscriptions confirmées atteint le maximum, le formulaire n'accepte plus
  de nouvelle inscription.
- **Hérité de BR-03, à ne pas oublier :** un plafond `max_participants` nul signifie « pas de
  limite », jamais zéro (D-30). Et la règle « on ne peut pas fixer un plafond inférieur au nombre
  de confirmés » n'a pas pu être écrite en BR-03 faute de table `participants` : elle appartient à
  cette story, dans `EventUpdateRequest`.
- L'ouverture des inscriptions se lit via `$event->lifecycle()->allowsRegistration()`, jamais en
  recomparant `$event->status` à `EventStatus::Registration` (D-29).
- Le contact d'urgence et son téléphone sont obligatoires. Les informations complémentaires
  sont libres.
- Un participant ne voit et ne modifie que sa propre inscription.
- Une inscription confirmée n'est plus modifiable par le participant.

## Critères d'acceptation

```gherkin
Étant donné un événement au statut "registration" avec 40 places et 12 inscriptions confirmées
Lorsqu'un utilisateur connecté soumet une inscription valide
Alors son inscription est enregistrée au statut "pending"
Et la page affiche "12 / 40 participants"

Étant donné un utilisateur qui a déjà une inscription
Lorsqu'il en soumet une seconde
Alors la demande est refusée

Étant donné un événement dont les 40 places confirmées sont pourvues
Lorsqu'un utilisateur soumet une inscription
Alors la demande est refusée
Et le motif "complet" lui est présenté

Étant donné un participant dont l'inscription est "confirmed"
Lorsqu'il tente de modifier son inscription
Alors la modification est refusée

Étant donné un participant A et un participant B
Lorsque A tente d'ouvrir l'inscription de B
Alors l'accès est refusé
```

## Cas limites et erreurs

- Email déjà utilisé par un autre compte : message clair sur le champ email.
- Date de naissance dans le futur ou coureur mineur : refus à la validation.
- Téléphone au format libre mais non vide : accepté, la normalisation n'est pas un enjeu ici.
- Événement en `draft` ou en `running` : le formulaire n'est pas accessible.
- Deux inscriptions simultanées sur la dernière place : une seule est acceptée.

## Impacts techniques

Les données collectées comportent des informations personnelles et un contact d'urgence.
Elles ne doivent être visibles que du participant concerné et du gérant, jamais des autres
coureurs, y compris sur le tableau public des participants.

## Tâches

- [x] **T1** — Migration et modèle `Participant`, énumération de statut, lien vers `User` `2 pts`
- [x] **T2** — Factory et seeder d'une trentaine de participants pour le développement `1 pt`
- [x] **T3** — Form Request de validation, y compris capacité et unicité `2 pts`
- [x] **T4** — Contrôleur d'inscription, Policy « ma propre inscription » `2 pts`
- [x] **T5** — Écran d'inscription et écran de consultation de son inscription `3 pts`
- [x] **T6** — Tests : nominal, doublon, complet, cloisonnement entre participants `2 pts`
