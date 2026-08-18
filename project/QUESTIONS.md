# Points ouverts

Chaque question bloque ou oriente une story. On tranche, on note la réponse dans
[DECISIONS.md](DECISIONS.md), puis on retire l'entrée.

## Q-01 — Quand traduit-on les écrans hérités du starter kit ?

Ouverte le 2026-08-19 par BR-02.

BR-02 a établi la convention de traduction (D-27) et l'applique à tout ce qu'elle écrit, mais
elle n'a pas fait de passe d'i18n sur l'existant. Restent en **anglais** : les 7 pages
`pages/auth/*`, les 3 pages `pages/settings/*`, et les composants passkeys / 2FA
(`ManagePasskeys`, `ManageTwoFactor`, `TwoFactorRecoveryCodes`, `DeleteUser`).

Conséquence concrète : **un participant voit `/login` et `/register` en anglais** avant
d'atteindre le moindre écran BR-02. Ce n'est pas tenable le soir de l'événement.

Aucune story ne porte ce travail aujourd'hui. BR-05 (inscription) touche l'inscription et en
est le foyer naturel ; les réglages et la 2FA n'ont pas de propriétaire.

À trancher : on rattache la traduction des écrans hérités à BR-05, ou on ouvre une story
dédiée dans l'epic 1 ?

---

Les cinq questions initiales ont toutes été tranchées : les graphiques (D-16), la distance de
boucle (D-17), Mailpit (D-18), l'hébergement (D-19) et `laravel/boost` (D-22).
