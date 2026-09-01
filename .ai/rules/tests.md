---
paths:
  - 'tests/**'
---

# Tests

## Isolation et propriété des données de test
Les tests doivent pouvoir tourner sur une copie de la base de production et la laisser dans l'état où ils l'ont trouvée.
- Ne jamais dépendre de l'état initial de la base (lignes existantes, comptes, IDs) ; monter ses données via des factories et n'asserter que sur ce qu'on a créé.
- Nettoyer après exécution les données créées ; ne jamais supprimer tables ou données sans les restaurer.
- Contre une base partagée : utiliser `DatabaseTransactions`, pas `RefreshDatabase` ni `migrate:fresh`.
- Fixtures fichier sous `sys_get_temp_dir()` + `uniqid()`, retirées en `afterEach`.
- TDD pour les corrections de bug : écrire d'abord le test qui échoue, puis corriger.
- Supprimer les tests créés uniquement pour investiguer un bug une fois l'investigation terminée.
- Lancer les tests concernés avant d'annoncer une fonctionnalité comme terminée.
