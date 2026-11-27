<?php

namespace Tests\Feature;

use App\Enums\ActivityLevel;
use App\Enums\Sex;
use App\Filament\App\Pages\Objectifs;
use App\Models\Goal;
use App\Models\Profile;
use App\Models\User;
use App\Models\WeighIn;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Objectifs : budget proposé et détail (FR-6, FR-7), saisie (FR-8),
 * historisation (FR-9), date cible indicative (FR-10), isolation (FR-5).
 */
class ObjectifsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-27 08:00');
    }

    private function utilisateurAvecProfil(): User
    {
        $utilisateur = User::factory()->create();
        Profile::factory()->for($utilisateur)->create([
            'birthdate' => '1970-05-12', 'sex' => Sex::Homme, 'height_cm' => 178,
            'activity_level' => ActivityLevel::Leger, 'start_weight_kg' => 95.0,
        ]);
        WeighIn::factory()->for($utilisateur)->create(['measured_on' => '2026-11-26', 'weight_kg' => 92.4]);

        return $utilisateur;
    }

    public function test_la_page_propose_un_budget_detaille_calcule_sur_la_derniere_pesee(): void
    {
        $this->actingAs($this->utilisateurAvecProfil());

        $this->get('/app/objectifs')->assertOk()->assertSee('Estimation de départ');

        // 92,4 kg, 178 cm, 56 ans, homme, léger, 0,5 kg/sem. (voir CalorieBudgetTest)
        Livewire::test(Objectifs::class)
            ->assertSchemaStateSet(['target_rate_kg_per_week' => 0.5, 'daily_calorie_budget' => 1870])
            ->assertSee(['1 762 kcal', '2 422 kcal', '− 550 kcal', '1 870 kcal']);
    }

    public function test_le_budget_propose_suit_le_rythme_saisi_et_peut_etre_repris(): void
    {
        $this->actingAs($this->utilisateurAvecProfil());

        Livewire::test(Objectifs::class)
            ->fillForm(['target_rate_kg_per_week' => 0.25, 'daily_calorie_budget' => 1500])
            ->assertSee('− 275 kcal')
            ->callAction(TestAction::make('utiliserBudgetPropose')->schemaComponent('proposition'))
            ->assertSchemaStateSet(['daily_calorie_budget' => 2150]);
    }

    public function test_enregistrer_un_objectif_le_conserve_avec_une_date_cible_indicative(): void
    {
        $utilisateur = $this->utilisateurAvecProfil();
        $this->actingAs($utilisateur);

        Livewire::test(Objectifs::class)
            ->fillForm(['target_weight_kg' => 80, 'target_rate_kg_per_week' => 0.5, 'daily_calorie_budget' => 1900, 'protein_target_g' => 120])
            ->call('enregistrer')
            ->assertHasNoFormErrors()
            ->assertNotified('Objectif enregistré');

        $objectif = Goal::sole();
        $this->assertSame($utilisateur->id, $objectif->user_id);
        $this->assertSame(1900, $objectif->daily_calorie_budget);
        $this->assertSame('2026-11-27', $objectif->effective_from->toDateString());
        // 12,4 kg à 0,5 kg/semaine → 174 jours
        $this->assertSame('2027-05-20', $objectif->indicative_target_date?->toDateString());
    }

    public function test_un_changement_d_objectif_cree_une_nouvelle_ligne_et_garde_l_ancienne(): void
    {
        $utilisateur = $this->utilisateurAvecProfil();
        $this->actingAs($utilisateur);
        $ancien = Goal::factory()->for($utilisateur)->create(['effective_from' => '2026-10-01', 'daily_calorie_budget' => 2000, 'target_weight_kg' => 82]);

        Livewire::test(Objectifs::class)
            ->assertSchemaStateSet(['daily_calorie_budget' => 2000])
            ->fillForm(['target_weight_kg' => 80, 'daily_calorie_budget' => 1850])
            ->call('enregistrer')
            ->assertHasNoFormErrors();

        $this->assertSame(2, Goal::count());
        $this->assertSame(2000, $ancien->refresh()->daily_calorie_budget);
        $this->assertSame(1850, Goal::courant()?->daily_calorie_budget);

        Livewire::test(Objectifs::class)
            ->assertCanSeeTableRecords(Goal::all())
            ->assertSchemaStateSet(['daily_calorie_budget' => 1850]);
    }

    public function test_un_profil_incomplet_est_signale_au_lieu_du_budget(): void
    {
        $utilisateur = User::factory()->create();
        Profile::factory()->for($utilisateur)->create(['sex' => null, 'activity_level' => null]);
        WeighIn::factory()->for($utilisateur)->create(['weight_kg' => 80]);
        $this->actingAs($utilisateur);

        Livewire::test(Objectifs::class)
            ->assertSee('renseignez dans « Mon profil » votre sexe, niveau d&#039;activité physique', escape: false)
            ->assertSchemaStateSet(['daily_calorie_budget' => null])
            ->assertActionDisabled(TestAction::make('utiliserBudgetPropose')->schemaComponent('proposition'));
    }

    public function test_sans_aucun_poids_connu_la_page_demande_une_pesee(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Objectifs::class)->assertSee('saisissez une pesée dans « Pesées »');
    }

    public function test_des_valeurs_manquantes_ou_hors_bornes_sont_refusees(): void
    {
        $this->actingAs($this->utilisateurAvecProfil());

        Livewire::test(Objectifs::class)
            ->fillForm(['target_weight_kg' => null, 'target_rate_kg_per_week' => 3, 'daily_calorie_budget' => 300])
            ->call('enregistrer')
            ->assertHasFormErrors([
                'target_weight_kg' => ['Indiquez votre poids cible, en kg.'],
                'target_rate_kg_per_week' => ['Un rythme de plus de 1,5 kg par semaine n\'est pas raisonnable sans suivi médical.'],
                'daily_calorie_budget' => 'min',
            ]);

        $this->assertSame(0, Goal::count());
    }

    public function test_les_objectifs_d_un_autre_utilisateur_n_apparaissent_jamais(): void
    {
        $moi = $this->utilisateurAvecProfil();
        $autre = User::factory()->admin()->create();
        $mien = Goal::factory()->for($moi)->create();

        $this->actingAs($autre);
        Livewire::test(Objectifs::class)->assertCanNotSeeTableRecords([$mien]);
        $this->assertNull(Goal::courant());
    }
}
