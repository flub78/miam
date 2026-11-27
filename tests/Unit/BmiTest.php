<?php

namespace Tests\Unit;

use App\Enums\ZoneCorpulence;
use App\Services\Bmi;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BmiTest extends TestCase
{
    public function test_l_imc_est_le_poids_divise_par_le_carre_de_la_taille(): void
    {
        $this->assertSame(25.0, Bmi::calculer(81.0, 180));
        $this->assertSame(29.2, Bmi::calculer(92.4, 178));
    }

    /**
     * @return array<string, array{float, ZoneCorpulence}>
     */
    public static function frontieres(): array
    {
        return [
            'juste sous 18,5' => [18.4, ZoneCorpulence::Maigreur],
            '18,5 inclus' => [18.5, ZoneCorpulence::Normale],
            'juste sous 25' => [24.9, ZoneCorpulence::Normale],
            '25 inclus' => [25.0, ZoneCorpulence::Surpoids],
            '30 inclus' => [30.0, ZoneCorpulence::ObesiteModeree],
            '35 inclus' => [35.0, ZoneCorpulence::ObesiteSevere],
            'juste sous 40' => [39.9, ZoneCorpulence::ObesiteSevere],
            '40 inclus' => [40.0, ZoneCorpulence::ObesiteMorbide],
            'très élevé' => [62.0, ZoneCorpulence::ObesiteMorbide],
        ];
    }

    #[DataProvider('frontieres')]
    public function test_les_frontieres_des_zones_oms(float $imc, ZoneCorpulence $zone): void
    {
        $this->assertSame($zone, Bmi::zone($imc));
    }

    public function test_les_zones_se_suivent_sans_trou(): void
    {
        $zones = ZoneCorpulence::cases();

        for ($i = 1; $i < count($zones); $i++) {
            $this->assertSame($zones[$i - 1]->maximum(), $zones[$i]->minimum());
        }
    }

    public function test_une_taille_nulle_est_refusee_avec_un_message_explicite(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('La taille doit être strictement positive');

        Bmi::calculer(80.0, 0);
    }
}
