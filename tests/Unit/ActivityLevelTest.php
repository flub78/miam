<?php

namespace Tests\Unit;

use App\Enums\ActivityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ActivityLevelTest extends TestCase
{
    /**
     * @return array<string, array{ActivityLevel, string, float}>
     */
    public static function niveaux(): array
    {
        return [
            'sédentaire' => [ActivityLevel::Sedentaire, 'Sédentaire', 1.2],
            'léger' => [ActivityLevel::Leger, 'Léger', 1.375],
            'modéré' => [ActivityLevel::Modere, 'Modéré', 1.55],
            'soutenu' => [ActivityLevel::Soutenu, 'Soutenu', 1.725],
            'intense' => [ActivityLevel::Intense, 'Intense', 1.9],
        ];
    }

    #[DataProvider('niveaux')]
    public function test_chaque_niveau_a_son_libelle_et_son_facteur(ActivityLevel $niveau, string $libelle, float $facteur): void
    {
        $this->assertSame($libelle, $niveau->libelle());
        $this->assertSame($facteur, $niveau->facteur());
    }

    public function test_les_options_du_selecteur_couvrent_les_cinq_niveaux_avec_leur_description(): void
    {
        $options = ActivityLevel::options();

        $this->assertSame(['sedentaire', 'leger', 'modere', 'soutenu', 'intense'], array_keys($options));
        $this->assertSame('Modéré — exercice modéré 3 à 5 jours par semaine', $options['modere']);
    }
}
