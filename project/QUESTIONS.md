# Points ouverts

Chaque question bloque ou oriente une story. On tranche, on note la réponse dans
[DECISIONS.md](DECISIONS.md), puis on retire l'entrée.

---

Q-04 (les 72 px du bouton de validation) est fermée par [D-76](DECISIONS.md), en sens inverse de
D-24 : le gérant a vu le bouton en situation, l'a jugé trop gros, et les gestes de course prennent
désormais la taille des autres boutons du site — 44 px au doigt, 40 au grand format. La valeur vit
dans `lib/actionButton.ts` et un test la garde.

Q-01 (traduction des écrans hérités du starter kit) est fermée : à moitié par D-42, entièrement
par D-43 — les écrans qui restaient en anglais sont soit supprimés, soit traduits.

Q-02 (la page d'erreur de l'application) est fermée par D-66 : ni story rattachée à BR-13, ni statu
quo — la page est devenue publique le jour où l'adresse a circulé, et BR-40 la porte en propre dans
l'epic 1.

Q-03 (le compte dont l'inscription est annulée) est fermée par D-48, sur la première branche
combinée à la troisième : l'inscription annulée est en lecture seule pour le coureur, et c'est le
gérant qui la remet en attente sans que personne ne ressaisisse quoi que ce soit. Aucun écran de
création ne revient côté connecté, donc D-45 tient.

Les cinq questions initiales ont toutes été tranchées : les graphiques (D-16), la distance de
boucle (D-17), Mailpit (D-18), l'hébergement (D-19) et `laravel/boost` (D-22).
