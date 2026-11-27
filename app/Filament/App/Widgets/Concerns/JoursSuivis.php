<?php

namespace App\Filament\App\Widgets\Concerns;

use App\Services\WeightTrend;
use Carbon\CarbonImmutable;
use Laraskel\Charts\Palette;
use Laraskel\Charts\Periode;

/**
 * Axe des jours commun aux courbes de suivi : un point par jour, de la première
 * pesée (ou du début de la période choisie) à aujourd'hui, pour que l'écart
 * entre deux points reflète le temps écoulé.
 */
trait JoursSuivis
{
    /**
     * @return list<CarbonImmutable>
     */
    protected function joursAffiches(WeightTrend $tendance, ?Periode $periode): array
    {
        $premier = $tendance->premierJour();

        if ($premier === null) {
            return [];
        }

        $aujourdhui = CarbonImmutable::today();
        $debutPeriode = $periode?->debut($aujourdhui);
        $jour = $debutPeriode !== null && $debutPeriode->greaterThan($premier) ? $debutPeriode : $premier;
        $jours = [];

        for (; $jour->lessThanOrEqualTo($aujourdhui); $jour = $jour->addDay()) {
            $jours[] = $jour;
        }

        return $jours;
    }

    /**
     * Vrai si au moins un jour affiché porte une pesée : sinon, l'état vide de
     * la brique invite à élargir la période.
     *
     * @param  list<CarbonImmutable>  $jours
     */
    protected function unJourPese(WeightTrend $tendance, array $jours): bool
    {
        $poids = $tendance->poidsJournaliers();

        foreach ($jours as $jour) {
            if (isset($poids[$jour->toDateString()])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<CarbonImmutable>  $jours
     * @return list<string>
     */
    protected function etiquettes(array $jours): array
    {
        $format = count($jours) > 366 ? 'd/m/y' : 'd/m';

        return array_map(fn (CarbonImmutable $jour): string => $jour->format($format), $jours);
    }

    /**
     * Couleur de chaque point : grisée pendant une suspension (FR-36).
     *
     * @param  list<CarbonImmutable>  $jours
     * @return list<string>
     */
    protected function couleursDesPoints(WeightTrend $tendance, array $jours, string $couleur): array
    {
        return array_map(
            fn (CarbonImmutable $jour): string => $tendance->estSuspendu($jour) ? Palette::NEUTRE : $couleur,
            $jours,
        );
    }
}
