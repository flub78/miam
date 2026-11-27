<?php

namespace Tests\Unit;

use App\Enums\ActivityLevel;
use App\Enums\Sex;
use App\Exceptions\DonneesInsuffisantes;
use App\Models\Profile;
use App\Services\CalorieBudget;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Valeurs de référence calculées à la main : Mifflin-St Jeor
 * (10 × poids + 6,25 × taille − 5 × âge + 5 homme / − 161 femme).
 */
class CalorieBudgetTest extends TestCase
{
    private function profil(Sex $sexe, string $naissance, int $taille, ActivityLevel $activite): Profile
    {
        return new Profile(['sex' => $sexe, 'birthdate' => $naissance, 'height_cm' => $taille, 'activity_level' => $activite]);
    }

    private function aujourdhui(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-11-27');
    }

    /**
     * @return array<string, array{Sex, string, int, float, ActivityLevel, float, int, int, int, int, bool}>
     */
    public static function references(): array
    {
        return [
            // 56 ans : 924 + 1112,5 − 280 + 5 = 1761,5 ; × 1,375 = 2422 ; − 550 = 1872 → 1870
            'homme, léger, 0,5 kg/sem.' => [Sex::Homme, '1970-05-12', 178, 92.4, ActivityLevel::Leger, 0.5, 1762, 2422, 550, 1870, false],
            // 41 ans : 700 + 1031,25 − 205 − 161 = 1365,25 ; × 1,55 = 2116 ; − 275 = 1841 → 1840
            'femme, modérée, 0,25 kg/sem.' => [Sex::Femme, '1985-03-01', 165, 70.0, ActivityLevel::Modere, 0.25, 1365, 2116, 275, 1840, false],
            // maintien : 1761,5 × 1,9 = 3346,85 → 3350
            'homme, intense, maintien' => [Sex::Homme, '1970-05-12', 178, 92.4, ActivityLevel::Intense, 0.0, 1762, 3347, 0, 3350, false],
            // 1638 − 825 = 813 : relevé au plancher de 1200
            'femme, sédentaire, 0,75 kg/sem.' => [Sex::Femme, '1985-03-01', 165, 70.0, ActivityLevel::Sedentaire, 0.75, 1365, 1638, 825, 1200, true],
        ];
    }

    #[DataProvider('references')]
    public function test_valeurs_de_reference(Sex $sexe, string $naissance, int $taille, float $poids, ActivityLevel $activite, float $rythme, int $metabolisme, int $maintenance, int $deficit, int $budget, bool $plancher): void
    {
        $detail = CalorieBudget::calculer($this->profil($sexe, $naissance, $taille, $activite), $poids, $rythme, $this->aujourdhui());

        $this->assertSame($metabolisme, $detail->metabolismeDeBase);
        $this->assertSame($maintenance, $detail->maintenance);
        $this->assertSame($deficit, $detail->deficit);
        $this->assertSame($budget, $detail->budget);
        $this->assertSame($plancher, $detail->plancherApplique);
    }

    public function test_le_budget_est_coherent_avec_son_detail(): void
    {
        $detail = CalorieBudget::calculer($this->profil(Sex::Femme, '1985-03-01', 165, ActivityLevel::Modere), 70.0, 0.5, $this->aujourdhui());

        $this->assertEqualsWithDelta($detail->maintenance - $detail->deficit, $detail->budget, 5);
    }

    public function test_un_profil_incomplet_est_refuse_en_nommant_les_champs_manquants(): void
    {
        $this->expectException(DonneesInsuffisantes::class);
        $this->expectExceptionMessage('renseignez dans « Mon profil » votre sexe, niveau d\'activité physique');

        CalorieBudget::calculer(new Profile(['birthdate' => '1980-01-01', 'height_cm' => 170]), 80.0, 0.5, $this->aujourdhui());
    }

    public function test_sans_profil_tous_les_champs_sont_demandes(): void
    {
        $this->expectException(DonneesInsuffisantes::class);
        $this->expectExceptionMessage('date de naissance, taille, sexe');

        CalorieBudget::calculer(null, 80.0, 0.5, $this->aujourdhui());
    }

    public function test_la_date_cible_indicative_suit_le_rythme_vise(): void
    {
        // 12,4 kg à 0,5 kg/semaine = 173,6 jours → 174
        $this->assertSame('2027-05-20', CalorieBudget::dateCibleIndicative(92.4, 80.0, 0.5, $this->aujourdhui())?->toDateString());
    }

    public function test_pas_de_date_cible_sans_rythme_ou_cible_deja_atteinte(): void
    {
        $this->assertNull(CalorieBudget::dateCibleIndicative(80.0, 75.0, 0.0, $this->aujourdhui()));
        $this->assertNull(CalorieBudget::dateCibleIndicative(74.0, 75.0, 0.5, $this->aujourdhui()));
    }
}
