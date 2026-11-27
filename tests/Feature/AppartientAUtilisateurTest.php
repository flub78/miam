<?php

namespace Tests\Feature;

use App\Models\Concerns\AppartientAUtilisateur;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Isolation stricte par utilisateur apportée par le trait
 * {@see AppartientAUtilisateur} : deux utilisateurs ne voient jamais les lignes
 * l'un de l'autre, `user_id` est rempli automatiquement, et hors contexte
 * authentifié le scope se retire.
 *
 * Le test monte sa propre table sonde et la retire ensuite.
 */
class AppartientAUtilisateurTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('sonde_appartenance', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('libelle');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('sonde_appartenance');

        parent::tearDown();
    }

    public function test_un_utilisateur_ne_voit_que_ses_propres_lignes(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $this->actingAs($alice);
        SondeAppartenance::create(['libelle' => 'ligne alice']);

        $this->actingAs($bob);
        SondeAppartenance::create(['libelle' => 'ligne bob']);

        $this->assertSame(['ligne bob'], SondeAppartenance::pluck('libelle')->all());

        $this->actingAs($alice);
        $this->assertSame(['ligne alice'], SondeAppartenance::pluck('libelle')->all());
    }

    public function test_user_id_est_affecte_automatiquement_a_la_creation(): void
    {
        $alice = User::factory()->create();
        $this->actingAs($alice);

        $ligne = SondeAppartenance::create(['libelle' => 'peu importe']);

        $this->assertSame($alice->id, $ligne->user_id);
        $this->assertTrue($ligne->user()->is($alice));
    }

    public function test_une_valeur_user_id_explicite_n_est_pas_ecrasee(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $this->actingAs($alice);

        $ligne = SondeAppartenance::create([
            'user_id' => $bob->id,
            'libelle' => 'attribuée à bob',
        ]);

        $this->assertSame($bob->id, $ligne->user_id);
    }

    public function test_hors_contexte_authentifie_le_scope_ne_filtre_pas(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        DB::table('sonde_appartenance')->insert([
            ['user_id' => $alice->id, 'libelle' => 'a'],
            ['user_id' => $bob->id, 'libelle' => 'b'],
        ]);

        $this->assertCount(2, SondeAppartenance::all());
    }
}

/**
 * Modèle sonde : le plus petit modèle possible portant le trait sous test.
 */
class SondeAppartenance extends Model
{
    use AppartientAUtilisateur;

    protected $table = 'sonde_appartenance';

    protected $guarded = [];

    public $timestamps = false;
}
