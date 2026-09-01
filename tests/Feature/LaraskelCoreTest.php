<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Vérifie que la brique cœur `flub78/laraskel-core` est bien câblée dans miam :
 * la page de santé répond et les macros de schéma `auditColumns()` /
 * `dropAuditColumns()` sont enregistrées.
 *
 * Le test monte lui-même les tables dont il a besoin (`users` si absente, plus
 * une table sonde) et les retire ensuite, sans dépendre de l'état initial de la
 * base ni la modifier durablement.
 */
class LaraskelCoreTest extends TestCase
{
    private bool $usersTableCreee = false;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('sonde_audit');

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table): void {
                $table->id();
            });
            $this->usersTableCreee = true;
        }
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('sonde_audit');

        if ($this->usersTableCreee) {
            Schema::dropIfExists('users');
        }

        parent::tearDown();
    }

    public function test_la_page_de_sante_est_servie_par_la_brique_coeur(): void
    {
        $response = $this->get('/laraskel/sante');

        $response->assertOk();
        $response->assertSee('flub78/laraskel-core');
        $response->assertSee('brique cœur active', false);
    }

    public function test_la_macro_audit_columns_pose_created_by_et_updated_by(): void
    {
        Schema::create('sonde_audit', function (Blueprint $table): void {
            $table->id();
            $table->auditColumns();
        });

        $this->assertTrue(Schema::hasColumns('sonde_audit', ['created_by', 'updated_by']));

        $clesEtrangeres = collect(Schema::getForeignKeys('sonde_audit'));
        $this->assertSame(['users'], $clesEtrangeres->pluck('foreign_table')->unique()->values()->all());
        $this->assertSame(
            ['created_by', 'updated_by'],
            $clesEtrangeres->flatMap(fn (array $fk): array => $fk['columns'])->sort()->values()->all(),
        );

        // Colonnes nullables : une insertion sans auteur passe.
        DB::table('sonde_audit')->insert(['id' => 1]);
        $this->assertNull(DB::table('sonde_audit')->where('id', 1)->value('created_by'));
    }

    public function test_la_macro_drop_audit_columns_retire_les_colonnes(): void
    {
        Schema::create('sonde_audit', function (Blueprint $table): void {
            $table->id();
            $table->auditColumns();
        });

        Schema::table('sonde_audit', function (Blueprint $table): void {
            $table->dropAuditColumns();
        });

        $this->assertFalse(Schema::hasColumn('sonde_audit', 'created_by'));
        $this->assertFalse(Schema::hasColumn('sonde_audit', 'updated_by'));
    }
}
