<?php

namespace App\Filament\App\Widgets;

use App\Filament\App\Widgets\Concerns\JoursSuivis;
use App\Models\Goal;
use App\Services\WeightTrend;
use Carbon\CarbonImmutable;
use Laraskel\Charts\Donnees\JeuDeDonnees;
use Laraskel\Charts\Donnees\Serie;
use Laraskel\Charts\Palette;
use Laraskel\Charts\Periode;
use Laraskel\Charts\TypeGraphique;
use Laraskel\Charts\Widgets\Graphique;

/**
 * Courbe de poids (FR-28) : pesées, moyenne mobile 7 jours, poids cible.
 * Les pesées d'une suspension sont grisées et exclues de la moyenne (FR-36).
 */
class CourbePoids extends Graphique
{
    use JoursSuivis;

    protected ?string $heading = 'Poids';

    protected ?string $description = 'Pesées, moyenne mobile sur 7 jours et poids cible. Points gris : pesées pendant une suspension, exclues de la tendance.';

    protected bool $selecteurPeriode = true;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected ?string $emptyStateDescription = 'Saisissez votre première pesée dans « Pesées » pour voir apparaître la courbe.';

    protected function type(): TypeGraphique
    {
        return TypeGraphique::Courbe;
    }

    protected function jeuDeDonnees(?Periode $periode): JeuDeDonnees
    {
        $tendance = WeightTrend::utilisateurCourant();
        $jours = $this->joursAffiches($tendance, $periode);

        if (! $this->unJourPese($tendance, $jours)) {
            return JeuDeDonnees::vide();
        }

        $poids = $tendance->poidsJournaliers();
        $series = [
            new Serie(
                'Pesée',
                array_map(fn (CarbonImmutable $jour): ?float => $poids[$jour->toDateString()] ?? null, $jours),
                couleursParPoint: $this->couleursDesPoints($tendance, $jours, Palette::couleur(0)),
                unite: 'kg',
            ),
            new Serie(
                'Moyenne mobile 7 j',
                array_map(fn (CarbonImmutable $jour): ?float => $tendance->moyenneMobile($jour), $jours),
                unite: 'kg',
            ),
        ];

        $objectif = Goal::courant();

        if ($objectif !== null) {
            $series[] = new Serie('Poids cible', array_fill(0, count($jours), (float) $objectif->target_weight_kg), pointilles: true, unite: 'kg');
        }

        return new JeuDeDonnees($this->etiquettes($jours), $series, axeY: 'kg');
    }
}
