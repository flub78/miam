# Plan d'implémentation — Suivi de poids et de calories (miam)

- **Statut** : vivant
- **Date** : 2026-09-02
- **PRD lié** : [suivi-poids-calories.md](../prds/suivi-poids-calories.md)
- **Note de design liée** : [suivi-poids-calories.md](../design/suivi-poids-calories.md)
- **Documents liés** : Laraskel — `doc/design/distribution-packages.md`,
  `doc/development/guide-application-derivee.md`, `doc/todo.md` §2 (priorisation des briques),
  `doc/prds/gestion-utilisateurs-roles.md`, `doc/prds/interface-llm.md`

Le plan suit l'ordre des lots de la note de design §8. Chaque lot livre son code, ses tests
et sa documentation utilisateur. On coche au fil de l'avancement ; on met à jour ce fichier,
sans document de synthèse à côté (`.ai/rules/doc.md`).

---

## 1. Principe : miam avance au rythme de Laraskel

miam ne recode aucune brique transverse : elle les `require` en packages Composer et les
étend **par composition** (note de design §2, guide de l'application dérivée). Plusieurs
lots sont donc **bloqués tant que la brique correspondante n'est pas extraite de
`laraskel` en package consommable**. Un lot n'est *validable* que lorsque ses prérequis
Laraskel sont mergés et disponibles via `composer require`.

Le travail Laraskel correspondant est suivi dans `laraskel/doc/todo.md` §2 et ses propres
plans — ce plan-ci ne fait que **pointer la dépendance et son état**, il ne pilote pas le
développement du socle.

### 1.1 Dépendances Laraskel — vue d'ensemble

| Brique Laraskel | Requise par | État au 2026-09-02 | Nature |
|---|---|---|---|
| `laraskel-core` (macros d'audit, conventions, page de santé) | tous les lots | **Disponible**, consommée en path repository | — |
| Trait `TracksAuthor` (`created_by` / `updated_by` depuis `Auth::id()`) | Lot 1 → 5 | À livrer (dans `laraskel-core` ou la brique users) | **Bloquant Lot 1** |
| **Gestion utilisateurs + rôles** (Fortify, `spatie/permission`, modèle `User` extensible, panel Filament d'administration, cycle de vie des comptes) | Lot 1 | Existe dans `laraskel/app/`, **pas encore packagée**. Découpage (`laraskel-users` dédié vs intégré à `laraskel-core`) = décision Laraskel (`todo.md` §2 priorité 2) | **Bloquant Lot 1** |
| Socle Filament (panels, installation, thème charte) | Lot 1 → 5 | Fourni avec la brique users/rôles ou à installer dans miam — à trancher côté Laraskel | **Bloquant Lot 1** |
| **Visualisation graphique** (widgets de graphiques Filament / Chart.js, sélecteur de période) | Lot 2 | À extraire (`todo.md` §2 priorité 4, `README.md` §7) | **Bloquant Lot 2** |
| **Stockage d'image** — disque privé + URL signée (sous-ensemble minimal, pas la médiathèque) | Lot 3 | Réalisable sans brique avec `Storage` natif (§ Lot 3, option A) ; brique optionnelle si extraite d'ici là | Léger |
| **Pont LLM** (`llm-bridge` : Prism, entrée image, prompts, journalisation, coûts) | Lot 4 | À extraire (`todo.md` §2 priorité 5) — module le moins mûr, `prds/interface-llm.md` est un squelette | **Bloquant Lot 4** |
| Signalement à la connexion (bannière « bilan dû ») | Lot 5 | Réalisable dans miam (contrôle au login + notification Filament) ; s'aligner sur `prds/navigation-dashboards.md` si un mécanisme générique arrive | Léger |

### 1.2 Conventions d'implémentation (rappel)

- **Isolation par utilisateur** : toute table du domaine porte `user_id` ; un trait
  `AppartientAUtilisateur` (global scope Eloquent sur l'utilisateur courant + affectation
  automatique à la création + policy) est appliqué à tous les modèles du domaine. Aucune
  vue inter-utilisateurs, y compris pour l'administrateur (FR-5).
- **Colonnes d'audit** : `$table->auditColumns()` / `dropAuditColumns()` sur chaque
  nouvelle table (`.ai/rules/migrations.md`).
- **Français** partout dans le code (commentaires, PHPDoc, messages, chaînes UI).
- **Jamais d'échec silencieux** : chaque action rend son résultat visible, messages
  d'erreur descriptifs (`.ai/rules/app.md`).
- **Tests** : `DatabaseTransactions`, jamais `RefreshDatabase` ; données montées par
  factories, nettoyées ; assertions uniquement sur les données créées (`.ai/rules/tests.md`).
- **IHM** : *resources* et *pages* Filament, widgets de graphiques pour les
  visualisations (note de design §6).
- **Calculs dérivés** : services dédiés, jamais de colonne ni de vue matérialisée
  (note de design §4).

---

## Lot 0 — Fondations miam

**Objectif.** Mettre en place ce qui est transverse à tous les lots fonctionnels.

**Dépendances Laraskel.** `laraskel-core` (fait). Le reste de ce lot n'est **pas
bloquant** et peut démarrer immédiatement.

- [x] Consommer `flub78/laraskel-core` en path repository.
- [x] Page d'accueil à la charte graphique.
- [x] PRD et note de design (avec diagrammes) rédigés et commités.
- [ ] Classe de base de test (`Tests\TestCase`) appliquant `DatabaseTransactions` ;
      documenter le squelette de test « monte / nettoie ses données ».
- [ ] Trait `App\Models\Concerns\AppartientAUtilisateur` : global scope sur
      `user_id = Auth::id()`, affectation automatique à la création, exposition d'une
      relation `user()`. Test unitaire dédié (deux utilisateurs, aucune fuite).
- [ ] Politique d'autorisation générique du domaine (un utilisateur n'agit que sur ses
      lignes) + enregistrement.
- [ ] `bin/puml.sh` opérationnel (fait) ; `bin/puml.sh --check` intégré au réflexe de
      commit documentation.
- [ ] `doc/users/README.md` : sommaire de la documentation utilisateur, à compléter lot
      par lot.

**Tests.**
- [ ] `AppartientAUtilisateurTest` — isolation stricte entre deux utilisateurs.
- [ ] La suite existante (`LaraskelCoreTest`, `PageAccueilTest`) reste verte.

**Documentation utilisateur.**
- [ ] `doc/users/README.md` (sommaire).

---

## Lot 1 — Comptes et profil

**Objectif.** Un administrateur crée des comptes ; l'utilisateur définit son mot de
passe, renseigne son profil et l'historique de ses changements d'habitudes acquis.

**Couvre.** FR-1, FR-2, FR-3, FR-4, FR-5. Début de CU-5.

**Dépendances Laraskel — BLOQUANTES.**

| À livrer par Laraskel | Détail attendu |
|---|---|
| Brique **gestion utilisateurs + rôles** en package | Modèle `User` de base **extensible par composition** ; Fortify (connexion, mot de passe oublié, lien de première connexion) ; `spatie/laravel-permission` avec rôles plats `admin` / `utilisateur` ; panel Filament d'administration des comptes (création, activation / désactivation, déclenchement d'une réinitialisation) ; commande de création d'un administrateur. |
| Décision de découpage | `laraskel-users` dédié **ou** intégration à `laraskel-core` (décision Laraskel, `todo.md` §2). miam s'adapte au nom de package retenu. |
| Socle Filament | Installation, configuration d'un **panel utilisateur** (distinct du panel d'administration), thème aligné sur la charte. Fourni par la brique ou à installer dans miam — à trancher côté Laraskel. |
| Trait `TracksAuthor` | Remplissage `created_by` / `updated_by` depuis `Auth::id()` (annoncé par `.ai/rules/migrations.md` comme fourni par le package cœur). |

