# Points ouverts

Chaque question bloque ou oriente une story. On tranche, on note la réponse dans
[DECISIONS.md](DECISIONS.md), puis on retire l'entrée.

## Q-04 — Le bouton de validation garde-t-il ses 72 px ?

Ouverte le 2026-08-20 par D-46.

D-24 exigeait 72 px de haut pour la validation d'une boucle — le geste le plus répété de la nuit,
fait debout, une main occupée, parfois avec des gants. La charte « Tableau des départs » a reposé le
bouton : la variante `validate` d'`ActionButton` mesure aujourd'hui 50 px sur 90 px. Le plancher
général de 44 px, lui, est respecté.

Personne ne l'a vu parce que les cibles n'ont jamais eu de token : leur intention vivait dans les
variantes nommées, et aucun test ne les garde. Le bouton n'existe pour l'instant que dans la galerie
du design system, où sa taille ne se juge pas.

À trancher dans BR-09 ou BR-13, qui le posent en situation réelle : soit la variante remonte à
72 px, soit la règle tombe explicitement — et dans les deux cas, la valeur retenue mérite un test,
faute de quoi la prochaine charte la déplacera aussi silencieusement.

---

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
