# PRD — Suivi de poids et de calories (miam)

- **Statut** : brouillon
- **Date** : 2026-09-01
- **Auteur** : Frédéric Peignot
- **Périmètre** : application `miam` (v1 / MVP)
- **Référence fonctionnelle** : dépôt `pdp` (suivi personnel tenu en fichiers texte) — voir §2

---

## 1. Résumé

miam est une application web de suivi de poids et de calories, utilisable au quotidien
sur smartphone (saisie des repas au moment où ils sont pris, à partir d'une photo) et
sur PC (analyse, bilans, édition du profil et des objectifs).

L'utilisateur renseigne un profil et un objectif de poids ; l'application en déduit un
budget calorique quotidien. Il pèse son poids quelques fois par semaine et décrit ses
repas en langage naturel — l'estimation des calories est faite par un assistant (LLM),
à partir du texte ou d'une photo d'assiette, sans pesée ni comptage précis au quotidien.
L'application affiche l'évolution du poids, de l'IMC et des apports caloriques, et
propose à intervalle régulier un bilan qui compare la perte réelle au rythme visé et
suggère d'ajuster le budget.

miam reprend **les fonctionnalités** de `pdp` ; l'implémentation (application web
multi-utilisateur bâtie sur Laraskel, données en base) est nouvelle.

## 2. Contexte et problème

### 2.1 L'existant : `pdp`

`pdp` est un suivi personnel de perte de poids tenu sous forme de fichiers texte
(Markdown + CSV) édités à la main ou via un assistant IA. Il fonctionne bien pour un
seul utilisateur avancé, mais :

- il n'a pas d'interface : tout passe par l'édition de fichiers ou une conversation ;
- il est mono-utilisateur, sans compte ni authentification ;
- la saisie d'un repas demande deux étapes manuelles sur deux outils différents
  (estimation sur smartphone, puis copier-coller dans un second outil qui écrit le CSV
  et régénère les graphiques) ;
- les graphiques sont régénérés par un script à relancer ;
- les rappels de bilan sont gérés « à la main » dans un fichier de consignes.

### 2.2 Ce que miam doit apporter

Une application web qui offre les mêmes fonctions de suivi, mais :

- avec des comptes utilisateurs (plusieurs personnes, chacune son suivi) ;
- avec une saisie de repas en une seule étape depuis le smartphone (photo → estimation
  → enregistrement) ;
- avec des visualisations toujours à jour, sans étape manuelle ;
- avec des rappels de bilan automatiques.

### 2.3 Ce que miam n'a pas à réinventer

L'authentification, la gestion des comptes et des rôles, les migrations de base, la
visualisation graphique et l'interface LLM sont fournies par Laraskel. Ce PRD décrit
**le comportement attendu de miam**, pas ces briques ni le schéma de données.

### 2.4 Ce qui se reprend de `pdp` comme matériau

- les consignes de coaching (posture « diététicien », méthode de bilan) → comportement
  de l'assistant ;
- `calories_reference.md` → contenu initial de la table de référence calorique ;
- `profile.md`, `goals.md`, `habits.md`, `journal.md` → structure des données de suivi ;
- les trois graphiques (poids, IMC, calories/jour) → spécification des visualisations ;
- `data/weight.csv` et `data/food.csv` → jeu de données d'amorçage (import).

## 3. Objectifs et métriques de succès

| Objectif | Métrique cible |
|---|---|
| Saisie d'un repas rapide sur smartphone | De la photo à l'enregistrement en une seule opération, sans copier-coller entre outils |
| Suivi tenable sans comptage | Aucune saisie de poids d'aliment ou de calorie obligatoire au quotidien ; tout est estimé |
| Signal de perte fiable | Le bilan s'appuie sur une moyenne mobile 7 jours, pas sur la dernière pesée |
| Le suivi reste à jour tout seul | Toute pesée ou tout repas enregistré met à jour les visualisations sans action supplémentaire |
| Les bilans ne sont pas oubliés | Un bilan dû est signalé à l'utilisateur à sa prochaine connexion |
| Plusieurs utilisateurs isolés | Chaque utilisateur ne voit et ne modifie que ses propres données |
| Reprise de l'historique existant | Les données `pdp` (poids, repas) sont importables sans perte |

## 4. Utilisateurs et rôles

| Rôle | Description | Ce qu'il peut faire |
|---|---|---|
| **Administrateur** | Gère l'instance | Créer, activer/désactiver les comptes utilisateurs ; réinitialiser un mot de passe ; il n'accède pas aux données de suivi des autres |
| **Utilisateur** | Personne qui suit son poids | Gérer son profil et ses objectifs ; saisir pesées et repas ; consulter ses visualisations et bilans ; dialoguer avec l'assistant ; importer un historique |