> Tant que ces éléments ne sont pas consommables via `composer require`, le Lot 1 ne peut
> être ni terminé ni validé. Les étapes miam ci-dessous se préparent, mais leur test
> d'intégration dépend de la brique.

**Étapes.**
- [ ] `composer require` de la brique users/rôles (nom selon décision Laraskel) ;
      vérifier l'auto-découverte (`php artisan about`, panels visibles).
- [ ] `App\Models\User` **étend** le `User` de la brique (composition) ; y accrocher les
      relations du domaine (ajoutées au fil des lots) et le rôle `utilisateur` par
      défaut à la création.
- [ ] Migration `profiles` : `user_id` unique, `birthdate`, `sex`, `height_cm`,
      `start_weight_kg`, `start_date`, `activity_level` (enum), `medical_constraints`,
      `allergies`, `food_preferences` + `auditColumns()`.
- [ ] Migration `acquired_habit_changes` : `user_id`, `changed_on`, `description` +
      `auditColumns()`.
- [ ] Modèles `Profile`, `AcquiredHabitChange` avec `AppartientAUtilisateur`, casts,
      factories.
- [ ] Enum `App\Enums\ActivityLevel` — échelle fermée de niveaux. **Options de barème**
      (à confirmer, cf. PRD §9) :
      - sédentaire 1,2 · léger 1,375 · modéré 1,55 · soutenu 1,725 · intense 1,9
        (barème Harris-Benedict classique, **retenu par défaut**) ;
      - variante à 4 niveaux (1,2 / 1,375 / 1,55 / 1,725) si « intense » n'est pas utile.
      Le profil stocke le **niveau**, pas le facteur.
