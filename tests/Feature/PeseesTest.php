<?php

namespace Tests\Feature;

use App\Filament\App\Resources\WeighIns\Pages\ManageWeighIns;
use App\Models\SuspensionPeriod;
use App\Models\User;
use App\Models\WeighIn;
use App\Services\WeightTrend;
use Carbon\CarbonImmutable;
use Laraskel\Charts\Widgets\Graphique;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pesées (FR-11, FR-12), rafraîchissement des courbes (FR-32), isolation (FR-5)
 * et suspensions (FR-59, FR-61).
 */
class PeseesTest extends TestCase
{
    public function test_un_utilisateur_saisit_une_pesee_datee_d_aujourd_hui_par_defaut(): void
    {
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);

        $this->get('/app/pesees')->assertOk()->assertSee('Pesées');

        Livewire::test(ManageWeighIns::class)
            ->mountAction('create')
            ->assertSchemaStateSet(['measured_on' => now()->toDateString()], 'mountedActionSchema0')
            ->fillForm(['weight_kg' => 84.3, 'note' => 'après le footing'], 'mountedActionSchema0')
            ->callMountedAction()
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertDispatched(Graphique::EVENEMENT_RAFRAICHIR);

        $pesee = WeighIn::sole();
        $this->assertSame($utilisateur->id, $pesee->user_id);
        $this->assertSame(now()->toDateString(), $pesee->measured_on->toDateString());
        $this->assertSame('84.3', $pesee->weight_kg);
        $this->assertSame($utilisateur->id, $pesee->created_by);
    }

    public function test_une_pesee_anterieure_est_acceptee_mais_pas_une_pesee_future(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageWeighIns::class)
            ->callAction('create', data: ['measured_on' => '2026-01-15', 'weight_kg' => 88])
            ->assertHasNoFormErrors();

        Livewire::test(ManageWeighIns::class)
            ->callAction('create', data: ['measured_on' => now()->addDay()->toDateString(), 'weight_kg' => 88])
            ->assertHasFormErrors(['measured_on']);

        $this->assertSame(['2026-01-15'], WeighIn::pluck('measured_on')->map->toDateString()->all());
    }

    public function test_un_poids_manquant_ou_aberrant_est_refuse_avec_un_message(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageWeighIns::class)
            ->callAction('create', data: ['weight_kg' => null])
            ->assertHasFormErrors(['weight_kg' => ['Indiquez le poids mesuré, en kg (entre 20 et 400).']]);

        Livewire::test(ManageWeighIns::class)
            ->callAction('create', data: ['weight_kg' => 850])
            ->assertHasFormErrors(['weight_kg' => 'max']);

        $this->assertSame(0, WeighIn::count());
    }

    public function test_un_utilisateur_corrige_puis_supprime_une_pesee_et_les_courbes_se_rafraichissent(): void
    {
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);
        $pesee = WeighIn::factory()->for($utilisateur)->create(['weight_kg' => 90.0]);

        Livewire::test(ManageWeighIns::class)
            ->callTableAction('edit', $pesee, data: ['weight_kg' => 80.9])
            ->assertHasNoTableActionErrors()
            ->assertDispatched(Graphique::EVENEMENT_RAFRAICHIR);

        $this->assertSame('80.9', $pesee->refresh()->weight_kg);

        Livewire::test(ManageWeighIns::class)
            ->callTableAction('delete', $pesee)
            ->assertDispatched(Graphique::EVENEMENT_RAFRAICHIR);

        $this->assertModelMissing($pesee);
    }

    public function test_les_pesees_sont_isolees_entre_utilisateurs(): void
    {
        $moi = User::factory()->create();
        $autre = User::factory()->admin()->create();
        $miennes = WeighIn::factory()->for($moi)->count(2)->create();
        $sienne = WeighIn::factory()->for($autre)->create();

        $this->actingAs($autre);
        Livewire::test(ManageWeighIns::class)
            ->assertCanSeeTableRecords([$sienne])
            ->assertCanNotSeeTableRecords($miennes);

        $this->assertFalse($autre->can('update', $miennes->first()));
    }

    public function test_une_pesee_pendant_une_suspension_reste_listee_mais_est_exclue_de_la_pente(): void
    {
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);
        $aujourdhui = CarbonImmutable::today();

        foreach ([20, 13, 6, 0] as $jours) {
            WeighIn::factory()->for($utilisateur)->create(['measured_on' => $aujourdhui->subDays($jours), 'weight_kg' => 80 - (20 - $jours) * 0.1]);
        }

        $pendantVoyage = WeighIn::factory()->for($utilisateur)->create(['measured_on' => $aujourdhui->subDays(3), 'weight_kg' => 84.0]);
        SuspensionPeriod::factory()->for($utilisateur)->create(['starts_on' => $aujourdhui->subDays(4), 'ends_on' => $aujourdhui->subDays(2)]);

        Livewire::test(ManageWeighIns::class)
            ->assertCanSeeTableRecords([$pendantVoyage])
            ->assertSee('Pendant une suspension : exclue de la tendance');

        $pente = WeightTrend::utilisateurCourant()->pente($aujourdhui->subDays(20), $aujourdhui);
        $this->assertNotNull($pente);
        $this->assertLessThan(0, $pente);
    }
}