- Rôles plats (pas de hiérarchie), conformément à Laraskel.
- Les comptes sont créés par l'administrateur (pas d'auto-inscription en v1).

## 5. Périmètre

### 5.1 Inclus (v1)

- Compte, authentification, profil, objectifs.
- Saisie et historique des pesées.
- Saisie et historique des repas, avec estimation calorique par l'assistant (texte et
  photo).
- Table de référence calorique des aliments, enrichie au fil de l'eau.
- Visualisations : poids, IMC, calories par jour ; vue budget hebdomadaire.
- Bilan périodique avec rappel automatique et proposition d'ajustement.
- Assistant de coaching contextuel + journal des points de suivi.
- Suivi de la régularité de saisie.
- Tag de contexte sur les repas.
- Menus indicatifs de la semaine.
- Import d'un historique (poids, repas).
- Périodes de suspension du suivi.

### 5.2 Exclu (v1, différé)

- Multi-tenant, moteur de formulaires, calendrier/réservations, gestion documentaire,
  messagerie interne.
- Export CSV / PDF / Excel.
- Sauvegarde/restauration hors-site (au-delà de ce que fournit Laraskel).
- Médiathèque complète (seul l'attachement d'une photo à un repas est requis).
- Application mobile native (le web responsive suffit).
- Suivi de macronutriments détaillé autre que les protéines.
- Connexion à des objets connectés (balance, montre).

## 6. Concepts du domaine

Description fonctionnelle, sans schéma de base.

| Concept | Description |
|---|---|
| **Profil** | Données personnelles stables : date de naissance, sexe, taille, poids et date de départ, niveau d'activité physique, contraintes médicales, allergies/intolérances, préférences alimentaires. |
| **Objectif** | Poids cible (sans date impérative), rythme visé (kg/semaine), budget calorique quotidien, apport protéique cible, date cible indicative. Historisé : un changement d'objectif conserve l'ancienne valeur et sa date. |
| **Pesée** | Une mesure de poids : date, poids en kg, note libre. Plusieurs par semaine, fréquence non imposée. |
| **Repas** | Une prise alimentaire : date, moment (petit-déjeuner, déjeuner, collation, dîner, apéro, dessert…), description en texte libre, calories estimées, note libre, tag de contexte optionnel, photo optionnelle. |
| **Aliment de référence** | Une entrée de la table de référence calorique : libellé, valeur en kcal/100 g (ou 100 ml) **ou** kcal par pièce/portion fixe. Sert à fiabiliser les estimations suivantes. |
| **Bilan** | Un point périodique : date, pente de poids réelle observée, comparaison au rythme visé, décision (ajustement du budget, de l'estimation de maintenance, ou statu quo), texte de conclusion. Fixe la date du bilan suivant. |
| **Entrée de journal de suivi** | Note datée de coaching/décision, la plus récente en premier ; inclut la régularité de saisie de la période. |
| **Analyse des habitudes** | Document vivant : freins identifiés, leviers, hypothèses à tester, stratégies. |
| **Menu de la semaine** | Plan de repas indicatif pour une semaine donnée, distinct du journal réel. |
| **Période de suspension** | Intervalle de dates pendant lequel aucun suivi n'est attendu (voyage, etc.) : pas de saisie demandée, pas d'alarme de régularité, exclu des calculs de pente. |

## 7. Exigences fonctionnelles

### 7.1 Compte et profil

- **FR-1** — Un administrateur crée un compte utilisateur (identité, e-mail) ; l'utilisateur définit son mot de passe via un lien de première connexion.
- **FR-2** — Un administrateur peut désactiver puis réactiver un compte, et déclencher une réinitialisation de mot de passe.
- **FR-3** — L'utilisateur renseigne et modifie son profil (§6). La taille et la date de naissance sont requises pour le calcul du budget ; les autres champs sont optionnels.
- **FR-4** — L'utilisateur enregistre dans son profil un historique libre des changements d'habitudes déjà acquis (ex. « sucre supprimé dans le café »).
- **FR-5** — Chaque utilisateur n'accède qu'à ses propres données de suivi ; aucune vue inter-utilisateurs n'existe, y compris pour l'administrateur.

### 7.2 Objectifs et budget calorique

- **FR-6** — À partir du profil, l'application calcule un **budget calorique quotidien initial** : métabolisme de base (méthode Mifflin-St Jeor), multiplié par un facteur d'activité déduit du niveau d'activité déclaré, moins un déficit correspondant au rythme de perte visé.
- **FR-7** — L'application affiche le détail du calcul (métabolisme de base, dépense de maintenance estimée, déficit, budget) et précise qu'il s'agit d'une estimation de départ, à ajuster sur les données réelles.
- **FR-8** — L'utilisateur peut accepter la valeur proposée ou saisir manuellement son budget, son rythme visé, son poids cible et son apport protéique cible.
- **FR-9** — Toute modification d'un objectif est historisée (valeur précédente + date) ; l'historique est consultable.
- **FR-10** — Le poids cible n'a pas de date impérative ; l'application peut afficher une date cible **indicative** calculée à partir du rythme visé, en signalant qu'elle est non contractuelle.

### 7.3 Suivi du poids

- **FR-11** — L'utilisateur saisit une pesée (date par défaut = aujourd'hui, modifiable ; poids ; note). Il peut saisir une pesée antérieure.
- **FR-12** — Il peut corriger ou supprimer une pesée.
- **FR-13** — L'application calcule et expose une **moyenne mobile 7 jours** du poids, utilisée comme signal de tendance de préférence à la pesée brute.
- **FR-14** — L'application estime la **pente de poids réelle** (kg/semaine) sur une période donnée, à partir de la moyenne mobile.