- [ ] *Resource* Filament « Profil » (panel utilisateur) : formulaire, `height_cm` et
      `birthdate` requis (nécessaires au budget), reste optionnel ; message explicite si
      un champ requis manque.
- [ ] *Resource* / section « Changements d'habitudes acquis » : liste + création.
- [ ] Vérifier l'isolation : un utilisateur ne voit jamais le profil d'un autre ;
      l'administrateur non plus (FR-5).

**Tests.**
- [ ] Feature : création de compte par l'administrateur → l'utilisateur définit son mot
      de passe → connexion au panel utilisateur.
- [ ] Feature : désactivation d'un compte → connexion refusée avec message ;
      réactivation → connexion rétablie.
- [ ] Feature : CRUD du profil ; champs requis manquants → message descriptif.
- [ ] Feature : isolation profil / changements d'habitudes entre deux utilisateurs et
      vis-à-vis de l'administrateur.
- [ ] Unitaire : `ActivityLevel` — libellés et facteurs.

**Documentation utilisateur.**
- [ ] `doc/users/comptes.md` — première connexion, rôle de l'administrateur.
- [ ] `doc/users/profil.md` — renseigner son profil, changements d'habitudes.

---

## Lot 2 — Objectifs, budget calorique, pesées et courbes

**Objectif.** L'utilisateur fixe un objectif (budget calculé ou saisi), pèse son poids
et lit l'évolution de son poids et de son IMC.

**Couvre.** FR-6 à FR-14, FR-28, FR-29, FR-32 à FR-36, FR-59 à FR-61. CU-2, CU-4.

**Dépendances Laraskel.**

| À livrer par Laraskel | Nature |
|---|---|
| **Brique visualisation graphique** : widgets de graphiques Filament (courbes, barres), lignes de repère, bandes colorées, sélecteur de période, rendu lisible sur smartphone | **Bloquant** pour FR-28 à FR-36 (les courbes). Le reste du lot — objectifs, budget, pesées, calculs — ne dépend que du Lot 1. |

> Repli possible si la brique tarde : livrer d'abord les données et les services
> (`WeightTrend`, `Bmi`) avec leurs tests, et un rendu tabulaire ; brancher les widgets
> dès que la brique est disponible. À arbitrer avec l'utilisateur.

**Étapes — objectifs et budget.**
- [ ] Migration `goals` (historisée) : `user_id`, `effective_from`, `target_weight_kg`,
      `target_rate_kg_per_week`, `daily_calorie_budget`, `protein_target_g`,
      `indicative_target_date` + `auditColumns()`. Pas de mise à jour en place : un
      changement crée une ligne (FR-9).
