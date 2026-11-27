<?php

namespace Tests\Feature;

use App\Filament\App\Widgets\CourbeImc;
use App\Filament\App\Widgets\CourbePoids;
use App\Filament\App\Widgets\Tendance;
use App\Models\Goal;
use App\Models\Profile;
use App\Models\SuspensionPeriod;
use App\Models\User;
use App\Models\WeighIn;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Laraskel\Charts\Palette;
use Laraskel\Charts\Widgets\Graphique;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Courbes de poids (FR-28) et d'IMC (FR-29), tendance (CU-2), suspensions
 * grisées (FR-36), rafraîchissement (FR-32), isolation (FR-5).
 */
class WidgetsSuiviTest extends TestCase
{
    private User $utilisateur;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-27 08:00');

        $this->utilisateur = User::factory()->create();
        Profile::factory()->for($this->utilisateur)->create(['height_cm' => 180]);
        Goal::factory()->for($this->utilisateur)->create(['target_weight_kg' => 75, 'target_rate_kg_per_week' => 0.5]);

        foreach (['2026-11-20' => 82.0, '2026-11-24' => 81.4, '2026-11-27' => 81.0] as $date => $poids) {
            WeighIn::factory()->for($this->utilisateur)->create(['measured_on' => $date, 'weight_kg' => $poids]);
        }

        WeighIn::factory()->for(User::factory()->create())->create(['measured_on' => '2026-11-25', 'weight_kg' => 120.0]);
        $this->actingAs($this->utilisateur);
    }

    /**
     * @return array{labels: list<string>, datasets: list<array<string, mixed>>}
     */
    private function donnees(ChartWidget $graphique): array
    {
        return (fn () => $this->getCachedData())->call($graphique);
    }

    public function test_la_courbe_de_poids_montre_pesees_moyenne_et_poids_cible_de_l_utilisateur_seul(): void
    {
        $donnees = $this->donnees(Livewire::test(CourbePoids::class)->set('filter', '4s')->instance());

        $this->assertSame('20/11', $donnees['labels'][0]);
        $this->assertSame('27/11', end($donnees['labels']));
        $this->assertSame(['Pesée', 'Moyenne mobile 7 j', 'Poids cible'], array_column($donnees['datasets'], 'label'));
        $pesees = array_values(array_filter($donnees['datasets'][0]['data'], fn (?float $poids): bool => $poids !== null));
        $this->assertSame([82.0, 81.4, 81.0], $pesees);
        $this->assertNotContains(120.0, $donnees['datasets'][0]['data']);
        $this->assertSame('kg', $donnees['datasets'][0]['unite']);
        $this->assertSame(75.0, $donnees['datasets'][2]['data'][0]);
    }

    public function test_les_pesees_d_une_suspension_sont_grisees(): void
    {
        SuspensionPeriod::factory()->for($this->utilisateur)->create(['starts_on' => '2026-11-23', 'ends_on' => '2026-11-25']);

        $donnees = $this->donnees(Livewire::test(CourbePoids::class)->set('filter', '4s')->instance());
        $index = array_search('24/11', $donnees['labels'], true);

        $this->assertSame(Palette::NEUTRE, $donnees['datasets'][0]['pointBackgroundColor'][$index]);
        $this->assertSame(Palette::couleur(0), $donnees['datasets'][0]['pointBackgroundColor'][$index + 3]);
    }

    public function test_la_courbe_d_imc_porte_les_zones_oms_et_l_imc_cible(): void
    {
        $composant = Livewire::test(CourbeImc::class)->set('filter', '4s');
        $donnees = $this->donnees($composant->instance());
        $options = (fn () => $this->getOptions())->call($composant->instance());

        // 81 kg pour 1,80 m → 25,0 ; poids cible 75 kg → 23,1
        $this->assertSame(25.0, end($donnees['datasets'][0]['data']));
        $this->assertSame(23.1, $donnees['datasets'][1]['data'][0]);
        $this->assertSame(['Maigreur', 'Corpulence normale', 'Surpoids', 'Obésité modérée', 'Obésité sévère', 'Obésité morbide'], array_column($options['plugins']['laraskel']['bandes'], 'libelle'));
    }

    public function test_sans_taille_la_courbe_d_imc_explique_quoi_renseigner(): void
    {
        Profile::query()->delete();

        Livewire::test(CourbeImc::class)
            ->assertSee('Données indisponibles')
            ->assertSee('Renseignez votre taille dans « Mon profil »');
    }

    public function test_sans_pesee_les_courbes_invitent_a_saisir(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CourbePoids::class)->assertSee('Saisissez votre première pesée');
        Livewire::test(Tendance::class)->assertSee('Saisissez une pesée dans « Pesées »');
    }

    public function test_la_tendance_affiche_moyenne_pente_imc_et_distance_au_poids_cible(): void
    {
        // moyenne 7 j le 27/11 : (81,4 + 81,0) / 2 = 81,2
        Livewire::test(Tendance::class)
            ->assertSee(['81,2 kg', 'kg/sem.', 'Rythme visé : -0,50 kg/sem.', '25,1', 'Surpoids', 'Encore 6,2 kg à perdre']);
    }

    public function test_une_nouvelle_pesee_est_prise_en_compte_au_rafraichissement(): void
    {
        $tendance = Livewire::test(Tendance::class)->assertSee('81,2 kg');

        WeighIn::factory()->for($this->utilisateur)->create(['measured_on' => '2026-11-27', 'weight_kg' => 79.0]);

        // le 27/11 : (81,0 + 79,0) / 2 = 80,0 ; moyenne 7 j : (81,4 + 80,0) / 2 = 80,7
        $tendance->dispatch(Graphique::EVENEMENT_RAFRAICHIR)->assertSee('80,7 kg');
    }

    public function test_le_tableau_de_bord_affiche_les_widgets_de_suivi(): void
    {
        $this->get('/app')
            ->assertOk()
            ->assertSeeLivewire(Tendance::class)
            ->assertSeeLivewire(CourbePoids::class)
            ->assertSeeLivewire(CourbeImc::class);
    }
}
