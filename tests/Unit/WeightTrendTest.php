<?php

namespace Tests\Unit;

use App\Models\SuspensionPeriod;
use App\Models\WeighIn;
use App\Services\WeightTrend;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Tests\TestCase;

class WeightTrendTest extends TestCase
{
    /**
     * @param  array<string, float>  $pesees  date => poids
     * @param  list<array{string, string}>  $suspensions
     */
    private function tendance(array $pesees, array $suspensions = []): WeightTrend
    {
        return new WeightTrend(
            Collection::make($pesees)->map(fn (float $poids, string $date): WeighIn => new WeighIn(['measured_on' => $date, 'weight_kg' => $poids]))->values(),
            Collection::make($suspensions)->map(fn (array $p): SuspensionPeriod => new SuspensionPeriod(['starts_on' => $p[0], 'ends_on' => $p[1], 'reason' => 'Voyage'])),
        );
    }

    /**
     * Une pesée par jour, en perte régulière de 0,1 kg par jour à partir de 80 kg.
     *
     * @return array<string, float>
     */
    private function perteReguliere(string $debut, int $jours): array
    {
        $pesees = [];

        for ($i = 0; $i < $jours; $i++) {
            $pesees[CarbonImmutable::parse($debut)->addDays($i)->toDateString()] = round(80 - 0.1 * $i, 1);
        }

        return $pesees;
    }

    public function test_plusieurs_pesees_d_un_jour_donnent_leur_moyenne(): void
    {
        $tendance = new WeightTrend(Collection::make([
            new WeighIn(['measured_on' => '2026-11-02', 'weight_kg' => 80.0]),
            new WeighIn(['measured_on' => '2026-11-01', 'weight_kg' => 81.0]),
            new WeighIn(['measured_on' => '2026-11-02', 'weight_kg' => 80.4]),
        ]), Collection::make());

        $this->assertSame(['2026-11-01' => 81.0, '2026-11-02' => 80.2], $tendance->poidsJournaliers());
        $this->assertSame(80.2, $tendance->dernierPoids());
    }

    public function test_la_moyenne_mobile_porte_sur_les_7_derniers_jours_pesés(): void
    {
        $tendance = $this->tendance(['2026-11-01' => 90.0, '2026-11-03' => 80.0, '2026-11-05' => 82.0, '2026-11-08' => 81.0]);

        // le 08/11 : fenêtre du 02 au 08 → 80, 82, 81 (le 01/11 en sort)
        $this->assertSame(81.0, $tendance->moyenneMobile(CarbonImmutable::parse('2026-11-08')));
        $this->assertSame(85.0, $tendance->moyenneMobile(CarbonImmutable::parse('2026-11-03')));
        $this->assertNull($tendance->moyenneMobile(CarbonImmutable::parse('2026-10-31')));
    }

    public function test_la_pente_est_exprimee_en_kg_par_semaine(): void
    {
        $tendance = $this->tendance($this->perteReguliere('2026-10-01', 42));

        // après les 7 premiers jours, la moyenne mobile baisse aussi de 0,1 kg/jour
        $this->assertSame(-0.7, $tendance->pente(CarbonImmutable::parse('2026-10-15'), CarbonImmutable::parse('2026-11-11')));
    }

    public function test_une_suspension_est_exclue_de_la_moyenne_et_de_la_pente_mais_les_pesees_restent(): void
    {
        $pesees = $this->perteReguliere('2026-10-01', 42);
        // voyage du 30/10 au 05/11 : +3 kg pendant la période
        foreach ($pesees as $date => $poids) {
            if ($date >= '2026-10-30' && $date <= '2026-11-05') {
                $pesees[$date] = $poids + 3;
            }
        }

        $sansSuspension = $this->tendance($pesees);
        $avecSuspension = $this->tendance($pesees, [['2026-10-30', '2026-11-05']]);
        $debut = CarbonImmutable::parse('2026-10-08');
        $fin = CarbonImmutable::parse('2026-11-11');

        $this->assertSame(79.7, $avecSuspension->poidsJournaliers()['2026-11-03']);
        $this->assertTrue($avecSuspension->estSuspendu(CarbonImmutable::parse('2026-11-05')));
        $this->assertFalse($avecSuspension->estSuspendu(CarbonImmutable::parse('2026-11-06')));
        // juste après la suspension, la fenêtre compte moins de jours : léger écart toléré
        $this->assertEqualsWithDelta(-0.7, $avecSuspension->pente($debut, $fin), 0.05);
        // sans exclusion, la reprise de poids du voyage écrase la pente
        $this->assertEqualsWithDelta(-0.25, $sansSuspension->pente($debut, $fin), 0.05);
        // le 06/11, la fenêtre (31/10 → 06/11) ne garde que la pesée du 06
        $this->assertSame(76.4, $avecSuspension->moyenneMobile(CarbonImmutable::parse('2026-11-06')));
    }

    public function test_pas_de_pente_avec_moins_de_deux_jours_exploitables(): void
    {
        $tendance = $this->tendance(['2026-11-01' => 80.0, '2026-11-05' => 79.0], [['2026-11-05', '2026-11-06']]);

        $this->assertNull($tendance->pente(CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-11-30')));
        $this->assertNull($this->tendance([])->pente(CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-11-30')));
        $this->assertNull($this->tendance([])->dernierPoids());
    }
}