- [ ] Migration `tracking_settings` : `user_id` unique, `review_interval_days`,
      `next_review_on` + `auditColumns()` (l'intervalle et la date active servent au
      Lot 5 ; la table est créée ici car liée au profil de suivi).
- [ ] Modèles `Goal`, `TrackingSettings` (+ `AppartientAUtilisateur`, factories) ;
      accès à l'objectif courant = ligne `effective_from` la plus récente.
- [ ] Service `App\Services\CalorieBudget` : métabolisme de base Mifflin-St Jeor ×
      facteur d'activité (`ActivityLevel`) − déficit déduit du rythme visé (FR-6).
      Expose le détail : métabolisme de base, maintenance estimée, déficit, budget
      (FR-7).
- [ ] Écran Filament « Objectifs » : affiche le calcul détaillé et la mention
      « estimation de départ » ; l'utilisateur accepte la valeur proposée ou saisit
      budget / rythme / poids cible / protéines (FR-8) ; historique consultable (FR-9) ;
      date cible indicative calculée + mention « non contractuelle » (FR-10).

**Étapes — pesées et suspensions.**
- [ ] Migration `weigh_ins` : `user_id`, `measured_on`, `weight_kg`, `body_fat_pct`,
      `muscle_mass_pct`, `note` + `auditColumns()`.
- [ ] Migration `suspension_periods` : `user_id`, `starts_on`, `ends_on`, `reason` +
      `auditColumns()`.
- [ ] Modèles `WeighIn`, `SuspensionPeriod` (+ trait, factories).
- [ ] Service `WeightTrend` : moyenne mobile 7 jours (FR-13), pente kg/semaine sur une
      période (FR-14), **exclusion des intervalles de suspension** (FR-61).
- [ ] Service `Bmi` : IMC + zone de corpulence OMS à partir du poids courant et de la
      taille (FR-29).
- [ ] *Resource* Filament « Pesées » : saisie (date par défaut aujourd'hui, modifiable,
      antérieure possible), correction, suppression (FR-11, FR-12).
- [ ] *Resource* Filament « Périodes de suspension » : déclaration (dates + motif) ;
      pendant une suspension aucune saisie n'est demandée et aucune alarme n'est émise
      (FR-59, FR-60) ; les données saisies quand même sont conservées mais exclues des
      pentes (FR-61).

**Étapes — visualisations.**
- [ ] Widget « Courbe de poids » : points de pesée, moyenne mobile 7 j, ligne du poids
      cible (FR-28).
- [ ] Widget « Courbe d'IMC » : bandes de couleur des zones OMS, repère du poids cible
      (FR-29).
- [ ] Sélecteur de période commun (4 semaines / 3 mois / tout) (FR-35).
- [ ] Détail d'un point au survol / tap (FR-34).
- [ ] Périodes de suspension distinguées visuellement, sans rupture trompeuse (FR-36).
- [ ] Rafraîchissement automatique après toute écriture de pesée (FR-32).
- [ ] Rendu vérifié sur smartphone et PC (FR-33) — contrôle navigateur manuel.

