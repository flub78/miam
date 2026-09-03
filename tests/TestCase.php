<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Classe de base des tests Feature.
 *
 * La suite tourne sur SQLite en mémoire (voir `phpunit.xml`). {@see RefreshDatabase}
 * rejoue les migrations à neuf une fois, puis enveloppe chaque test dans une
 * transaction annulée à la fin : aucune donnée ne survit d'un test à l'autre.
 *
 * La règle « utiliser `DatabaseTransactions`, pas `RefreshDatabase` » de
 * `.ai/rules/tests.md` ne vise que l'exécution **contre une base partagée** (copie
 * de production). Elle ne s'applique pas ici, où la base est jetable et recréée à
 * chaque exécution.
 *
 * Squelette d'un test qui « monte et nettoie ses données » :
 * - créer les lignes nécessaires via les factories, dans le test ou `setUp()` ;
 * - n'asserter que sur les lignes créées, jamais sur l'état initial de la base ;
 * - le nettoyage est automatique (transaction annulée) ;
 * - pour une table hors migrations (table sonde d'un test de trait), la créer
 *   dans `setUp()` avec `Schema::create()` et la retirer dans `tearDown()` avec
 *   `Schema::dropIfExists()`.
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;
}
