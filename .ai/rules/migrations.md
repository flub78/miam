---
paths:
  - 'database/migrations/**'
---

# Migrations

## Colonnes d'audit obligatoires
Toute nouvelle table porte `created_at` + `updated_at` (`$table->timestamps()`) et `created_by` + `updated_by` référençant l'utilisateur qui a créé puis modifié la ligne en dernier. Les migrations du framework (`users`, `cache`, `jobs`) sont antérieures à cette règle et exemptées.

## Utiliser la macro Blueprint `auditColumns()`
Le package cœur Laraskel enregistre les macros Blueprint `auditColumns()` et `dropAuditColumns()`. Dans la migration d'une nouvelle table, appeler `$table->auditColumns()` (ajoute les FK `created_by` / `updated_by` nullable vers `users`, `nullOnDelete`) plutôt que de les écrire à la main ; utiliser `$table->dropAuditColumns()` dans `down()`. Le remplissage côté modèle depuis `Auth::id()` est fourni par le trait du package cœur.

## Les migrations des packages tiers sont exemptées
La règle des colonnes d'audit s'applique aux migrations écrites pour ce projet. Les migrations publiées par des packages tiers (`spatie/laravel-permission`, Fortify, etc.) sont laissées telles que le package les livre — même exemption que les tables du framework.