**Tests.**
- [ ] Unitaire `CalorieBudget` : valeurs de référence (homme / femme, plusieurs
      niveaux d'activité, plusieurs rythmes) ; détail cohérent avec le budget.
- [ ] Unitaire `WeightTrend` : moyenne mobile, pente, exclusion d'une période de
      suspension.
- [ ] Unitaire `Bmi` : frontières des zones OMS.
- [ ] Feature : historisation d'un changement d'objectif (ancienne ligne conservée).
- [ ] Feature : CRUD pesées, saisie antérieure, isolation entre utilisateurs.
- [ ] Feature : une pesée créée pendant une suspension est exclue de la pente mais
      visible dans la liste.
- [ ] Feature/rendu : les widgets se peuplent des seules données de l'utilisateur.

**Documentation utilisateur.**
- [ ] `doc/users/objectifs.md` — budget calculé, ajustement manuel, historique.
- [ ] `doc/users/pesees.md` — saisir une pesée, lire la courbe et la tendance.
- [ ] `doc/users/suspension.md` — déclarer une période de suspension.

---

## Lot 3 — Journal alimentaire et table de référence (saisie manuelle)

**Objectif.** Enregistrer des repas et gérer la table de référence calorique **sans
assistant** : les calories sont saisies à la main. L'estimation LLM arrive au Lot 4.

**Couvre.** FR-17, FR-18, FR-19, FR-20, FR-22 à FR-27, FR-30, FR-31, FR-50.

**Dépendances Laraskel — légères.**

| Besoin | Options |
|---|---|
| Photo attachée à un repas, servie de façon privée (FR-19) | **Option A (retenue)** : disque Laravel privé (`storage/app/private` ou disque `s3` privé selon l'environnement), chemin stocké sur `meals.photo_path`, accès par route à **URL signée** temporaire. Aucun package. **Option B** : brique « stockage d'image » Laraskel si elle est extraite d'ici là (sous-ensemble de `mediatheque.md`). **Option C** : `spatie/medialibrary` — **écartée** (note de design §7, PRD §5.2). |
| Widgets calories/jour et budget hebdomadaire (FR-30, FR-31) | Brique visualisation (déjà requise au Lot 2). |

**Étapes.**
- [ ] Migration `reference_foods` : `user_id`, `label`, `kcal_per_100`, `kcal_per_unit`,
      `unit_label` + `auditColumns()` (une des deux valeurs kcal renseignée).
- [ ] Migration `meals` : `user_id`, `eaten_at`, `moment`, `description`,
      `estimated_kcal`, `context_tag`, `note`, `photo_path` + `auditColumns()`.
- [ ] Migration `meal_items` : `meal_id`, `reference_food_id` nullable, `label`,
      `quantity`, `unit`, `kcal` + `auditColumns()`.
- [ ] Modèles `ReferenceFood`, `Meal`, `MealItem` (+ trait sur les modèles portant
      `user_id`, factories). `moment` = liste ouverte (enum souple ou chaîne validée
      contre une liste extensible : petit-déjeuner, déjeuner, collation, dîner, apéro,
      goûter, dessert…).
- [ ] Amorçage de `reference_foods` depuis `calories_reference.md` de `pdp` :
      - [ ] importer le fichier `pdp` dans `database/data/` (jeu de données de
            référence) ;
      - [ ] seeder / écouteur exécuté **à la création d'un compte** (FR-27) ;
      - [ ] commande d'amorçage rétroactif pour les comptes déjà créés.
- [ ] Service `App\Services\DailyBalance` : budget restant du jour (budget quotidien −
      repas du jour) et budget hebdomadaire (budget × 7 − repas de la semaine)
      (FR-17, FR-31).
- [ ] Logique table de référence :
      - [ ] à l'enregistrement d'un repas, un aliment avec quantité précise absent de la
            table y est ajouté sans confirmation (FR-23) ;
      - [ ] un aliment déjà présent recevant une valeur nettement différente est
            **ajusté**, jamais dupliqué (FR-24) — définir le seuil d'écart déclenchant
            l'ajustement (option : ± 15 % ; à confirmer).
- [ ] *Resource* Filament « Repas » : CRUD (correction, suppression, date antérieure —
      FR-20) ; `moment` et `context_tag` (FR-18) ; lignes de repas (aliment / quantité /
      unité / kcal) saisies à la main ; **budget restant du jour affiché avant
      enregistrement** (FR-17) ; upload photo → disque privé, URL signée, suppression de
      la photo sans supprimer le repas (FR-19).
- [ ] *Resource* Filament « Table de référence » : consultation, correction,
      suppression, tri et filtre par libellé (FR-26).
- [ ] Widget « Calories par jour » : barres de la somme quotidienne, couleur selon
      l'écart au budget (vert près du budget → rouge près de la maintenance), lignes de
      repère « budget » et « maintenance » (FR-30).
- [ ] Widget « Budget hebdomadaire » : cumul de la semaine face au budget × 7 (FR-31).
- [ ] Vue « Contexte » : sur une période choisie, fréquence et impact calorique cumulé
      des repas par `context_tag` (FR-50).
- [ ] Rafraîchissement automatique des widgets après écriture d'un repas (FR-32).

**Tests.**
- [ ] Unitaire `DailyBalance` : budget restant jour et semaine, avec et sans repas.
- [ ] Feature : CRUD repas, photo (upload + URL signée + suppression photo seule),
      isolation entre utilisateurs.
- [ ] Feature : FR-23 (ajout auto d'un aliment pesé inconnu) et FR-24 (ajustement sans
      doublon) — cas nominal et cas limite du seuil.
- [ ] Feature : amorçage de la table de référence à la création d'un compte ; commande
      rétroactive idempotente.
- [ ] Feature/rendu : widgets calories/jour et hebdomadaire alimentés des seules
      données de l'utilisateur.

**Documentation utilisateur.**
- [ ] `doc/users/repas.md` — enregistrer un repas, budget restant, photo, tags.
- [ ] `doc/users/table-reference.md` — consulter et corriger la table de référence.
- [ ] `doc/users/contexte.md` — lire la vue par tag de contexte.

---

## Lot 4 — Assistant : estimation, coaching, menus

**Objectif.** Brancher l'assistant LLM : estimation calorique par texte ou photo en une
opération, dialogue de coaching contextuel, proposition de menus.

**Couvre.** FR-15, FR-16, FR-21, FR-25 (réutilisation par l'assistant), FR-44 à FR-46,
FR-49, FR-52 à FR-55. CU-1, CU-6 (préparation).

**Dépendances Laraskel — BLOQUANTES.**

| À livrer par Laraskel | Détail attendu |
|---|---|
| Brique **`llm-bridge`** en package | Wrapper Prism : appels multi-provider, **entrée image** (photo d'assiette), prompts internes, journalisation des échanges, suivi des coûts, gestion d'erreur exploitable par l'appelant (pour le mode dégradé). |
| Spécification | `laraskel/doc/prds/interface-llm.md` est aujourd'hui un squelette (`todo.md` §2 priorité 5) : la brique est **le module le plus à spécifier**. Ce lot est le plus exposé au calendrier Laraskel. |

> Le Lot 4 ne démarre réellement que lorsque `llm-bridge` est consommable. En attendant,
> le Lot 3 garantit un chemin manuel complet : miam reste utilisable sans assistant.

**Étapes.**
- [ ] `composer require flub78/llm-bridge` ; configuration du provider et des clés en
      `.env` ; vérifier un appel texte et un appel image de bout en bout.
- [ ] `App\Assistant\AssistantContext` : sérialise profil, objectif courant + historique,
      N dernières pesées, N derniers repas, table de référence, analyse des habitudes,
      dernières entrées de journal (FR-45). Paramétrer N.
- [ ] Prompt système « diététicien / nutritionniste » repris des consignes de `pdp`
      (`CLAUDE.md` de `pdp`) : analyses fondées sur des données établies, aveu
      d'incertitude plutôt qu'extrapolation (FR-44). Stocké en ressource versionnée.
- [ ] Cas d'usage typé **estimer un repas** (texte ou photo) → réponse **structurée**
      `{ aliments: [{ libellé, quantité, unité, kcal }], kcal_total }` ; mapping en
      `MealItem` ; réutilisation prioritaire de la table de référence pour un aliment
      connu (FR-25).
- [ ] **Page Filament dédiée « Nouveau repas » (CU-1)** : photo ou description →
      estimation → liste d'aliments ajustable (retrait d'un aliment, correction de
      quantité) avec **recalcul du total et du budget restant** → choix du moment, tag,
      note → enregistrement **en une seule opération** ; à l'enregistrement, application
      de la logique table de référence du Lot 3 (FR-15, FR-16, FR-23, FR-24).
- [ ] **Mode dégradé** : pont indisponible → message explicite, bascule sur la saisie
      manuelle du Lot 3, aucun échec silencieux (`.ai/rules/app.md`, note de design §5) ;
      pas de rejeu différé de l'estimation photo (PRD §9, note de design §1).
- [ ] Cas d'usage **dialogue de coaching** texte + image (FR-46) : *resource* / page de
      conversation, contexte injecté via `AssistantContext`.
- [ ] Migration `habit_analyses` : `user_id` unique, `body` + `auditColumns()` ; écran
      d'édition du document vivant (FR-49).
- [ ] Migrations `weekly_menus` (`user_id`, `week_start`) et `menu_items` (`weekly_menu_id`,
      `moment`, `description`, `indicative_kcal`, `is_free_meal`) + `auditColumns()`.
- [ ] *Resource* Filament « Menus de la semaine » : création manuelle (FR-52), repas
      libre hebdomadaire non quantifié (FR-53), proposition par l'assistant à partir du
      profil / objectifs / préférences / saison puis édition (FR-54), reprise d'un repas
      du menu comme repas réel en un geste (FR-55).

**Tests.**
- [ ] Unitaire : mapping d'une réponse structurée de l'assistant → `MealItem`
      (pont **mocké**, aucun appel réseau réel).
- [ ] Unitaire : `AssistantContext` sérialise les bonnes données, bornées à N.
- [ ] Feature : mode dégradé — pont en erreur → message + formulaire manuel
      fonctionnel.
- [ ] Feature : parcours CU-1 complet (pont mocké) — retrait d'un aliment recalcule le
      total et le budget ; enregistrement crée `Meal` + `MealItem` + met à jour la
      table de référence.
- [ ] Feature : proposition puis édition d'un menu ; reprise d'un repas du menu.
- [ ] Feature : isolation de l'analyse des habitudes et des menus.

**Documentation utilisateur.**
- [ ] `doc/users/repas-photo.md` — enregistrer un repas par photo depuis le smartphone.
- [ ] `doc/users/assistant.md` — dialoguer avec l'assistant, ce qu'il sait, ses limites.
- [ ] `doc/users/menus.md` — planifier une semaine, repas libre, proposition assistée.
- [ ] `doc/users/habitudes.md` — tenir l'analyse des habitudes.

---

## Lot 5 — Bilan périodique, journal de suivi, régularité, import

**Objectif.** Fermer la boucle de pilotage : bilan récurrent avec rappel et ajustement,
journal de suivi, suivi de la régularité, reprise de l'historique `pdp`.

**Couvre.** FR-37 à FR-43, FR-47, FR-48, FR-51, FR-56 à FR-58. CU-3, CU-5 (import),
CU-6 (croisement bilan / contexte).

**Dépendances Laraskel — légères.**

| Besoin | Options |
|---|---|
| Signalement « bilan dû » à la connexion (FR-37) | **Option A (retenue)** : contrôle dans miam à l'entrée du panel + notification / bannière Filament, report sans blocage. **Option B** : s'appuyer sur un mécanisme générique de tableau de bord Laraskel (`prds/navigation-dashboards.md`) s'il est disponible. |

**Étapes.**
- [ ] Migration `reviews` : `user_id`, `reviewed_on`, `observed_slope_kg_per_week`,
      `target_slope_kg_per_week`, `decision`, `conclusion`, `next_review_on` +
      `auditColumns()`.
- [ ] Migration `journal_entries` : `user_id`, `entered_on`, `body`,
      `weigh_in_count_7d`, `meal_count_7d`, `review_id` nullable + `auditColumns()`.
- [ ] Modèles `Review`, `JournalEntry` (+ trait, factories).
- [ ] Service `App\Services\LoggingRegularity` : nombre de pesées / repas sur 7 jours
      glissants, détection d'une baisse marquée par rapport aux périodes précédentes,
      hors périodes de suspension (FR-48, FR-51).
- [ ] Intervalle de bilan sur `tracking_settings` : **options de configuration** —
      valeur libre en jours (**retenu**, défaut 18 j, plage conseillée 14–21) ; ou choix
      contraint parmi 14 / 21 jours. `next_review_on` « active » y est maintenue.
- [ ] Signalement à la connexion quand `next_review_on` est atteinte ou dépassée :
      proposition de faire le bilan tout de suite, report possible sans blocage (FR-37).
- [ ] **Page Filament « Bilan » (CU-3)** : calcule la pente réelle (moyenne mobile 7 j
      via `WeightTrend`) sur la période écoulée et la compare au rythme visé (FR-38) ;
      si l'écart est significatif et persistant, propose un **ajustement du budget
      quotidien** — accepté / modifié / refusé, l'acceptation crée une **nouvelle ligne
      `goals`** (FR-39) ; invite à réévaluer la **maintenance estimée** (FR-40) ;
      exclut les périodes de suspension du calcul et peut décaler la date de bilan
      (FR-43) ; à la clôture, consigne une **entrée de journal** rattachée au bilan avec
      la régularité figée, et fixe `next_review_on` (typiquement +2 à 3 semaines)
      (FR-41).
- [ ] *Resource* Filament « Journal de suivi » : entrées datées, la plus récente en
      premier, création manuelle ou issue d'un bilan (FR-47).
- [ ] Indicateur de régularité de saisie visible + alarme sur baisse marquée, hors
      suspension (FR-51).
- [ ] **Import d'historique `pdp`** :
      - [ ] migrations `import_batches` (`user_id`, `kind`, `source_filename`,
            `imported_at`, `rows_total`, `rows_imported`, `rows_rejected`) et
            `import_rejections` (`import_batch_id`, `line_no`, `raw`, `reason`) +
            `auditColumns()` ;
      - [ ] parseurs `weight.csv` (date, poids, note) et `food.csv` (date, moment,
            description, calories estimées, note) au format `pdp` (FR-56) ;
      - [ ] lignes rejetées tracées avec un motif exploitable (format invalide, date
            manquante), sans interrompre l'import des lignes valides (FR-57) ;
      - [ ] idempotence par **clé naturelle** (utilisateur + date + type + charge utile
            normalisée) vérifiée avant insertion : un ré-import ne crée pas de doublon
            (FR-58) ;
      - [ ] écran Filament d'import : dépôt du fichier, rapport (totaux + liste des
            rejets), possibilité de corriger et relancer.

**Tests.**
- [ ] Unitaire `LoggingRegularity` : comptes sur 7 jours, détection de baisse,
      neutralisation pendant une suspension.
- [ ] Feature : bilan complet (CU-3) — pente calculée, ajustement accepté → nouvelle
      ligne `goals`, entrée de journal créée avec régularité figée, `next_review_on`
      fixée.
- [ ] Feature : signalement d'un bilan dû à la connexion ; report sans blocage.
- [ ] Feature import : lignes valides importées, lignes sans date rejetées avec motif,
      ré-import sans doublon (FR-58), isolation par utilisateur.
- [ ] Feature : entrée de journal manuelle ; ordre antéchronologique.

**Documentation utilisateur.**
- [ ] `doc/users/bilan.md` — faire un bilan, accepter ou refuser un ajustement.
- [ ] `doc/users/journal.md` — tenir le journal de suivi, lire la régularité.
- [ ] `doc/users/import.md` — importer un historique `pdp`, corriger les rejets.

---

## 3. Suivi des lots

| Lot | Périmètre | Prérequis Laraskel | État |
|---|---|---|---|
| 0 | Fondations miam (test, isolation, doc) | `laraskel-core` (fait) | En cours |
| 1 | Comptes et profil | Brique users/rôles + Filament + `TracksAuthor` | **Bloqué** (brique à extraire) |
| 2 | Objectifs, budget, pesées, courbes | Brique visualisation graphique | **Bloqué** (dépend Lot 1 + brique viz) |
| 3 | Journal alimentaire + table de référence (manuel) | Stockage image (léger) | À faire (dépend Lot 1, 2) |
| 4 | Assistant : estimation, coaching, menus | Brique `llm-bridge` | **Bloqué** (brique à extraire et à spécifier) |
| 5 | Bilan, journal, régularité, import | Signalement connexion (léger) | À faire (dépend Lot 2, 3) |