### 7.4 Journal alimentaire et estimation

- **FR-15** — L'utilisateur enregistre un repas en décrivant son contenu en texte libre ; l'assistant en estime les calories. L'utilisateur peut corriger l'estimation ou la description avant enregistrement.
- **FR-16** — Depuis un smartphone, l'utilisateur prend une **photo de l'assiette** (et du repas complet si utile) ; l'assistant identifie les aliments, propose des quantités et une estimation calorique. L'utilisateur ajuste (aliment mal identifié, quantité), puis enregistre — **en une seule opération**, sans passer par un autre outil.
- **FR-17** — Avant d'enregistrer, l'application affiche le **budget restant de la journée** (budget quotidien − repas déjà enregistrés) pour aider à décider (retirer un aliment si le total dépasse, en ajouter s'il reste de la marge).
- **FR-18** — Chaque repas porte un **moment** (liste ouverte : petit-déjeuner, déjeuner, collation, dîner, apéro, goûter, dessert…) et, optionnellement, un **tag de contexte** (ex. `aéroclub`, `club marche`, `repas amis`).
- **FR-19** — Une **photo** peut rester attachée au repas ; sa suppression est possible sans supprimer le repas.
- **FR-20** — L'utilisateur peut corriger ou supprimer un repas, et saisir un repas à une date antérieure.
- **FR-21** — L'estimation par photo est faite côté smartphone au moment du repas ; l'application n'impose pas d'être en ligne au domicile pour enregistrer.

### 7.5 Table de référence calorique

- **FR-22** — L'application maintient une table de référence des aliments déjà rencontrés : libellé + valeur kcal/100 g (ou 100 ml) ou kcal par pièce/portion fixe.
- **FR-23** — Lorsqu'un repas est estimé avec un **poids ou une quantité précise** pour un aliment absent de la table, l'aliment y est ajouté automatiquement, sans demande de confirmation, au moment de l'enregistrement du repas.
- **FR-24** — Si un aliment déjà présent reçoit une nouvelle estimation significativement différente, la valeur existante est **ajustée** (pas de doublon).
- **FR-25** — L'assistant réutilise la table de référence en priorité pour estimer un aliment déjà connu.
- **FR-26** — L'utilisateur peut consulter, corriger et supprimer les entrées de la table ; la liste est triable et filtrable par libellé.
- **FR-27** — La table est initialisée avec le contenu de `calories_reference.md` de `pdp`.

### 7.6 Visualisations

- **FR-28** — **Courbe de poids** : points de pesée, moyenne mobile 7 jours, ligne horizontale du poids cible.
- **FR-29** — **Courbe d'IMC** : IMC calculé à partir du poids et de la taille, avec bandes de couleur des zones de corpulence (OMS) et repère du poids cible.
- **FR-30** — **Calories par jour** : barres de la somme des calories estimées par jour, colorées selon l'écart au budget (du vert près du budget au rouge près de la dépense de maintenance), avec lignes de repère « budget » et « maintenance ».
- **FR-31** — **Vue budget hebdomadaire** : cumul des calories de la semaine face au budget hebdomadaire (budget quotidien × 7), pour situer un écart ponctuel dans la semaine plutôt que dans la seule journée.
- **FR-32** — Toute création, modification ou suppression de pesée ou de repas met à jour les visualisations sans action de l'utilisateur.
- **FR-33** — Les visualisations sont lisibles sur smartphone comme sur PC.
- **FR-34** — Chaque visualisation offre le détail d'un point au survol ou au tap (date, valeur, éléments du jour).
- **FR-35** — L'utilisateur choisit la période affichée (ex. 4 semaines, 3 mois, tout).
- **FR-36** — Les périodes de suspension sont visuellement distinguées et n'introduisent pas de rupture trompeuse dans les courbes.

### 7.7 Bilan périodique

- **FR-37** — L'application connaît une **date de prochain bilan**. Quand elle est atteinte ou dépassée, elle le signale à l'utilisateur à sa connexion et propose de faire le bilan tout de suite (report possible, sans blocage).
- **FR-38** — Le bilan calcule la pente de poids réelle (moyenne mobile 7 jours) sur la période écoulée et la compare au rythme visé dans les objectifs.
- **FR-39** — Si l'écart est significatif et persistant, le bilan **propose un ajustement du budget calorique quotidien** ; l'utilisateur accepte, modifie ou refuse.
- **FR-40** — Le bilan invite à réévaluer la **dépense de maintenance estimée** (possibilité d'adaptation métabolique après plusieurs semaines de déficit).
- **FR-41** — Les conclusions du bilan sont enregistrées comme une entrée de journal de suivi, et la **date du bilan suivant** est fixée (typiquement +2 à 3 semaines).
- **FR-42** — L'intervalle par défaut entre deux bilans est de 2 à 3 semaines ; il est configurable par l'utilisateur.
- **FR-43** — Les périodes de suspension sont exclues du calcul de la pente et peuvent décaler la date de bilan.

### 7.8 Assistant de coaching et journal de suivi

- **FR-44** — L'utilisateur dialogue avec un assistant qui adopte une **posture de diététicien/nutritionniste** : analyses fondées sur des données établies (physiologie, littérature sur la perte de poids, apports de référence), et non sur des régimes à la mode. En cas de données insuffisantes, l'assistant le dit plutôt que d'extrapoler.
- **FR-45** — L'assistant dispose du **contexte de l'utilisateur** : profil, objectifs (et historique), pesées, repas, table de référence, analyse des habitudes, entrées de journal.
- **FR-46** — L'assistant peut recevoir texte et image (photo d'assiette).
- **FR-47** — L'utilisateur tient un **journal de suivi** : entrées datées, la plus récente en premier, consignant décisions et ajustements. Une entrée peut être créée manuellement ou lors d'un bilan.
- **FR-48** — Chaque entrée de journal note la **régularité de saisie** de la période (nombre de pesées et de repas enregistrés sur les 7 derniers jours).

### 7.9 Habitudes et contexte

- **FR-49** — L'utilisateur tient une **analyse des habitudes** (freins, leviers, hypothèses à tester, stratégies) sous forme de document éditable.
- **FR-50** — L'application restitue, sur une période choisie, la **fréquence et l'impact calorique cumulé** des repas par tag de contexte, pour objectiver si un contexte social freine réellement la perte.
- **FR-51** — L'application expose un **indicateur de régularité de saisie** et signale une baisse marquée de régularité (moins de pesées/repas que sur les périodes précédentes), hors périodes de suspension.

### 7.10 Menus de la semaine

- **FR-52** — L'utilisateur crée, pour une semaine donnée, un **menu indicatif** (repas proposés, quantités et calories indicatives), distinct du journal réel.
- **FR-53** — Un menu peut prévoir un **repas libre** par semaine (pas de quantification).
- **FR-54** — L'assistant peut proposer un menu de semaine à partir du profil, des objectifs, des préférences et de la saison ; l'utilisateur l'édite.
- **FR-55** — Un repas du menu peut être repris comme repas réel en un geste, avec ajustement éventuel.

### 7.11 Import d'un historique

- **FR-56** — L'utilisateur importe un historique de pesées (date, poids, note) et de repas (date, moment, description, calories estimées, note) au format des fichiers `pdp` (`weight.csv`, `food.csv`).
- **FR-57** — L'import signale les lignes rejetées (format invalide, date manquante) avec un message permettant de corriger, sans interrompre l'import des lignes valides.
- **FR-58** — L'import est rejouable sans créer de doublons pour des lignes identiques déjà présentes.

### 7.12 Suspension du suivi

- **FR-59** — L'utilisateur déclare une période de suspension (dates de début et de fin, motif libre).
- **FR-60** — Pendant une suspension, aucune saisie n'est demandée, aucune alarme de régularité n'est émise, et la date de bilan est décalée si nécessaire.
- **FR-61** — Les données saisies malgré tout pendant une suspension sont conservées mais exclues des calculs de pente.

## 8. Cas d'usage

### CU-1 — Enregistrer un repas depuis le smartphone

1. Au restaurant, l'utilisateur ouvre miam sur son téléphone et choisit « Nouveau repas ».
2. Il prend une photo de son assiette.
3. L'assistant liste les aliments identifiés, une quantité estimée et une estimation calorique totale.
4. L'application affiche le budget restant de la journée.
5. L'utilisateur voit qu'il dépasserait ; il retire un aliment de la liste et l'estimation se recalcule.
6. Il corrige une quantité mal estimée, choisit le moment « déjeuner », ajoute le tag `repas amis`, puis enregistre.
7. Le repas apparaît dans l'historique ; la vue calories/jour et le budget hebdomadaire se mettent à jour ; les aliments pesés inconnus sont ajoutés à la table de référence.

### CU-2 — Peser et consulter la tendance

1. Le matin, l'utilisateur saisit son poids.
2. La courbe de poids se met à jour ; la moyenne mobile 7 jours et la pente estimée sur 3 semaines s'affichent.
3. La courbe d'IMC montre sa position dans les zones de corpulence et la distance au poids cible.

### CU-3 — Bilan périodique

1. À la connexion, miam signale qu'un bilan est dû depuis 2 jours et propose de le faire.
2. L'utilisateur accepte. Le bilan affiche : pente réelle −0,25 kg/semaine, rythme visé −0,45 kg/semaine.
3. Il propose de réduire le budget quotidien de 150 kcal et de réviser la maintenance estimée à la baisse.
4. L'utilisateur accepte l'ajustement du budget, refuse celui de la maintenance.
5. Le bilan est consigné au journal (avec la régularité de saisie de la période) et la date du prochain bilan est fixée à +3 semaines.

### CU-4 — Voyage

1. Avant un voyage gastronomique d'une semaine, l'utilisateur déclare une période de suspension.
2. Pendant le voyage, aucune relance ni alarme.
3. Au retour, le suivi reprend ; la période de voyage est grisée dans les courbes et ignorée dans le calcul de pente ; la date du prochain bilan a été décalée pour laisser passer la stabilisation du poids d'eau.

### CU-5 — Démarrage d'un nouvel utilisateur avec historique

1. L'administrateur crée le compte ; l'utilisateur définit son mot de passe.
2. L'utilisateur renseigne son profil ; l'application propose un budget calorique, qu'il ajuste.
3. Il importe ses fichiers `weight.csv` et `food.csv` existants.
4. Deux lignes de repas sans date sont rejetées avec un message ; il les corrige et relance l'import sans créer de doublons.
5. Les courbes affichent immédiatement l'historique importé.

### CU-6 — Contexte social sous surveillance

1. Sur 4 semaines, l'utilisateur tague ses repas d'apéro à l'aéroclub et au club de marche.
2. Il ouvre la vue « contexte » : 6 repas tagués, +2 900 kcal cumulés au-delà d'un repas normal.
3. Le bilan de fin de mois croise cette donnée avec les semaines où la perte a ralenti.

## 9. Hypothèses et questions ouvertes

- **Estimation LLM hors ligne** : l'estimation par photo suppose une connexion au moment du repas. Comportement attendu si l'utilisateur est hors ligne (file d'attente locale ? saisie texte de secours ?) — à préciser.
- **Étalonnage** : `pdp` prévoit des pesées d'aliments ponctuelles pour étalonner les estimations. Faut-il un mode « repas pesé » explicite, ou l'utilisateur indique-t-il simplement les poids dans la description ?
- **Facteur d'activité** : correspondance entre le « niveau d'activité » déclaré (texte libre dans `pdp`) et un facteur chiffré — liste de niveaux prédéfinis à définir.
- **Confidentialité** : les photos d'assiette et le contexte de santé sont des données sensibles ; les exigences de conservation/suppression restent à cadrer.
- **Multi-objectifs** : la v1 vise la perte de poids ; la stabilisation (maintien) après atteinte de la cible est-elle dans le périmètre v1 ou différée ?

## 10. Références

- Dépôt `pdp` : `README.md`, `CLAUDE.md`, `profile.md`, `goals.md`, `habits.md`, `journal.md`, `menus.md`, `calories_reference.md`, `data/weight.csv`, `data/food.csv`, `courbes.html`.
- Laraskel : `doc/todo.md` (priorisation des modules pour la première application), `README.md` §7 (visualisation), `doc/prds/gestion-utilisateurs-roles.md`, `doc/prds/interface-llm.md`, `doc/prds/navigation-dashboards.md`.
